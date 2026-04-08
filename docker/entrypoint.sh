#!/bin/sh
set -e

echo "[entrypoint] Setando permissÃµes storaga"
mkdir -pv /var/www/html/storage/app/public/
mkdir -pv /var/www/html/storage/framework/{cache,sessions,views}
mkdir -pv /var/www/html/storage/framework/cache/data
mkdir -pv /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

until php -r "
    try {
        new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
"; do
  echo "[entrypoint] Banco nÃ£o disponÃ­vel ainda, aguardando 2s..."
  sleep 2
done

echo "[entrypoint] Banco disponÃ­vel!"

if [ "${APP_ENV}" = "local" ] || [ "${APP_ENV}" = "testing" ]; then
  echo "[entrypoint] Limpando caches de configuração/rotas para ambiente ${APP_ENV}..."
  php artisan config:clear || true
  php artisan route:clear || true
else
  echo "[entrypoint] Cacheando configurações..."
  php artisan config:cache
  php artisan route:cache
fi

echo "[entrypoint] Executando migrations..."
php artisan migrate --force

echo "[entrypoint] Importando UFs e municípios (IBGE)..."
php artisan localidades:importar-ibge || echo "[entrypoint] Aviso: importação IBGE falhou (rede?). Execute: php artisan localidades:importar-ibge"

echo "[entrypoint] Executando seeders controlados..."
php artisan db:seed --force || true

echo "[entrypoint] Gerando documentaÃ§Ã£o Swagger..."
php artisan l5-swagger:generate || echo "[entrypoint] Aviso: falha ao gerar Swagger (ignorando)"

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "[entrypoint] InicializaÃ§Ã£o concluÃ­da. Iniciando supervisord..."
exec "$@"
