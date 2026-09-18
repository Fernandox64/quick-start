<?php

/**
 * Completa o site: paginas de Contato e Servicos (que faltaram na primeira
 * importacao), uma pagina inicial de verdade (com destaque de noticias
 * recentes) e o menu principal com links para todas as secoes.
 *
 * Roda via: drush scr scripts/az_finalizar_site.php
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\system\Entity\Menu;
use Drupal\menu_link_content\Entity\MenuLinkContent;

$dataFile = __DIR__ . '/az_import_data.json';
$data = json_decode(file_get_contents($dataFile), true);

function az_html(string $texto): string {
  $paragrafos = preg_split('/\n{2,}/', trim($texto));
  $paragrafos = array_filter(array_map('trim', $paragrafos));
  return implode('', array_map(fn ($p) => '<p>' . nl2br(htmlspecialchars($p)) . '</p>', $paragrafos));
}

// ---------------------------------------------------------------
// 1) Pagina de Servicos (az_flexible_page + paragrafo az_cards)
// ---------------------------------------------------------------
$servicos = $data['servicos'] ?? ['titulo' => 'Serviços', 'itens' => []];

$cardValues = [];
foreach ($servicos['itens'] ?? [] as $item) {
  $cardValues[] = [
    'title' => $item['titulo'] ?? '',
    'body' => $item['texto'] ?? '',
    'body_format' => 'plain_text',
  ];
}
$cardsParagraph = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => $cardValues,
]);
$cardsParagraph->save();

$servicosNode = Node::create([
  'type' => 'az_flexible_page',
  'title' => $servicos['titulo'] ?? 'Serviços',
  'field_az_summary' => [
    'value' => mb_substr($servicos['introducao'] ?? '', 0, 255),
    'format' => 'plain_text',
  ],
  'field_az_main_content' => [[
    'target_id' => $cardsParagraph->id(),
    'target_revision_id' => $cardsParagraph->getRevisionId(),
  ]],
  'status' => 1,
]);
$servicosNode->save();
echo "Página Serviços criada (nid {$servicosNode->id()})\n";

// ---------------------------------------------------------------
// 2) Pagina de Contato (az_flexible_page + paragrafo az_contact + texto)
// ---------------------------------------------------------------
$contato = $data['contato'] ?? [
  'titulo' => 'Contato',
  'endereco' => '',
  'telefone_1' => '',
  'email_1' => '',
];

$contactParagraph = Paragraph::create([
  'type' => 'az_contact',
  'field_az_title' => $contato['titulo'] ?? 'Fale conosco',
  'field_az_email' => $contato['email_1'] ?? '',
  'field_az_phone' => $contato['telefone_1'] ?? '',
]);
$contactParagraph->save();

$enderecoTexto = trim(($contato['endereco'] ?? '') . ($contato['telefone_2'] ? "\nTelefone: " . $contato['telefone_2'] : ''));
$enderecoParagraph = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => az_html($enderecoTexto),
    'format' => 'basic_html',
  ],
]);
$enderecoParagraph->save();

$contatoNode = Node::create([
  'type' => 'az_flexible_page',
  'title' => $contato['titulo'] ?? 'Contato',
  'field_az_main_content' => [
    [
      'target_id' => $enderecoParagraph->id(),
      'target_revision_id' => $enderecoParagraph->getRevisionId(),
    ],
    [
      'target_id' => $contactParagraph->id(),
      'target_revision_id' => $contactParagraph->getRevisionId(),
    ],
  ],
  'status' => 1,
]);
$contatoNode->save();
echo "Página Contato criada (nid {$contatoNode->id()})\n";

// ---------------------------------------------------------------
// 3) Pagina inicial de verdade: texto de boas-vindas + grade de noticias
//    recentes (usa o mecanismo nativo "View Reference" do az_quickstart).
// ---------------------------------------------------------------
$welcomeParagraph = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => az_html(($data['sobre']['texto_intro'] ?? 'Bem-vindo ao site do departamento.')),
    'format' => 'basic_html',
  ],
]);
$welcomeParagraph->save();

$newsViewParagraph = Paragraph::create([
  'type' => 'az_view_reference',
  'field_az_title' => 'Últimas notícias',
  'field_az_view_reference' => [
    'target_id' => 'az_news',
    'display_id' => 'az_grid',
  ],
]);
$newsViewParagraph->save();

$homeNode = Node::create([
  'type' => 'az_flexible_page',
  'title' => 'Início',
  'field_az_main_content' => [
    [
      'target_id' => $welcomeParagraph->id(),
      'target_revision_id' => $welcomeParagraph->getRevisionId(),
    ],
    [
      'target_id' => $newsViewParagraph->id(),
      'target_revision_id' => $newsViewParagraph->getRevisionId(),
    ],
  ],
  'status' => 1,
]);
$homeNode->save();
echo "Página inicial criada (nid {$homeNode->id()})\n";

// Define como pagina inicial do site.
\Drupal::configFactory()->getEditable('system.site')
  ->set('page.front', '/node/' . $homeNode->id())
  ->save();
echo "Pagina inicial do site definida para /node/{$homeNode->id()}\n";

// ---------------------------------------------------------------
// 4) Menu principal
// ---------------------------------------------------------------
$links = [
  ['title' => 'Início', 'uri' => 'internal:/node/' . $homeNode->id(), 'weight' => -10],
  ['title' => 'Sobre', 'uri' => 'internal:/node/1000', 'weight' => -9],
  ['title' => 'Notícias', 'uri' => 'internal:/news', 'weight' => -8],
  ['title' => 'Eventos', 'uri' => 'internal:/events', 'weight' => -7],
  ['title' => 'Graduação', 'uri' => 'internal:/categoria-de-curso/graduacao', 'weight' => -6],
  ['title' => 'Pós-Graduação', 'uri' => 'internal:/categoria-de-curso/pos-graduacao', 'weight' => -5],
  ['title' => 'Pessoal', 'uri' => 'internal:/people', 'weight' => -4],
  ['title' => 'Serviços', 'uri' => 'internal:/node/' . $servicosNode->id(), 'weight' => -3],
  ['title' => 'Contato', 'uri' => 'internal:/node/' . $contatoNode->id(), 'weight' => -2],
];

foreach ($links as $link) {
  $existing = \Drupal::entityTypeManager()->getStorage('menu_link_content')
    ->loadByProperties(['title' => $link['title'], 'menu_name' => 'main']);
  if (!empty($existing)) {
    continue;
  }
  MenuLinkContent::create([
    'title' => $link['title'],
    'link' => ['uri' => $link['uri']],
    'menu_name' => 'main',
    'weight' => $link['weight'],
    'enabled' => TRUE,
  ])->save();
  echo "Item de menu criado: {$link['title']}\n";
}

echo "\nSite finalizado.\n";
