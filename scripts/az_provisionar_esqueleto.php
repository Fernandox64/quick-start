<?php

/**
 * Esqueleto completo de site novo: mesma estrutura de menu do site
 * principal (Início, Notícias, Eventos, Graduação, Pós-Graduação, Pessoal,
 * Serviços, Sobre, Contato), preenchida com conteúdo genérico (notícias com
 * imagem, pessoal, eventos, cursos, páginas institucionais), tradução
 * pt-br e o mesmo tema/cores.
 *
 * Reutilizável: roda em QUALQUER site do multisite via
 *   drush --uri=http://dominio:porta scr scripts/az_provisionar_esqueleto.php
 * Detecta o site pela pasta (sites/<nome>) e usa um nome de departamento
 * generico, a menos que o nome esteja na tabela $DEPARTAMENTOS abaixo.
 *
 * Seguro rodar mais de uma vez: cada etapa confere se ja existe antes de
 * criar de novo (idempotente o bastante para nao duplicar menu/paginas).
 */

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\block\Entity\Block;
use Drupal\Core\File\FileSystemInterface;

// ---------------------------------------------------------------------
// 0) Detecta o site atual e o nome do departamento.
// ---------------------------------------------------------------------
$site_path = \Drupal::service('kernel')->getSitePath();
$site_dir = basename($site_path);

$DEPARTAMENTOS = [
  'dfis' => ['nome' => 'Departamento de Física', 'sigla' => 'DFIS', 'area' => 'física'],
  'demat' => ['nome' => 'Departamento de Matemática', 'sigla' => 'DEMAT', 'area' => 'matemática'],
  'demed' => ['nome' => 'Departamento de Medicina', 'sigla' => 'DEMED', 'area' => 'medicina'],
  'defil' => ['nome' => 'Departamento de Filosofia', 'sigla' => 'DEFIL', 'area' => 'filosofia'],
  'delet' => ['nome' => 'Departamento de Letras', 'sigla' => 'DELET', 'area' => 'letras'],
  'depro' => ['nome' => 'Departamento de Engenharia de Produção', 'sigla' => 'DEPRO', 'area' => 'engenharia de produção'],
  'demet' => ['nome' => 'Departamento de Metalurgia', 'sigla' => 'DEMET', 'area' => 'metalurgia'],
  'desoc' => ['nome' => 'Departamento de Serviço Social', 'sigla' => 'DESOC', 'area' => 'serviço social'],
  'dequi' => ['nome' => 'Departamento de Química', 'sigla' => 'DEQUI', 'area' => 'química'],
  'decom' => ['nome' => 'Departamento de Ciência da Computação', 'sigla' => 'DECOM', 'area' => 'computação'],
  'decivil' => ['nome' => 'Departamento de Engenharia Civil', 'sigla' => 'DECIVIL', 'area' => 'engenharia civil'],
  'deelet' => ['nome' => 'Departamento de Engenharia Elétrica', 'sigla' => 'DEELET', 'area' => 'engenharia elétrica'],
  'degeo' => ['nome' => 'Departamento de Geologia', 'sigla' => 'DEGEO', 'area' => 'geologia'],
  'defarm' => ['nome' => 'Departamento de Farmácia', 'sigla' => 'DEFARM', 'area' => 'farmácia'],
  'denutri' => ['nome' => 'Departamento de Nutrição', 'sigla' => 'DENUTRI', 'area' => 'nutrição'],
  'edfis' => ['nome' => 'Departamento de Educação Física', 'sigla' => 'EDFIS', 'area' => 'educação física'],
  'deeco' => ['nome' => 'Departamento de Economia', 'sigla' => 'DEECO', 'area' => 'economia'],
  'dedir' => ['nome' => 'Departamento de Direito', 'sigla' => 'DEDIR', 'area' => 'direito'],
];
$dep = $DEPARTAMENTOS[$site_dir] ?? [
  'nome' => 'Departamento Exemplo',
  'sigla' => strtoupper(substr($site_dir, 0, 6)),
  'area' => 'sua área de atuação',
];
$nome = $dep['nome'];
$sigla = $dep['sigla'];
$area = $dep['area'];
$prefixo_arquivo = $site_dir;

echo "=== Provisionando esqueleto para '$site_dir' ($nome) ===\n";

// ---------------------------------------------------------------------
// 1) Tradução pt-br (pacote da comunidade + strings customizadas).
// ---------------------------------------------------------------------
$module_handler = \Drupal::service('module_handler');
if (!$module_handler->moduleExists('language')) {
  \Drupal::service('module_installer')->install(['language', 'locale', 'config_translation']);
  echo "Módulos de idioma instalados.\n";
}
$precisa_baixar_pacote = FALSE;
if (!\Drupal::languageManager()->getLanguage('pt-br')) {
  \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode('pt-br')->save();
  $precisa_baixar_pacote = TRUE;
}
\Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'pt-br')->save();
// pt-br sem prefixo (""): e a unica lingua de navegacao de verdade aqui (so
// queremos a interface traduzida, nao multilinguismo de conteudo de
// verdade). Com prefixo obrigatorio ("/pt-br/..."), qualquer conteudo/alias
// criado com langcode "en" (a maioria, incluindo o que este proprio script
// cria) vira 404 nessa URL - Drupal nao faz fallback de idioma pra alias e
// pra pagina de taxonomia nesse cenario.
\Drupal::configFactory()->getEditable('language.negotiation')
  ->set('url.source', 'path_prefix')
  ->set('url.prefixes.pt-br', '')
  ->save();

// Strings de interface que nao vem no pacote da comunidade (hardcoded no
// tema/modulos, ver az_traduzir_interface.php do site principal).
$db = \Drupal::database();
$traducoes = [
  'Read more' => 'Leia mais',
  'View all news' => 'Ver todas as notícias',
  'View all events on the calendar' => 'Ver todos os eventos no calendário',
  'Search' => 'Buscar',
  'Apply' => 'Aplicar',
  'Reset' => 'Limpar',
  'Submit' => 'Enviar',
  'Back to top' => 'Voltar ao topo',
  'Menu' => 'Menu',
  'Home' => 'Início',
  'Close' => 'Fechar',
  'today' => 'hoje',
];
foreach ($traducoes as $original => $traducao) {
  $lid = $db->select('locales_source', 's')->fields('s', ['lid'])
    ->condition('source', $original)->condition('context', '')->execute()->fetchField();
  if (!$lid) {
    $lid = $db->insert('locales_source')->fields(['source' => $original, 'context' => '', 'version' => 'none'])->execute();
  }
  $existe = $db->select('locales_target', 't')->fields('t', ['lid'])
    ->condition('lid', $lid)->condition('language', 'pt-br')->execute()->fetchField();
  if ($existe) {
    $db->update('locales_target')->fields(['translation' => $traducao, 'customized' => 1])
      ->condition('lid', $lid)->condition('language', 'pt-br')->execute();
  }
  else {
    $db->insert('locales_target')->fields(['lid' => $lid, 'translation' => $traducao, 'language' => 'pt-br', 'customized' => 1])->execute();
  }
}
echo "Strings de interface traduzidas.\n";

foreach (['views.view.az_news' => 'Notícias', 'views.view.az_events' => 'Calendário'] as $view_name => $titulo) {
  $config = \Drupal::configFactory()->getEditable($view_name);
  if ($config->isNew()) {
    continue;
  }
  foreach ($config->get('display') as $display_id => $display) {
    $key = "display.$display_id.display_options.title";
    if ($config->get($key) !== NULL) {
      $config->set($key, $titulo);
    }
  }
  $config->save();
}
echo "Títulos de views traduzidos.\n";

// Rodape: land_acknowledgment/info_security_privacy sao texto fixo da
// University of Arizona - nao fazem sentido aqui.
$barrio = \Drupal::configFactory()->getEditable('az_barrio.settings');
$barrio->set('land_acknowledgment', FALSE);
$barrio->set('info_security_privacy', FALSE);
$barrio->set('copyright_notice', "$nome, Universidade Federal de Ouro Preto (UFOP). NTI-UFOP");
$barrio->save();

// ---------------------------------------------------------------------
// 2) Logo (gerado na hora - nao depende de arquivo pre-existente).
// ---------------------------------------------------------------------
$fs = \Drupal::service('file_system');
$public_dir = 'public://';
$fs->prepareDirectory($public_dir, FileSystemInterface::CREATE_DIRECTORY);
$logo_uri = 'public://logo-departamento.svg';
if (!is_file($fs->realpath($logo_uri) ?: '')) {
  $partes = explode(' ', $nome, 2);
  $linha1 = $partes[0];
  $linha2 = $partes[1] ?? '';
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 452 71" width="452" height="71" role="img" aria-label="' . htmlspecialchars($nome) . '">'
    . '<rect x="0" y="10" width="10" height="51" fill="#8B0015"/>'
    . '<text x="24" y="34" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" fill="#0C234B">' . htmlspecialchars($linha1) . '</text>'
    . '<text x="24" y="60" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" fill="#0C234B">' . htmlspecialchars($linha2) . '</text>'
    . '</svg>';
  file_put_contents($fs->realpath($logo_uri), $svg);
}
$barrio->set('logo.path', "sites/$site_dir/files/logo-departamento.svg");
$barrio->set('logo.use_default', FALSE);
$barrio->set('footer_logo_path', "sites/$site_dir/files/logo-departamento.svg");
$barrio->set('footer_default_logo', FALSE);
$barrio->save();
echo "Logo definido.\n";

// ---------------------------------------------------------------------
// Helpers de imagem (GD) e midia.
// ---------------------------------------------------------------------
$font_bold = '/usr/share/fonts/opentype/urw-base35/NimbusSans-Bold.otf';
$paleta = [[139, 0, 21], [12, 35, 75], [0, 90, 90], [90, 60, 10], [60, 20, 90], [20, 90, 40], [130, 60, 0], [40, 40, 90]];

function az_esq_banner(string $caminho, string $texto, array $cor, string $font): void {
  $w = 1200; $h = 630;
  $img = imagecreatetruecolor($w, $h);
  [$r, $g, $b] = $cor;
  for ($y = 0; $y < $h; $y++) {
    $t = $y / $h;
    imageline($img, 0, $y, $w, $y, imagecolorallocate($img, (int) ($r * (1 - $t * 0.5)), (int) ($g * (1 - $t * 0.5)), (int) ($b * (1 - $t * 0.5))));
  }
  $bl = imagecolorallocatealpha($img, 255, 255, 255, 110);
  imagefilledellipse($img, $w - 150, 100, 300, 300, $bl);
  imagefilledellipse($img, 100, $h - 80, 220, 220, $bl);
  $branco = imagecolorallocate($img, 255, 255, 255);
  $bbox = imagettfbbox(56, 0, $font, $texto);
  imagettftext($img, 56, 0, (int) (($w - ($bbox[2] - $bbox[0])) / 2), (int) ($h / 2) + 20, $branco, $font, $texto);
  imagejpeg($img, $caminho, 85);
  imagedestroy($img);
}

function az_esq_avatar(string $caminho, string $iniciais, array $cor, string $font): void {
  $s = 400;
  $img = imagecreatetruecolor($s, $s);
  [$r, $g, $b] = $cor;
  imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
  $branco = imagecolorallocate($img, 255, 255, 255);
  $bbox = imagettfbbox(120, 0, $font, $iniciais);
  $tw = $bbox[2] - $bbox[0];
  $th = $bbox[1] - $bbox[7];
  imagettftext($img, 120, 0, (int) (($s - $tw) / 2), (int) (($s + $th) / 2), $branco, $font, $iniciais);
  imagepng($img, $caminho);
  imagedestroy($img);
}

function az_esq_media(string $uri, string $nome): Media {
  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();
  $media = Media::create(['bundle' => 'az_image', 'name' => $nome, 'field_media_az_image' => ['target_id' => $file->id(), 'alt' => $nome], 'status' => 1]);
  $media->save();
  return $media;
}

$dir_n = "public://imported/$prefixo_arquivo-noticias";
$dir_a = "public://imported/$prefixo_arquivo-avatares";
$fs->prepareDirectory($dir_n, FileSystemInterface::CREATE_DIRECTORY);
$fs->prepareDirectory($dir_a, FileSystemInterface::CREATE_DIRECTORY);

// ---------------------------------------------------------------------
// 3) Notícias genéricas (todas com imagem - nunca fica card sem thumb).
// ---------------------------------------------------------------------
$existentes = (int) \Drupal::entityQuery('node')->condition('type', 'az_news')->accessCheck(FALSE)->count()->execute();
if ($existentes === 0) {
  $temas_noticias = [
    "$nome realiza semana acadêmica 2026",
    "Novo laboratório é inaugurado",
    "Pesquisadores publicam artigo em revista científica",
    "Editais de iniciação científica estão abertos",
    "Palestra sobre $area atrai grande público",
    "Parceria internacional amplia intercâmbio de pesquisadores",
    "Alunos do departamento vencem olimpíada acadêmica",
    "Departamento recebe novo equipamento para pesquisa",
    "Resultado do processo seletivo de mestrado é divulgado",
    "Nova disciplina optativa é aberta para o próximo semestre",
  ];
  $hoje = new DateTime();
  foreach ($temas_noticias as $i => $titulo) {
    $idx = $i + 1;
    $cor = $paleta[$idx % count($paleta)];
    $uri = "$dir_n/banner_$idx.jpg";
    az_esq_banner($fs->realpath($uri), $sigla, $cor, $font_bold);
    $media = az_esq_media($uri, $titulo);
    $data = (clone $hoje)->modify('-' . ($idx * 2) . ' days');
    $node = Node::create([
      'type' => 'az_news',
      'title' => $titulo,
      'field_az_summary' => ['value' => "Notícia de demonstração do site do $nome.", 'format' => 'plain_text'],
      'field_az_body' => ['value' => '<p>Texto de demonstração - conteúdo genérico gerado automaticamente. Edite pelo painel administrativo para usar o conteúdo real.</p>', 'format' => 'az_standard'],
      'field_az_media_image' => ['target_id' => $media->id()],
      'field_az_published' => ['value' => $data->format('Y-m-d')],
      'created' => $data->getTimestamp(),
      'status' => 1,
    ]);
    $node->save();
  }
  echo "10 notícias genéricas criadas (todas com imagem).\n";
}
else {
  echo "Notícias já existem ($existentes) - etapa pulada.\n";
}

// Corrige noticias antigas sem imagem, se houver (evita card sem thumb).
$sem_imagem = \Drupal::entityQuery('node')
  ->condition('type', 'az_news')
  ->notExists('field_az_media_image')
  ->accessCheck(FALSE)
  ->execute();
if ($sem_imagem) {
  $idx = 900;
  foreach ($sem_imagem as $nid) {
    $idx++;
    $cor = $paleta[$idx % count($paleta)];
    $uri = "$dir_n/banner_extra_$idx.jpg";
    az_esq_banner($fs->realpath($uri), $sigla, $cor, $font_bold);
    $media = az_esq_media($uri, "Notícia $nid");
    $node = Node::load($nid);
    $node->set('field_az_media_image', ['target_id' => $media->id()]);
    $node->save();
  }
  echo count($sem_imagem) . " notícia(s) antiga(s) sem imagem receberam thumbnail.\n";
}

// ---------------------------------------------------------------------
// 4) Pessoal genérico.
// ---------------------------------------------------------------------
$docente_tid = NULL;
$termos = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'az_person_categories', 'name' => 'Docente']);
if ($termos) {
  $docente_tid = reset($termos)->id();
}

$existentes_p = (int) \Drupal::entityQuery('node')->condition('type', 'az_person')->accessCheck(FALSE)->count()->execute();
if ($existentes_p === 0) {
  $nomes = [
    ['Ricardo', 'Almeida Souza', 'Professor Titular'],
    ['Camila', 'Ferreira Lima', 'Professora Associada'],
    ['Eduardo', 'Martins Costa', 'Professor Adjunto'],
    ['Juliana', 'Rocha Pereira', 'Professora Associada'],
    ['Thiago', 'Barbosa Nunes', 'Professor Adjunto'],
    ['Patrícia', 'Gomes Cardoso', 'Professora Titular'],
  ];
  foreach ($nomes as $i => [$fname, $lname, $titulo]) {
    $idx = $i + 1;
    $iniciais = mb_strtoupper(mb_substr($fname, 0, 1) . mb_substr($lname, 0, 1));
    $cor = $paleta[($idx + 2) % count($paleta)];
    $uri = "$dir_a/avatar_$idx.png";
    az_esq_avatar($fs->realpath($uri), $iniciais, $cor, $font_bold);
    $media = az_esq_media($uri, "$fname $lname");
    $node = Node::create([
      'type' => 'az_person',
      'title' => "$fname $lname",
      'field_az_fname' => $fname,
      'field_az_lname' => $lname,
      'field_az_titles' => [$titulo],
      'field_az_research_interests' => ['value' => "Pesquisa em $area.", 'format' => 'plain_text'],
      'field_az_body' => ['value' => "<p>{$titulo} do $nome. Perfil de demonstração (dados fictícios) - edite pelo painel administrativo.</p>", 'format' => 'az_standard'],
      'field_az_media_image' => ['target_id' => $media->id()],
      'field_az_person_category' => $docente_tid ? [['target_id' => $docente_tid]] : [],
      'status' => 1,
    ]);
    $node->save();
  }
  echo "6 professores genéricos criados.\n";
}
else {
  echo "Pessoal já existe ($existentes_p) - etapa pulada.\n";
}

// ---------------------------------------------------------------------
// 5) Eventos genéricos.
// ---------------------------------------------------------------------
$existentes_e = (int) \Drupal::entityQuery('node')->condition('type', 'az_event')->accessCheck(FALSE)->count()->execute();
if ($existentes_e === 0) {
  $hoje = new DateTime();
  $eventos = [
    ["Semana Acadêmica $sigla 2026", 10, 9, 12],
    ['Seminário de pesquisa', 20, 14, 16],
    ['Defesa de dissertação de mestrado', 27, 10, 11],
    ['Colóquio do departamento', 45, 15, 16],
  ];
  foreach ($eventos as [$titulo, $dias, $hi, $hf]) {
    $inicio = (clone $hoje)->modify("+$dias days")->setTime($hi, 0);
    $fim = (clone $hoje)->modify("+$dias days")->setTime($hf, 0);
    $node = Node::create([
      'type' => 'az_event',
      'title' => $titulo,
      'field_az_summary' => ['value' => "Evento de demonstração do $nome.", 'format' => 'plain_text'],
      'field_az_body' => ['value' => '<p>Descrição de demonstração - conteúdo genérico gerado automaticamente.</p>', 'format' => 'az_standard'],
      'field_az_event_date' => [['value' => $inicio->getTimestamp(), 'end_value' => $fim->getTimestamp(), 'duration' => ($hf - $hi) * 60, 'timezone' => 'America/Sao_Paulo']],
      'status' => 1,
    ]);
    $node->save();
  }
  echo "4 eventos genéricos criados.\n";
}
else {
  echo "Eventos já existem ($existentes_e) - etapa pulada.\n";
}

// ---------------------------------------------------------------------
// 6) Graduação / Pós-Graduação (taxonomia + cursos genéricos).
// ---------------------------------------------------------------------
function az_esq_termo(string $vid, string $nome, string $alias_wanted): Term {
  $termos = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $nome]);
  if ($termos) {
    return reset($termos);
  }
  $term = Term::create(['vid' => $vid, 'name' => $nome, 'langcode' => 'pt-br']);
  $term->save();
  \Drupal::service('path_alias.manager');
  \Drupal\path_alias\Entity\PathAlias::create([
    'path' => '/taxonomy/term/' . $term->id(),
    'alias' => $alias_wanted,
    'langcode' => 'pt-br',
  ])->save();
  return $term;
}

$tGrad = az_esq_termo('az_curso_categoria', 'Graduação', '/categoria-de-curso/graduacao');
$tPos = az_esq_termo('az_curso_categoria', 'Pós-Graduação', '/categoria-de-curso/pos-graduacao');

$existentes_c = (int) \Drupal::entityQuery('node')->condition('type', 'az_curso')->accessCheck(FALSE)->count()->execute();
if ($existentes_c === 0) {
  $cursos = [
    ["Bacharelado em " . ucfirst($area), $tGrad],
    ["Licenciatura em " . ucfirst($area), $tGrad],
    ["Mestrado em " . ucfirst($area), $tPos],
    ["Doutorado em " . ucfirst($area), $tPos],
  ];
  foreach ($cursos as [$titulo, $termo]) {
    $node = Node::create([
      'type' => 'az_curso',
      'title' => $titulo,
      'field_az_curso_categoria' => ['target_id' => $termo->id()],
      'status' => 1,
    ]);
    $node->save();
  }
  echo "Taxonomia Graduação/Pós-Graduação + 4 cursos genéricos criados.\n";
}
else {
  echo "Cursos já existem ($existentes_c) - etapa pulada.\n";
}

// Landing page de verdade pra Graduação e Pós-Graduação (antes o menu
// levava direto pra página padrão de taxonomia do Drupal - uma listagem
// genérica de "conteúdo marcado com...", sem nenhum texto explicativo).
function az_esq_cursos_por_categoria(int $tid): array {
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
function az_esq_texto_modalidade(string $titulo_curso, string $area): string {
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

function az_esq_pagina_curso(string $titulo, string $intro_html, array $cursos_titulos, string $area): int {
  $nid = az_esq_pagina_existe($titulo);
  if ($nid) {
    return $nid;
  }
  $paragrafos = [];
  $intro = Paragraph::create(['type' => 'az_text', 'field_az_text_area' => ['value' => $intro_html, 'format' => 'full_html']]);
  $intro->save();
  $paragrafos[] = $intro;
  if ($cursos_titulos) {
    $cards = array_map(fn($t) => ['title' => $t, 'body' => az_esq_texto_modalidade($t, $area), 'body_format' => 'plain_text'], $cursos_titulos);
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

$nidGraduacao = az_esq_pagina_curso(
  'Graduação',
  "<p>O $nome oferece curso(s) de graduação em $area, com um currículo estruturado para formar profissionais capacitados tanto para o mercado de trabalho quanto para a pesquisa científica.</p><h2>Por que estudar aqui</h2><ul><li>Corpo docente qualificado, com professores atuantes em pesquisa e extensão.</li><li>Infraestrutura de laboratórios e bibliotecas para apoio ao ensino.</li><li>Oportunidades de iniciação científica e monitoria já durante a graduação.</li></ul><h2>Nossos cursos</h2>",
  az_esq_cursos_por_categoria($tGrad->id()),
  $area
);
$nidPosGraduacao = az_esq_pagina_curso(
  'Pós-Graduação',
  "<p>O programa de pós-graduação do $nome forma pesquisadores e profissionais de alto nível em $area, com linhas de pesquisa consolidadas e produção científica relevante na área.</p><h2>Por que fazer pós aqui</h2><ul><li>Linhas de pesquisa ativas, com projetos financiados por agências de fomento.</li><li>Bolsas de mestrado e doutorado sujeitas à disponibilidade de editais.</li><li>Intercâmbio com outros programas e grupos de pesquisa nacionais e internacionais.</li></ul><h2>Nossos programas</h2>",
  az_esq_cursos_por_categoria($tPos->id()),
  $area
);

// ---------------------------------------------------------------------
// 7) Páginas institucionais: Sobre, Serviços, Contato.
// ---------------------------------------------------------------------
function az_esq_pagina_existe(string $titulo): ?int {
  $nids = \Drupal::entityQuery('node')->condition('type', 'az_flexible_page')->condition('title', $titulo)->accessCheck(FALSE)->execute();
  return $nids ? (int) reset($nids) : NULL;
}

$nidSobre = az_esq_pagina_existe('Sobre');
if (!$nidSobre) {
  $texto = Paragraph::create([
    'type' => 'az_text',
    'field_az_text_area' => ['value' => "<p>O $nome é uma unidade acadêmica dedicada ao ensino, pesquisa e extensão em $area. Este é um texto de demonstração - edite pelo painel administrativo com as informações reais do departamento.</p>", 'format' => 'full_html'],
  ]);
  $texto->save();
  $sobre = Node::create([
    'type' => 'az_flexible_page',
    'title' => 'Sobre',
    'field_az_main_content' => [['target_id' => $texto->id(), 'target_revision_id' => $texto->getRevisionId()]],
    'status' => 1,
  ]);
  $sobre->save();
  $nidSobre = (int) $sobre->id();
  echo "Página 'Sobre' criada (nid=$nidSobre).\n";
}

// Landing page individual de cada serviço (antes eram só cards estáticos,
// sem link nenhum - agora cada card leva pra uma página de verdade).
function az_esq_pagina_servico(string $titulo, string $corpo_html): int {
  $nid = az_esq_pagina_existe($titulo);
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

$nidIC = az_esq_pagina_servico('Iniciação Científica', "<p>O $nome oferece oportunidades de iniciação científica para alunos de graduação interessados em desenvolver pesquisa em $area, sob orientação de um professor do departamento.</p><h2>Como participar</h2><ul><li>Acompanhe os editais de bolsas (PIBIC/PIBITI e fluxo contínuo) divulgados pelo departamento.</li><li>Procure um professor orientador com linha de pesquisa de seu interesse.</li><li>Elabore o plano de trabalho em conjunto com o orientador e submeta dentro do prazo do edital.</li></ul><p>Dúvidas podem ser esclarecidas pelos canais de <a href=\"/contact\">contato</a> do departamento. Conteúdo de demonstração.</p>");
$nidMon = az_esq_pagina_servico('Monitoria', "<p>O programa de monitoria do $nome envolve alunos de graduação com bom desempenho acadêmico no apoio a disciplinas, sob supervisão de um professor responsável.</p><h2>Atividades do monitor</h2><ul><li>Atendimento a colegas com dúvidas sobre o conteúdo das aulas.</li><li>Apoio na preparação de listas de exercícios e material de apoio.</li><li>Participação em plantões de dúvidas presenciais ou online.</li></ul><p>As vagas são divulgadas por edital semestral. Conteúdo de demonstração.</p>");
$nidLab = az_esq_pagina_servico('Laboratórios', "<p>O $nome conta com laboratórios equipados para atividades de ensino, pesquisa e extensão em $area, disponíveis para alunos e pesquisadores do departamento.</p><h2>Utilização</h2><ul><li>Reserva de horário e equipamentos junto à secretaria do departamento.</li><li>Uso vinculado a disciplinas, projetos de pesquisa ou iniciação científica.</li><li>Normas de segurança e uso compartilhado disponíveis com o responsável técnico.</li></ul><p>Conteúdo de demonstração.</p>");

$nidServicos = az_esq_pagina_existe('Serviços');
if (!$nidServicos) {
  $cards = Paragraph::create([
    'type' => 'az_cards',
    'field_az_title' => '',
    'field_az_cards' => [
      ['title' => 'Iniciação Científica', 'body' => 'Editais e orientação para alunos interessados em pesquisa.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidIC],
      ['title' => 'Monitoria', 'body' => 'Apoio acadêmico oferecido por alunos monitores das disciplinas.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidMon],
      ['title' => 'Laboratórios', 'body' => 'Infraestrutura de laboratórios para ensino e pesquisa.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidLab],
    ],
  ]);
  $cards->save();
  $servicos = Node::create([
    'type' => 'az_flexible_page',
    'title' => 'Serviços',
    'field_az_main_content' => [['target_id' => $cards->id(), 'target_revision_id' => $cards->getRevisionId()]],
    'status' => 1,
  ]);
  $servicos->save();
  $nidServicos = (int) $servicos->id();
  echo "Página 'Serviços' criada (nid=$nidServicos).\n";
}

$nidContato = az_esq_pagina_existe('Contato');
if (!$nidContato) {
  $texto = Paragraph::create([
    'type' => 'az_text',
    'field_az_text_area' => ['value' => '<h2>Fale Conosco</h2><p>Entre em contato pelos canais abaixo.</p>', 'format' => 'full_html'],
  ]);
  $texto->save();
  $contato_p = Paragraph::create([
    'type' => 'az_contact',
    'field_az_title' => 'Contato',
    'field_az_email' => 'contato@departamento.br',
    'field_az_phone' => '(00) 0000-0000',
  ]);
  $contato_p->save();
  $contato = Node::create([
    'type' => 'az_flexible_page',
    'title' => 'Contato',
    'field_az_main_content' => [
      ['target_id' => $texto->id(), 'target_revision_id' => $texto->getRevisionId()],
      ['target_id' => $contato_p->id(), 'target_revision_id' => $contato_p->getRevisionId()],
    ],
    'status' => 1,
  ]);
  $contato->save();
  $nidContato = (int) $contato->id();
  echo "Página 'Contato' criada (nid=$nidContato).\n";
}

// ---------------------------------------------------------------------
// 8) Home completa (banner, destaques, noticias, servicos, eventos, quem somos).
// ---------------------------------------------------------------------
$nidHome = az_esq_pagina_existe("Início - $nome");
$boasVindas = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => ['value' => "<div class=\"az-ufop-hero az-ufop-hero-center\"><h2>Bem-vindo ao $nome</h2><p>Site de demonstração - ensino, pesquisa e extensão em $area.</p><div class=\"az-ufop-hero-actions\"><a class=\"btn btn-light\" href=\"/news\">Ver notícias</a> <a class=\"btn btn-outline-light\" href=\"/people\">Conheça a equipe</a></div></div>", 'format' => 'full_html'],
]);
$boasVindas->save();

$destaquesCards = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => [
    ['title' => 'Atendimento', 'body' => 'Horários e canais de atendimento ao público.', 'body_format' => 'plain_text', 'link_title' => 'Ver contato', 'link_uri' => '/node/' . $nidContato],
    ['title' => 'Serviços', 'body' => 'Principais serviços oferecidos pelo departamento.', 'body_format' => 'plain_text', 'link_title' => 'Ver serviços', 'link_uri' => '/node/' . $nidServicos],
    ['title' => 'Equipe', 'body' => 'Conheça os professores responsáveis por cada área.', 'body_format' => 'plain_text', 'link_title' => 'Ver equipe', 'link_uri' => '/people'],
  ],
]);
$destaquesCards->save();

$introNoticias = Paragraph::create(['type' => 'az_text', 'field_az_text_area' => ['value' => '<p>Acompanhe as últimas novidades do departamento.</p>', 'format' => 'full_html']]);
$introNoticias->save();
$noticiasView = Paragraph::create(['type' => 'az_view_reference', 'field_az_title' => 'Últimas notícias', 'field_az_view_reference' => ['target_id' => 'az_news', 'display_id' => 'az_grid']]);
$noticiasView->save();
$tituloServicos = Paragraph::create(['type' => 'az_text', 'field_az_text_area' => ['value' => '<h2>Nossos Serviços</h2>', 'format' => 'full_html']]);
$tituloServicos->save();
$cardsServicos = Paragraph::create([
  'type' => 'az_cards',
  'field_az_title' => '',
  'field_az_cards' => [
    ['title' => 'Iniciação Científica', 'body' => 'Editais e orientação para alunos interessados em pesquisa.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidIC],
    ['title' => 'Monitoria', 'body' => 'Apoio acadêmico oferecido por alunos monitores das disciplinas.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidMon],
    ['title' => 'Laboratórios', 'body' => 'Infraestrutura de laboratórios para ensino e pesquisa.', 'body_format' => 'plain_text', 'link_title' => 'Saiba mais', 'link_uri' => '/node/' . $nidLab],
  ],
]);
$cardsServicos->save();
$eventosView = Paragraph::create(['type' => 'az_view_reference', 'field_az_title' => 'Próximos eventos', 'field_az_view_reference' => ['target_id' => 'az_events', 'display_id' => 'az_grid']]);
$eventosView->save();

$mediaId = az_esq_media("$dir_n/banner_1.jpg", 'Quem somos')->id();
$quemSomos = Paragraph::create([
  'type' => 'az_splitscreen',
  'field_az_media' => ['target_id' => $mediaId],
  'field_az_text_area' => ['value' => "<h2>Quem somos</h2><p>O $nome atua no ensino, pesquisa e extensão, com uma equipe dedicada e comprometida com a excelência acadêmica. Site de demonstração (multisite Arizona Quickstart).</p>", 'format' => 'full_html'],
]);
$quemSomos->save();

$paragrafos = [$boasVindas, $destaquesCards, $introNoticias, $noticiasView, $tituloServicos, $cardsServicos, $eventosView, $quemSomos];
$valores = array_map(fn($p) => ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()], $paragrafos);

if ($nidHome) {
  $home = Node::load($nidHome);
  $home->set('field_az_main_content', $valores);
  $home->save();
}
else {
  $home = Node::create(['type' => 'az_flexible_page', 'title' => "Início - $nome", 'field_az_main_content' => $valores, 'status' => 1]);
  $home->save();
  $nidHome = (int) $home->id();
}
\Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $nidHome)->save();
echo "Home completa pronta (nid=$nidHome).\n";

// ---------------------------------------------------------------------
// 9) Menu principal (mesma estrutura do site principal).
// ---------------------------------------------------------------------
$itens_menu = [
  ['Início', '/node/' . $nidHome, -10],
  ['Notícias', '/news', -9],
  ['Eventos', '/calendar', -8],
  ['Graduação', '/node/' . $nidGraduacao, -7],
  ['Pós-Graduação', '/node/' . $nidPosGraduacao, -6],
  ['Pessoal', '/people', -5],
  ['Serviços', '/node/' . $nidServicos, -4],
  ['Sobre', '/node/' . $nidSobre, -3],
  ['Contato', '/node/' . $nidContato, -2],
];
foreach ($itens_menu as [$titulo, $path, $peso]) {
  $existentes_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $titulo, 'menu_name' => 'main']);
  if ($existentes_link) {
    $link = reset($existentes_link);
    $link->set('link', ['uri' => 'internal:' . $path]);
    $link->set('weight', $peso);
    $link->save();
    continue;
  }
  MenuLinkContent::create([
    'title' => $titulo,
    'link' => ['uri' => 'internal:' . $path],
    'menu_name' => 'main',
    'weight' => $peso,
    'enabled' => TRUE,
  ])->save();
}
echo "Menu principal com 9 itens (igual ao site principal).\n";

// ---------------------------------------------------------------------
// 10) Bloco do carrossel de notícias na home.
// ---------------------------------------------------------------------
$id_bloco = 'az_ufop_noticias_carousel';
if (Block::load($id_bloco)) {
  Block::load($id_bloco)->delete();
}
Block::create([
  'id' => $id_bloco,
  'theme' => 'az_barrio',
  'region' => 'content_featured',
  'plugin' => 'az_ufop_noticias_carousel',
  'weight' => -20,
  'visibility' => ['request_path' => ['id' => 'request_path', 'negate' => FALSE, 'pages' => '<front>']],
  'settings' => ['id' => 'az_ufop_noticias_carousel', 'label' => 'Carrossel de Notícias', 'label_display' => '0', 'provider' => 'az_ufop_departamento'],
])->save();
echo "Bloco do carrossel colocado na home.\n";

// ---------------------------------------------------------------------
// 10.5) Bloco de "Acesso Rápido" (mesmos links do menu) + redes sociais
// no rodapé, no estilo do site principal em Laravel.
// ---------------------------------------------------------------------
$id_footer = 'az_ufop_footer_acesso_rapido';
if (Block::load($id_footer)) {
  Block::load($id_footer)->delete();
}
Block::create([
  'id' => $id_footer,
  'theme' => 'az_barrio',
  'region' => 'footer',
  'plugin' => $id_footer,
  'weight' => 0,
  'settings' => ['id' => $id_footer, 'label' => 'Acesso Rápido', 'label_display' => '0', 'provider' => 'az_ufop_departamento'],
  'visibility' => [],
])->save();
echo "Bloco 'Acesso Rápido + Redes Sociais' colocado no rodapé.\n";

// ---------------------------------------------------------------------
// 11.5) Desativa o link "Home" estatico do perfil (duplica o "Início" que
// criamos no menu - ver az_disable_home_link.php do site principal).
// ---------------------------------------------------------------------
$menuLinkManager = \Drupal::service('plugin.manager.menu.link');
try {
  $definition = $menuLinkManager->getDefinition('az_quickstart.front_page');
  if (!empty($definition['enabled'])) {
    $menuLinkManager->updateDefinition('az_quickstart.front_page', ['enabled' => FALSE]);
    $menuLinkManager->resetDefinitions();
    echo "Link 'Home' estático do perfil desativado (evita duplicar com 'Início').\n";
  }
}
catch (\Exception $e) {
  // Plugin nao existe nesta versao do profile - nada a fazer.
}

// ---------------------------------------------------------------------
// 11) Remove modulos nao essenciais com bugs conhecidos (masquerade_log).
// ---------------------------------------------------------------------
if ($module_handler->moduleExists('masquerade_log')) {
  \Drupal::service('module_installer')->uninstall(['masquerade_log', 'masquerade']);
  echo "masquerade_log removido (bug conhecido de compatibilidade com Drush no Drupal 11.4).\n";
}

echo "\n=== Esqueleto de '$nome' pronto. Rode 'drush cache:rebuild' em seguida. ===\n";
if ($precisa_baixar_pacote) {
  echo "IMPORTANTE: rode também 'drush --uri=... locale:update' agora - baixa o pacote\n";
  echo "completo de tradução pt-br (11 mil+ strings). Não dá pra chamar isso de dentro\n";
  echo "deste script (drush não permite rodar outro comando drush por dentro de si mesmo).\n";
}
