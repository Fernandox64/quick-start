<?php

use Drupal\node\Entity\Node;

$node = Node::load(9);
$medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'az_image']);
$media = reset($medias);

// So mexe no field_az_media_image, NAO no thumbnail.
$node->set('field_az_media_image', ['target_id' => $media->id()]);
$node->save();

$reloaded = Node::load(9);
echo "field_az_media_image: " . print_r($reloaded->get('field_az_media_image')->getValue(), true) . "\n";
echo "field_az_media_thumbnail_image (deveria estar vazio se nao houver auto-copy): " . print_r($reloaded->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";

// Procura hooks/modulos customizados que mexem em field_az_media_thumbnail
$moduleHandler = \Drupal::moduleHandler();
foreach (['node_presave', 'ENTITY_TYPE_presave', 'node_insert'] as $hookBase) {
  // apenas placeholder de investigacao, nao executavel diretamente
}
