<?php

namespace Drupal\az_ufop_departamento\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Página /tutorial: guia de edição do site pra quem administra o conteúdo.
 *
 * Acesso público (ver az_ufop_departamento.routing.yml) - de propósito, pra
 * ser fácil de mostrar/compartilhar o link sem precisar logar antes.
 */
class TutorialController extends ControllerBase {

  public function view(): array {
    return [
      '#theme' => 'az_ufop_tutorial',
      '#attached' => [
        'library' => ['az_ufop_departamento/tutorial'],
      ],
    ];
  }

}
