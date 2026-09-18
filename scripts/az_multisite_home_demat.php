<?php

/**
 * Cria uma home simples (com o hero no estilo UFOP ja usado no site
 * principal) para o site DEMAT e define como pagina inicial, no lugar da
 * tela padrao de instalacao do Drupal ("Welcome!").
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$nome = 'Departamento de Matemática';

$html = '<div class="az-ufop-hero az-ufop-hero-center">'
  . "<h2>$nome</h2>"
  . '<p>Site de demonstração rodando em multisite: mesmo código-base do Departamento Modelo, banco de dados e conteúdo independentes.</p>'
  . '<div class="az-ufop-hero-actions">'
  . '<a class="btn btn-light" href="/news">Ver notícias</a>'
  . '</div>'
  . '</div>';

$paragraph = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => ['value' => $html, 'format' => 'full_html'],
]);
$paragraph->save();

$node = Node::create([
  'type' => 'az_flexible_page',
  'title' => "Início - $nome",
  'field_az_main_content' => [
    ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()],
  ],
  'status' => 1,
]);
$node->save();

\Drupal::configFactory()->getEditable('system.site')
  ->set('page.front', '/node/' . $node->id())
  ->save();

echo "Home criada (nid={$node->id()}) e definida como pagina inicial.\n";
