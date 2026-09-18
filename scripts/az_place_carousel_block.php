<?php

/**
 * Coloca o bloco do carrossel de noticias na regiao "content_featured" do
 * tema az_barrio, visivel apenas na pagina inicial.
 */

use Drupal\block\Entity\Block;

$id = 'az_ufop_noticias_carousel';

if (Block::load($id)) {
  Block::load($id)->delete();
}

$block = Block::create([
  'id' => $id,
  'theme' => 'az_barrio',
  'region' => 'content_featured',
  'plugin' => 'az_ufop_noticias_carousel',
  'weight' => -20,
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'negate' => FALSE,
      'pages' => '<front>',
    ],
  ],
  'settings' => [
    'id' => 'az_ufop_noticias_carousel',
    'label' => 'Carrossel de Notícias',
    'label_display' => '0',
    'provider' => 'az_ufop_departamento',
  ],
]);
$block->save();

echo "Bloco de carrossel colocado em content_featured (so na home).\n";
