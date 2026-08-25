#!/usr/bin/env bash
# سكربت تحديث الموقع بعد أول نشر.
# الاستخدام على الخادم:  cd /var/www/html/mutazsatti.com && ./deploy/deploy.sh
set -euo pipefail

APP_DIR="/var/www/html/mutazsatti.com"
cd "$APP_DIR"

echo "==> وضع الصيانة"
php artisan down --render="errors::503" --retry=60 || true
trap 'php artisan up || true' EXIT

echo "==> سحب آخر نسخة"
git pull --ff-only origin main

echo "==> اعتماديات PHP"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> بناء الأصول"
npm ci
npm run build

echo "==> الترحيلات"
php artisan migrate --force

echo "==> إعادة بناء الكاش"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> الأذونات"
sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache database

echo "==> إعادة تشغيل الخدمات"
sudo systemctl reload php8.3-fpm
sudo systemctl reload apache2

echo "✅ تم النشر بنجاح"
