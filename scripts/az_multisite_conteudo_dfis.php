<?php

/**
 * Conteudo minimo de demonstracao - site DFIS (Departamento de Fisica),
 * parte do multisite de demonstracao.
 */

use Drupal\node\Entity\Node;

$sigla = 'DFIS';
$nome = 'Departamento de Física';

$noticias = [
  [
    'titulo' => "Bem-vindo ao site do $nome",
    'resumo' => "Site de demonstração do $nome, rodando em multisite Drupal - mesmo código do Departamento Modelo, banco de dados e conteúdo próprios.",
    'corpo' => "<p>Este é um site de demonstração criado para mostrar o Arizona Quickstart funcionando em <strong>multisite</strong>: o mesmo código-base (core, módulos, tema, e o módulo próprio az_ufop_departamento) atende a vários departamentos ao mesmo tempo, cada um com seu próprio banco de dados e conteúdo, sem duplicar a instalação.</p>",
  ],
  [
    'titulo' => "$sigla também usa o painel Notícias",
    'resumo' => "As mesmas funcionalidades do site principal (notícias, eventos, pessoal) estão disponíveis aqui, de forma independente.",
    'corpo' => "<p>Cada departamento administra seu próprio conteúdo de forma independente - editar uma notícia aqui não afeta o site do Departamento Modelo nem o de outros departamentos, mesmo compartilhando o mesmo código.</p>",
  ],
];

foreach ($noticias as $n) {
  $node = Node::create([
    'type' => 'az_news',
    'title' => $n['titulo'],
    'field_az_summary' => ['value' => $n['resumo'], 'format' => 'plain_text'],
    'field_az_body' => ['value' => $n['corpo'], 'format' => 'az_standard'],
    'field_az_published' => ['value' => date('Y-m-d')],
    'status' => 1,
  ]);
  $node->save();
  echo "Notícia criada: {$n['titulo']} (nid={$node->id()})\n";
}

\Drupal::configFactory()->getEditable('system.site')
  ->set('slogan', "$nome - demonstração de multisite Arizona Quickstart")
  ->save();

echo "Site '$nome' ($sigla) configurado.\n";
