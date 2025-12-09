#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force
  php -r '$e=file_get_contents(".env");
    $m=[
      "DB_CONNECTION"=>getenv("DB_CONNECTION"),
      "DB_HOST"=>getenv("DB_HOST"),
      "DB_PORT"=>getenv("DB_PORT"),
      "DB_DATABASE"=>getenv("DB_DATABASE"),
      "DB_USERNAME"=>getenv("DB_USERNAME"),
      "DB_PASSWORD"=>getenv("DB_PASSWORD"),
      "APP_URL"=>getenv("APP_URL"),
      "JWT_SECRET"=>getenv("JWT_SECRET")?:"please_change_me",
    ];
    foreach($m as $k=>$v){ if($v){ $e=preg_replace("/^{$k}=.*/m","{$k}={$v}",$e);} }
    file_put_contents(".env",$e);'
fi

# Clear caches
php artisan config:clear || true
php artisan route:clear || true

# Ensure replica read config is used
php artisan config:cache || true
php artisan route:cache || true

# Remove default Laravel users migration to avoid table conflicts
rm -f database/migrations/0001_01_01_000000_create_users_table.php || true

# Run migrations against MASTER
php artisan migrate --force || true

exec "$@"
