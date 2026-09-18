<?php

/**
 * Substitui a foto compartilhada (3 fotos placeholder reaproveitadas entre
 * 36 pessoas) por um avatar com iniciais unico por pessoa, gerado em
 * sites/default/files/imported/avatars/avatar_N.png (N = ordem no JSON,
 * mesma ordem de criacao dos nodes az_person).
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

$nids = \Drupal::entityQuery('node')
  ->condition('type', 'az_person')
  ->sort('nid', 'ASC')
  ->accessCheck(FALSE)
  ->execute();

$storage = \Drupal::entityTypeManager()->getStorage('node');
$i = 0;
$trocados = 0;

foreach ($nids as $nid) {
  $i++;
  $uri = "public://imported/avatars/avatar_{$i}.png";
  $realpath = \Drupal::service('file_system')->realpath($uri);
  if (!is_file($realpath)) {
    continue;
  }

  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();

  $media = Media::create([
    'bundle' => 'az_image',
    'name' => "avatar_{$i}.png",
    'field_media_az_image' => ['target_id' => $file->id(), 'alt' => 'Foto de perfil'],
    'status' => 1,
  ]);
  $media->save();

  $node = $storage->load($nid);
  $node->set('field_az_media_image', ['target_id' => $media->id()]);
  $node->save();
  $trocados++;
}

echo "Avatares individuais aplicados: {$trocados}\n";
