<?php

namespace Drupal\glazed_builder\Controller;

use Drupal\Core\Controller\ControllerBase;

interface PageControllerInterface {

  /**
   * Page controller for the glazed builder configuration page
   *
   * @return array
   *   A render array representing the page, and containing the configuration form
   */
  public function configPage();

  /**
   * Page controller for the glazed builder paths page
   *
   * @return array
   *   A render array representing the page, and containing the paths form
   */
  public function pathsPage();
}
