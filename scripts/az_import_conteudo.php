<?php

/**
 * Importa o conteudo real do site Laravel (porta 8097) para dentro do
 * az_quickstart: Noticias/Editais -> az_news, Eventos -> az_event,
 * Pessoal -> az_person, Cursos -> az_curso (modulo az_ufop_departamento),
 * Sobre -> az_flexible_page.
 *
 * Roda uma vez via: drush scr scripts/az_import_conteudo.php
 *
 * Nao migra imagens/anexos (fica pra uma segunda etapa se for preciso) -
 * o foco aqui e paridade de conteudo (texto, datas, categorizacao).
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

$dataFile = __DIR__ . '/az_import_data.json';
$data = json_decode(file_get_contents($dataFile), true);

function az_import_term_id(string $vid, string $name): ?int {
  static $cache = [];
  $key = $vid . '|' . $name;
  if (isset($cache[$key])) {
    return $cache[$key];
  }
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => $vid, 'name' => $name]);
  $term = reset($terms);
  return $cache[$key] = $term ? (int) $term->id() : NULL;
}

function az_import_html(string $texto): string {
  // Textos vem como texto puro com \n\n entre paragrafos; converte para <p>.
  $paragrafos = preg_split('/\n{2,}/', trim($texto));
  $paragrafos = array_filter(array_map('trim', $paragrafos));
  return implode('', array_map(fn ($p) => '<p>' . nl2br(htmlspecialchars($p)) . '</p>', $paragrafos));
}

// ---------------------------------------------------------------
// 1) Nome do site
// ---------------------------------------------------------------
if (!empty($data['site_name'])) {
  \Drupal::configFactory()->getEditable('system.site')
    ->set('name', $data['site_name'] . ' (via Laravel)')
    ->save();
  echo "Nome do site atualizado: {$data['site_name']} (via Laravel)\n";
}

// ---------------------------------------------------------------
// 2) Noticias / Editais -> az_news
// ---------------------------------------------------------------
$idTagNoticia = az_import_term_id('az_news_tags', 'Notícia');
$idTagEdital = az_import_term_id('az_news_tags', 'Edital');

$totalNoticias = 0;
foreach ($data['noticias'] ?? [] as $item) {
  $tipo = $item['tipo'] ?? 'noticia';
  $tagId = $tipo === 'edital' ? $idTagEdital : $idTagNoticia;

  $node = Node::create([
    'type' => 'az_news',
    'title' => mb_substr($item['titulo'] ?? 'Sem título', 0, 255),
    'created' => strtotime($item['data_publicacao'] ?? 'now') ?: time(),
    'field_az_summary' => [
      'value' => $item['resumo'] ?? '',
      'format' => 'plain_text',
    ],
    'field_az_body' => [
      'value' => az_import_html($item['conteudo'] ?? ''),
      'format' => 'basic_html',
    ],
    'field_az_news_tags' => $tagId ? [['target_id' => $tagId]] : [],
    'status' => 1,
  ]);
  $node->save();
  // Node::save() com 'created' explicito no create() as vezes e sobrescrito
  // pelo hook padrao; forca de novo e salva sem gerar nova revisao.
  $node->setCreatedTime(strtotime($item['data_publicacao'] ?? 'now') ?: time());
  $node->save();

  $totalNoticias++;
  if ($totalNoticias % 100 === 0) {
    echo "  ... {$totalNoticias} noticias importadas\n";
  }
}
echo "Noticias/Editais importados: {$totalNoticias}\n";

// ---------------------------------------------------------------
// 3) Eventos -> az_event
// ---------------------------------------------------------------
$totalEventos = 0;
foreach ($data['eventos'] ?? [] as $item) {
  $inicio = strtotime(($item['data_evento'] ?? 'now') . ' 09:00:00') ?: time();
  $fim = $inicio + 3600;

  $node = Node::create([
    'type' => 'az_event',
    'title' => mb_substr($item['titulo'] ?? 'Evento', 0, 255),
    'field_az_summary' => [
      'value' => mb_substr($item['descricao'] ?? '', 0, 255),
      'format' => 'plain_text',
    ],
    'field_az_body' => [
      'value' => az_import_html($item['descricao'] ?? ''),
      'format' => 'basic_html',
    ],
    'field_az_location' => $item['local'] ?? '',
    'field_az_event_date' => [
      'value' => $inicio,
      'end_value' => $fim,
      'duration' => 60,
      'timezone' => 'America/Sao_Paulo',
    ],
    'status' => 1,
  ]);
  if (!empty($item['link'])) {
    $node->set('field_az_link', ['uri' => $item['link'], 'title' => 'Mais informações']);
  }
  $node->save();
  $totalEventos++;
}
echo "Eventos importados: {$totalEventos}\n";

// ---------------------------------------------------------------
// 4) Pessoal -> az_person
// ---------------------------------------------------------------
$idCatDocente = az_import_term_id('az_person_categories', 'Docente');
$idCatFuncionario = az_import_term_id('az_person_categories', 'Funcionário');

$totalPessoal = 0;
foreach ($data['pessoal'] ?? [] as $item) {
  $nomeCompleto = trim($item['nome'] ?? '');
  $partes = explode(' ', $nomeCompleto, 2);
  $fname = $partes[0] ?? $nomeCompleto;
  $lname = $partes[1] ?? '';
  $categoria = ($item['categoria'] ?? 'docente') === 'funcionario' ? $idCatFuncionario : $idCatDocente;

  $node = Node::create([
    'type' => 'az_person',
    'title' => $nomeCompleto !== '' ? $nomeCompleto : 'Sem nome',
    'field_az_fname' => $fname,
    'field_az_lname' => $lname,
    'field_az_titles' => $item['cargo'] ?? '',
    'field_az_person_category' => $categoria ? [['target_id' => $categoria]] : [],
    'status' => 1,
  ]);
  $node->save();
  $totalPessoal++;
}
echo "Pessoal importado: {$totalPessoal}\n";

// ---------------------------------------------------------------
// 5) Graduacao / Pos-Graduacao -> az_curso (modulo az_ufop_departamento)
// ---------------------------------------------------------------
$idCatGrad = az_import_term_id('az_curso_categoria', 'Graduação');
$idCatPos = az_import_term_id('az_curso_categoria', 'Pós-Graduação');

$totalCursos = 0;
foreach (($data['graduacao']['cursos'] ?? []) as $curso) {
  $node = Node::create([
    'type' => 'az_curso',
    'title' => $curso['nome'] ?? 'Curso',
    'field_az_curso_categoria' => $idCatGrad ? ['target_id' => $idCatGrad] : NULL,
  ]);
  if (!empty($curso['link'])) {
    $node->set('field_az_curso_link', ['uri' => $curso['link'], 'title' => 'Saiba mais']);
  }
  $node->save();
  $totalCursos++;
}
foreach (($data['pos_graduacao']['cursos'] ?? []) as $curso) {
  $node = Node::create([
    'type' => 'az_curso',
    'title' => $curso['nome'] ?? 'Curso',
    'field_az_curso_categoria' => $idCatPos ? ['target_id' => $idCatPos] : NULL,
  ]);
  if (!empty($curso['link'])) {
    $node->set('field_az_curso_link', ['uri' => $curso['link'], 'title' => 'Saiba mais']);
  }
  $node->save();
  $totalCursos++;
}
echo "Cursos importados: {$totalCursos}\n";

// ---------------------------------------------------------------
// 6) Sobre -> az_flexible_page
// ---------------------------------------------------------------
if (!empty($data['sobre'])) {
  $sobre = $data['sobre'];
  $texto = trim(($sobre['texto_intro'] ?? '') . "\n\n" . ($sobre['texto_corpo'] ?? ''));

  $paragraph = Paragraph::create([
    'type' => 'az_text',
    'field_az_text_area' => [
      'value' => az_import_html($texto),
      'format' => 'basic_html',
    ],
  ]);
  $paragraph->save();

  $node = Node::create([
    'type' => 'az_flexible_page',
    'title' => $sobre['titulo'] ?? 'Sobre o Departamento',
    'field_az_summary' => [
      'value' => mb_substr($sobre['texto_intro'] ?? '', 0, 255),
      'format' => 'plain_text',
    ],
    'field_az_main_content' => [[
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ]],
    'status' => 1,
  ]);
  $node->save();
  echo "Página Sobre criada (nid {$node->id()})\n";
}

echo "\nImportação concluída.\n";
