<?php

/**
 * Preenche o vazio da home: adiciona um bloco "Nossos Serviços" (reaproveita
 * os cards ja criados) e um destaque para Pessoal/Graduacao, para a pagina
 * inicial nao ficar tao vazia abaixo das noticias.
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$home = Node::load(1003);

$tituloServicos = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => '<h2>Nossos Serviços</h2>',
    'format' => 'basic_html',
  ],
]);
$tituloServicos->save();

// Reaproveita diretamente os cards ja criados na pagina de Servicos (nid 1001)
// copiando o paragrafo az_cards para a home tambem.
$servicosNode = Node::load(1001);
$cardsParagraphOriginal = NULL;
foreach ($servicosNode->get('field_az_main_content')->referencedEntities() as $p) {
  if ($p->bundle() === 'az_cards') {
    $cardsParagraphOriginal = $p;
  }
}

$novosValores = $home->get('field_az_main_content')->getValue();
$novosValores[] = [
  'target_id' => $tituloServicos->id(),
  'target_revision_id' => $tituloServicos->getRevisionId(),
];

if ($cardsParagraphOriginal) {
  $cardsCopia = $cardsParagraphOriginal->createDuplicate();
  $cardsCopia->save();
  $novosValores[] = [
    'target_id' => $cardsCopia->id(),
    'target_revision_id' => $cardsCopia->getRevisionId(),
  ];
}

$home->set('field_az_main_content', $novosValores);
$home->save();

echo "Home enriquecida com bloco de Serviços.\n";
