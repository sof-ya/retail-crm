#!/bin/bash
set -e

SAIL="./vendor/bin/sail"

# Composer
echo "Installing composer dependencies..."
composer install

# Проверка наличия sail
if [ ! -f "$SAIL" ]; then
    echo "Error: Sail not found. Run 'composer install' first."
    exit 1
fi

# Проверка .env
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    NEED_KEY=1
fi

# Запуск контейнеров
echo "Starting containers..."
$SAIL up -d

# Ожидание готовности MySQL
echo "Waiting for MySQL..."
until $SAIL artisan db:show --quiet 2>/dev/null; do
    sleep 2
done

# Генерация ключа (если .env был создан)
if [ "$NEED_KEY" = "1" ]; then
    $SAIL artisan key:generate
fi

# Миграции + сидеры
echo "Running migrations..."
$SAIL artisan migrate
read -p "Seed database? (y/N): " seed
if [ "$seed" = "y" ] || [ "$seed" = "Y" ]; then
    $SAIL artisan db:seed --force
fi

# Swagger
read -p "Generate Swagger docs? (Y/n): " swagger
if [ "$swagger" != "n" ] && [ "$swagger" != "N" ]; then
    $SAIL artisan l5-swagger:generate
fi

# npm
echo "Installing npm dependencies..."
$SAIL npm install

# Режим запуска
echo ""
echo "Select mode:"
echo "  1) Dev  (vite dev — hot reload)"
echo "  2) Build (vite build — production)"
read -p "Choice [1]: " mode
mode=${mode:-1}

if [ "$mode" = "2" ]; then
    $SAIL npm run build
else
    $SAIL npm run dev
fi

echo ""
echo "Done! App: http://localhost"
echo "Swagger: http://localhost/api/documentation"