<?php

namespace Drupal\az_ufop_departamento\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rodape com os mesmos links do menu principal + redes sociais, no estilo
 * do site principal em Laravel (footer com "Links rapidos" e "Siga-nos").
 */
#[Block(
  id: 'az_ufop_footer_acesso_rapido',
  admin_label: new TranslatableMarkup('Acesso Rápido + Redes Sociais (rodapé UFOP)'),
)]
class FooterAcessoRapidoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MenuLinkTreeInterface $menuLinkTree,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
    );
  }

  public function build(): array {
    $params = new MenuTreeParameters();
    $params->setTopLevelOnly();
    $params->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load('main', $params);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $links = [];
    foreach ($tree as $item) {
      if (!$item->access->isAllowed() && !$item->access->isNeutral()) {
        continue;
      }
      $links[] = [
        'title' => $item->link->getTitle(),
        'url' => $item->link->getUrlObject()->toString(),
      ];
    }

    // Contas oficiais da UFOP (nao ha conta propria por departamento nesta
    // demonstracao). Sem YouTube: nao ha um canal institucional central
    // unico e confiavel da UFOP, so canais por departamento/orgao.
    $redes = [
      'facebook' => 'https://www.facebook.com/minhaUFOP/',
      'instagram' => 'https://www.instagram.com/minhaufop/',
      'twitter' => 'https://x.com/UFOP',
    ];

    return [
      '#theme' => 'az_ufop_footer_acesso_rapido',
      '#links' => $links,
      '#redes' => $redes,
      '#attached' => [
        'library' => ['az_ufop_departamento/tema'],
      ],
      '#cache' => [
        'max-age' => 3600,
        'tags' => ['config:system.menu.main'],
      ],
    ];
  }

}
