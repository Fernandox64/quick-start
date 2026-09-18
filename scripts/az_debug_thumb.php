<?php

use Drupal\node\Entity\Node;

$node = Node::load(3);
echo "Node 3 tipo: " . $node->bundle() . "\n";
echo "Tem campo field_az_media_thumbnail_image? " . ($node->hasField('field_az_media_thumbnail_image') ? 'sim' : 'nao') . "\n";

// Pega qualquer media az_image ja existente para testar
$medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'az_image']);
$media = reset($medias);
echo "Media de teste: " . $media->id() . "\n";

$node->set('field_az_media_thumbnail_image', ['target_id' => $media->id()]);
$violations = $node->validate();
echo "Violacoes de validacao: " . count($violations) . "\n";
foreach ($violations as $v) {
  echo " - " . $v->getMessage() . " (" . $v->getPropertyPath() . ")\n";
}

$node->save();

$reloaded = Node::load(3);
$val = $reloaded->get('field_az_media_thumbnail_image')->getValue();
echo "Valor apos reload: " . print_r($val, true) . "\n";
