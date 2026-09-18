<?php

use Drupal\node\Entity\Node;

$terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'az_curso_categoria', 'name' => 'Graduação']);
$term = reset($terms);

if (!$term) {
  echo "ERRO: termo Graduacao nao encontrado.\n";
  exit(1);
}

$node = Node::create([
  'type' => 'az_curso',
  'title' => 'Ciência da Computação (Bacharelado)',
  'field_az_curso_categoria' => ['target_id' => $term->id()],
  'field_az_curso_link' => ['uri' => 'https://decom.ufop.br', 'title' => 'Saiba mais'],
  'status' => 1,
]);
$node->save();

echo "Node criado: " . $node->id() . "\n";
echo "Tipo: " . $node->bundle() . "\n";
echo "Categoria: " . $term->label() . "\n";
