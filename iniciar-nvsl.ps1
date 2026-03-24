# ============================================================
# NVSL - Script de Inicializacao Local
# Execute: .\iniciar-nvsl.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  NVSL - Iniciando ambiente local" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verifica se Docker esta rodando
try {
    docker info 2>$null | Out-Null
} catch {
    Write-Host "[ERRO] Docker nao esta rodando. Inicie o Docker Desktop e tente novamente." -ForegroundColor Red
    exit 1
}

# Verifica se as pastas existem
$required = @("NVSL_DOCKER", "NVSL_BACKEND", "NVSL_FRONTEND")
foreach ($dir in $required) {
    if (-not (Test-Path "$baseDir\$dir")) {
        Write-Host "[ERRO] Pasta $dir nao encontrada. Execute o clone dos repositorios primeiro." -ForegroundColor Red
        exit 1
    }
}

Set-Location "$baseDir\NVSL_DOCKER"

Write-Host "[1/4] Construindo e iniciando containers (backend, postgres, redis)..." -ForegroundColor Yellow
docker compose up --build -d

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRO] Falha ao iniciar os containers." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/4] Aguardando servicos iniciarem por 30 segundos..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

Write-Host ""
Write-Host "[3/4] Executando migrations e seeders (banco de dados)..." -ForegroundColor Yellow
docker exec nvsl-backend php artisan migrate --force 2>$null
docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  Banco populado com usuarios e solicitacoes de exemplo (Carlos Souza: 4 perfis, Roberto Alves: 3 perfis)." -ForegroundColor Gray
}

Write-Host ""
Write-Host "[4/4] Iniciando Vite dev server (frontend)..." -ForegroundColor Yellow
Set-Location "$baseDir\NVSL_FRONTEND"

# Instala dependencias se necessario
if (-not (Test-Path "node_modules")) {
    Write-Host "  Instalando dependencias npm..." -ForegroundColor Gray
    npm install
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  NVSL esta rodando!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Frontend:  http://localhost:5176" -ForegroundColor White
Write-Host "  Backend:   http://localhost:8081/api" -ForegroundColor White
Write-Host "  Health:    http://localhost:8081/api/health" -ForegroundColor White
Write-Host ""
Write-Host "  Login: Federal | Gerenciar Cadastros | Detalhar em Carlos Souza para ver perfis vinculados." -ForegroundColor Gray
Write-Host "  Para parar: Ctrl+C (Vite) e depois: cd NVSL_DOCKER; docker compose down" -ForegroundColor Gray
Write-Host ""

# Inicia o Vite dev server em primeiro plano
npm run dev
