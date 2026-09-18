<?php

/**
 * Reconstroi a home do DFIS na mesma estrutura do site principal: banner
 * de boas-vindas, 3 destaques, ultimas noticias, "Nossos Servicos" com 3
 * cards, proximos eventos e "Quem somos". Substitui a home simples criada
 * antes (so o banner).
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

function az3_media_de_arquivo(string $uri, string $nome): int {
  $file = File::create(['uri' => $uri, 'status' => 1]);
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

$nome_depto = 'Departamento de Física';

$boasVindas = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => "<div class=\"az-ufop-hero az-ufop-hero-center\"><h2>Bem-vindo ao $nome_depto</h2><p>Site de demonstração de multisite - ensino, pesquisa e extensão em física.</p><div class=\"az-ufop-hero-actions\"><a class=\"btn btn-light\" href=\"/news\">Ver notícias</a> <a class=\"btn btn-outline-light\" href=\"/people\">Conheça a equipe</a></div></div>",
    'format' => 'full_html',
  ],
]);
$boasVindas->save();

$destaquesCards = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => [
    ['title' => 'Atendimento', 'body' => 'Horários e canais de atendimento ao público.', 'body_format' => 'plain_text', 'link_title' => 'Ver notícias', 'link_uri' => 'internal:/news'],
    ['title' => 'Serviços', 'body' => 'Principais serviços oferecidos pelo departamento.', 'body_format' => 'plain_text', 'link_title' => 'Ver mais', 'link_uri' => 'internal:/news'],
    ['title' => 'Equipe', 'body' => 'Conheça os professores responsáveis por cada área.', 'body_format' => 'plain_text', 'link_title' => 'Ver equipe', 'link_uri' => 'internal:/people'],
  ],
]);
$destaquesCards->save();

$introNoticias = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => ['value' => '<p>Acompanhe as últimas novidades do departamento.</p>', 'format' => 'full_html'],
]);
$introNoticias->save();

$noticiasView = Paragraph::create([
  'type' => 'az_view_reference',
  'field_az_title' => 'Últimas notícias',
  'field_az_view_reference' => ['target_id' => 'az_news', 'display_id' => 'az_grid'],
]);
$noticiasView->save();

$tituloServicos = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => ['value' => '<h2>Nossos Serviços</h2>', 'format' => 'full_html'],
]);
$tituloServicos->save();

$cardsServicos = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => [
    ['title' => 'Iniciação Científica', 'body' => 'Editais e orientação para alunos interessados em pesquisa.', 'body_format' => 'plain_text'],
    ['title' => 'Monitoria', 'body' => 'Apoio acadêmico oferecido por alunos monitores das disciplinas.', 'body_format' => 'plain_text'],
    ['title' => 'Laboratórios', 'body' => 'Infraestrutura de laboratórios para ensino e pesquisa experimental.', 'body_format' => 'plain_text'],
  ],
]);
$cardsServicos->save();

$eventosView = Paragraph::create([
  'type' => 'az_view_reference',
  'field_az_title' => 'Próximos eventos',
  'field_az_view_reference' => ['target_id' => 'az_events', 'display_id' => 'az_grid'],
]);
$eventosView->save();

// Reaproveita um dos banners gerados como imagem do "Quem somos".
$mediaId = az3_media_de_arquivo('public://imported/dfis-noticias/banner_1.jpg', 'Quem somos');
$quemSomos = Paragraph::create([
  'type' => 'az_splitscreen',
  'field_az_media' => ['target_id' => $mediaId],
  'field_az_text_area' => [
    'value' => "<h2>Quem somos</h2><p>O $nome_depto atua no ensino, pesquisa e extensão, com uma equipe dedicada e comprometida com a excelência acadêmica. Site de demonstração (multisite Arizona Quickstart).</p>",
    'format' => 'full_html',
  ],
]);
$quemSomos->save();

$paragrafos = [$boasVindas, $destaquesCards, $introNoticias, $noticiasView, $tituloServicos, $cardsServicos, $eventosView, $quemSomos];
$valores = array_map(fn($p) => ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()], $paragrafos);

// A home simples criada antes (so o hero) - acha pelo titulo pra substituir.
$nids = \Drupal::entityQuery('node')->condition('type', 'az_flexible_page')->condition('title', 'Início - ' . $nome_depto)->accessCheck(FALSE)->execute();
$nid = reset($nids);

if ($nid) {
  $home = Node::load($nid);
  $home->set('field_az_main_content', $valores);
  $home->save();
  echo "Home existente (nid=$nid) atualizada com a estrutura completa.\n";
}
else {
  $home = Node::create([
    'type' => 'az_flexible_page',
    'title' => "Início - $nome_depto",
    'field_az_main_content' => $valores,
    'status' => 1,
  ]);
  $home->save();
  \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $home->id())->save();
  echo "Home criada (nid={$home->id()}) com a estrutura completa.\n";
}
