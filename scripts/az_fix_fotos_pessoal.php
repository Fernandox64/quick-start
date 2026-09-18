<?php

/**
 * Corrige os 12 nodes de Pessoal que ficaram sem foto porque team/3.jpg nao
 * tinha sido copiado ainda no primeiro import de imagens.
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

$realDir = \Drupal::service('file_system')->realpath('public://imported');
$relPath = 'assets/images/team/3.jpg';

$file = File::create(['uri' => 'public://imported/' . $relPath, 'status' => 1]);
$file->save();

$media = Media::create([
  'bundle' => 'az_image',
  'name' => basename($relPath),
  'field_media_az_image' => ['target_id' => $file->id(), 'alt' => 'Foto de perfil'],
  'status' => 1,
]);
$media->save();

$nids = \Drupal::entityQuery('node')
  ->condition('type', 'az_person')
  ->notExists('field_az_media_image')
  ->accessCheck(FALSE)
  ->execute();

$storage = \Drupal::entityTypeManager()->getStorage('node');
foreach ($nids as $nid) {
  $node = $storage->load($nid);
  $node->set('field_az_media_image', ['target_id' => $media->id()]);
  $node->save();
}

echo "Corrigidos: " . count($nids) . " nodes de Pessoal.\n";
