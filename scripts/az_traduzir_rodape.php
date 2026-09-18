<?php

/**
 * Ajusta o rodape do tema az_barrio: "land_acknowledgment" e
 * "info_security_privacy" sao textos fixos em ingles especificos da
 * University of Arizona/Tucson (reconhecimento de terras indigenas locais,
 * link para politica de privacidade da arizona.edu) - nao fazem sentido
 * traduzidos, ja que o conteudo em si nao se aplica a UFOP. Desliga os dois
 * (sao apenas toggles no tema, sem texto alternativo) e usa o ponto de
 * customizacao nativo do tema (copyright_notice) para um aviso em
 * portugues.
 */

$config = \Drupal::configFactory()->getEditable('az_barrio.settings');
$config->set('land_acknowledgment', FALSE);
$config->set('info_security_privacy', FALSE);
$config->set('copyright_notice', 'Departamento Modelo, Universidade Federal de Ouro Preto (UFOP). Site de demonstração.');
$config->save();

echo "Rodape do az_barrio atualizado.\n";
