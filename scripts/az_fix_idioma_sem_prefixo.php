<?php

/**
 * Corrige o bug de 404/conteudo sumido em qualquer entidade (node, termo,
 * paragrafo, item de menu, midia, alias...) com langcode "en" quando pt-br
 * e a lingua padrao: o Drupal nao faz fallback de idioma nesse cenario, e
 * cada tipo de entidade que ainda estava em "en" ia aparecendo quebrado aos
 * poucos (primeiro Graduacao/Pos-Graduacao, depois a home inteira, depois
 * os paragrafos dentro da home).
 *
 * Em vez de corrigir tabela por tabela conforme aparece, varre TODAS as
 * tabelas do banco que tem uma coluna "langcode" e converte "en" -> "pt-br"
 * de uma vez so - cobre nodes, termos, paragrafos, itens de menu, midia,
 * blocos de conteudo, aliases, o que for.
 *
 * Roda em qualquer site: drush --uri=... scr scripts/az_fix_idioma_sem_prefixo.php
 */

$config = \Drupal::configFactory()->getEditable('language.negotiation');
$config->set('url.prefixes.pt-br', '');
$config->save();
echo "pt-br configurado sem prefixo.\n";

$db = \Drupal::database();

$tabelas = $db->query("
  SELECT DISTINCT TABLE_NAME
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND COLUMN_NAME = 'langcode'
")->fetchCol();

$total_tabelas = 0;
foreach ($tabelas as $tabela) {
  $afetadas = $db->update($tabela)
    ->fields(['langcode' => 'pt-br'])
    ->condition('langcode', 'en')
    ->execute();
  if ($afetadas > 0) {
    echo "$tabela: $afetadas registro(s) convertido(s) de en para pt-br.\n";
    $total_tabelas++;
  }
}

echo "Concluído ($total_tabelas tabelas afetadas) - rode 'drush cache:rebuild' em seguida.\n";
