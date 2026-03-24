# Troubleshooting — UF e Município não carregam

## Possíveis causas e soluções

### 1. Backend não está rodando

**Sintoma:** Campos Estado (UF) e Município vazios.

**Solução:** Inicie o backend:
```powershell
# Com Docker
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER
docker compose up -d

# Sem Docker (php artisan serve)
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_BACKEND
php artisan serve --port=8081
```

### 2. Backend em porta diferente

**Sintoma:** API falha; fallback estático é usado (lista limitada).

**Solução:** Configure a URL da API no frontend.

Crie/edite `NVSL_FRONTEND\.env`:
```
# Se o backend está em http://localhost:8000
VITE_API_BASE_URL=http://localhost:8000/api
```

Ou no `vite.config.ts`, o proxy usa `VITE_API_BASE_URL` ou `localhost:8081` por padrão.

### 3. CORS (Frontend e Backend em origens diferentes)

**Sintoma:** Erro de CORS no console do navegador.

**Solução:** O Laravel já inclui `HandleCors`. Verifique `config/cors.php` ou use o proxy do Vite em desenvolvimento (requisições vão para a mesma origem).

### 4. Tabelas ufs/municipios vazias

**Sintoma:** UFs ou municípios não aparecem; API retorna listas vazias.

**Solução:** Os dados vêm do banco (não do IBGE em tempo de execução). Execute a importação:

```bash
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_BACKEND
php artisan migrate
php artisan localidades:importar-ibge
```

### 5. Fallback estático (frontend)

Quando a API falha, o frontend usa dados estáticos limitados. Para lista completa, o backend deve responder e as tabelas `ufs` e `municipios` devem estar populadas.

### 6. Verificar se a API responde

Abra no navegador ou use curl:
```
http://localhost:8081/api/localidades/ufs
http://localhost:8081/api/localidades/municipios?uf=GO
http://localhost:8081/api/localidades/completo  — UFs e municípios de todas as UFs em uma única requisição
```

Se retornar JSON com `data`, a API está OK.
