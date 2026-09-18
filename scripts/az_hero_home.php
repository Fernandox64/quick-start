<?php

/**
 * Adiciona um banner de destaque no topo da home, no estilo do banner
 * "Gerenciamento de Sites da UFOP" do guiadesites.ufop.br - texto em
 * negrito sobre um bloco colorido (a paragraph--type--az-text envolvida
 * pela classe .az-ufop-hero, ver css/az-ufop-tema.css).
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$hero = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => '<div class="az-ufop-hero"><h2>Departamento Modelo</h2><p>Site institucional do departamento, com notícias, editais, corpo docente e informações de contato reunidos em um só lugar.</p></div>',
    'format' => 'full_html',
  ],
]);
$hero->save();

$home = Node::load(1003);
$valores = $home->get('field_az_main_content')->getValue();
array_unshift($valores, [
  'target_id' => $hero->id(),
  'target_revision_id' => $hero->getRevisionId(),
]);
$home->set('field_az_main_content', $valores);
$home->save();

echo "Hero adicionado a home.\n";
