<?php

namespace Drupal\az_ufop_departamento\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Página /tutorial: guia de edição do site pra quem administra o conteúdo.
 *
 * Restrita a administradores (ver az_ufop_departamento.routing.yml) - não é
 * conteúdo institucional, é documentação interna do painel.
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
