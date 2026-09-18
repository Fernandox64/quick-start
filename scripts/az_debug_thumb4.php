<?php

use Drupal\node\Entity\Node;

// Node ainda intocado (nao usado nos testes anteriores)
$node = Node::load(6);
echo "Antes: " . print_r($node->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";

$medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'az_image']);
$media = reset($medias);

$node->set('field_az_media_thumbnail_image', ['target_id' => $media->id()]);
$node->save();
$node->save(); // segunda chamada, mesma instancia

$reloaded = Node::load(6);
echo "Apos 2 saves seguidos (mesma instancia): " . print_r($reloaded->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";

echo "Moderation state: " . ($node->hasField('moderation_state') ? $node->moderation_state->value : 'sem campo') . "\n";
echo "Default revision? " . ($node->isDefaultRevision() ? 'sim' : 'nao') . "\n";
echo "isPublished? " . ($node->isPublished() ? 'sim' : 'nao') . "\n";
