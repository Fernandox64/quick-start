<?php

/**
 * Mais conteudo de demonstracao pro site DFIS: mais noticias, mais
 * professores e eventos (que ainda nao existiam nesse site).
 */

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

$font_bold = '/usr/share/fonts/opentype/urw-base35/NimbusSans-Bold.otf';

$paleta = [
  [139, 0, 21], [12, 35, 75], [0, 90, 90], [90, 60, 10],
  [60, 20, 90], [20, 90, 40], [130, 60, 0], [40, 40, 90],
];

function az2_gera_banner(string $caminho, string $texto, array $cor, string $font_bold): void {
  $w = 1200;
  $h = 630;
  $img = imagecreatetruecolor($w, $h);
  [$r, $g, $b] = $cor;
  for ($y = 0; $y < $h; $y++) {
    $t = $y / $h;
    $linha = imagecolorallocate($img, (int) ($r * (1 - $t * 0.5)), (int) ($g * (1 - $t * 0.5)), (int) ($b * (1 - $t * 0.5)));
    imageline($img, 0, $y, $w, $y, $linha);
  }
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

function az2_gera_avatar(string $caminho, string $iniciais, array $cor, string $font_bold): void {
  $s = 400;
  $img = imagecreatetruecolor($s, $s);
  [$r, $g, $b] = $cor;
  imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
  $branco = imagecolorallocate($img, 255, 255, 255);
  $bbox = imagettfbbox(120, 0, $font_bold, $iniciais);
  $tw = $bbox[2] - $bbox[0];
  $th = $bbox[1] - $bbox[7];
  imagettftext($img, 120, 0, (int) (($s - $tw) / 2), (int) (($s + $th) / 2), $branco, $font_bold, $iniciais);
  imagepng($img, $caminho);
  imagedestroy($img);
}

function az2_media(string $uri, string $nome): Media {
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

$fs = \Drupal::service('file_system');
$dir_n = 'public://imported/dfis-noticias';
$dir_a = 'public://imported/dfis-avatares';
$fs->prepareDirectory($dir_n, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
$fs->prepareDirectory($dir_a, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

// ---- Mais noticias (continuando a numeracao dos banners existentes) ----
$mais_noticias = [
  'Departamento inicia ciclo de seminários de física quântica',
  'Grupo de extensão leva experimentos de física a escolas públicas',
  'Convocação para eleição de coordenação do departamento',
  'Resultado do processo seletivo de mestrado é divulgado',
  'Professores participam de congresso internacional de física',
  'Departamento firma convênio com centro de pesquisa nacional',
  'Nova disciplina optativa de física computacional é aberta',
];

$hoje = new DateTime();
$n_existentes = 8;
foreach ($mais_noticias as $i => $titulo) {
  $idx = $n_existentes + $i + 1;
  $cor = $paleta[$idx % count($paleta)];
  $uri = "$dir_n/banner_$idx.jpg";
  az2_gera_banner($fs->realpath($uri), 'DFIS', $cor, $font_bold);
  $media = az2_media($uri, $titulo);
  $data = (clone $hoje)->modify('-' . ($idx * 2) . ' days');

  $node = Node::create([
    'type' => 'az_news',
    'title' => $titulo,
    'field_az_summary' => ['value' => 'Notícia de demonstração do site do Departamento de Física.', 'format' => 'plain_text'],
    'field_az_body' => ['value' => '<p>Texto de demonstração - conteúdo genérico gerado para o teste de multisite.</p>', 'format' => 'az_standard'],
    'field_az_media_image' => ['target_id' => $media->id()],
    'field_az_published' => ['value' => $data->format('Y-m-d')],
    'created' => $data->getTimestamp(),
    'status' => 1,
  ]);
  $node->save();
  echo "Notícia criada: $titulo (nid={$node->id()})\n";
}

// ---- Mais professores ----
$docente_tid = NULL;
$termos = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'az_person_categories', 'name' => 'Docente']);
if ($termos) {
  $docente_tid = reset($termos)->id();
}

$mais_professores = [
  ['Gustavo', 'Pinheiro Araújo', 'Professor Adjunto', 'Física Estatística'],
  ['Renata', 'Carvalho Duarte', 'Professora Titular', 'Física Computacional'],
  ['Felipe', 'Moreira Castro', 'Professor Associado', 'Física do Estado Sólido'],
  ['Vanessa', 'Lopes Andrade', 'Professora Adjunta', 'Física Médica'],
];
$p_existentes = 6;
foreach ($mais_professores as $i => [$fname, $lname, $titulo, $area]) {
  $idx = $p_existentes + $i + 1;
  $iniciais = mb_strtoupper(mb_substr($fname, 0, 1) . mb_substr($lname, 0, 1));
  $cor = $paleta[($idx + 2) % count($paleta)];
  $uri = "$dir_a/avatar_$idx.png";
  az2_gera_avatar($fs->realpath($uri), $iniciais, $cor, $font_bold);
  $media = az2_media($uri, "$fname $lname");

  $node = Node::create([
    'type' => 'az_person',
    'title' => "$fname $lname",
    'field_az_fname' => $fname,
    'field_az_lname' => $lname,
    'field_az_titles' => [$titulo],
    'field_az_research_interests' => ['value' => $area, 'format' => 'plain_text'],
    'field_az_body' => ['value' => "<p>{$titulo} do Departamento de Física, com pesquisa na área de {$area}. Perfil de demonstração (dados fictícios).</p>", 'format' => 'az_standard'],
    'field_az_media_image' => ['target_id' => $media->id()],
    'field_az_person_category' => $docente_tid ? [['target_id' => $docente_tid]] : [],
    'status' => 1,
  ]);
  $node->save();
  echo "Professor criado: $fname $lname (nid={$node->id()})\n";
}

// ---- Eventos ----
$eventos = [
  ['Semana Acadêmica de Física 2026', 10, 9, 12],
  ['Seminário de Física de Partículas', 20, 14, 16],
  ['Defesa de dissertação de mestrado', 27, 10, 11],
  ['Oficina de física experimental para calouros', 35, 9, 17],
  ['Colóquio do Departamento de Física', 45, 15, 16],
];

foreach ($eventos as [$titulo, $dias, $hi, $hf]) {
  $inicio = (clone $hoje)->modify("+$dias days")->setTime($hi, 0);
  $fim = (clone $hoje)->modify("+$dias days")->setTime($hf, 0);

  $node = Node::create([
    'type' => 'az_event',
    'title' => $titulo,
    'field_az_summary' => ['value' => 'Evento de demonstração do Departamento de Física.', 'format' => 'plain_text'],
    'field_az_body' => ['value' => '<p>Descrição de demonstração - conteúdo genérico gerado para o teste de multisite.</p>', 'format' => 'az_standard'],
    'field_az_event_date' => [[
      'value' => $inicio->getTimestamp(),
      'end_value' => $fim->getTimestamp(),
      'duration' => ($hf - $hi) * 60,
      'timezone' => 'America/Sao_Paulo',
    ]],
    'status' => 1,
  ]);
  $node->save();
  echo "Evento criado: $titulo (nid={$node->id()})\n";
}

echo "Concluído.\n";
