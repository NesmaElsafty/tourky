#!/usr/bin/env bash
# Tourky — one-shot VPS setup (Ubuntu 24.04). Run as root on the server.
set -euo pipefail

APP_DIR="/var/www/tourky"
DOMAIN_API="${DOMAIN_API:-backend.tourkygroup.com}"
GIT_REPO="${GIT_REPO:-https://github.com/NesmaElsafty/tourky.git}"
GIT_BRANCH="${GIT_BRANCH:-dev}"
APP_NAME="${APP_NAME:-Tourky}"
MAIL_USER="${MAIL_USER:-info@tourkygroup.com}"
MAIL_PASS="${MAIL_PASSWORD:-}"

CREDS_FILE="/root/tourky-deploy-credentials.txt"
DB_NAME="tourky"
DB_USER="tourky"
DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
TRACKING_SECRET="$(openssl rand -hex 32)"
APP_KEY_PLACEHOLDER=""

echo "==> Tourky VPS install — ${DOMAIN_API}"

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

apt-get install -y -qq software-properties-common unzip curl git nginx mysql-server redis-server \
  supervisor certbot python3-certbot-nginx

add-apt-repository ppa:ondrej/php -y
apt-get update -qq
apt-get install -y -qq php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl \
  php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis

if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
fi

if ! command -v node >/dev/null 2>&1 || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 20 ]]; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y -qq nodejs
fi

systemctl enable nginx mysql redis-server php8.2-fpm
systemctl start nginx mysql redis-server php8.2-fpm

mysql -u root <<MYSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL

if [[ -d "${APP_DIR}/.git" ]]; then
  cd "${APP_DIR}"
  git fetch origin
  git checkout "${GIT_BRANCH}"
  git pull origin "${GIT_BRANCH}"
else
  mkdir -p /var/www
  git clone --branch "${GIT_BRANCH}" "${GIT_REPO}" "${APP_DIR}"
  cd "${APP_DIR}"
fi

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

php artisan key:generate --force 2>/dev/null || true

# Patch .env (idempotent-ish)
set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

set_env APP_NAME "\"${APP_NAME}\""
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "\"https://${DOMAIN_API}\""
set_env LOG_LEVEL error
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE "${DB_NAME}"
set_env DB_USERNAME "${DB_USER}"
set_env DB_PASSWORD "\"${DB_PASS}\""
set_env CACHE_STORE database
set_env SESSION_DRIVER database
set_env QUEUE_CONNECTION database
set_env REDIS_HOST 127.0.0.1
set_env REDIS_PORT 6379
set_env TRACKING_SERVICE_URL http://127.0.0.1:6001
set_env TRACKING_SOCKET_URL "\"https://${DOMAIN_API}\""
set_env TRACKING_INTERNAL_SECRET "\"${TRACKING_SECRET}\""
set_env MAIL_MAILER smtp
set_env MAIL_HOST smtp.hostinger.com
set_env MAIL_PORT 465
set_env MAIL_USERNAME "\"${MAIL_USER}\""
set_env MAIL_FROM_ADDRESS "\"${MAIL_USER}\""
if [[ -n "${MAIL_PASS}" ]]; then
  set_env MAIL_PASSWORD "\"${MAIL_PASS}\""
fi

export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

cat > /etc/nginx/sites-available/tourky <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN_API};
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

ln -sf /etc/nginx/sites-available/tourky /etc/nginx/sites-enabled/tourky
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# Tracking service
TRACK_DIR="${APP_DIR}/tracking-service"
if [[ -d "${TRACK_DIR}" ]]; then
  cat > "${TRACK_DIR}/.env" <<TRACKENV
PORT=6001
HOST=0.0.0.0
LARAVEL_URL=https://${DOMAIN_API}
TRACKING_INTERNAL_SECRET=${TRACKING_SECRET}
REDIS_URL=redis://127.0.0.1:6379
CORS_ORIGINS=https://${DOMAIN_API}
TRACKENV
  cd "${TRACK_DIR}"
  npm ci --omit=dev 2>/dev/null || npm install --omit=dev
fi

cat > /etc/supervisor/conf.d/tourky-worker.conf <<SUP
[program:tourky-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
SUP

cat > /etc/supervisor/conf.d/tourky-tracking.conf <<SUP
[program:tourky-tracking]
command=/usr/bin/node ${TRACK_DIR}/src/server.js
directory=${TRACK_DIR}
autostart=true
autorestart=true
user=www-data
environment=NODE_ENV="production"
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/tracking.log
SUP

supervisorctl reread
supervisorctl update
supervisorctl restart tourky-worker:* || supervisorctl start tourky-worker:*
supervisorctl restart tourky-tracking:* || supervisorctl start tourky-tracking:*

{
  echo "=== Tourky deploy credentials — $(date -Iseconds) ==="
  echo "API URL: https://${DOMAIN_API}"
  echo "DB_NAME=${DB_NAME}"
  echo "DB_USER=${DB_USER}"
  echo "DB_PASSWORD=${DB_PASS}"
  echo "TRACKING_INTERNAL_SECRET=${TRACKING_SECRET}"
  echo "APP_DIR=${APP_DIR}"
} > "${CREDS_FILE}"
chmod 600 "${CREDS_FILE}"

if getent hosts "${DOMAIN_API}" >/dev/null 2>&1; then
  certbot --nginx -d "${DOMAIN_API}" --non-interactive --agree-tos -m "${MAIL_USER}" --redirect || true
fi

echo ""
echo "=== DONE ==="
echo "Credentials saved: ${CREDS_FILE}"
echo "Test: curl -sI http://127.0.0.1 | head -3"
cat "${CREDS_FILE}"
