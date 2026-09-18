<?php

/**
 * Corrige a pagina de Contato (nid 1002), que ficou vazia porque os dados
 * de contato nao entraram na exportacao original. Usa os mesmos valores
 * padrao definidos em App\Support\ContentDefaults::contato() no site Laravel.
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$contato = [
  'titulo' => 'Fale Conosco',
  'texto_intro' => 'Entre em contato com o departamento pelos canais abaixo ou envie uma mensagem.',
  'endereco' => 'Endereço do departamento, Cidade - UF',
  'telefone_1' => '(00) 0000-0000',
  'telefone_2' => '',
  'email_1' => 'contato@departamento.br',
  'email_2' => '',
];

$contactParagraph = Paragraph::create([
  'type' => 'az_contact',
  'field_az_title' => $contato['titulo'],
  'field_az_email' => $contato['email_1'],
  'field_az_phone' => $contato['telefone_1'],
]);
$contactParagraph->save();

$enderecoParagraph = Paragraph::create([
  'type' => 'az_text',
  'field_az_text_area' => [
    'value' => '<p>' . htmlspecialchars($contato['texto_intro']) . '</p><p>' . htmlspecialchars($contato['endereco']) . '</p>',
    'format' => 'basic_html',
  ],
]);
$enderecoParagraph->save();

$node = Node::load(1002);
$node->set('field_az_main_content', [
  [
    'target_id' => $enderecoParagraph->id(),
    'target_revision_id' => $enderecoParagraph->getRevisionId(),
  ],
  [
    'target_id' => $contactParagraph->id(),
    'target_revision_id' => $contactParagraph->getRevisionId(),
  ],
]);
$node->save();

echo "Página Contato (nid 1002) corrigida.\n";
