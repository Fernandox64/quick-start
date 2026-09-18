<?php

/**
 * Remove o banner estatico "az-ufop-hero" (bloco vinho fixo) da home,
 * agora que o carrossel de noticias (bloco em content_featured) cumpre
 * esse papel com conteudo de verdade.
 */

use Drupal\node\Entity\Node;

$home = Node::load(1003);
$valores = $home->get('field_az_main_content')->getValue();

// O hero foi inserido como o primeiro item (array_unshift em az_hero_home.php).
$paragrafoStorage = \Drupal::entityTypeManager()->getStorage('paragraph');
$primeiro = $paragrafoStorage->load($valores[0]['target_id']);

$texto = $primeiro->get('field_az_text_area')->value ?? '';
if (str_contains($texto, 'az-ufop-hero')) {
  array_shift($valores);
  $home->set('field_az_main_content', $valores);
  $home->save();
  $primeiro->delete();
  echo "Hero estatico removido da home.\n";
}
else {
  echo "Primeiro paragrafo nao e o hero estatico - nada removido (confira manualmente).\n";
}
