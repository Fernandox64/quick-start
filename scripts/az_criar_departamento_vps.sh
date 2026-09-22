#!/usr/bin/env bash
#
# Versão do scripts/az_criar_departamento.sh pra rodar DIRETO NA VPS, sem
# precisar de PC local nem DDEV - cria o banco e instala o Drupal direto
# nos containers de produção. Reusa os mesmos scripts/az_criar_departamento.php
# e scripts/az_fix_settings_local_include.php do fluxo local.
#
# Uso (na VPS, dentro de /opt/arizona-ufop):
#   ./scripts/az_criar_departamento_vps.sh <site_dir> "<Nome completo>" ["<área>"]
#
# Exemplo:
#   ./scripts/az_criar_departamento_vps.sh demet "Departamento de Metalurgia" "metalurgia"
#
# Requisitos: rodar dentro da pasta do projeto na VPS, com os containers
# "web" e "db" já no ar (docker compose ps).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SITE_DIR="${1:-}"
NOME="${2:-}"
AREA="${3:-}"
DOMINIO_VPS="srv1654694.hstgr.cloud"

if [[ -z "$SITE_DIR" || -z "$NOME" ]]; then
  echo "Uso: $0 <site_dir> \"<Nome completo>\" [\"<área>\"]" >&2
  echo "Exemplo: $0 demet \"Departamento de Metalurgia\" \"metalurgia\"" >&2
  exit 1
fi

if [[ ! "$SITE_DIR" =~ ^[a-z][a-z0-9]{2,10}$ ]]; then
  echo "ERRO: site_dir deve ser só letras minúsculas/números, 3-11 caracteres (ex.: demet)." >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "ERRO: .env não encontrado - rode este script dentro da pasta do projeto na VPS (/opt/arizona-ufop)." >&2
  exit 1
fi

if [[ -z "$AREA" ]]; then
  AREA="$(echo "$NOME" | sed -E 's/^Departamento (de |do |da )?//I' | tr '[:upper:]' '[:lower:]')"
fi
SIGLA="$(echo "$SITE_DIR" | tr '[:lower:]' '[:upper:]')"

ULTIMA_PORTA=$(grep -oE "\\\$sites\['[0-9]+\." web/sites/sites.php | grep -oE '[0-9]+' | sort -n | tail -1)
PORTA=$((ULTIMA_PORTA + 100))
SENHA_ADMIN=$(openssl rand -base64 12 | tr -d '/+=' | cut -c1-14)
DB_USER=$(grep '^DB_USER=' .env | cut -d= -f2)
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d= -f2)
MYSQL_ROOT_PASSWORD=$(grep '^MYSQL_ROOT_PASSWORD=' .env | cut -d= -f2)

echo "=================================================================="
echo " Criando site de departamento (direto na VPS)"
echo "   Pasta (site_dir): $SITE_DIR"
echo "   Nome:             $NOME"
echo "   Área:             $AREA"
echo "   Sigla:            $SIGLA"
echo "   Porta:            $PORTA"
echo "=================================================================="
read -r -p "Confere? Pressione ENTER pra continuar ou Ctrl+C pra cancelar..." < /dev/tty

# ---------------------------------------------------------------------
# 1) Edita os 6 arquivos de configuracao. O host da VPS nao tem PHP
#    instalado (so dentro dos containers) - usa um container PHP
#    descartavel, com a pasta do projeto montada, so pra essa edicao.
# ---------------------------------------------------------------------
echo
echo "--- Parte 1: editando arquivos de configuração ---"
docker run --rm -v "$ROOT:/workdir" -w /workdir php:8.3-cli \
  php scripts/az_criar_departamento.php "$SITE_DIR" "$PORTA" "$SIGLA" "$AREA" "$NOME"

# ---------------------------------------------------------------------
# 2) Cria o banco de producao e reconstroi o container (pra ele
#    carregar sites.php/az_provisionar_esqueleto.php/entrypoint.sh
#    atualizados).
# ---------------------------------------------------------------------
echo
echo "--- Parte 2: criando banco e reconstruindo o container ---"
docker compose exec db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
CREATE DATABASE IF NOT EXISTS $SITE_DIR;
GRANT ALL PRIVILEGES ON $SITE_DIR.* TO '$DB_USER'@'%';
FLUSH PRIVILEGES;
"
grep -q "^${SIGLA}_PORT=" .env || echo "${SIGLA}_PORT=$PORTA" >> .env

docker compose up -d --build

echo
echo "--- Instalando o Drupal (isso demora um pouco) ---"
docker compose exec web vendor/bin/drush site:install az_quickstart \
  --sites-subdir="$SITE_DIR" --db-url="mysql://$DB_USER:$DB_PASSWORD@db/$SITE_DIR" \
  --site-name="$NOME" --account-name=admin --account-pass="$SENHA_ADMIN" -y

echo
echo "--- Corrigindo settings.php (dentro do container) ---"
docker compose exec web php scripts/az_fix_settings_local_include.php "$SITE_DIR"

# ---------------------------------------------------------------------
# 3) Modulo + esqueleto de conteudo + traducoes + fixes conhecidos.
# ---------------------------------------------------------------------
echo
echo "--- Populando conteúdo ---"
URI="$PORTA.$DOMINIO_VPS"
docker compose exec web vendor/bin/drush --uri="$URI" pm:enable az_ufop_departamento -y
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_provisionar_esqueleto.php
docker compose exec web vendor/bin/drush --uri="$URI" locale:update
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_fix_idioma_sem_prefixo.php
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_fix_rodape_copyright.php
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_add_landing_servicos.php
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_criar_landing_cursos.php
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_ocultar_titulo_tutorial.php
docker compose exec web vendor/bin/drush --uri="$URI" scr scripts/az_traduzir_views_texto_fixo.php
docker compose exec web vendor/bin/drush --uri="$URI" cache:rebuild

# ---------------------------------------------------------------------
# 4) Exporta o dump e traz o settings.php de dentro do container pro
#    host, pra poderem ser versionados no git.
# ---------------------------------------------------------------------
echo
echo "--- Exportando dump e recuperando settings.php ---"
docker compose exec db mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$SITE_DIR" 2>/dev/null | gzip > "docker/seed-$SITE_DIR.sql.gz"
docker compose cp "web:/app/web/sites/$SITE_DIR/settings.php" "web/sites/$SITE_DIR/settings.php"

echo
echo "=================================================================="
echo " Site no ar em: http://$DOMINIO_VPS:$PORTA/"
echo " Login admin: admin / $SENHA_ADMIN  (GUARDE ESSA SENHA)"
echo "=================================================================="

# ---------------------------------------------------------------------
# 5) git add + commit + push - so com confirmacao explicita.
# ---------------------------------------------------------------------
echo
read -r -p "Subir pro GitHub agora? Isso vai fazer 'git add/commit/push'. [s/N] " RESP < /dev/tty
if [[ "$RESP" =~ ^[sS]$ ]]; then
  git add "web/sites/$SITE_DIR" web/sites/sites.php docker-compose.yml \
    docker/init-db.sql docker/entrypoint.sh .env.example \
    scripts/az_provisionar_esqueleto.php "docker/seed-$SITE_DIR.sql.gz"
  git commit -m "Adiciona site de departamento: $NOME ($SITE_DIR)"
  git push origin main
  echo "Enviado pro GitHub."
else
  echo "Ok, não subi nada. O site já está no ar mesmo assim - revise com 'git status' e suba quando quiser."
fi

echo
echo "Pronto! http://$DOMINIO_VPS:$PORTA/  (admin / $SENHA_ADMIN)"
