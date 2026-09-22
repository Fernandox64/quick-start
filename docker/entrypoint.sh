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
gerar_settings_local demed demed
gerar_settings_local defil defil
gerar_settings_local delet delet
gerar_settings_local depro depro

gerar_settings_local demet demet
gerar_settings_local desoc desoc
gerar_settings_local dequi dequi
gerar_settings_local decom decom
gerar_settings_local decivil decivil
gerar_settings_local deelet deelet
gerar_settings_local degeo degeo
gerar_settings_local defarm defarm
gerar_settings_local denutri denutri
gerar_settings_local edfis edfis
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
importar_se_vazio demed /app/docker/seed-demed.sql.gz "http://localhost:8499"
importar_se_vazio defil /app/docker/seed-defil.sql.gz "http://localhost:8599"
importar_se_vazio delet /app/docker/seed-delet.sql.gz "http://localhost:8699"
importar_se_vazio depro /app/docker/seed-depro.sql.gz "http://localhost:8799"

importar_se_vazio demet /app/docker/seed-demet.sql.gz "http://localhost:8899"

importar_se_vazio desoc /app/docker/seed-desoc.sql.gz "http://localhost:8999"

importar_se_vazio dequi /app/docker/seed-dequi.sql.gz "http://localhost:9099"

importar_se_vazio decom /app/docker/seed-decom.sql.gz "http://localhost:9199"

importar_se_vazio decivil /app/docker/seed-decivil.sql.gz "http://localhost:9299"

importar_se_vazio deelet /app/docker/seed-deelet.sql.gz "http://localhost:9399"

importar_se_vazio degeo /app/docker/seed-degeo.sql.gz "http://localhost:9499"

importar_se_vazio defarm /app/docker/seed-defarm.sql.gz "http://localhost:9599"

importar_se_vazio denutri /app/docker/seed-denutri.sql.gz "http://localhost:9699"

importar_se_vazio edfis /app/docker/seed-edfis.sql.gz "http://localhost:9799"

chown -R www-data:www-data /app/web/sites/default/files /app/web/sites/dfis/files /app/web/sites/demat/files /app/web/sites/demed/files /app/web/sites/defil/files /app/web/sites/delet/files /app/web/sites/depro/files /app/web/sites/demet/files /app/web/sites/desoc/files /app/web/sites/dequi/files /app/web/sites/decom/files /app/web/sites/decivil/files /app/web/sites/deelet/files /app/web/sites/degeo/files /app/web/sites/defarm/files /app/web/sites/denutri/files /app/web/sites/edfis/files

exec "$@"
