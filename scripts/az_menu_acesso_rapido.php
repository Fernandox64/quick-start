<?php

/**
 * Cria o menu "Acesso Rápido" (Área do Aluno, Notícias, Editais, Eventos,
 * Contato) e coloca um bloco dele na barra lateral, em todo o site.
 */

use Drupal\system\Entity\Menu;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\block\Entity\Block;

$menuId = 'acesso-rapido';

if (!Menu::load($menuId)) {
  Menu::create([
    'id' => $menuId,
    'label' => 'Acesso Rápido',
    'description' => 'Links de acesso rapido exibidos na barra lateral.',
  ])->save();
  echo "Menu 'acesso-rapido' criado.\n";
}

$links = [
  ['title' => 'Área do Aluno', 'uri' => 'internal:/user/login', 'weight' => -5, 'icon' => 'school'],
  ['title' => 'Notícias', 'uri' => 'internal:/news', 'weight' => -4],
  ['title' => 'Editais', 'uri' => 'internal:/news-tags/edital', 'weight' => -3],
  ['title' => 'Eventos', 'uri' => 'internal:/calendar', 'weight' => -2],
  ['title' => 'Fale Conosco', 'uri' => 'internal:/node/1002', 'weight' => -1],
];

foreach ($links as $link) {
  $existentes = \Drupal::entityTypeManager()->getStorage('menu_link_content')
    ->loadByProperties(['title' => $link['title'], 'menu_name' => $menuId]);
  if (!empty($existentes)) {
    continue;
  }
  MenuLinkContent::create([
    'title' => $link['title'],
    'link' => ['uri' => $link['uri']],
    'menu_name' => $menuId,
    'weight' => $link['weight'],
    'enabled' => TRUE,
  ])->save();
  echo "Link criado: {$link['title']}\n";
}

// Coloca o bloco do menu na barra lateral, em todo o site.
$blockId = 'az_ufop_acesso_rapido';
if (Block::load($blockId)) {
  Block::load($blockId)->delete();
}
Block::create([
  'id' => $blockId,
  'theme' => 'az_barrio',
  'region' => 'sidebar_first',
  'plugin' => 'system_menu_block:' . $menuId,
  'weight' => -10,
  'settings' => [
    'id' => 'system_menu_block:' . $menuId,
    'label' => 'Acesso Rápido',
    'label_display' => 'visible',
    'provider' => 'system',
    'level' => 1,
    'depth' => 1,
    'expand_all_items' => FALSE,
  ],
  'visibility' => [],
])->save();

echo "Bloco 'Acesso Rápido' colocado em sidebar_first (site inteiro).\n";
