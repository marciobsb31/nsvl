#!/bin/sh
# ============================================================
# NVSL Backend â€” Docker Entrypoint
# Inicializa a aplicaÃ§Ã£o antes de subir o supervisord
# ============================================================
set -e

echo "[entrypoint] Setando permissÃµes storaga"
mkdir -pv /var/www/html/storage/app/public/
mkdir -pv /var/www/html/storage/framework/{cache,sessions,views}
mkdir -pv /var/www/html/storage/framework/cache/data
mkdir -pv /var/www/html/storage/logs

chown www-data:www-data /var/www/html/storage
chown www-data:www-data /var/www/html/storage/app
chown www-data:www-data /var/www/html/storage/app/public
chown www-data:www-data /var/www/html/storage/framework
chown www-data:www-data /var/www/html/storage/framework/{cache,sessions,views}
chown www-data:www-data /var/www/html/storage/framework/cache/data
chown www-data:www-data /var/www/html/storage/logs

chmod 775 /var/www/html/storage
chmod 775 /var/www/html/storage/app
chmod 775 /var/www/html/storage/app/public
chmod 775 /var/www/html/storage/framework
chmod 775 /var/www/html/storage/framework/{cache,sessions,views}
chmod 775 /var/www/html/storage/framework/cache/data
chmod 775 /var/www/html/storage/logs

# Loop simples de espera para o PostgreSQL subir
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

# # Gera APP_KEY se nÃ£o estiver definida
# if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
#     echo "[entrypoint] Gerando APP_KEY..."
#     php artisan key:generate --force
# fi

# Limpa e recria caches de configuraÃ§Ã£o
echo "[entrypoint] Cacheando configuraÃ§Ãµes..."
php artisan config:cache
php artisan route:cache

# Executa migrations
echo "[entrypoint] Executando migrations..."
php artisan migrate --force

# Seeders essenciais (perfis, esferas)
echo "[entrypoint] Executando seeders..."
php artisan db:seed --class=PerfilSeeder --force || true
php artisan db:seed --class=EsferaSeeder --force || true

# Gera documentaÃ§Ã£o Swagger
echo "[entrypoint] Gerando documentaÃ§Ã£o Swagger..."
php artisan l5-swagger:generate || echo "[entrypoint] Aviso: falha ao gerar Swagger (ignorando)"

echo "[entrypoint] InicializaÃ§Ã£o concluÃ­da. Iniciando supervisord..."
exec "$@"
