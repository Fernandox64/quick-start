<?php

/**
 * Corrige as imagens de noticias: o campo certo a preencher e
 * field_az_media_image (existe um hook que deriva field_az_media_thumbnail_image
 * automaticamente a partir dele no presave - escrever direto no thumbnail e
 * sobrescrito/ignorado).
 *
 * Reaproveita as entidades de midia ja criadas na primeira tentativa
 * (localizadas pelo nome do arquivo), em vez de recriar.
 */

use Drupal\node\Entity\Node;

$dataFile = __DIR__ . '/az_import_data.json';
$data = json_decode(file_get_contents($dataFile), true);
$noticias = $data['noticias'] ?? [];

$mediaStorage = \Drupal::entityTypeManager()->getStorage('media');

function az_media_by_basename(string $relPath, $mediaStorage): ?int {
  static $cache = [];
  $nome = basename($relPath);
  if (isset($cache[$nome])) {
    return $cache[$nome];
  }
  $medias = $mediaStorage->loadByProperties(['name' => $nome]);
  $media = reset($medias);
  return $cache[$nome] = $media ? (int) $media->id() : NULL;
}

$newsNids = \Drupal::entityQuery('node')
  ->condition('type', 'az_news')
  ->sort('nid', 'ASC')
  ->accessCheck(FALSE)
  ->execute();

$storage = \Drupal::entityTypeManager()->getStorage('node');
$i = 0;
$corrigidos = 0;
$semImagem = 0;

foreach ($newsNids as $nid) {
  $item = $noticias[$i] ?? NULL;
  $i++;
  if (!$item || empty($item['imagem'])) {
    $semImagem++;
    continue;
  }
  $mediaId = az_media_by_basename($item['imagem'], $mediaStorage);
  if (!$mediaId) {
    $semImagem++;
    continue;
  }
  $node = $storage->load($nid);
  $node->set('field_az_media_image', ['target_id' => $mediaId]);
  $node->save();
  $corrigidos++;
  if ($corrigidos % 100 === 0) {
    echo "  ... {$corrigidos} corrigidas\n";
  }
}

echo "Noticias corrigidas: {$corrigidos} / sem imagem: {$semImagem}\n";
