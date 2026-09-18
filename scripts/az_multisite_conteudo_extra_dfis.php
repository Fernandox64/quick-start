<?php

/**
 * Conteudo extra de demonstracao pro site DFIS: gera banners e avatares
 * variados via GD (nao ha fotos reais pra esse departamento fictício) e
 * cria noticias genericas + professores ficticios, pra popular o carrossel
 * da home e a pagina de pessoal do mesmo jeito que o site principal.
 */

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;

$font_bold = '/usr/share/fonts/opentype/urw-base35/NimbusSans-Bold.otf';
$font_regular = '/usr/share/fonts/opentype/urw-base35/NimbusSans-Regular.otf';

$paleta = [
  [139, 0, 21],    // vinho (cor institucional)
  [12, 35, 75],    // azul marinho
  [0, 90, 90],     // petroleo
  [90, 60, 10],    // marrom queimado
  [60, 20, 90],    // roxo
  [20, 90, 40],    // verde escuro
  [130, 60, 0],    // laranja queimado
  [40, 40, 90],    // indigo
];

function az_gera_banner(string $caminho, string $texto, array $cor, string $font_bold): void {
  $w = 1200;
  $h = 630;
  $img = imagecreatetruecolor($w, $h);

  // Gradiente diagonal simples entre a cor base e uma variante mais escura.
  [$r, $g, $b] = $cor;
  for ($y = 0; $y < $h; $y++) {
    $t = $y / $h;
    $rr = (int) ($r * (1 - $t * 0.5));
    $gg = (int) ($g * (1 - $t * 0.5));
    $bb = (int) ($b * (1 - $t * 0.5));
    $linha = imagecolorallocate($img, $rr, $gg, $bb);
    imageline($img, 0, $y, $w, $y, $linha);
  }

  // Alguns circulos translucidos decorativos.
  $branco_leve = imagecolorallocatealpha($img, 255, 255, 255, 110);
  imagefilledellipse($img, $w - 150, 100, 300, 300, $branco_leve);
  imagefilledellipse($img, 100, $h - 80, 220, 220, $branco_leve);

  $branco = imagecolorallocate($img, 255, 255, 255);
  $bbox = imagettfbbox(56, 0, $font_bold, $texto);
  $tw = $bbox[2] - $bbox[0];
  imagettftext($img, 56, 0, (int) (($w - $tw) / 2), (int) ($h / 2) + 20, $branco, $font_bold, $texto);

  imagejpeg($img, $caminho, 85);
  imagedestroy($img);
}

function az_gera_avatar(string $caminho, string $iniciais, array $cor, string $font_bold): void {
  $s = 400;
  $img = imagecreatetruecolor($s, $s);
  [$r, $g, $b] = $cor;
  $fundo = imagecolorallocate($img, $r, $g, $b);
  imagefill($img, 0, 0, $fundo);

  $branco = imagecolorallocate($img, 255, 255, 255);
  $bbox = imagettfbbox(120, 0, $font_bold, $iniciais);
  $tw = $bbox[2] - $bbox[0];
  $th = $bbox[1] - $bbox[7];
  imagettftext($img, 120, 0, (int) (($s - $tw) / 2), (int) (($s + $th) / 2), $branco, $font_bold, $iniciais);

  imagepng($img, $caminho);
  imagedestroy($img);
}

function az_criar_media_imagem(string $uri, string $nome): Media {
  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();
  $media = Media::create([
    'bundle' => 'az_image',
    'name' => $nome,
    'field_media_az_image' => ['target_id' => $file->id(), 'alt' => $nome],
    'status' => 1,
  ]);
  $media->save();
  return $media;
}

$file_system = \Drupal::service('file_system');
$dir_noticias = 'public://imported/dfis-noticias';
$dir_avatares = 'public://imported/dfis-avatares';
\Drupal::service('file_system')->prepareDirectory($dir_noticias, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
\Drupal::service('file_system')->prepareDirectory($dir_avatares, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

$noticias = [
  'Departamento de Física realiza Semana Acadêmica 2026',
  'Novo laboratório de óptica é inaugurado',
  'Pesquisadores publicam artigo sobre supercondutividade',
  'Editais de iniciação científica estão abertos',
  'Palestra sobre física de partículas atrai grande público',
  'Parceria internacional amplia intercâmbio de pesquisadores',
  'Alunos do departamento vencem olimpíada de física',
  'Departamento recebe novo equipamento de espectroscopia',
];

$hoje = new DateTime();
foreach ($noticias as $i => $titulo) {
  $cor = $paleta[$i % count($paleta)];
  $uri = "$dir_noticias/banner_" . ($i + 1) . '.jpg';
  $realpath = $file_system->realpath($uri);
  az_gera_banner($realpath, 'DFIS', $cor, $font_bold);
  $media = az_criar_media_imagem($uri, $titulo);

  $data = (clone $hoje)->modify('-' . ($i * 3) . ' days');

  $node = Node::create([
    'type' => 'az_news',
    'title' => $titulo,
    'field_az_summary' => [
      'value' => 'Notícia de demonstração do site do Departamento de Física - conteúdo genérico gerado para o teste de multisite.',
      'format' => 'plain_text',
    ],
    'field_az_body' => [
      'value' => '<p>Este é um texto de demonstração. Em um site real, aqui entraria o conteúdo completo da notícia, editado pela equipe do departamento pelo próprio painel administrativo do Drupal.</p>',
      'format' => 'az_standard',
    ],
    'field_az_media_image' => ['target_id' => $media->id()],
    'field_az_published' => ['value' => $data->format('Y-m-d')],
    'created' => $data->getTimestamp(),
    'status' => 1,
  ]);
  $node->save();
  echo "Notícia criada: $titulo (nid={$node->id()})\n";
}

$docente_tid = NULL;
$termos = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'az_person_categories', 'name' => 'Docente']);
if ($termos) {
  $docente_tid = reset($termos)->id();
}

$professores = [
  ['Ricardo', 'Almeida Souza', 'Professor Associado', 'Física de Partículas'],
  ['Camila', 'Ferreira Lima', 'Professora Adjunta', 'Óptica e Fotônica'],
  ['Eduardo', 'Martins Costa', 'Professor Titular', 'Física Teórica'],
  ['Juliana', 'Rocha Pereira', 'Professora Associada', 'Física da Matéria Condensada'],
  ['Thiago', 'Barbosa Nunes', 'Professor Adjunto', 'Astrofísica'],
  ['Patrícia', 'Gomes Cardoso', 'Professora Associada', 'Física Nuclear'],
];

foreach ($professores as $i => [$fname, $lname, $titulo, $area]) {
  $iniciais = mb_strtoupper(mb_substr($fname, 0, 1) . mb_substr($lname, 0, 1));
  $cor = $paleta[($i + 3) % count($paleta)];
  $uri = "$dir_avatares/avatar_" . ($i + 1) . '.png';
  $realpath = $file_system->realpath($uri);
  az_gera_avatar($realpath, $iniciais, $cor, $font_bold);
  $media = az_criar_media_imagem($uri, "$fname $lname");

  $node = Node::create([
    'type' => 'az_person',
    'title' => "$fname $lname",
    'field_az_fname' => $fname,
    'field_az_lname' => $lname,
    'field_az_titles' => [$titulo],
    'field_az_research_interests' => ['value' => $area, 'format' => 'plain_text'],
    'field_az_body' => [
      'value' => "<p>{$titulo} do Departamento de Física, com pesquisa na área de {$area}. Perfil de demonstração (dados fictícios).</p>",
      'format' => 'az_standard',
    ],
    'field_az_media_image' => ['target_id' => $media->id()],
    'field_az_person_category' => $docente_tid ? [['target_id' => $docente_tid]] : [],
    'status' => 1,
  ]);
  $node->save();
  echo "Professor criado: $fname $lname (nid={$node->id()})\n";
}

echo "Concluído.\n";
