<?php

/**
 * Atualiza o texto de copyright do rodape pro padrao pedido, sem precisar
 * saber o nome do departamento de cada site na mao - pega do site_name.
 *
 * Roda em qualquer site: drush --uri=... scr scripts/az_fix_rodape_copyright.php
 */

$nome = \Drupal::configFactory()->get('system.site')->get('name');
// O site_name costuma vir como "Departamento X - UFOP", "Departamento X
// (via Laravel)" ou so "Departamento X" - tira esses sufixos.
$nome = preg_replace('/\s*-\s*UFOP$/i', '', $nome);
$nome = preg_replace('/\s*\(via[^)]*\)$/i', '', $nome);
$nome = trim($nome);

$config = \Drupal::configFactory()->getEditable('az_barrio.settings');
$config->set('copyright_notice', "$nome, Universidade Federal de Ouro Preto (UFOP). NTI-UFOP");
$config->save();

echo "Copyright atualizado: \"$nome, Universidade Federal de Ouro Preto (UFOP). NTI-UFOP\"\n";
