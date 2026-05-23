#!/usr/bin/env bash
# Point existing VPS install to backend.tourkygroup.com (run as root).
set -euo pipefail

DOMAIN="${1:-backend.tourkygroup.com}"
APP_DIR="/var/www/tourky"
MAIL_USER="${MAIL_USER:-info@tourkygroup.com}"

cd "${APP_DIR}"

if grep -q "^APP_URL=" .env; then
  sed -i "s|^APP_URL=.*|APP_URL=\"https://${DOMAIN}\"|" .env
else
  echo "APP_URL=\"https://${DOMAIN}\"" >> .env
fi

php artisan config:cache
php artisan route:cache

cat > /etc/nginx/sites-available/tourky <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

nginx -t
systemctl reload nginx

certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${MAIL_USER}" --redirect 2>/dev/null || \
  echo "Run manually after DNS propagates: certbot --nginx -d ${DOMAIN}"

echo "Done. APP_URL=https://${DOMAIN}"
