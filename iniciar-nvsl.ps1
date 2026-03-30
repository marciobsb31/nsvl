#Requires -Version 5.1
<#
.SYNOPSIS
  Sobe o ambiente NVSL de forma automatica (Docker, banco, seed).

.DESCRIPTION
  - Tenta iniciar o Docker Desktop se o engine nao estiver acessivel.
  - docker compose up (backend, postgres, redis, frontend no Docker, ssl-proxy).
  - Aguarda o /api/health do backend (em vez de sleep fixo).
  - php artisan migrate --force, importacao IBGE (UFs/municipios) e seed de exemplo.
  - Por padrao NAO abre o Vite local (o frontend no Docker usa http://localhost, porta 80).

.PARAMETER ViteLocal
  Para o servico frontend do Docker e abre uma NOVA janela do PowerShell com npm run dev
  (hot-reload no codigo Vue). Use quando for desenvolver so o frontend.

.PARAMETER SemImportacaoIbge
  Nao executa php artisan localidades:importar-ibge (rede indisponivel ou dados ja carregados).

.PARAMETER SemBuild
  Usa docker compose up -d sem --build (inicio mais rapido).

.EXAMPLE
  .\iniciar-nvsl.ps1

.EXAMPLE
  .\iniciar-nvsl.ps1 -ViteLocal
#>
[CmdletBinding()]
param(
    [switch] $ViteLocal,
    [switch] $SemImportacaoIbge,
    [switch] $SemBuild
)

$ErrorActionPreference = "Stop"
$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path

function Test-DockerEngine {
    try {
        docker info 2>$null | Out-Null
        return ($LASTEXITCODE -eq 0)
    } catch {
        return $false
    }
}

function Start-DockerDesktopAndWait {
    param([int] $TimeoutSec = 120)
    $exe = "${env:ProgramFiles}\Docker\Docker\Docker Desktop.exe"
    if (-not (Test-Path $exe)) {
        Write-Host "[ERRO] Docker Desktop nao encontrado em: $exe" -ForegroundColor Red
        return $false
    }
    Write-Host "[Docker] Tentando iniciar Docker Desktop..." -ForegroundColor Yellow
    Start-Process -FilePath $exe
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        Start-Sleep -Seconds 3
        if (Test-DockerEngine) {
            Write-Host "[Docker] Engine disponivel." -ForegroundColor Green
            return $true
        }
        Write-Host "  Aguardando Docker... ($([int](($deadline - (Get-Date)).TotalSeconds))s restantes)" -ForegroundColor Gray
    }
    return $false
}

function Wait-BackendHealth {
    param([int] $MaxAttempts = 40, [int] $IntervalSec = 2)
    $url = "http://127.0.0.1:8081/api/health"
    for ($i = 1; $i -le $MaxAttempts; $i++) {
        try {
            $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
            if ($r.StatusCode -eq 200) {
                Write-Host "[OK] Backend respondeu em $url" -ForegroundColor Green
                return $true
            }
        } catch {
            Write-Host "  Aguardando backend ($i/$MaxAttempts)..." -ForegroundColor Gray
        }
        Start-Sleep -Seconds $IntervalSec
    }
    return $false
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  NVSL - Inicializacao automatica" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-DockerEngine)) {
    if (-not (Start-DockerDesktopAndWait)) {
        Write-Host "[ERRO] Docker nao ficou disponivel. Abra o Docker Desktop manualmente." -ForegroundColor Red
        exit 1
    }
}

$required = @("NVSL_DOCKER", "NVSL_BACKEND", "NVSL_FRONTEND")
foreach ($dir in $required) {
    if (-not (Test-Path "$baseDir\$dir")) {
        Write-Host "[ERRO] Pasta $dir nao encontrada em $baseDir" -ForegroundColor Red
        exit 1
    }
}

Set-Location "$baseDir\NVSL_DOCKER"

$composeArgs = @("compose", "up", "-d")
if (-not $SemBuild) {
    $composeArgs = @("compose", "up", "--build", "-d")
}

Write-Host "[1/6] Subindo containers: docker $($composeArgs -join ' ')" -ForegroundColor Yellow
& docker @composeArgs
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRO] docker compose falhou." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/6] Aguardando API /api/health..." -ForegroundColor Yellow
if (-not (Wait-BackendHealth)) {
    Write-Host "[AVISO] Health nao respondeu a tempo. Verifique: docker compose ps / logs nvsl-backend" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[3/6] Migrations (php artisan migrate --force)..." -ForegroundColor Yellow
docker exec nvsl-backend php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRO] migrate falhou." -ForegroundColor Red
    exit 1
}

if (-not $SemImportacaoIbge) {
    Write-Host ""
    Write-Host "[4/6] Importando UFs e municipios (IBGE, rede necessaria)..." -ForegroundColor Yellow
    docker exec nvsl-backend php artisan localidades:importar-ibge
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[AVISO] Importacao IBGE falhou. Execute depois: docker exec nvsl-backend php artisan localidades:importar-ibge" -ForegroundColor Yellow
    }
} else {
    Write-Host ""
    Write-Host "[4/6] Importacao IBGE ignorada (-SemImportacaoIbge)." -ForegroundColor Gray
}

Write-Host ""
Write-Host "[5/6] Seed de exemplo (UsuarioExemploSeeder)..." -ForegroundColor Yellow
docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "  Dados de exemplo aplicados (perfis, usuarios de teste, solicitacoes)." -ForegroundColor Gray
}

Write-Host ""
Write-Host "[6/6] Frontend..." -ForegroundColor Yellow
if ($ViteLocal) {
    Write-Host "  Parando container nvsl-frontend (porta 80) para subir Vite em http://localhost:5176..." -ForegroundColor Gray
    docker compose stop frontend 2>$null
    $frontDir = "$baseDir\NVSL_FRONTEND"
    if (-not (Test-Path "$frontDir\node_modules")) {
        Write-Host "  npm install em NVSL_FRONTEND..." -ForegroundColor Gray
        Push-Location $frontDir
        npm install
        Pop-Location
    }
    $viteCmd = "Set-Location -LiteralPath '$frontDir'; Write-Host 'Vite NVSL (local). Pare com Ctrl+C.' -ForegroundColor Cyan; npm run dev"
    Start-Process powershell.exe -ArgumentList @("-NoExit", "-NoLogo", "-Command", $viteCmd)
    Write-Host "  Nova janela aberta com npm run dev." -ForegroundColor Green
} else {
    Write-Host "  Usando frontend servido pelo Docker em http://localhost (porta 80)" -ForegroundColor Gray
    Write-Host "  Para Vite local com hot-reload: .\iniciar-nvsl.ps1 -ViteLocal" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  NVSL pronto" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Frontend:  http://localhost" -ForegroundColor White
Write-Host "  Backend:   http://localhost:8081/api" -ForegroundColor White
Write-Host "  Health:    http://localhost:8081/api/health" -ForegroundColor White
Write-Host ""
Write-Host "  Parar tudo:  cd NVSL_DOCKER; docker compose down" -ForegroundColor Gray
Write-Host "  Opcoes: -ViteLocal  |  -SemImportacaoIbge  |  -SemBuild" -ForegroundColor Gray
Write-Host ""
