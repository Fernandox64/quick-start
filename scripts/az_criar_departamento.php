<?php

/**
 * Edita os arquivos de configuração necessários pra registrar um novo site
 * de departamento (Parte 1 do docs/novo-departamento.md). Roda com PHP puro
 * (sem bootstrap do Drupal) - é chamado pelo scripts/az_criar_departamento.sh,
 * que cuida do resto (DDEV, drush, dump, git).
 *
 * Cada edição procura uma âncora estável no arquivo e falha alto (exit 1)
 * se não encontrar, em vez de tentar adivinhar e corromper o arquivo -
 * mais seguro que sed/awk às cegas.
 *
 * Uso: php scripts/az_criar_departamento.php <site_dir> <porta> <sigla>
 */

[, $siteDir, $porta, $sigla] = $argv + [NULL, NULL, NULL, NULL];
if (!$siteDir || !$porta || !$sigla) {
  fwrite(STDERR, "Uso: php scripts/az_criar_departamento.php <site_dir> <porta> <sigla>\n");
  exit(1);
}

$root = dirname(__DIR__);
$dominioVps = 'srv1654694.hstgr.cloud';

function ler(string $path): string {
  $conteudo = file_get_contents($path);
  if ($conteudo === FALSE) {
    fwrite(STDERR, "ERRO: não consegui ler $path\n");
    exit(1);
  }
  return $conteudo;
}

function gravar(string $path, string $conteudo): void {
  file_put_contents($path, $conteudo);
}

/**
 * Insere $insercao logo ANTES da primeira ocorrência de $ancora em
 * $conteudo. Falha se a âncora não existir (arquivo mudou de formato).
 */
function inserirAntes(string $conteudo, string $ancora, string $insercao, string $arquivo): string {
  $pos = strpos($conteudo, $ancora);
  if ($pos === FALSE) {
    fwrite(STDERR, "ERRO: âncora não encontrada em $arquivo:\n  \"$ancora\"\n  O arquivo pode ter mudado de formato - edite manualmente (ver docs/novo-departamento.md).\n");
    exit(1);
  }
  return substr($conteudo, 0, $pos) . $insercao . substr($conteudo, $pos);
}

// ---------------------------------------------------------------------
// 1) web/sites/sites.php - so acrescenta no fim, ordem nao importa.
// ---------------------------------------------------------------------
$path = "$root/web/sites/sites.php";
$conteudo = ler($path);
if (str_contains($conteudo, "'$siteDir'")) {
  fwrite(STDERR, "ERRO: '$siteDir' já existe em web/sites/sites.php. Escolha outra pasta.\n");
  exit(1);
}
$conteudo = rtrim($conteudo) . "\n\$sites['$porta.$dominioVps'] = '$siteDir';\n\$sites['$porta.localhost'] = '$siteDir';\n";
gravar($path, $conteudo);
echo "web/sites/sites.php atualizado.\n";

// ---------------------------------------------------------------------
// 2) docker-compose.yml - porta antes de "volumes:", volume antes de
//    "depends_on:".
// ---------------------------------------------------------------------
$path = "$root/docker-compose.yml";
$conteudo = ler($path);
$linhaPorta = "      - \"\${" . $sigla . "_PORT:-" . $porta . "}:80\"\n";
$conteudo = inserirAntes($conteudo, "    volumes:\n", $linhaPorta, 'docker-compose.yml (ports)');
$conteudo = inserirAntes($conteudo, "    depends_on:\n", "      - ./web/sites/$siteDir/files:/app/web/sites/$siteDir/files\n", 'docker-compose.yml (volumes)');
gravar($path, $conteudo);
echo "docker-compose.yml atualizado.\n";

// ---------------------------------------------------------------------
// 3) docker/init-db.sql - antes de "FLUSH PRIVILEGES;".
// ---------------------------------------------------------------------
$path = "$root/docker/init-db.sql";
$conteudo = ler($path);
$conteudo = inserirAntes($conteudo, "FLUSH PRIVILEGES;", "CREATE DATABASE IF NOT EXISTS $siteDir;\nGRANT ALL PRIVILEGES ON $siteDir.* TO 'db'@'%';\n", 'docker/init-db.sql');
gravar($path, $conteudo);
echo "docker/init-db.sql atualizado.\n";

// ---------------------------------------------------------------------
// 4) docker/entrypoint.sh - gerar_settings_local antes do "echo
//    Esperando", importar_se_vazio antes do "chown -R", e acrescenta a
//    pasta de files nessa mesma linha do chown.
// ---------------------------------------------------------------------
$path = "$root/docker/entrypoint.sh";
$conteudo = ler($path);
$conteudo = inserirAntes($conteudo, 'echo "Esperando o banco de dados', "gerar_settings_local $siteDir $siteDir\n", 'docker/entrypoint.sh (gerar_settings_local)');
$conteudo = inserirAntes($conteudo, 'chown -R www-data:www-data', "importar_se_vazio $siteDir /app/docker/seed-$siteDir.sql.gz \"http://localhost:$porta\"\n\n", 'docker/entrypoint.sh (importar_se_vazio)');
// Acrescenta a pasta de files no fim da linha do chown (ela termina em \n).
$conteudo = preg_replace(
  '/^(chown -R www-data:www-data[^\n]*)\n/m',
  '$1 /app/web/sites/' . $siteDir . "/files\n",
  $conteudo,
  1,
  $qtd
);
if ($qtd !== 1) {
  fwrite(STDERR, "ERRO: não achei a linha \"chown -R www-data:www-data...\" em docker/entrypoint.sh pra completar com a pasta de files - adicione manualmente.\n");
  exit(1);
}
gravar($path, $conteudo);
echo "docker/entrypoint.sh atualizado.\n";

// ---------------------------------------------------------------------
// 5) .env.example - depois da última linha "*_PORT=".
// ---------------------------------------------------------------------
$path = "$root/.env.example";
$conteudo = ler($path);
if (!preg_match_all('/^[A-Z_]+_PORT=\d+$/m', $conteudo, $m, PREG_OFFSET_CAPTURE)) {
  fwrite(STDERR, "ERRO: não achei nenhuma linha \"*_PORT=\" em .env.example.\n");
  exit(1);
}
$ultimo = end($m[0]);
$fimLinha = $ultimo[1] + strlen($ultimo[0]);
$conteudo = substr($conteudo, 0, $fimLinha) . "\n" . $sigla . "_PORT=" . $porta . substr($conteudo, $fimLinha);
gravar($path, $conteudo);
echo ".env.example atualizado.\n";

// ---------------------------------------------------------------------
// 6) scripts/az_provisionar_esqueleto.php - dentro do array
//    $DEPARTAMENTOS, antes do "];" que fecha ele.
// ---------------------------------------------------------------------
$path = "$root/scripts/az_provisionar_esqueleto.php";
$conteudo = ler($path);
$inicio = strpos($conteudo, '$DEPARTAMENTOS = [');
if ($inicio === FALSE) {
  fwrite(STDERR, "ERRO: não achei \"\$DEPARTAMENTOS = [\" em scripts/az_provisionar_esqueleto.php.\n");
  exit(1);
}
$fimArray = strpos($conteudo, "\n];", $inicio);
if ($fimArray === FALSE) {
  fwrite(STDERR, "ERRO: não achei o fim do array \$DEPARTAMENTOS.\n");
  exit(1);
}
$area = $GLOBALS['argv'][4] ?? NULL;
$nome = $GLOBALS['argv'][5] ?? NULL;
if (!$area || !$nome) {
  fwrite(STDERR, "ERRO interno: nome/área não recebidos.\n");
  exit(1);
}
$linha = "\n  '$siteDir' => ['nome' => '$nome', 'sigla' => '$sigla', 'area' => '$area'],";
$conteudo = substr($conteudo, 0, $fimArray) . $linha . substr($conteudo, $fimArray);
gravar($path, $conteudo);
echo "scripts/az_provisionar_esqueleto.php atualizado.\n";

echo "\nTodos os arquivos de configuração foram atualizados com sucesso.\n";
