<?php

/**
 * Troca o logo placeholder ("Name of Department") do tema az_barrio por um
 * logo de verdade com o nome do site, e ajusta o alt/title text.
 */

$config = \Drupal::configFactory()->getEditable('az_barrio.settings');

$logoPath = 'sites/default/files/logo-departamento.svg';

$config->set('logo.path', $logoPath);
$config->set('logo.use_default', FALSE);
$config->set('footer_logo_path', $logoPath);
$config->set('footer_default_logo', FALSE);
$config->save();

echo "Logo atualizado para: {$logoPath}\n";
