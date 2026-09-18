<?php

/**
 * Desativa o link "Home" padrao que o proprio perfil az_quickstart define
 * no menu principal (az_quickstart.links.menu.yml), ja que agora existe o
 * "Início" (traduzido) apontando pro mesmo lugar - fica duplicado.
 */

$pluginId = 'az_quickstart.front_page';

/** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager */
$menuLinkManager = \Drupal::service('plugin.manager.menu.link');

$definition = $menuLinkManager->getDefinition($pluginId);
echo "Encontrado: {$definition['title']} -> enabled atual: {$definition['enabled']}\n";

$menuLinkManager->updateDefinition($pluginId, ['enabled' => FALSE]);

$menuLinkManager->resetDefinitions();
$novaDefinicao = $menuLinkManager->getDefinition($pluginId);
echo "Novo status enabled: {$novaDefinicao['enabled']}\n";
