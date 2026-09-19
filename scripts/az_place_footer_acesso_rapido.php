<?php

/**
 * Coloca o bloco de "Acesso Rapido" (mesmos links do menu principal) +
 * "Siga-nos" (redes sociais) na regiao "footer" do tema, em todo o site.
 */

use Drupal\block\Entity\Block;

$id = 'az_ufop_footer_acesso_rapido';

if (Block::load($id)) {
  Block::load($id)->delete();
}

$block = Block::create([
  'id' => $id,
  'theme' => 'az_barrio',
  'region' => 'footer',
  'plugin' => 'az_ufop_footer_acesso_rapido',
  'weight' => 0,
  'settings' => [
    'id' => 'az_ufop_footer_acesso_rapido',
    'label' => 'Acesso Rápido',
    'label_display' => '0',
    'provider' => 'az_ufop_departamento',
  ],
  'visibility' => [],
]);
$block->save();

echo "Bloco 'Acesso Rápido + Redes Sociais' colocado no rodapé (site inteiro).\n";
