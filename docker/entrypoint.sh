#!/bin/sh
set -e
cd /app

# `php artisan serve` retire les variables d'environnement Docker : on les écrit dans .env
for v in APP_ENV APP_DEBUG SESSION_DRIVER CACHE_STORE QUEUE_CONNECTION \
         DB_CONNECTION DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD \
         AUTH_URL PATRIMOINE_URL MOUVEMENT_URL; do
  eval "val=\${$v:-}"
  if [ -n "$val" ]; then
    sed -i "/^$v=/d" .env
    echo "$v=$val" >> .env
  fi
done

if [ "$RUN_MIGRATIONS" = "1" ]; then
  until php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=5432;dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }'; do
    echo "En attente de PostgreSQL..."; sleep 2
  done
  php artisan migrate --force
  php artisan db:seed --force
fi
php artisan config:clear
exec php artisan serve --host=0.0.0.0 --port=8000