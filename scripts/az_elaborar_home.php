<?php

/**
 * Deixa a home mais parecida com o site principal (Laravel, porta 8097):
 * banner de boas-vindas com botoes, 3 destaques (Atendimento/Servicos/
 * Equipe), bloco de "Proximos eventos" e secao "Quem somos" com imagem.
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

function az_media_de_arquivo(string $relPath, string $nome): int {
  $realDir = \Drupal::service('file_system')->realpath('public://imported');
  $file = File::create(['uri' => 'public://imported/' . $relPath, 'status' => 1]);
  $file->save();
  $media = Media::create([
    'bundle' => 'az_image',
    'name' => $nome,
    'field_media_az_image' => ['target_id' => $file->id(), 'alt' => $nome],
    'status' => 1,
  ]);
  $media->save();
  return (int) $media->id();
}

$home = Node::load(1003);
$valores = $home->get('field_az_main_content')->getValue();

// ---------------------------------------------------------------
// 1) Banner de boas-vindas com 2 botoes (igual ao site em Laravel)
// ---------------------------------------------------------------
$boasVindas = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => '<div class="az-ufop-hero az-ufop-hero-center"><h2>Bem-vindo ao Departamento Modelo</h2><p>Servindo a comunidade com excelência, transparência e compromisso.</p><div class="az-ufop-hero-actions"><a class="btn btn-light" href="/node/1000">Conheça o Departamento</a> <a class="btn btn-outline-light" href="/node/1002">Fale Conosco</a></div></div>',
    'format' => 'full_html',
  ],
]);
$boasVindas->save();

// ---------------------------------------------------------------
// 2) 3 Destaques: Atendimento / Servicos / Equipe (paragrafo az_cards)
// ---------------------------------------------------------------
$destaquesCards = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => [
    [
      'title' => 'Atendimento',
      'body' => 'Horários e canais de atendimento ao público.',
      'body_format' => 'plain_text',
      'link_title' => 'Ver contato',
      'link_uri' => 'internal:/node/1002',
    ],
    [
      'title' => 'Serviços',
      'body' => 'Principais serviços oferecidos pelo departamento.',
      'body_format' => 'plain_text',
      'link_title' => 'Ver serviços',
      'link_uri' => 'internal:/node/1001',
    ],
    [
      'title' => 'Equipe',
      'body' => 'Conheça os profissionais responsáveis por cada área.',
      'body_format' => 'plain_text',
      'link_title' => 'Ver equipe',
      'link_uri' => 'internal:/people',
    ],
  ],
]);
$destaquesCards->save();

// ---------------------------------------------------------------
// 3) Proximos eventos (view reference -> az_events / az_grid)
// ---------------------------------------------------------------
$eventosView = Paragraph::create([
  'type' => 'az_view_reference',
  'field_az_title' => 'Próximos eventos',
  'field_az_view_reference' => [
    'target_id' => 'az_events',
    'display_id' => 'az_grid',
  ],
]);
$eventosView->save();

// ---------------------------------------------------------------
// 4) "Quem somos" (az_splitscreen: imagem + texto + botao)
// ---------------------------------------------------------------
$mediaId = az_media_de_arquivo('_quemsomos.jpg', 'Quem somos');

$quemSomos = Paragraph::create([
  'type' => 'az_splitscreen',
  'field_az_media' => ['target_id' => $mediaId],
  'field_az_text_area' => [
    'value' => '<h2>Quem somos</h2><p>O Departamento Modelo atua para oferecer serviços de qualidade à comunidade, com uma equipe dedicada e comprometida com a transparência.</p><p><a class="btn btn-primary" href="/node/1000">Saiba mais</a></p>',
    'format' => 'full_html',
  ],
]);
$quemSomos->save();

// ---------------------------------------------------------------
// Monta a ordem final: boas-vindas, destaques, eventos, (resto que ja
// existia: noticias, servicos), quem somos por ultimo.
// ---------------------------------------------------------------
$novos = [
  ['target_id' => $boasVindas->id(), 'target_revision_id' => $boasVindas->getRevisionId()],
  ['target_id' => $destaquesCards->id(), 'target_revision_id' => $destaquesCards->getRevisionId()],
];
// $valores atual = [introParagraph, newsViewParagraph, tituloServicos, cardsServicos]
$novos = array_merge($novos, $valores);
$novos[] = ['target_id' => $eventosView->id(), 'target_revision_id' => $eventosView->getRevisionId()];
$novos[] = ['target_id' => $quemSomos->id(), 'target_revision_id' => $quemSomos->getRevisionId()];

$home->set('field_az_main_content', $novos);
$home->save();

echo "Home elaborada com sucesso.\n";
