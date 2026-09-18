<?php

namespace Drupal\az_ufop_departamento\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Carrossel de imagens na home, conectado direto as Noticias/Editais mais
 * recentes (com imagem) - cada slide leva para a publicacao completa.
 */
#[Block(
  id: 'az_ufop_noticias_carousel',
  admin_label: new TranslatableMarkup('Carrossel de Notícias (UFOP)'),
)]
class NoticiasCarouselBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  public function build(): array {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $fileUrlGenerator = \Drupal::service('file_url_generator');

    $nids = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'az_news')
      ->condition('status', 1)
      ->exists('field_az_media_thumbnail_image')
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE)
      ->execute();

    if (empty($nids)) {
      return [];
    }

    $slides = [];
    foreach ($nodeStorage->loadMultiple($nids) as $node) {
      $media = $node->get('field_az_media_thumbnail_image')->entity;
      if (!$media || !$media->hasField('field_media_az_image')) {
        continue;
      }
      $file = $media->get('field_media_az_image')->entity;
      if (!$file) {
        continue;
      }

      $summary = '';
      if ($node->hasField('field_az_summary') && !$node->get('field_az_summary')->isEmpty()) {
        $summary = $node->get('field_az_summary')->value;
      }

      $slides[] = [
        'title' => $node->label(),
        'summary' => $summary,
        'url' => $node->toUrl()->toString(),
        'image_url' => $fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
      ];
    }

    if (empty($slides)) {
      return [];
    }

    return [
      '#theme' => 'az_ufop_noticias_carousel',
      '#slides' => $slides,
      '#attached' => [
        'library' => ['az_ufop_departamento/noticias-carousel'],
      ],
      '#cache' => [
        'max-age' => 300,
        'tags' => Cache::mergeTags(['node_list:az_news'], []),
        'contexts' => ['url.path'],
      ],
    ];
  }

}
