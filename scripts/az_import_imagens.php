<?php

/**
 * Cria entidades de midia (az_image) para as imagens copiadas em
 * sites/default/files/imported/ e associa aos nodes az_news (thumbnail) e
 * az_person (foto), na mesma ordem em que os nodes foram criados pelo
 * az_import_conteudo.php (por nid crescente == ordem do JSON original).
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

$dataFile = __DIR__ . '/az_import_data.json';
$data = json_decode(file_get_contents($dataFile), true);

$importedDir = 'public://imported';
$realDir = \Drupal::service('file_system')->realpath($importedDir);

function az_media_from_path(string $relPath, string $realDir, string $importedDir): ?int {
  static $cache = [];
  if (isset($cache[$relPath])) {
    return $cache[$relPath];
  }

  $realFile = $realDir . '/' . $relPath;
  if (!is_file($realFile)) {
    return $cache[$relPath] = NULL;
  }

  $uri = $importedDir . '/' . $relPath;
  $file = File::create([
    'uri' => $uri,
    'status' => 1,
  ]);
  $file->save();

  $media = Media::create([
    'bundle' => 'az_image',
    'name' => basename($relPath),
    'field_media_az_image' => [
      'target_id' => $file->id(),
      'alt' => basename($relPath),
    ],
    'status' => 1,
  ]);
  $media->save();

  return $cache[$relPath] = (int) $media->id();
}

// ---------------------------------------------------------------
// Noticias -> field_az_media_thumbnail_image (mesma ordem de criacao: nid asc)
// ---------------------------------------------------------------
$newsNids = \Drupal::entityQuery('node')
  ->condition('type', 'az_news')
  ->sort('nid', 'ASC')
  ->accessCheck(FALSE)
  ->execute();

$noticias = $data['noticias'] ?? [];
$storage = \Drupal::entityTypeManager()->getStorage('node');

$i = 0;
$comImagem = 0;
$semImagem = 0;
foreach ($newsNids as $nid) {
  $item = $noticias[$i] ?? NULL;
  $i++;
  if (!$item || empty($item['imagem'])) {
    $semImagem++;
    continue;
  }
  $mediaId = az_media_from_path($item['imagem'], $realDir, $importedDir);
  if (!$mediaId) {
    $semImagem++;
    continue;
  }
  $node = $storage->load($nid);
  $node->set('field_az_media_thumbnail_image', ['target_id' => $mediaId]);
  $node->save();
  $comImagem++;
  if ($comImagem % 100 === 0) {
    echo "  ... {$comImagem} noticias com imagem associada\n";
  }
}
echo "Noticias com imagem: {$comImagem} / sem imagem: {$semImagem}\n";

// ---------------------------------------------------------------
// Pessoal -> field_az_media_image
// ---------------------------------------------------------------
$personNids = \Drupal::entityQuery('node')
  ->condition('type', 'az_person')
  ->sort('nid', 'ASC')
  ->accessCheck(FALSE)
  ->execute();

$pessoal = $data['pessoal'] ?? [];
$i = 0;
$comFoto = 0;
foreach ($personNids as $nid) {
  $item = $pessoal[$i] ?? NULL;
  $i++;
  if (!$item || empty($item['foto'])) {
    continue;
  }
  $mediaId = az_media_from_path($item['foto'], $realDir, $importedDir);
  if (!$mediaId) {
    continue;
  }
  $node = $storage->load($nid);
  $node->set('field_az_media_image', ['target_id' => $mediaId]);
  $node->save();
  $comFoto++;
}
echo "Pessoal com foto: {$comFoto}\n";

echo "\nImportação de imagens concluída.\n";
