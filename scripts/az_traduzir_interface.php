<?php

/**
 * Adiciona traducoes customizadas (pt-br) para strings de interface que o
 * pacote de traducao da comunidade do Drupal ainda nao cobre (proprias de
 * modulos custom do az_quickstart, como "Read more" do az_news).
 *
 * Grava direto via query builder (a API de objetos do locale exigia um
 * fluxo mais complexo para strings ja existentes sem traducao).
 */

$db = \Drupal::database();

$traducoes = [
  'Read more' => 'Leia mais',
  'View all news' => 'Ver todas as notícias',
  'View all events on the calendar' => 'Ver todos os eventos no calendário',
  'Search' => 'Buscar',
  'Apply' => 'Aplicar',
  'Reset' => 'Limpar',
  'Submit' => 'Enviar',
  'Back to top' => 'Voltar ao topo',
];

foreach ($traducoes as $original => $traducao) {
  $lid = $db->select('locales_source', 's')
    ->fields('s', ['lid'])
    ->condition('source', $original)
    ->condition('context', '')
    ->execute()
    ->fetchField();

  if (!$lid) {
    $lid = $db->insert('locales_source')
      ->fields([
        'source' => $original,
        'context' => '',
        'version' => 'none',
      ])
      ->execute();
    echo "Origem criada: \"$original\" (lid=$lid)\n";
  }

  $existeTraducao = $db->select('locales_target', 't')
    ->fields('t', ['lid'])
    ->condition('lid', $lid)
    ->condition('language', 'pt-br')
    ->execute()
    ->fetchField();

  if ($existeTraducao) {
    $db->update('locales_target')
      ->fields(['translation' => $traducao, 'customized' => 1])
      ->condition('lid', $lid)
      ->condition('language', 'pt-br')
      ->execute();
  }
  else {
    $db->insert('locales_target')
      ->fields([
        'lid' => $lid,
        'translation' => $traducao,
        'language' => 'pt-br',
        'customized' => 1,
      ])
      ->execute();
  }
  echo "Traduzido: \"$original\" -> \"$traducao\"\n";
}
