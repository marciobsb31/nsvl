$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer sanctum_test_token_novo1694365127"
}

# Teste 1: GET /perfis-ativos
Write-Host "=== TESTE 1: Listar Perfis Ativos ===" -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8081/api/user/perfis-ativos" `
        -Method GET -Headers $headers -ErrorAction Stop
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    $content = $response.Content | ConvertFrom-Json
    $content | ConvertTo-Json -Depth 10 | Write-Host
} catch {
    Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $_.Exception.Response.Content.ReadAsStream() | Get-Content
    }
}

Write-Host "`n=== TESTE 2: Trocar Contexto ===" -ForegroundColor Cyan
try {
    $body = @{"perfil_usuario_id" = 101} | ConvertTo-Json
    $response = Invoke-WebRequest -Uri "http://localhost:8081/api/user/trocar-contexto" `
        -Method POST -Headers $headers -Body $body -ErrorAction Stop
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    $content = $response.Content | ConvertFrom-Json
    $content | ConvertTo-Json -Depth 10 | Write-Host
} catch {
    Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor Red
}
