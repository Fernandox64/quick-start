<?php

use Drupal\node\Entity\Node;

$node = Node::load(958);

// Testa um campo simples (nao entity reference) primeiro.
$node->setTitle('TESTE TITULO MODIFICADO');
$node->save();
$reloaded = Node::load(958);
echo "Titulo apos save: " . $reloaded->getTitle() . "\n";

// Agora tenta a mesma coisa com field_az_media_image (campo que FUNCIONOU
// em Pessoal) neste node de noticia, para ver se o campo em si funciona
// aqui, so nao no _thumbnail_.
$medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'az_image']);
$media = reset($medias);

$reloaded->field_az_media_image->target_id = $media->id();
$reloaded->save();
$reloaded2 = Node::load(958);
echo "field_az_media_image apos save: " . print_r($reloaded2->get('field_az_media_image')->getValue(), true) . "\n";

$reloaded2->field_az_media_thumbnail_image->target_id = $media->id();
$saveResult = $reloaded2->save();
echo "Save result: $saveResult\n";
$reloaded3 = Node::load(958);
echo "field_az_media_thumbnail_image apos save (atribuicao direta): " . print_r($reloaded3->get('field_az_media_thumbnail_image')->getValue(), true) . "\n";
