<?php

/**
 * Remove o bloco "Acesso Rapido" da barra lateral (sidebar_first), mas
 * mantem o menu e os links salvos - so tira de exibicao por enquanto.
 */

use Drupal\block\Entity\Block;

$block = Block::load('az_ufop_acesso_rapido');
if ($block) {
  $block->delete();
  echo "Bloco 'Acesso Rápido' removido da barra lateral.\n";
}
else {
  echo "Bloco ja nao existia.\n";
}
