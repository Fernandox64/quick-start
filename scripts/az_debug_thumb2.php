<?php

use Drupal\node\Entity\Node;

$node = Node::load(958);
$medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'az_image']);
$media = reset($medias);

echo "Antes: " . print_r($node->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";
$node->set('field_az_media_thumbnail_image', ['target_id' => $media->id()]);
echo "Em memoria (antes do save): " . print_r($node->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";
$result = $node->save();
echo "Resultado do save(): " . $result . "\n";

$reloaded = Node::load(958);
echo "Apos reload: " . print_r($reloaded->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";

// Forca reset do cache de entidades, caso seja so um problema de cache estatico
\Drupal::entityTypeManager()->getStorage('node')->resetCache([958]);
$reloaded2 = Node::load(958);
echo "Apos reset de cache: " . print_r($reloaded2->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";
