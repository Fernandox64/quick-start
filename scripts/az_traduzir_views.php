<?php

/**
 * Traduz para portugues os titulos de views que ficaram em ingles (nao sao
 * strings de interface cobertas pelo pacote de traducao da comunidade -
 * sao valores literais salvos na config da view).
 */

$config = \Drupal::configFactory()->getEditable('views.view.az_news');
foreach (['default', 'az_sidebar', 'az_small_row', 'az_paged_row', 'az_grid', 'az_grid_filter', 'az_feature', 'marquee'] as $display) {
  $key = "display.$display.display_options.title";
  if ($config->get($key) !== NULL) {
    $config->set($key, 'Notícias');
  }
}
$config->save();
echo "views.view.az_news traduzida.\n";

$config = \Drupal::configFactory()->getEditable('views.view.az_events');
foreach (['default', 'page_1', 'az_grid', 'az_grid_images', 'az_past_grid_images', 'az_past_row', 'az_sidebar'] as $display) {
  $key = "display.$display.display_options.title";
  if ($config->get($key) !== NULL) {
    $config->set($key, 'Calendário');
  }
}
$config->save();
echo "views.view.az_events traduzida.\n";

$config = \Drupal::configFactory()->getEditable('views.view.az_person');
foreach ($config->get('display') as $displayId => $display) {
  $key = "display.$displayId.display_options.title";
  if ($config->get($key) !== NULL && $config->get($key) === 'Directory') {
    $config->set($key, 'Diretório');
  }
}
$config->save();
echo "views.view.az_person verificada.\n";
