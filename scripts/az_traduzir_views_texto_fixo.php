<?php

/**
 * Corrige texto fixo em inglês que vem direto da config das views
 * (az_news/az_events), não do sistema de tradução de interface - por isso
 * o pacote de tradução pt-br nunca alcança:
 *
 * - O link "Read all news"/"View all events..." no rodapé dos blocos de
 *   notícias/eventos (um "text_custom" com HTML fixo, exportado em inglês
 *   no config/install do módulo).
 * - Os botões "Apply"/"Reset"/"Sort by" do formulário de filtro exposto
 *   do calendário de eventos (default de config do plugin de exposed
 *   form, também congelado em inglês no config exportado).
 *
 * Reutilizável: drush --uri=... scr scripts/az_traduzir_views_texto_fixo.php
 * Idempotente - só troca se ainda estiver em inglês.
 */

$substituicoes_texto = [
  'Read all news' => 'Ver todas as notícias',
  'View all events on the calendar' => 'Ver todos os eventos no calendário',
  'View all events' => 'Ver todos os eventos',
];

$substituicoes_exposed_form = [
  'submit_button' => ['Apply' => 'Aplicar'],
  'reset_button_label' => ['Reset' => 'Limpar'],
  'exposed_sorts_label' => ['Sort by' => 'Ordenar por'],
];

foreach (['views.view.az_news', 'views.view.az_events'] as $view_name) {
  $config = \Drupal::configFactory()->getEditable($view_name);
  if ($config->isNew()) {
    echo "$view_name não existe neste site - pulado.\n";
    continue;
  }
  $mudou = FALSE;
  foreach ($config->get('display') as $display_id => $display) {
    // Blocos de texto fixo (footer/header) tipo "Read all news".
    foreach (['footer', 'header'] as $area) {
      $key = "display.$display_id.display_options.$area.area_text_custom.content";
      $content = $config->get($key);
      if ($content !== NULL) {
        $novo = $content;
        foreach ($substituicoes_texto as $en => $pt) {
          $novo = str_replace($en, $pt, $novo);
        }
        if ($novo !== $content) {
          $config->set($key, $novo);
          $mudou = TRUE;
          echo "$view_name/$display_id/$area: texto traduzido.\n";
        }
      }
    }
    // Botoes do formulario de filtro exposto.
    foreach ($substituicoes_exposed_form as $campo => $pares) {
      $key = "display.$display_id.display_options.exposed_form.options.$campo";
      $valor = $config->get($key);
      if ($valor !== NULL && isset($pares[$valor])) {
        $config->set($key, $pares[$valor]);
        $mudou = TRUE;
        echo "$view_name/$display_id: $campo '$valor' -> '{$pares[$valor]}'.\n";
      }
    }
  }
  if ($mudou) {
    $config->save();
  }
  else {
    echo "$view_name: nada em inglês encontrado (ou já traduzido).\n";
  }
}

echo "Concluído. Rode 'drush cache:rebuild' em seguida.\n";
