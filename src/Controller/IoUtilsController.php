<?php

namespace Drupal\io_utils\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\io_utils\Form\SearchReplaceForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the IO Utils admin page.
 */
class IoUtilsController extends ControllerBase {

  /**
   * Displays the search and replace form.
   *
   * @return array
   *   A render array containing the search and replace form.
   */
  public function searchReplacePage() {
    $form = \Drupal::formBuilder()->getForm(SearchReplaceForm::class);
    return [
      '#title' => $this->t('Search and Replace'),
      'form' => $form,
    ];
  }
}
