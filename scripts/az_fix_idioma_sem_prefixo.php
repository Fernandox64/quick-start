<?php

/**
 * Corrige o bug de 404 em qualquer conteudo/alias/termo com langcode "en"
 * quando pt-br e a lingua padrao: o Drupal nao faz fallback de idioma pra
 * entidade nem pra alias nesse cenario, entao qualquer coisa criada como
 * "ingles" (a maioria do conteudo, incluindo o que o proprio esqueleto
 * cria) vira 404 - inclusive a propria home, como aconteceu no DFIS.
 *
 * Primeiro tira a exigencia de prefixo "/pt-br/" (nao e um site
 * multilingue de verdade, so queriamos a interface traduzida) e depois
 * converte TODO o conteudo/alias/termo de "en" pra "pt-br" em bloco, pra
 * nao ficar corrigindo pagina por pagina conforme aparece.
 *
 * Roda em qualquer site: drush --uri=... scr scripts/az_fix_idioma_sem_prefixo.php
 */

$config = \Drupal::configFactory()->getEditable('language.negotiation');
$config->set('url.prefixes.pt-br', '');
$config->save();
echo "pt-br configurado sem prefixo.\n";

$db = \Drupal::database();

$tabelas = [
  'node_field_data' => 'nid',
  'node_field_revision' => 'nid',
  'taxonomy_term_field_data' => 'tid',
  'taxonomy_term_field_revision' => 'tid',
  'path_alias' => 'id',
  'path_alias_revision' => 'id',
];

foreach ($tabelas as $tabela => $coluna) {
  if (!$db->schema()->tableExists($tabela)) {
    continue;
  }
  $afetadas = $db->update($tabela)
    ->fields(['langcode' => 'pt-br'])
    ->condition('langcode', 'en')
    ->execute();
  echo "$tabela: $afetadas registro(s) convertido(s) de en para pt-br.\n";
}

echo "Concluído - rode 'drush cache:rebuild' em seguida.\n";
