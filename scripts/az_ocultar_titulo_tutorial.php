<?php

/**
 * A pagina /tutorial ja tem seu proprio titulo estilizado (banner vinho) -
 * sem isso, o titulo padrao do Drupal ("Tutorial: como editar o site")
 * aparece duplicado em cima do banner. O bloco "Page title" do tema ja tem
 * uma condicao de visibilidade (esconde em /person/* e /news/*) - so
 * acrescenta /tutorial nessa mesma lista.
 *
 * Reutilizavel: drush --uri=... scr scripts/az_ocultar_titulo_tutorial.php
 */

$block = \Drupal\block\Entity\Block::load('az_barrio_page_title');
if (!$block) {
  echo "Bloco 'az_barrio_page_title' nao encontrado neste site - nada a fazer.\n";
  return;
}

$visibility = $block->getVisibility();
$pages = $visibility['request_path']['pages'] ?? '';
if (str_contains($pages, '/tutorial')) {
  echo "/tutorial ja esta na lista de exclusao do titulo - nada a fazer.\n";
  return;
}

$pages = rtrim($pages) . "\r\n/tutorial";
$block->setVisibilityConfig('request_path', [
  'id' => 'request_path',
  'negate' => TRUE,
  'pages' => $pages,
]);
$block->save();

echo "/tutorial adicionado a lista de paginas sem o titulo padrao duplicado.\n";
