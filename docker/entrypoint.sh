#!/bin/bash
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-db}"
DB_USER="${DB_USER:-db}"
DB_PASSWORD="${DB_PASSWORD:-db}"
HASH_SALT="${HASH_SALT:-}"
TRUSTED_HOST_PATTERN="${TRUSTED_HOST_PATTERN:-}"

# --skip-ssl: o cliente mariadb (>=11) desta imagem exige TLS por padrao,
# mas o servidor mariadb:10.11 oficial nao tem TLS configurado.
MYSQL_CLIENT_OPTS="-h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD --skip-ssl"

# Gera settings.local.php pra um site (sites/default, sites/dfis, sites/demat)
# a partir das variaveis de ambiente - nunca vai pro git (settings.php de
# cada site so inclui esse arquivo se ele existir). Em dev local (DDEV) esse
# arquivo nunca eh criado, entao o comportamento de la fica intacto.
gerar_settings_local() {
  local site_dir="$1"
  local db_name="$2"
  cat > "/app/web/sites/${site_dir}/settings.local.php" <<PHP
<?php
\$databases['default']['default'] = [
  'database' => '${db_name}',
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
    echo "\$settings['hash_salt'] = '${HASH_SALT}-${site_dir}';" >> "/app/web/sites/${site_dir}/settings.local.php"
  fi
  if [ -n "$TRUSTED_HOST_PATTERN" ]; then
    echo "\$settings['trusted_host_patterns'] = ['${TRUSTED_HOST_PATTERN}'];" >> "/app/web/sites/${site_dir}/settings.local.php"
  fi
}

gerar_settings_local default "$DB_NAME"
gerar_settings_local dfis dfis
gerar_settings_local demat demat

echo "Esperando o banco de dados em ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
  if mysqladmin ping $MYSQL_CLIENT_OPTS --silent 2>/dev/null; then
    break
  fi
  sleep 2
done

# Importa o dump de semente de um site se o banco dele ainda estiver vazio
# (nunca sobrescreve um banco que ja tem dados).
importar_se_vazio() {
  local db_name="$1"
  local seed_file="$2"
  local uri="$3"

  local table_count
  table_count=$(mysql $MYSQL_CLIENT_OPTS -N -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${db_name}';" 2>/dev/null || echo 0)

  if [ "$table_count" -eq 0 ] && [ -f "$seed_file" ]; then
    echo "Banco '${db_name}' vazio - importando conteudo inicial (${seed_file})..."
    gunzip -c "$seed_file" | mysql $MYSQL_CLIENT_OPTS "$db_name"
    echo "Importacao de '${db_name}' concluida."
  fi

  if [ "$table_count" -gt 0 ] || [ -f "$seed_file" ]; then
    su -s /bin/bash www-data -c "/app/vendor/bin/drush --root=/app/web --uri=${uri} cache:rebuild" || true
  fi
}

importar_se_vazio "$DB_NAME" /app/docker/seed.sql.gz "http://localhost"
importar_se_vazio dfis /app/docker/seed-dfis.sql.gz "http://localhost:8299"
importar_se_vazio demat /app/docker/seed-demat.sql.gz "http://localhost:8399"

chown -R www-data:www-data /app/web/sites/default/files /app/web/sites/dfis/files /app/web/sites/demat/files

exec "$@"
