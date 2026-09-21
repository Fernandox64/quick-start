<?php

/**
 * Retrofit para sites já provisionados: cria uma landing page de verdade
 * pra cada serviço (Iniciação Científica, Monitoria, Laboratórios) e liga
 * os cards de "Serviços" (a página /servicos e a seção "Nossos Serviços"
 * da home) a elas - antes eram só texto estático, sem link nenhum.
 *
 * Reutilizável: roda em QUALQUER site do multisite via
 *   drush --uri=http://dominio:porta scr scripts/az_add_landing_servicos.php
 * Detecta o nome/área do departamento pelo mesmo jeito do
 * az_provisionar_esqueleto.php. Seguro rodar mais de uma vez - só cria as
 * páginas se ainda não existirem, e sempre reescreve os cards com os links
 * certos (idempotente).
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

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

echo "=== Landing pages de serviços para '$site_dir' ($nome) ===\n";

function az_lp_pagina_existe(string $titulo): ?int {
  $nids = \Drupal::entityQuery('node')->condition('type', 'az_flexible_page')->condition('title', $titulo)->accessCheck(FALSE)->execute();
  return $nids ? (int) reset($nids) : NULL;
}

function az_lp_pagina_servico(string $titulo, string $corpo_html): int {
  $nid = az_lp_pagina_existe($titulo);
  if ($nid) {
    return $nid;
  }
  $texto = Paragraph::create(['type' => 'az_text', 'field_az_text_area' => ['value' => $corpo_html, 'format' => 'full_html']]);
  $texto->save();
  $pagina = Node::create([
    'type' => 'az_flexible_page',
    'title' => $titulo,
    'field_az_main_content' => [['target_id' => $texto->id(), 'target_revision_id' => $texto->getRevisionId()]],
    'status' => 1,
  ]);
  $pagina->save();
  $nid = (int) $pagina->id();
  echo "Página '$titulo' criada (nid=$nid).\n";
  return $nid;
}

$nidIC = az_lp_pagina_servico('Iniciação Científica', "<p>O $nome oferece oportunidades de iniciação científica para alunos de graduação interessados em desenvolver pesquisa em $area, sob orientação de um professor do departamento.</p><h2>Como participar</h2><ul><li>Acompanhe os editais de bolsas (PIBIC/PIBITI e fluxo contínuo) divulgados pelo departamento.</li><li>Procure um professor orientador com linha de pesquisa de seu interesse.</li><li>Elabore o plano de trabalho em conjunto com o orientador e submeta dentro do prazo do edital.</li></ul><p>Dúvidas podem ser esclarecidas pelos canais de <a href=\"/contact\">contato</a> do departamento. Conteúdo de demonstração.</p>");
$nidMon = az_lp_pagina_servico('Monitoria', "<p>O programa de monitoria do $nome envolve alunos de graduação com bom desempenho acadêmico no apoio a disciplinas, sob supervisão de um professor responsável.</p><h2>Atividades do monitor</h2><ul><li>Atendimento a colegas com dúvidas sobre o conteúdo das aulas.</li><li>Apoio na preparação de listas de exercícios e material de apoio.</li><li>Participação em plantões de dúvidas presenciais ou online.</li></ul><p>As vagas são divulgadas por edital semestral. Conteúdo de demonstração.</p>");
$nidLab = az_lp_pagina_servico('Laboratórios', "<p>O $nome conta com laboratórios equipados para atividades de ensino, pesquisa e extensão em $area, disponíveis para alunos e pesquisadores do departamento.</p><h2>Utilização</h2><ul><li>Reserva de horário e equipamentos junto à secretaria do departamento.</li><li>Uso vinculado a disciplinas, projetos de pesquisa ou iniciação científica.</li><li>Normas de segurança e uso compartilhado disponíveis com o responsável técnico.</li></ul><p>Conteúdo de demonstração.</p>");

$links = [
  'Iniciação Científica' => $nidIC,
  'Monitoria' => $nidMon,
  'Laboratórios' => $nidLab,
];

// O site principal foi montado à mão antes deste esqueleto existir e usa
// cards placeholder genéricos ("Servico 1/2/3", mesma descrição repetida) -
// renomeia pros 3 serviços de verdade antes de linkar, unificando com o
// conteúdo dos sites de departamento.
$renomear_generico = [
  'Servico 1' => ['title' => 'Iniciação Científica', 'body' => 'Editais e orientação para alunos interessados em pesquisa.'],
  'Servico 2' => ['title' => 'Monitoria', 'body' => 'Apoio acadêmico oferecido por alunos monitores das disciplinas.'],
  'Servico 3' => ['title' => 'Laboratórios', 'body' => 'Infraestrutura de laboratórios para ensino e pesquisa.'],
];

/**
 * Reescreve os itens de um paragrafo az_cards: renomeia placeholders
 * genéricos ("Servico 1" etc.) pro serviço de verdade, e adiciona
 * link_title/link_uri nos cards cujo título esteja no mapa $links acima.
 */
function az_lp_linkar_cards(Paragraph $paragraph, array $links, array $renomear_generico): bool {
  $itens = $paragraph->get('field_az_cards')->getValue();
  $mudou = FALSE;
  foreach ($itens as &$item) {
    if (isset($renomear_generico[$item['title']])) {
      $novo = $renomear_generico[$item['title']];
      $item['title'] = $novo['title'];
      $item['body'] = $novo['body'];
      $mudou = TRUE;
    }
    if (isset($links[$item['title']]) && empty($item['link_uri'])) {
      $item['link_title'] = 'Saiba mais';
      $item['link_uri'] = '/node/' . $links[$item['title']];
      $mudou = TRUE;
    }
    // Bug conhecido do formatter do az_card: ele resolve link_uri via
    // path.validator (espera um caminho puro, tipo "/node/5"), não via
    // Url::fromUri() - um link_uri com o prefixo de esquema "internal:"
    // (usado corretamente em campos de link nativos do Drupal, mas não
    // aqui) sempre vira um <a href=""> morto. Conserta qualquer card já
    // salvo assim antes deste conserto ser conhecido.
    if (!empty($item['link_uri']) && str_starts_with($item['link_uri'], 'internal:')) {
      $item['link_uri'] = substr($item['link_uri'], strlen('internal:'));
      $mudou = TRUE;
    }
  }
  unset($item);
  if ($mudou) {
    $paragraph->set('field_az_cards', $itens);
    $paragraph->save();
  }
  return $mudou;
}

// 1) Página "Serviços" (sites de departamento) ou "Nossos Servicos" (site
// principal, nome antigo de antes deste esqueleto existir).
$nidServicos = az_lp_pagina_existe('Serviços') ?? az_lp_pagina_existe('Nossos Servicos');
if ($nidServicos) {
  $servicos = Node::load($nidServicos);
  $mudou_algum = FALSE;
  foreach ($servicos->get('field_az_main_content') as $item) {
    $paragraph = $item->entity;
    if ($paragraph && $paragraph->bundle() === 'az_cards') {
      if (az_lp_linkar_cards($paragraph, $links, $renomear_generico)) {
        $mudou_algum = TRUE;
      }
    }
  }
  echo $mudou_algum ? "Cards da página 'Serviços' atualizados com links.\n" : "Cards da página 'Serviços' já tinham link (ou não encontrados) - nada a fazer.\n";
}
else {
  echo "AVISO: página 'Serviços' não encontrada neste site.\n";
}

// 2) Seção "Nossos Serviços" na home (distingue da outra seção de cards da
// home - "Atendimento/Serviços/Equipe" - pelo título dos cards).
$nidHome = (int) str_replace('/node/', '', \Drupal::config('system.site')->get('page.front'));
if ($nidHome) {
  $home = Node::load($nidHome);
  $mudou_algum = FALSE;
  if ($home) {
    foreach ($home->get('field_az_main_content') as $item) {
      $paragraph = $item->entity;
      if ($paragraph && $paragraph->bundle() === 'az_cards') {
        if (az_lp_linkar_cards($paragraph, $links, $renomear_generico)) {
          $mudou_algum = TRUE;
        }
      }
    }
  }
  echo $mudou_algum ? "Cards de 'Nossos Serviços' na home atualizados com links.\n" : "Cards de 'Nossos Serviços' na home já tinham link (ou não encontrados) - nada a fazer.\n";
}
else {
  echo "AVISO: página inicial (page.front) não encontrada neste site.\n";
}

echo "=== Concluído. Rode 'drush cache:rebuild' em seguida. ===\n";
