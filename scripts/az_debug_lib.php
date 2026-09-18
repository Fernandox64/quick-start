<?php

$discovery = \Drupal::service('library.discovery');
$lib = $discovery->getLibraryByName('az_ufop_departamento', 'tema');
echo "Library encontrada: " . ($lib ? 'sim' : 'nao') . "\n";
if ($lib) {
  print_r($lib);
}

$moduleHandler = \Drupal::moduleHandler();
echo "Modulo carregado: " . ($moduleHandler->moduleExists('az_ufop_departamento') ? 'sim' : 'nao') . "\n";

$implementations = $moduleHandler->getImplementations('page_attachments');
echo "Implementacoes de hook_page_attachments: " . implode(', ', $implementations) . "\n";
