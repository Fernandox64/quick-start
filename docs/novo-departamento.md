# Criar um novo site de departamento

Guia de referência para adicionar mais um departamento ao multisite (mesmo
codebase, banco de dados e domínio próprios). Não tem formulário de
autoatendimento pra isso — é um processo manual, mas totalmente
reprodutível seguindo os passos abaixo.

O exemplo usado neste guia é o **Departamento de Metalurgia**
(`demet`, porta `8899`) — troque pelos dados do departamento real.

## Atalho: script automatizado

As Partes 1 e 2 abaixo (editar os 6 arquivos de configuração, criar o
banco local, instalar o Drupal, popular o conteúdo e exportar o dump) têm
um script que faz tudo isso sozinho:

```bash
./scripts/az_criar_departamento.sh <site_dir> "<Nome completo>" ["<área>"]

# Exemplo:
./scripts/az_criar_departamento.sh demet "Departamento de Metalurgia" "metalurgia"
```

Ele calcula a próxima porta livre e gera uma senha de admin sozinho, e só
pergunta antes das duas coisas que mexem em algo compartilhado: dar
`git push` e aplicar na VPS (cada uma com sua própria confirmação [s/N] -
responder "N" simplesmente para por aí, sem fazer nada além do que já foi
feito localmente). O restante deste documento explica cada passo que o
script executa, útil pra quem prefere rodar manualmente ou pra debugar se
algo no script falhar no meio do caminho.

## Sites já existentes (referência de portas)

| Pasta (`site_dir`) | Nome | Porta |
|---|---|---|
| `default` | Departamento Modelo | `8198` |
| `dfis` | Departamento de Física | `8299` |
| `demat` | Departamento de Matemática | `8399` |
| `demed` | Departamento de Medicina | `8499` |
| `defil` | Departamento de Filosofia | `8599` |
| `delet` | Departamento de Letras | `8699` |
| `depro` | Departamento de Engenharia de Produção | `8799` |
| `demet` *(exemplo deste guia)* | Departamento de Metalurgia | `8899` |

A próxima porta livre é sempre a última + 100.

## Visão geral

O processo tem duas partes bem separadas:

- **Local (seu PC)** — onde ficam os arquivos do projeto e a chave SSH do
  GitHub. É aqui que se edita configuração, instala o Drupal via DDEV,
  popula o conteúdo, exporta o dump inicial e dá `git push`.
- **VPS** — só recebe o `git pull` e cria o banco de dados vazio; o
  conteúdo entra sozinho no primeiro boot do container (o
  `docker/entrypoint.sh` importa o dump automaticamente quando detecta
  um banco vazio).

## Arquivos que precisam ser editados (Parte 1, no PC)

| Arquivo | O que adicionar |
|---|---|
| `web/sites/sites.php` | Duas linhas em `$sites[]`: o domínio da VPS e o `localhost` de teste, apontando pra pasta do novo site |
| `docker-compose.yml` | Uma porta em `ports:` e um volume de `files` em `volumes:` |
| `docker/init-db.sql` | `CREATE DATABASE` + `GRANT` pro banco novo (só serve de referência para uma instalação nova do zero — na VPS existente o banco é criado manualmente, ver Parte 4) |
| `docker/entrypoint.sh` | Uma chamada `gerar_settings_local`, uma `importar_se_vazio`, e acrescentar a pasta de `files` na linha `chown -R` |
| `.env.example` | Uma variável `<SITE>_PORT` |
| `scripts/az_provisionar_esqueleto.php` | Uma entrada no array `$DEPARTAMENTOS` (nome, sigla, área) |

Depois da Parte 2 (instalação local), mais dois arquivos passam a existir
e também vão pro commit:

| Arquivo | Origem |
|---|---|
| `web/sites/<site_dir>/settings.php` | Gerado pelo `drush site:install` (precisa de um ajuste manual — ver Parte 2.3) |
| `docker/seed-<site_dir>.sql.gz` | Dump exportado do banco local já populado |

## Passo a passo

### Parte 1 — No seu PC: editar os arquivos de configuração

```bash
cd "C:\Users\Samantha\Documents\code\DOCKER\LARAVEL\HOSTINGER\template\arizona-ufop"
```

**1.1) `web/sites/sites.php`** — adicione nos dois blocos existentes:

```php
$sites['8899.srv1654694.hstgr.cloud'] = 'demet';
$sites['8899.localhost'] = 'demet';
```

**1.2) `docker-compose.yml`** — adicione em `ports:` e em `volumes:`:

```yaml
      - "${DEMET_PORT:-8899}:80"
      - ./web/sites/demet/files:/app/web/sites/demet/files
```

**1.3) `docker/init-db.sql`** — adicione:

```sql
CREATE DATABASE IF NOT EXISTS demet;
GRANT ALL PRIVILEGES ON demet.* TO 'db'@'%';
```

**1.4) `docker/entrypoint.sh`** — adicione nas duas listas de chamadas, e
acrescente a pasta na linha `chown -R`:

```bash
gerar_settings_local demet demet
importar_se_vazio demet /app/docker/seed-demet.sql.gz "http://localhost:8899"
```

**1.5) `.env.example`** — adicione:

```
DEMET_PORT=8899
```

**1.6) `scripts/az_provisionar_esqueleto.php`** — adicione dentro do array
`$DEPARTAMENTOS`:

```php
'demet' => ['nome' => 'Departamento de Metalurgia', 'sigla' => 'DEMET', 'area' => 'metalurgia'],
```

### Parte 2 — No seu PC: criar e popular o site local (DDEV)

Com `ddev start` já rodando na pasta do projeto:

```bash
# 2.1) Criar o banco local
ddev mysql -e "CREATE DATABASE IF NOT EXISTS demet;"
ddev mysql -e "GRANT ALL PRIVILEGES ON demet.* TO 'db'@'%'; FLUSH PRIVILEGES;"

# 2.2) Instalar o Drupal nesse banco (troque a senha por uma forte)
ddev drush site:install az_quickstart --sites-subdir=demet \
  --db-url=mysql://db:db@db/demet --site-name="Departamento de Metalurgia" \
  --account-name=admin --account-pass='TROQUE-ESTA-SENHA' -y
```

**2.3) Corrigir `web/sites/demet/settings.php` (passo manual, não pular)**

O `drush site:install` gera esse arquivo com o include do
`settings.local.php` **comentado** — sem esse ajuste, as credenciais de
produção do `.env` nunca são carregadas na VPS. Abra o arquivo, ache perto
do final:

```php
#
# if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
#   include $app_root . '/' . $site_path . '/settings.local.php';
# }
$databases['default']['default'] = array ( ... );
```

E troque para: primeiro o bloco `$databases`, **depois** (sem `#`
comentando):

```php
$databases['default']['default'] = array ( ... );
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

(compare com `web/sites/demed/settings.php` pra ver o formato exato já
funcionando)

```bash
# 2.4) Habilitar o módulo e rodar o esqueleto de conteúdo
ddev drush --uri=8899.localhost pm:enable az_ufop_departamento -y
ddev drush --uri=8899.localhost scr scripts/az_provisionar_esqueleto.php
ddev drush --uri=8899.localhost locale:update
ddev drush --uri=8899.localhost scr scripts/az_fix_idioma_sem_prefixo.php
ddev drush --uri=8899.localhost scr scripts/az_fix_rodape_copyright.php
ddev drush --uri=8899.localhost scr scripts/az_add_landing_servicos.php
ddev drush --uri=8899.localhost scr scripts/az_criar_landing_cursos.php
ddev drush --uri=8899.localhost scr scripts/az_ocultar_titulo_tutorial.php
ddev drush --uri=8899.localhost scr scripts/az_traduzir_views_texto_fixo.php
ddev drush --uri=8899.localhost cache:rebuild

# 2.5) Exportar o dump inicial
ddev export-db --database=demet --file=/tmp/seed-demet.sql.gz
cp /tmp/seed-demet.sql.gz docker/seed-demet.sql.gz
```

### Parte 3 — No seu PC: subir pro GitHub

```bash
git add web/sites/demet web/sites/sites.php docker-compose.yml \
  docker/init-db.sql docker/entrypoint.sh .env.example \
  scripts/az_provisionar_esqueleto.php docker/seed-demet.sql.gz

git commit -m "Adiciona site de departamento: Metalurgia (demet)"
git push origin main
```

### Parte 4 — Na VPS

```bash
cd /opt/arizona-ufop
git pull

# Criar o banco vazio na VPS
docker compose exec db mysql -uroot -p$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2) -e "
CREATE DATABASE IF NOT EXISTS demet;
GRANT ALL PRIVILEGES ON demet.* TO 'db'@'%';
FLUSH PRIVILEGES;
"

# Adicionar a porta nova no .env de produção
echo "DEMET_PORT=8899" >> .env

# Subir o container novo - ele importa o dump sozinho (banco vazio)
docker compose up -d --build

# Conferir se importou (procure "Banco 'demet' vazio - importando...")
docker compose logs web --tail=50 | grep -i demet

# Rebuild final de cache em todos os sites
for URI in default dfis demat demed defil delet depro demet; do
  docker compose exec web vendor/bin/drush --uri=$URI cache:rebuild
done
```

Site pronto em `http://srv1654694.hstgr.cloud:8899/`. Login: usuário
`admin`, senha definida no passo 2.2.

## Erros comuns

- **Site sobe mas dá erro de conexão com o banco**: esqueceu o passo 2.3
  (o include do `settings.local.php` continua comentado).
- **`git pull` trava na VPS**: normalmente é algum arquivo gerado
  automaticamente (sitemap, tradução, imagem) que ficou rastreado por
  engano — ver `.gitignore`. Rode `git status` antes de qualquer comando
  destrutivo.
- **Página de Serviços/Graduação/Pós-Graduação sem link nos cards, ou
  título "Tutorial" duplicado**: algum dos scripts do passo 2.4 não foi
  rodado. Todos são idempotentes — pode rodar de novo sem medo de
  duplicar conteúdo.
- **Conteúdo mudou depois mas a VPS não reflete**: `git pull` só move
  código. Mudança de conteúdo/config exige rodar o script `.php`
  correspondente via `drush scr` em cada site na VPS.
