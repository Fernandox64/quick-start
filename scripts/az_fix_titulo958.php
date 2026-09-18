<?php

use Drupal\node\Entity\Node;

$node = Node::load(958);
$node->setTitle('Abertas as inscrições para o curso de extensão "Cálculo Zero"');
$node->save();

echo "Título restaurado: " . $node->getTitle() . "\n";
