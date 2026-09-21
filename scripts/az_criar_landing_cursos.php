<?php

/**
 * Retrofit para sites já provisionados: cria uma landing page de verdade
 * pra "Graduação" e "Pós-Graduação" (texto explicando o programa + lista
 * dos cursos/programas existentes) e aponta o menu principal pra elas -
 * antes o menu levava direto pra página padrão de taxonomia do Drupal,
 * uma listagem genérica sem nenhum texto explicativo.
 *
 * Reutilizável: roda em QUALQUER site do multisite via
 *   drush --uri=http://dominio:porta scr scripts/az_criar_landing_cursos.php
 * Seguro rodar mais de uma vez - só cria a página se ainda não existir, e
 * sempre reaponta o menu pro nó certo (idempotente).
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\menu_link_content\Entity\MenuLinkContent;

$site_path = \Drupal::service('kernel')->getSitePath();
$site_dir = basename($site_path);

$DEPARTAMENTOS = [
  'dfis' => ['nome' => 'Departamento de Física', 'area' => 'física'],
  'demat' => ['nome' => 'Departamento de Matemática', 'area' => 'matemática'],
  'demed' => ['nome' => 'Departamento de Medicina', 'area' => 'medicina'],
  'defil' => ['nome' => 'Departamento de Filosofia', 'area' => 'filosofia'],
  'delet' => ['nome' => 'Departamento de Letras', 'area' => 'letras'],
  'depro' => ['nome' => 'Departamento de Engenharia de Produção', 'area' => 'engenharia de produção'],
];
$dep = $DEPARTAMENTOS[$site_dir] ?? ['nome' => \Drupal::config('system.site')->get('name'), 'area' => 'sua área de atuação'];
$nome = $dep['nome'];
$area = $dep['area'];

echo "=== Landing pages de Graduação/Pós-Graduação para '$site_dir' ($nome) ===\n";

function az_lc_pagina_existe(string $titulo): ?int {
  $nids = \Drupal::entityQuery('node')->condition('type', 'az_flexible_page')->condition('title', $titulo)->accessCheck(FALSE)->execute();
  return $nids ? (int) reset($nids) : NULL;
}

function az_lc_termo(string $vid, string $nome): ?Term {
  $termos = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $nome]);
  return $termos ? reset($termos) : NULL;
}

function az_lc_cursos_por_categoria(int $tid): array {
  $nids = \Drupal::entityQuery('node')->condition('type', 'az_curso')->condition('field_az_curso_categoria', $tid)->accessCheck(FALSE)->execute();
  $titulos = [];
  foreach ($nids as $nid) {
    $n = Node::load($nid);
    if ($n) {
      $titulos[] = $n->getTitle();
    }
  }
  return $titulos;
}

// Explicação curta (1-2 frases, pro corpo do card) de cada modalidade -
// detecta pelo nome do curso, já que o texto em si é o mesmo tipo de
// modalidade acadêmica em qualquer área/departamento.
function az_lc_texto_modalidade(string $titulo_curso, string $area): string {
  $t = mb_strtolower($titulo_curso);
  if (str_contains($t, 'bacharelado')) {
    return "Formação com ênfase em pesquisa científica e atuação técnica/profissional em $area, em cerca de 4 anos. Não habilita para a docência na educação básica.";
  }
  if (str_contains($t, 'licenciatura')) {
    return "Forma professores para atuar na educação básica, com formação pedagógica, estágio supervisionado e o conteúdo específico de $area, em cerca de 4 anos.";
  }
  if (str_contains($t, 'doutorado')) {
    return "Forma pesquisadores capazes de conduzir investigação científica original e autônoma em $area, com defesa pública de tese em cerca de 4 anos.";
  }
  if (str_contains($t, 'mestrado')) {
    return "Formação em pesquisa aplicada em $area, com defesa pública de dissertação em cerca de 2 anos.";
  }
  return "Modalidade oferecida em $area.";
}

function az_lc_pagina_curso(string $titulo, string $intro_html, array $cursos_titulos, string $area): int {
  $nid = az_lc_pagina_existe($titulo);
  if ($nid) {
    return $nid;
  }
  $paragrafos = [];
  $intro = Paragraph::create(['type' => 'az_text', 'field_az_text_area' => ['value' => $intro_html, 'format' => 'full_html']]);
  $intro->save();
  $paragrafos[] = $intro;
  if ($cursos_titulos) {
    $cards = array_map(fn($t) => ['title' => $t, 'body' => az_lc_texto_modalidade($t, $area), 'body_format' => 'plain_text'], $cursos_titulos);
    $cardsParagraph = Paragraph::create(['type' => 'az_cards', 'field_az_title' => '', 'field_az_cards' => $cards]);
    $cardsParagraph->save();
    $paragrafos[] = $cardsParagraph;
  }
  $valores = array_map(fn($p) => ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()], $paragrafos);
  $pagina = Node::create(['type' => 'az_flexible_page', 'title' => $titulo, 'field_az_main_content' => $valores, 'status' => 1]);
  $pagina->save();
  $nid = (int) $pagina->id();
  echo "Página '$titulo' criada (nid=$nid).\n";
  return $nid;
}

// Se a página já existe (ex.: rodada anterior deste mesmo script, antes
// deste texto explicativo existir), preenche o corpo dos cards que ainda
// estiverem vazios - não recria a página nem mexe no que já tem texto.
function az_lc_preencher_texto_cards(int $nid, string $area): void {
  $pagina = Node::load($nid);
  if (!$pagina) {
    return;
  }
  $mudou_algum = FALSE;
  foreach ($pagina->get('field_az_main_content') as $item) {
    $paragraph = $item->entity;
    if (!$paragraph || $paragraph->bundle() !== 'az_cards') {
      continue;
    }
    $itens = $paragraph->get('field_az_cards')->getValue();
    $mudou = FALSE;
    foreach ($itens as &$c) {
      if (empty($c['body'])) {
        $c['body'] = az_lc_texto_modalidade($c['title'], $area);
        $mudou = TRUE;
      }
    }
    unset($c);
    if ($mudou) {
      $paragraph->set('field_az_cards', $itens);
      $paragraph->save();
      $mudou_algum = TRUE;
    }
  }
  if ($mudou_algum) {
    echo "Texto explicativo preenchido nos cards de '" . $pagina->getTitle() . "'.\n";
  }
}

// Se o site principal ainda tiver os cursos com nome placeholder (nunca
// customizados), troca por um nome ao menos genérico e decente.
$renomear_generico = [
  'Nome do curso (Bacharelado)' => 'Bacharelado',
  'Nome do curso (Licenciatura)' => 'Licenciatura',
  'Nome do programa (Mestrado)' => 'Mestrado',
  'Nome do programa (Doutorado)' => 'Doutorado',
];
foreach ($renomear_generico as $antigo => $novo) {
  $nids = \Drupal::entityQuery('node')->condition('type', 'az_curso')->condition('title', $antigo)->accessCheck(FALSE)->execute();
  foreach ($nids as $nid) {
    $n = Node::load($nid);
    $n->setTitle($novo);
    $n->save();
    echo "Curso renomeado: '$antigo' -> '$novo'.\n";
  }
}

$tGrad = az_lc_termo('az_curso_categoria', 'Graduação');
$tPos = az_lc_termo('az_curso_categoria', 'Pós-Graduação');

$nidGraduacao = $tGrad ? az_lc_pagina_curso(
  'Graduação',
  "<p>O $nome oferece curso(s) de graduação em $area, com um currículo estruturado para formar profissionais capacitados tanto para o mercado de trabalho quanto para a pesquisa científica.</p><h2>Por que estudar aqui</h2><ul><li>Corpo docente qualificado, com professores atuantes em pesquisa e extensão.</li><li>Infraestrutura de laboratórios e bibliotecas para apoio ao ensino.</li><li>Oportunidades de iniciação científica e monitoria já durante a graduação.</li></ul><h2>Nossos cursos</h2>",
  az_lc_cursos_por_categoria($tGrad->id()),
  $area
) : NULL;
$nidPosGraduacao = $tPos ? az_lc_pagina_curso(
  'Pós-Graduação',
  "<p>O programa de pós-graduação do $nome forma pesquisadores e profissionais de alto nível em $area, com linhas de pesquisa consolidadas e produção científica relevante na área.</p><h2>Por que fazer pós aqui</h2><ul><li>Linhas de pesquisa ativas, com projetos financiados por agências de fomento.</li><li>Bolsas de mestrado e doutorado sujeitas à disponibilidade de editais.</li><li>Intercâmbio com outros programas e grupos de pesquisa nacionais e internacionais.</li></ul><h2>Nossos programas</h2>",
  az_lc_cursos_por_categoria($tPos->id()),
  $area
) : NULL;

if ($nidGraduacao) {
  az_lc_preencher_texto_cards($nidGraduacao, $area);
}
if ($nidPosGraduacao) {
  az_lc_preencher_texto_cards($nidPosGraduacao, $area);
}

if (!$tGrad || !$tPos) {
  echo "AVISO: taxonomia Graduação/Pós-Graduação não encontrada neste site - nada a fazer.\n";
}
else {
  // Reaponta os itens do menu principal pras novas páginas.
  $itens_menu = [
    ['Graduação', '/node/' . $nidGraduacao],
    ['Pós-Graduação', '/node/' . $nidPosGraduacao],
  ];
  foreach ($itens_menu as [$titulo, $path]) {
    $existentes_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $titulo, 'menu_name' => 'main']);
    if ($existentes_link) {
      $link = reset($existentes_link);
      $link->set('link', ['uri' => 'internal:' . $path]);
      $link->save();
      echo "Menu '$titulo' apontado pra $path.\n";
    }
  }
}

echo "=== Concluído. Rode 'drush cache:rebuild' em seguida. ===\n";
