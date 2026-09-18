<?php

/**
 * Corrige o link de "Eventos" no menu principal: a view de eventos do
 * az_quickstart fica em /calendar, nao /events.
 */

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$links = $storage->loadByProperties(['title' => 'Eventos', 'menu_name' => 'main']);

foreach ($links as $link) {
  $link->set('link', ['uri' => 'internal:/calendar']);
  $link->save();
  echo "Link 'Eventos' corrigido para /calendar\n";
}
