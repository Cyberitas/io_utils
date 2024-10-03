<?php

namespace Drupal\io_utils\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class SearchReplaceHelpController extends ControllerBase {

//  public function content() {
//    $module_path = drupal_get_path('module', 'io_utils');
//    $help_content = file_get_contents($module_path . '/help/search-replace-help.html');
//    return [
//      '#type' => 'markup',
//      '#markup' => $help_content,
//      '#allowed_tags' => ['style', 'h1', 'h2', 'h3', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a'],
//    ];
//  }


  public function content() {
    $module_path = drupal_get_path('module', 'io_utils');
    $help_content = file_get_contents($module_path . '/help/search-replace-help.html');

    return new Response($help_content, 200, ['Content-Type' => 'text/html']);
  }
}
