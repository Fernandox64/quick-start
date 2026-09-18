<?php

/**
 * Reordena o menu principal para que "Sobre" fique logo antes de
 * "Contato" (pedido do usuario). Ordem final: Inicio, Noticias, Eventos,
 * Graduacao, Pos-Graduacao, Pessoal, Servicos, Sobre, Contato.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$pesos = [
  'Início' => -10,
  'Notícias' => -9,
  'Eventos' => -8,
  'Graduação' => -7,
  'Pós-Graduação' => -6,
  'Pessoal' => -5,
  'Serviços' => -4,
  'Sobre' => -3,
  'Contato' => -2,
];

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$links = $storage->loadByProperties(['menu_name' => 'main']);

foreach ($links as $link) {
  $titulo = $link->getTitle();
  if (isset($pesos[$titulo])) {
    $link->set('weight', $pesos[$titulo]);
    $link->save();
    echo "$titulo => peso {$pesos[$titulo]}\n";
  }
}
