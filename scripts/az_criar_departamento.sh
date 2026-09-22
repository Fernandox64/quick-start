#!/usr/bin/env bash
#
# Automatiza a criação de um novo site de departamento (Partes 1 e 2 de
# docs/novo-departamento.md): edita os arquivos de configuração, cria o
# banco local, instala o Drupal, roda o esqueleto de conteúdo e exporta
# o dump inicial. Ao final, PERGUNTA antes de dar git push e antes de
# mexer na VPS - nada disso acontece sem confirmação explícita.
#
# Uso:
#   ./scripts/az_criar_departamento.sh <site_dir> "<Nome completo>" ["<área>"]
#
# Exemplo:
#   ./scripts/az_criar_departamento.sh demet "Departamento de Metalurgia" "metalurgia"
#
# site_dir: pasta curta, só letras minúsculas (ex.: demet, dequi, depsi).
# área: opcional - se omitida, é derivada do nome ("Departamento de X" -> "x").

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

if [[ -z "$AREA" ]]; then
  AREA="$(echo "$NOME" | sed -E 's/^Departamento (de |do |da )?//I' | tr '[:upper:]' '[:lower:]')"
fi
SIGLA="$(echo "$SITE_DIR" | tr '[:lower:]' '[:upper:]')"

# Proxima porta livre = maior porta ja usada em sites.php + 100.
ULTIMA_PORTA=$(grep -oE "\\\$sites\['[0-9]+\." web/sites/sites.php | grep -oE '[0-9]+' | sort -n | tail -1)
PORTA=$((ULTIMA_PORTA + 100))
SENHA_ADMIN=$(openssl rand -base64 12 | tr -d '/+=' | cut -c1-14)

echo "=================================================================="
echo " Criando site de departamento"
echo "   Pasta (site_dir): $SITE_DIR"
echo "   Nome:             $NOME"
echo "   Área:             $AREA"
echo "   Sigla:            $SIGLA"
echo "   Porta:            $PORTA"
echo "=================================================================="
read -r -p "Confere? Pressione ENTER pra continuar ou Ctrl+C pra cancelar..." < /dev/tty

# ---------------------------------------------------------------------
# 1) Edita os 6 arquivos de configuracao.
# ---------------------------------------------------------------------
echo
echo "--- Parte 1: editando arquivos de configuração ---"
php scripts/az_criar_departamento.php "$SITE_DIR" "$PORTA" "$SIGLA" "$AREA" "$NOME"

# ---------------------------------------------------------------------
# 2) Banco local + instalacao do Drupal via DDEV.
# ---------------------------------------------------------------------
echo
echo "--- Parte 2: criando banco local e instalando o Drupal (DDEV) ---"
ddev mysql -e "CREATE DATABASE IF NOT EXISTS $SITE_DIR;"
ddev mysql -e "GRANT ALL PRIVILEGES ON $SITE_DIR.* TO 'db'@'%'; FLUSH PRIVILEGES;"

ddev drush site:install az_quickstart --sites-subdir="$SITE_DIR" \
  --db-url="mysql://db:db@db/$SITE_DIR" --site-name="$NOME" \
  --account-name=admin --account-pass="$SENHA_ADMIN" -y

echo
echo "--- Corrigindo settings.php (include do settings.local.php) ---"
php scripts/az_fix_settings_local_include.php "$SITE_DIR"

# ---------------------------------------------------------------------
# 3) Modulo + esqueleto de conteudo + traducoes + fixes conhecidos.
# ---------------------------------------------------------------------
echo
echo "--- Populando conteúdo (isso demora um pouco) ---"
URI="$PORTA.localhost"
ddev drush --uri="$URI" pm:enable az_ufop_departamento -y
ddev drush --uri="$URI" scr scripts/az_provisionar_esqueleto.php
ddev drush --uri="$URI" locale:update
ddev drush --uri="$URI" scr scripts/az_fix_idioma_sem_prefixo.php
ddev drush --uri="$URI" scr scripts/az_fix_rodape_copyright.php
ddev drush --uri="$URI" scr scripts/az_add_landing_servicos.php
ddev drush --uri="$URI" scr scripts/az_criar_landing_cursos.php
ddev drush --uri="$URI" scr scripts/az_ocultar_titulo_tutorial.php
ddev drush --uri="$URI" scr scripts/az_traduzir_views_texto_fixo.php
ddev drush --uri="$URI" cache:rebuild

# ---------------------------------------------------------------------
# 4) Exporta o dump inicial.
# ---------------------------------------------------------------------
echo
echo "--- Exportando dump inicial ---"
ddev export-db --database="$SITE_DIR" --file="/tmp/seed-$SITE_DIR.sql.gz"
cp "/tmp/seed-$SITE_DIR.sql.gz" "docker/seed-$SITE_DIR.sql.gz"

echo
echo "=================================================================="
echo " Site local pronto!"
echo "   Login admin: admin / $SENHA_ADMIN  (GUARDE ESSA SENHA)"
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
  echo "Ok, não subi nada. Os arquivos continuam alterados localmente - revise com 'git status' e suba quando quiser."
  exit 0
fi

# ---------------------------------------------------------------------
# 6) Deploy na VPS - so com confirmacao explicita, e so se o comando
#    "ssh hostinger" estiver configurado.
# ---------------------------------------------------------------------
echo
read -r -p "Aplicar na VPS agora (ssh hostinger)? [s/N] " RESP_VPS < /dev/tty
if [[ ! "$RESP_VPS" =~ ^[sS]$ ]]; then
  echo "Ok. Pra aplicar na VPS depois, veja a Parte 4 de docs/novo-departamento.md."
  exit 0
fi

echo
echo "--- Aplicando na VPS ---"
ssh hostinger "cd /opt/arizona-ufop && git pull"
ssh hostinger "cd /opt/arizona-ufop && docker compose exec db mysql -uroot -p\$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2) -e \"CREATE DATABASE IF NOT EXISTS $SITE_DIR; GRANT ALL PRIVILEGES ON $SITE_DIR.* TO 'db'@'%'; FLUSH PRIVILEGES;\""
ssh hostinger "cd /opt/arizona-ufop && grep -q '^${SIGLA}_PORT=' .env || echo '${SIGLA}_PORT=$PORTA' >> .env"
ssh hostinger "cd /opt/arizona-ufop && docker compose up -d --build"

echo "Conferindo se o conteúdo foi importado..."
ssh hostinger "cd /opt/arizona-ufop && docker compose logs web --tail=80 | grep -i $SITE_DIR" || true

for URI in default dfis demat demed defil delet depro "$SITE_DIR"; do
  ssh hostinger "cd /opt/arizona-ufop && docker compose exec web vendor/bin/drush --uri=$URI cache:rebuild" || true
done

echo
echo "=================================================================="
echo " Pronto! Site no ar em: http://$DOMINIO_VPS:$PORTA/"
echo " Login admin: admin / $SENHA_ADMIN"
echo "=================================================================="
