<?php

/**
 * Corrige o bug de 404 em conteudo/alias com langcode "en" quando a URL usa
 * o prefixo "/pt-br/" (Drupal nao faz fallback de idioma nesse cenario).
 * pt-br passa a ser a lingua padrao SEM prefixo - e a unica lingua de
 * navegacao de verdade aqui, so queriamos a interface traduzida.
 *
 * Roda em qualquer site: drush --uri=... scr scripts/az_fix_idioma_sem_prefixo.php
 */

$config = \Drupal::configFactory()->getEditable('language.negotiation');
$config->set('url.prefixes.pt-br', '');
$config->save();
echo "pt-br configurado sem prefixo.\n";

$db = \Drupal::database();

$db->update('path_alias')
  ->fields(['langcode' => 'pt-br'])
  ->condition('alias', '/categoria-de-curso/%', 'LIKE')
  ->execute();
$db->update('path_alias_revision')
  ->fields(['langcode' => 'pt-br'])
  ->condition('alias', '/categoria-de-curso/%', 'LIKE')
  ->execute();
echo "Aliases de Graduação/Pós-Graduação corrigidos.\n";

$tids = $db->select('taxonomy_term_field_data', 't')
  ->fields('t', ['tid'])
  ->condition('vid', 'az_curso_categoria')
  ->execute()
  ->fetchCol();

if ($tids) {
  $db->update('taxonomy_term_field_data')
    ->fields(['langcode' => 'pt-br'])
    ->condition('vid', 'az_curso_categoria')
    ->execute();
  $db->update('taxonomy_term_field_revision')
    ->fields(['langcode' => 'pt-br'])
    ->condition('tid', $tids, 'IN')
    ->execute();
  echo "Termos de Graduação/Pós-Graduação corrigidos.\n";
}

echo "Concluído - rode 'drush cache:rebuild' em seguida.\n";
