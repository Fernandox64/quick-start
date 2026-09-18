#!/bin/bash
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-db}"
DB_USER="${DB_USER:-db}"
DB_PASSWORD="${DB_PASSWORD:-db}"
HASH_SALT="${HASH_SALT:-}"
TRUSTED_HOST_PATTERN="${TRUSTED_HOST_PATTERN:-}"

# Gera settings.local.php a cada boot a partir das variaveis de ambiente -
# nunca vai pro git (settings.php so inclui esse arquivo se ele existir, ver
# web/sites/default/settings.php). Em dev local (DDEV) esse arquivo nunca eh
# criado, entao o comportamento de lá fica intacto.
cat > /app/web/sites/default/settings.local.php <<PHP
<?php
\$databases['default']['default'] = [
  'database' => '${DB_NAME}',
  'username' => '${DB_USER}',
  'password' => '${DB_PASSWORD}',
  'host' => '${DB_HOST}',
  'port' => '${DB_PORT}',
  'driver' => 'mysql',
  'namespace' => 'Drupal\\\\mysql\\\\Driver\\\\Database\\\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];
PHP

if [ -n "$HASH_SALT" ]; then
  echo "\$settings['hash_salt'] = '${HASH_SALT}';" >> /app/web/sites/default/settings.local.php
fi

if [ -n "$TRUSTED_HOST_PATTERN" ]; then
  echo "\$settings['trusted_host_patterns'] = ['${TRUSTED_HOST_PATTERN}'];" >> /app/web/sites/default/settings.local.php
fi

# --skip-ssl: o cliente mariadb (>=11) desta imagem exige TLS por padrao,
# mas o servidor mariadb:10.11 oficial nao tem TLS configurado.
MYSQL_CLIENT_OPTS="-h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD --skip-ssl"

echo "Esperando o banco de dados em ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
  if mysqladmin ping $MYSQL_CLIENT_OPTS --silent 2>/dev/null; then
    break
  fi
  sleep 2
done

TABLE_COUNT=$(mysql $MYSQL_CLIENT_OPTS -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null || echo 0)

if [ "$TABLE_COUNT" -eq 0 ] && [ -f /app/docker/seed.sql.gz ]; then
  echo "Banco vazio - importando conteudo inicial (docker/seed.sql.gz)..."
  gunzip -c /app/docker/seed.sql.gz | mysql $MYSQL_CLIENT_OPTS "$DB_NAME"
  echo "Importacao concluida."
fi

chown -R www-data:www-data /app/web/sites/default/files

if [ "$TABLE_COUNT" -gt 0 ] || [ -f /app/docker/seed.sql.gz ]; then
  su -s /bin/bash www-data -c "/app/vendor/bin/drush --root=/app/web cache:rebuild" || true
fi

exec "$@"
