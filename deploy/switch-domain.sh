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

if grep -q "^TRACKING_SOCKET_URL=" .env; then
  sed -i "s|^TRACKING_SOCKET_URL=.*|TRACKING_SOCKET_URL=\"https://${DOMAIN}\"|" .env
else
  echo "TRACKING_SOCKET_URL=\"https://${DOMAIN}\"" >> .env
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

    location /socket.io/ {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400;
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
