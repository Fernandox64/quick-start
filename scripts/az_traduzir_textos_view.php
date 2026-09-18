<?php

/**
 * Traduz textos customizados (areas "text_custom") das views az_events e
 * az_news que ficaram em ingles - sao HTML literal salvo na config da view,
 * nao strings de interface, entao nao sao cobertos pelo pacote de traducao
 * nem pelas entradas manuais em locales_source/locales_target.
 */

$db = \Drupal::database();

$substituicoes = [
  'View all events on the calendar' => 'Ver todos os eventos no calendário',
  'View all news' => 'Ver todas as notícias',
];

foreach (['views.view.az_events', 'views.view.az_news'] as $viewName) {
  $config = \Drupal::configFactory()->getEditable($viewName);
  $displays = $config->get('display');
  $alterado = FALSE;

  foreach ($displays as $displayId => $display) {
    foreach (['header', 'footer', 'empty'] as $areaType) {
      $areas = $display['display_options'][$areaType] ?? NULL;
      if (!$areas) {
        continue;
      }
      foreach ($areas as $areaId => $area) {
        if (($area['plugin_id'] ?? NULL) !== 'text_custom' || empty($area['content'])) {
          continue;
        }
        $content = $area['content'];
        foreach ($substituicoes as $original => $traducao) {
          if (str_contains($content, $original)) {
            $content = str_replace($original, $traducao, $content);
          }
        }
        if ($content !== $area['content']) {
          $config->set("display.$displayId.display_options.$areaType.$areaId.content", $content);
          $alterado = TRUE;
          echo "$viewName / $displayId / $areaType.$areaId traduzido.\n";
        }
      }
    }
  }

  if ($alterado) {
    $config->save();
  }
}
