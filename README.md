# Departamento Modelo (Arizona Quickstart / Drupal)

Instalação nativa do [Arizona Quickstart](https://github.com/az-digital/az_quickstart) usada
como comparação com o site principal em Laravel (`../site`), com o mesmo conteúdo de
demonstração (notícias, pessoal, eventos, páginas institucionais). Módulo próprio em
[web/modules/custom/az_ufop_departamento](web/modules/custom/az_ufop_departamento) - tudo
que não é nativo do Arizona Quickstart está ali.

## Desenvolvimento local (DDEV)

```
ddev start
ddev launch
```

## Deploy (Hostinger / qualquer host com Docker)

Diferente do site em Laravel, este é um Drupal de verdade: precisa de banco de dados
(MariaDB) além do PHP. `docker-compose.yml` já sobe os dois.

1. Copie `.env.example` para `.env` e troque `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD` e gere um
   `HASH_SALT` novo (`openssl rand -base64 50`). Preencha `TRUSTED_HOST_PATTERN` com o domínio
   final quando tiver um.
2. `docker compose up -d --build`.
3. Na primeira subida, se o banco estiver vazio, o container importa sozinho
   `docker/seed.sql.gz` (dump do conteúdo de demonstração) - é assim que o site já abre
   "pronto", com o mesmo conteúdo do ambiente de referência.
4. O `vendor/`, `web/core` e os módulos/temas contrib **não vão pro git** - a imagem Docker
   reconstrói tudo via `composer install` a partir do `composer.lock` (ver `.gitignore` e
   `.dockerignore`). Só o módulo `az_ufop_departamento`, a config e o dump de conteúdo viajam
   com o repositório.
5. O volume `./web/sites/default/files` guarda as imagens enviadas (fica fora do git, exceto
   o conteúdo inicial já commitado) - sem ele, uploads novos somem a cada rebuild.

Para atualizar um deploy existente: `git pull`, depois `docker compose up -d --build`.
O `docker/entrypoint.sh` só importa o `seed.sql.gz` se o banco estiver **vazio** - em um
site já rodando, ele nunca sobrescreve dados existentes.

## Multisite de demonstração (seis departamentos, um só codebase)

Prova de conceito de como o Arizona Quickstart roda de verdade na Universidade do Arizona:
um único código (`vendor/`, `web/core`, módulos/tema, incluindo o `az_ufop_departamento`)
atendendo vários sites de departamento ao mesmo tempo, cada um com seu próprio banco de
dados e conteúdo, sem duplicar a instalação.

- `web/sites/dfis` - Departamento de Física (banco `dfis`, porta `DFIS_PORT`, padrão `8299`).
- `web/sites/demat` - Departamento de Matemática (banco `demat`, porta `DEMAT_PORT`,
  padrão `8399`).
- `web/sites/demed` - Departamento de Medicina (banco `demed`, porta `DEMED_PORT`,
  padrão `8499`).
- `web/sites/defil` - Departamento de Filosofia (banco `defil`, porta `DEFIL_PORT`,
  padrão `8599`).
- `web/sites/delet` - Departamento de Letras (banco `delet`, porta `DELET_PORT`,
  padrão `8699`).
- `web/sites/depro` - Departamento de Engenharia de Produção (banco `depro`, porta
  `DEPRO_PORT`, padrão `8799`).
- `web/sites/sites.php` mapeia porta+domínio para o diretório do site certo - é assim que o
  Drupal decide qual dos sete (default + 6 departamentos) responder, mesmo todos rodando no
  mesmo container/`index.php`. Ajuste os domínios ali se o servidor final tiver um domínio
  diferente de `srv1654694.hstgr.cloud`.
- Cada site importa seu próprio dump (`docker/seed-dfis.sql.gz`, `docker/seed-demat.sql.gz`
  etc.) isoladamente no primeiro boot, do mesmo jeito que o site principal.
- Criar mais um departamento = repetir o padrão: banco novo, `drush site:install` nesse
  banco, rodar `scripts/az_provisionar_esqueleto.php` (esqueleto completo de menu/conteúdo -
  basta adicionar o site à tabela `$DEPARTAMENTOS` no topo do script), entrada nova em
  `sites.php`, porta nova no `docker-compose.yml`/`.env.example`, e exportar o dump inicial
  pra `docker/seed-<site>.sql.gz`. Não tem formulário de autoatendimento pra isso -
  provisionar um site novo continua sendo tarefa de quem administra o servidor.

## Notes
If you are planning on pushing this site to Pantheon, you should use the
[Pantheon upstream repository](https://github.com/az-digital/az-quickstart-pantheon) as your scaffolding repo,
which has separate instructions.

![Security workflow](https://github.com/az-digital/az-quickstart-scaffolding/workflows/Security%20workflow/badge.svg)

- Designed to build a Quickstart website/project codebase
- Includes [Quickstart](https://github.com/az-digital/az_quickstart) as dependency
- Installs Drupal in a `web` subdirectory
- Utilizes [composer-installers](https://github.com/composer/installers) and [Drupal Composer Scaffold](https://github.com/drupal/core-composer-scaffold) plugins
- Can be used as a project template for the composer [create-project command](https://getcomposer.org/doc/03-cli.md#create-project)

## Choosing the Right Branch for Your Quickstart Project
In the Arizona Quickstart Scaffolding project, different branches serve various
purposes. Here's a guide to understanding which branch you should choose:

### Main Branch (`dev-main`)

- **Purpose**: The main branch contains all the latest features and changes. It
  represents the cutting-edge development and may be less stable than specific
  release branches. You can use it with `dev-main` or an alias for the latest
  unreleased version (e.g., `2.8.x`).
- **When to Use**: Choose this branch if you want access to the very latest
  features, and you are willing to work with potentially less-tested code. Ideal
  for development, experimentation, or testing unreleased functionality.

### Release Branches (e.g., `2.5.x`, `2.6.x`, `2.7.x`)

- **Purpose**: Release branches, named in the format of `2.x.x-dev`, are created
  for specific versions of the project and are considered stable and
  production-ready. They contain well-tested features suitable for live
  environments.
- **When to Use**: Select one of the latest two supported release branches
  (e.g., `2.7.x-dev`, `2.6.x-dev`) if you need a stable version of the project
  for production use, have specific environment constraints, or want to maintain
  compatibility with particular third-party modules or a specific release.
- **Note**: Only the latest two release branches are actively supported. Using
  one of these ensures that you receive updates, security patches, and support
  aligned with your system's requirements, avoiding potential conflicts and
  achieving a robust and reliable implementation.
- **Version Constraints in Supported Release Branches**: Each supported release
  branch in this repository specifies a version constraint for az_quickstart in composer.json.
  This constraint ensures that the release branch will work with compatible
  versions of Quickstart that correspond to the compatible minor release branch,
  providing flexibility while maintaining alignment with the intended versions.

### Feature or Issue Branches

Feature or issue branches in this repository are typically created to make
updates to the scaffolding to work with specific branches of
`az-digital/az_quickstart`. When creating such a branch, it is customary to pin
this repository's composer.json reference for `az-digital/az_quickstart` to a
specific issue/feature branch that the changes are intended to work with. This
ensures that the branch's changes align precisely with a particular state of the
Quickstart project.

These branches are useful for isolated development, testing of changes, or adding new features to the scaffolding that correspond to developments in the Quickstart project.

**Notes**
- If developing a feature for `az-digital/az_quickstart`, use the provided build tools (DDev or Lando) instead of running `composer create-project` directly.
- These branches are not as stable as the main or release branches, so use
  them for development, experimentation, or testing rather than in production
  environments.
- The use of feature or issue branches in this repository is tightly aligned
  with developments in the az-digital/az_quickstart project, and they should be
  used in conjunction with the corresponding Quickstart branches.

### Using the `composer create-project` command:

With Composer's `create-project` command, you can quickly scaffold a new project
using the Arizona Quickstart Scaffolding. Each `az-quickstart-scaffolding`
branch coincides with an `az_quickstart` branch, ensuring compatibility and
alignment between the scaffolding and the core project.


Here are some examples of how you might use this command with different branch
specifications:

If using any of the following commands, adjust `my_project_name` to your desired
project directory name.

- **Using the Main Branch (`main`) with dev dependencies**:
   ```bash
   composer create-project az-digital/az-quickstart-scaffolding:dev-main my_project_name --no-interaction

- **Using the Latest Unreleased Version Alias (`2.8.x`):**

   ```bash
   composer create-project az-digital/az-quickstart-scaffolding:2.8.x-dev my_project_name --no-interaction --no-dev
   ```

- **Using a Specific Release Branch (e.g., `2.7.x`):**
  ```bash
  composer create-project az-digital/az-quickstart-scaffolding:2.7.x-dev my_project_name --no-interaction --no-dev
  ```

- **Using an Issue or Feature Branch (e.g., `issue-45`)**

  ```bash
  composer create-project az-digital/az-quickstart-scaffolding:dev-issue/45 my_project_name --no-interaction
  ```

## Migration setup in Lando

Arizona Digital has added a [README for migrating into Quickstart 2](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/README.md).


## Updating Quickstart

Currently, Arizona Digital supports the two most recent [minor releases of Arizona Quickstart](https://github.com/az-digital/az_quickstart/blob/main/RELEASES.md).

- `composer install` will install updates and pull in dev dependencies, and also apply patches if they exist.
- `composer install  --no-dev` will install updates, and will remove dev dependencies, and also apply patches if they exist.
- `composer update` is supposed to get the latest available based on the [version constraints](https://getcomposer.org/doc/articles/versions.md#summary) in your composer.json file, and also apply patches if they exist.
- `composer require "az-digital/az_quickstart:2.7.0" --update-no-dev` Will pin to a specific version of Quickstart without dev dependencies. You can update your site, incrementing the version number whenever you want to update, or use version constraints with `composer update --no-dev` to only update to a tagged version.

Once your site's codebase is up to date, it is important to run database updates and distribution updates.

**Important** Always create a backup before running database updates or importing distribution updates.

Updating the database can be done via the command line:
```
drush updatedb
```
**Important** Always ensure your site is set on the correct strategy for importing distribution updates.
For Quickstart, it is recommended to use the merge strategy when importing distribution updates, which can be set via drush, or within the Admin UI.

It is advisable that you familiarize yourself with the functionality of the [Config Distro](https://www.drupal.org/project/config_distro) module to get the most out of Quickstart.

```
drush -y state:set config_sync.update_mode 1 --input-format=integer
```
Importing distribution updates can be done via the command line:
```
drush config-distro-update
```

# Launching your site

When you are ready to launch your site, you can remove dev dependencies included with Quickstart and this scaffolding repository with composer, but first you will want to make sure the development modules are not enabled in Drupal.

Example
```
drush pm:uninstall -y devel migrate_devel config_inspector
```

Once uninstalled, dev dependencies can safely be removed with composer.
```
composer remove --dev
```
