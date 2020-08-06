<?php

namespace Drupal\glazed_builder\Controller;

interface AjaxControllerInterface{

  /**
   * AJAX CSRF refresh: Refreshes csrf token on the fly
   */
  public function ajaxRefresh();

  /**
   * AJAX callback: Handles various operations for frontend drag and drop builder
   */
  public function ajaxCallback();

  /**
   * Callback to handle AJAX file uploads
   */
  public function fileUpload();
}
