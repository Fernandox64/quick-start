<?php

/**
 * O `drush site:install` gera settings.php com o include do
 * settings.local.php comentado (bloco padrão do core, nunca
 * descomentado por padrão) - sem esse ajuste, as credenciais de
 * produção do .env nunca são carregadas na VPS (ver
 * docs/novo-departamento.md, seção "Erros comuns").
 *
 * Remove o bloco comentado e insere o include de verdade DEPOIS do
 * bloco $databases (que o drush escreve nesse arquivo). Busca por texto
 * literal, sem regex - mais robusto que tentar escapar parênteses/
 * colchetes aninhados do PHP dentro de um padrão. Idempotente: se já
 * estiver corrigido, não faz nada.
 *
 * Uso: php scripts/az_fix_settings_local_include.php <site_dir>
 */

$siteDir = $argv[1] ?? NULL;
if (!$siteDir) {
  fwrite(STDERR, "Uso: php scripts/az_fix_settings_local_include.php <site_dir>\n");
  exit(1);
}

$path = dirname(__DIR__) . "/web/sites/$siteDir/settings.php";
$conteudo = file_get_contents($path);
if ($conteudo === FALSE) {
  fwrite(STDERR, "ERRO: não consegui ler $path\n");
  exit(1);
}

if (str_contains($conteudo, '// Credenciais padrao do DDEV')) {
  echo "settings.php já estava corrigido - nada a fazer.\n";
  exit(0);
}

$blocoComentado = "#\n# if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {\n#   include \$app_root . '/' . \$site_path . '/settings.local.php';\n# }\n";
if (!str_contains($conteudo, $blocoComentado)) {
  fwrite(STDERR, "ERRO: bloco comentado esperado não encontrado em $path - corrija manualmente (ver docs/novo-departamento.md, passo 2.3).\n");
  exit(1);
}

$comentario = "#\n// Credenciais padrao do DDEV (dev local) - sobrescritas por\n// settings.local.php em producao (ver docker/entrypoint.sh), que por isso\n// precisa ser incluido DEPOIS deste bloco para valer de verdade.\n";
$conteudo = str_replace($blocoComentado, $comentario, $conteudo);

$marcaDatabases = "\$databases['default']['default'] = array (\n";
$fimMarcador = ");\n";
$posDatabases = strpos($conteudo, $marcaDatabases);
if ($posDatabases === FALSE) {
  fwrite(STDERR, "ERRO: bloco \$databases não encontrado em $path.\n");
  exit(1);
}
$posFim = strpos($conteudo, $fimMarcador, $posDatabases);
if ($posFim === FALSE) {
  fwrite(STDERR, "ERRO: fim do bloco \$databases não encontrado em $path.\n");
  exit(1);
}
$posFim += strlen($fimMarcador);

$includeAtivo = "if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {\n  include \$app_root . '/' . \$site_path . '/settings.local.php';\n}\n";
$novo = substr($conteudo, 0, $posFim) . $includeAtivo . substr($conteudo, $posFim);

file_put_contents($path, $novo);
echo "settings.php corrigido: include do settings.local.php movido pra depois do \$databases e descomentado.\n";
