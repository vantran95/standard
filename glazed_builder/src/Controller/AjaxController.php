<?php

namespace Drupal\glazed_builder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\glazed_builder\Service\GlazedBuilderServiceInterface;
use Drupal\glazed_builder\Service\UploadHandler;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use Drupal\Core\Access\CsrfTokenGenerator;

class AjaxController extends ControllerBase implements AjaxControllerInterface {

  /**
   * The current user
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler service
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager service
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The asset resolver service
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
   protected $assetResolver;

  /**
   * The CSS asset collection renderer
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssAssetCollectionRenderer;

  /**
   * The JS asset collection renderer
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsAssetCollectionRenderer;

  /**
   * The entity type bundle info manager
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfoManager;

  /**
   * The glazed builder service
   *
   * @var \Drupal\glazed_builder\Service\GlazedBuilderServiceInterface
   */
  protected $glazedBuilderService;

  /**
   * The CSRF token generator service
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a GlazedBuilderService object
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The Drupal file system
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @ param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   * @param \Drupal\glazed_builder\Service\Handler\BlockHandlerInterface $glazedBlockHandler
   *   The glazed builder block handler service
   * @param \Drupal\glazed_builder\Service\Handler\ViewHandlerInterface $glazedViewHandler
   *   The glazed builder view handler service
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache service;
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager service
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service
   */

  /**
   * Construct an AjaxController object
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service
   * @param \\Drupal\Core\Render\AttachmentsResponseProcessorInterface $attachmentsResponseProcessor
   *   The attachments response processor service
   * @param \Drupal\Core\Asset\AssetResolverInterface $assetResolver
   *   The asset resolver service
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $cssAssetCollectionRenderer
   *   The CSS asset collection renderer
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $jsAssetCollectionRenderer
   *   The JS asset collection renderer
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfoManager
   *   The entity type bundle info manager
   * @param \Drupal\glazed_builder\Service\GlazedBuilderServiceInterface $glazedBuilderService
   *   The glazed builder service
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service
   */
  public function __construct(AccountProxyInterface $currentUser, ModuleHandlerInterface $moduleHandler, Connection $database, RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, AssetResolverInterface $assetResolver, AssetCollectionRendererInterface $cssAssetCollectionRenderer, AssetCollectionRendererInterface $jsAssetCollectionRenderer, EntityTypeBundleInfoInterface $entityTypeBundleInfoManager, GlazedBuilderServiceInterface $glazedBuilderService, CsrfTokenGenerator $csrfToken) {
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->database = $database;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->assetResolver = $assetResolver;
    $this->cssAssetCollectionRenderer = $cssAssetCollectionRenderer;
    $this->jsAssetCollectionRenderer = $jsAssetCollectionRenderer;
    $this->entityTypeBundleInfoManager = $entityTypeBundleInfoManager;
    $this->glazedBuilderService = $glazedBuilderService;
    $this->csrfToken = $csrfToken;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('asset.resolver'),
      $container->get('asset.css.collection_renderer'),
      $container->get('asset.js.collection_renderer'),
      $container->get('entity_type.bundle.info'),
      $container->get('glazed_builder.service'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxRefresh() {
    $url = Url::fromRoute('glazed_builder.ajax_callback');

    // Check if request related to enterprise.
    $enterprise = FALSE;
    if (isset($_GET['enterprise']) && $_GET['enterprise'] == 'true') {
      $enterprise = TRUE;
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($enterprise && $moduleHandler->moduleExists('glazed_builder_e')) {
      $url = Url::fromRoute('glazed_builder_e.ajax_callback');
    } elseif ($enterprise && !$moduleHandler->moduleExists('glazed_builder_e')) {
      throw new \Exception(t('The Glazed Builder Enterprise module doesn\'t exist or disabled.'));
    }

    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
    // return new JsonResponse();
    // return new Response($this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY), 200, ['Content-Type' => 'text/plain']);
    return new JsonResponse($url->toSTring());
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback() {
    $action = isset($_POST['action']) ? $_POST['action'] : FALSE;

    switch ($action) {
      // Determine if the current user has 'edit via glazed builder' permission
      case 'glazed_login':
        return $this->hasEditAccess();

        break;
      // Determine if the current user has 'edit via glazed builder' permission
      case 'glazed_builder_csrf':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $url = Url::fromRoute('glazed_builder.ajax_callback');
          $token = $this->csrfToken->get($url->getInternalPath());
          $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
          return new JsonResponse($url->toSTring());
        }

        break;

      // Get a list of glazed builder container types
      case 'glazed_get_container_types':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          return $this->getContainerTypes();
        }

        break;

      // Get a list of glazed builder container names
      case 'glazed_get_container_names':
        if ($this->currentUser->hasPermission('edit via glazed builder') && !empty($_POST['container_type'])) {
          $type = explode('|', $_POST['container_type']);
          $entity_type = $type[0];
          $bundle = $type[1];

          return $this->getContainerNames($entity_type, $bundle);
        }

        return new JsonResponse('');

        break;

      // Save a glazed builder container
      case 'glazed_save_container':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $type = explode('|', $_POST['type']);
          $entity_type = $type[0];
          $bundle = $type[1];
          $name = explode('|', $_POST['name']);
          if (count($name) > 2 && is_numeric($name[1])) {
            $entity_id = $name[0];
            $revision_id = $name[1];
            $field_name = $name[2];
          }
          else {
            $entity_id = $name[0];
            $field_name = $name[1];
            $revision_id = null;
          }
          $encoded_html = $_POST['shortcode'];
          if (isset($_POST['lang'])) {
            $langcode = $_POST['lang'];
          }
          else {
            $langcode = \Drupal::languageManager()
              ->getDefaultLanguage()
              ->getId();
          }

          return $this->saveContainer($entity_type, $bundle, $entity_id, $revision_id, $field_name, $encoded_html, $langcode);
        }

        break;

      // Load a glazed builder container
      case 'glazed_load_container':
        if ($_POST['type'] != 'block') {
          if (empty($_POST['type']) || empty($_POST['name'])) {
            new JsonResponse('');
          }
          $type = explode('|', $_POST['type']);
          $entity_type = $type[0];
          $bundle = $type[1];
          $name = explode('|', $_POST['name']);
          $id = $name[0];
          $field_name = $name[1];

          $langcode = $langcode = \Drupal::languageManager()
            ->getDefaultLanguage()
            ->getId();

          if (isset($name[2])) {
            $langcode = $name[2];
          }

          return new HtmlResponse($this->loadContainer($entity_type, $id, $field_name, $langcode));
        }

        break;

      // Get a list of block and view names
      case 'glazed_builder_get_cms_element_names':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          return $this->getCmsElementNames();
        }

        break;

      // Get settings for various CMS elements
      case 'glazed_get_cms_element_settings':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $name = explode('-', $_POST['name']);
          $type = $name[0];
          if ($name[0] == 'view') {
            $view_id = $name[1];
            $display_id = $name[2];
            $data = $this->getViewSettings($view_id, $display_id);
            return  new Response(render($data));
          }

          $this->moduleHandler->invokeAll('glazed_cms_element_settings', [$_POST['name']]);
        }

        return new AjaxResponse('');

        break;

      // Load a given CMS element
      case 'glazed_builder_load_cms_element':
        $name = $_POST['name'];
        $element_info = $this->glazedBuilderService->parseStringForCmsElementInfo($name);
        $settings = $_POST['settings'];
        $data = $_POST['data'];
        $assets = new AttachedAssets();
        $html = $this->loadCmsElement($element_info, $settings, $data, $assets);
        $css_assets = $this->assetResolver->getCssAssets($assets, TRUE);
        $css = $this->cssAssetCollectionRenderer->render($css_assets);

        $js_assets = $this->assetResolver->getJsAssets($assets, TRUE);
        $js = '';
        $settings = '';
        foreach($js_assets as $js_asset) {
          $render = $this->jsAssetCollectionRenderer->render($js_asset);
          if(count($render)) {
            foreach ($render as $script) {
              if (isset($script['#attributes']['type']) && $script['#attributes']['type'] == 'application/json') {
                $settings = json_decode($script['#value']);
              }
              else {
                $rendered = render($script);
                if($rendered) {
                  $js .= $rendered->__toString();
                }
              }
            }
          }
        }

        $response = [
          'data' => $html,
          'css' => count($css) ? render($css)->__toString() : '',
          'js' => $js,
          'settings' => $settings,
        ];

        return new JsonResponse($response);

        break;

      // Get the templates for a given page
      case 'glazed_get_page_templates':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          return $this->getPageTemplates();
        }

        return new JsonResponse('');

        break;

      // Load a given template
      case 'glazed_load_page_template':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $uuid = $_POST['uuid'];

          return new HtmlResponse($this->loadPageTemplate($uuid));
        }

        break;

      // Get the templates for the current user
      case 'glazed_get_templates':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          return $this->getUserTemplates();
        }

        break;

      // Load a template for the current user
      case 'glazed_load_template':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $template = $_POST['name'];

          return new HtmlResponse($this->loadUserTemplate($template));
        }

        break;

      // Save a template for the current user
      case 'glazed_save_template':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $name = $_POST['name'];
          $template = $_POST['template'];

          $this->saveUserTemplate($name, $template);
          return new JsonResponse('');
        }


        break;

      // Delete a template for the current user
      case 'glazed_delete_template':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $template_name = $_POST['name'];

          return $this->deleteUserTemplate($template_name);
        }

        break;

      // Accept an array of fids and return comma-separated image URLs
      case 'glazed_builder_get_image_urls':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $fileIds = $_POST['fileIds'];
          $imageStyle = $_POST['imageStyle'];

          return new HtmlResponse($this->getImageUrl($fileIds, $imageStyle));
        }

        break;

      case 'glazed_builder_get_image_style_url':
        if ($this->currentUser->hasPermission('edit via glazed builder')) {
          $imageStyle = $_POST['imageStyle'];
          $fileId = $_POST['fileId'];
          $fileEntity = \Drupal::entityTypeManager()
            ->getStorage('file')
            ->load($fileId);
          $fileUri = $fileEntity->getFileUri();

          $isSvg = strpos($fileUri, '.svg');
          if ($imageStyle !== 'original' && !$isSvg) {
            $image_style = ImageStyle::load($imageStyle);

            return new HtmlResponse(file_url_transform_relative($image_style->buildUrl($fileUri)) . '?fid=' . $fileId);
          }
          else {
            return new HtmlResponse(file_url_transform_relative(file_create_url($fileUri)) . '?fid=' . $fileId);
          }

        }

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fileUpload() {
    $upload_handler = new UploadHandler(array(
        'accept_file_types' => '/\.(gif|jpe?g|png|svg)$/i'
    ));

    return new Response('');
  }

  /**
   * Get the base URL of the current request
   */
  private function getBase() {
    $current_request = $this->requestStack->getCurrentRequest();
    return $current_request->getSchemeAndHttpHost() . $current_request->getBasePath();
  }

  /**
   * Determine if user has access to edit with the glazed builder
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function hasEditAccess() {
    return new JsonResponse($this->currentUser->hasPermission('edit via glazed builder'));
  }

  /**
   * Callback to get container types
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getContainerTypes() {
    // Lists fields that use Glazed Builder as default field formatter, used in Glazed Container element
    $container_types = [];

    $entity_definitions = $this->entityTypeManager->getDefinitions();
    foreach (array_keys($entity_definitions) as $entity_type) {
      // Only act on fieldable entity types
      if ($entity_definitions[$entity_type]->get('field_ui_base_route')) {
        $bundle_info = $this->entityTypeBundleInfoManager->getBundleInfo($entity_type);
        if ($bundle_info) {
          foreach (array_keys($bundle_info) as $bundle) {
            $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
            foreach ($fields as $field_id => $field_info) {
              $view_display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
              if ($view_display) {
                $renderer = $view_display->getRenderer($field_id);
                if ($renderer && $renderer->getBaseId() == 'glazed_builder_text') {
                  $container_types[$entity_type . '|' . $bundle] = $entity_type . ' - ' . $bundle;
                }
              }
            }
          }
        }
      }
    }

    return new JsonResponse($container_types);
  }

  /**
   * Callback to get container types
   *
   * @param string $entityType
   *   The type of entity to check
   * @param string $bundle
   *   The bundle of the given entity
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getContainerNames($entityType, $bundle) {
    // Lists field instances that use Glazed Builder as default field formatter, used in Glazed Container element

    // Get the display for the bundle
    $display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entityType . '.' . $bundle . '.default');

    $fields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    // Loop through each of the fields
    foreach ($fields as $field) {
      // Get the formatter for the field
      $renderer = $display->getRenderer($field->getName());
      if ($renderer) {
        // Check to see if the formatter is glazed_builder_text
        if ($renderer->getBaseId() == 'glazed_builder_text') {
          $glazed_fields[$field->getName()] = $field->getLabel();
        }
      }
    }

    $query = $this->entityTypeManager->getStorage($entityType)->getQuery();
    $entity_ids = $query->condition('type', $bundle)
      ->execute();
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple($entity_ids);
    foreach ($entities as $entity_id => $entity) {
      // return the translated entites if exist.
      if ($entity->isTranslatable()) {
        foreach ($glazed_fields as $field_name => $field_label) {
          $languages = $entity->getTranslationLanguages();
          foreach ($languages as $langcode => $language) {
            $translatedEntity = $entity->getTranslation($langcode);
            $container_names[$translatedEntity->id() . '|' . $field_name . '|' . $langcode] = $translatedEntity->label() . '|' . $field_label;
          }
        }
      } else {
        foreach ($glazed_fields as $field_name => $field_label) {
          $container_names[$entity_id . '|' . $field_name] = $entity->label() . '|' . $field_label;
        }
      }
    }

    return new JsonResponse($container_names);
  }

  /**
   * Saves a new container
   *
   * @param string $entityType
   *   The type of entity
   * @param string $bundle
   *   The type of bundle
   * @param $entityId
   *   The entity ID
   * @param $fieldName
   *   The field name
   * @param string $encodedHtml
   *   The html to be decoded
   *  @param string $langcode
   *   The language of the entity to be saved
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function saveContainer($entityType, $bundle, $entityId, $revisionId, $fieldName, $encodedHtml, $langcode) {
    // Saves Glazed Builder container instance to field, respecting permissions, language and revisions if supported
    $revisionableEntity = $this->entityTypeManager->getStorage($entityType)->getEntityType()->isRevisionable();
    if ($revisionableEntity && isset($revisionId)) {
      $entity = $this->entityTypeManager->getStorage($entityType)->loadRevision($revisionId);
    }
    else {
      $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);
    }
    // Load translation if available.
    if (isset($entity) && $entity->isTranslatable()) {
      $languages = $entity->getTranslationLanguages();
      if (isset($languages[$langcode])) {
        $entity = $entity->getTranslation($langcode);
      }
    }

    if ($entity && $entity->access('update', $this->currentUser)) {
      $decoded_short_code = rawurldecode($this->decodeData($encodedHtml));

      $field_values = $entity->get($fieldName)->getValue();
      $field_value = $field_values[0];
      $field_value['value'] = $decoded_short_code;

      $entity->get($fieldName)->set(0, $field_value);

      // Check if the entity type supports revisions.
      if ($revisionableEntity) {
        $entity->setNewRevision();
        $entity->isDefaultRevision(TRUE);
        if ($entity instanceof RevisionLogInterface) {
          // If a new revision is created, save the current user as
          // revision author.
          $entity->setRevisionUserId($this->currentUser->id());
          $entity->setRevisionLogMessage('Saved with Glazed builder');
          $entity->setRevisionCreationTime($this->getRequestTime());
        }
      }
      // Check if enterprise module exists.
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('glazed_builder_e')) {
        // Delete locked content.
        $content_lock = \Drupal::service('glazed_builder_e.content_lock');
        $content_lock->deleteLockedContent($entityId, $revisionId, $entityType, $langcode);
      }
      // Save entity.
      $entity->save();

      return new JsonResponse('');
    }
  }

  /**
   * Loads Glazed Builder field content
   *
   * @param string $entityType
   *   The type of entity
   * @param $entity_id
   *   The ID of the entity to be saved
   * @param string $fieldName
   *   The name of the field to return
   *
   * @return string
   */
  private function loadContainer($entityType, $entityId, $fieldName, $langcode) {
    $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);
    if ($entity->isTranslatable()) {
      $entity = $entity->getTranslation($langcode);
    }
    $field_data = $entity->get($fieldName)->value;
    $this->glazedBuilderService->replaceBaseTokens($field_data);

    return $field_data;
  }

  /**
   * Get a list of Drupal blocks and views displays, used in Glazed Builder elements modal
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getCmsElementNames() {
    return new JsonResponse($this->glazedbuilderService->getCmsElementNames());
  }

  /**
   * Get the element settings for the given view
   *
   * @param string $viewId
   *   The ID of the view
   * @param string $displayId
   *   The ID of the display for the given view
   *
   * @return array
   *   The settings for the given view
   */
  private function getViewSettings($viewId, $displayId) {
    // Fetches settings for views display element settings modal
    $executable_view = Views::getView($viewId);
    $executable_view->setDisplay($displayId);
    $executable_view->initHandlers();
    $executable_view->build();

    return $executable_view->exposed_widgets;
  }

  /**
   * Loads settings for a CMS element - often views
   *
   * @param array $element_info
   *   An array of info regarding the element to be returned
   * @param array $settings
   *   Settings for the elmeent to be loaded
   * @param array $data
   *   Data on the element to be returned
   * @param Drupal\Core\Asset\AttachedAssets $assets
   *   Any assets for the found element will be attached to this element
   */
  private function loadCmsElement($element_info, $settings, $data, AttachedAssets $assets) {
    // Loads Drupal block or views display
    return $this->glazedBuilderService->loadCmsElement($element_info, $settings, $data, $assets);
  }

  /**
   * Get the list of page templates for the glazed builder
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getPageTemplates() {
    // this refers to the templates you see when you click "CHOOSE A TEMPLATE" on an empty Glazed Builder container
    $result = $this->database->select('glazed_page_templates', 't')
      ->fields('t', array('uuid', 'title', 'module', 'category', 'image'))
      ->orderBy('weight', 'ASC')
      ->execute();

    $templates = array();
    while ($template = $result->fetchAssoc()) {
      $templates[] = array(
        'title' => $template['title'],
        'uuid' => $template['uuid'],
        'module' => $template['module'],
        'category' => $template['category'],
        'image' => !(empty($template['image'])) ? $this->getBase() . '/' . drupal_get_path('module', $template['module']) . '/' . $template['image'] : $this->getBase() . '/' . drupal_get_path('module', 'glazed_builder') . '/' . 'images/glazed_templates/not-found.png',
      );
    }

    return new JsonResponse($templates);
  }

  /**
   * Load a glazed builder page template
   *
   * @param string $uuid
   *   The unique identifier for the page template to be loaded
   *
   * @return string
   *   The template for the page, with all tokens replaced with actual values
   */
  private function loadPageTemplate($uuid) {
    $template = $this->database->select('glazed_page_templates', 't')
      ->fields('t', array('template',))
      ->condition('t.uuid', $_POST['uuid'])
      ->execute()
      ->fetchField();

    $this->glazedBuilderService->replaceBaseTokens($template);

    return $template;
  }

  /**
   * Get the list of glazed builder templates for the current user
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getUserTemplates() {
    // this refers to the templates in the "Saved Templates" tab in the Glazed Builder elements modal
    $query = $this->database->select('glazed_user_templates', 't')
      ->fields('t', array('uid', 'name', 'global'));
    // Collect current user and global templates.
    $group = $query->orConditionGroup()
      ->condition('t.uid', $this->currentUser->id())
      ->condition('t.global', 1);
    $query->condition($group);

    $result = $query->execute();

    $templates = array();
    $i = 0;
    while ($template = $result->fetchAssoc()) {
      $templates[$i]['name'] = htmlspecialchars($template['name']);
      $templates[$i]['global'] = (bool) $template['global'];
      // Check if the current user is the author of template
      $current_user_is_author = FALSE;
      if ($this->currentUser->id() == $template['uid']) {
        $current_user_is_author = TRUE;
      }
      $templates[$i]['current_user_is_author'] = $current_user_is_author;
      $i ++;
    }

    return new JsonResponse($templates);
  }

  /**
   * Load the given template for the current user
   *
   * @param string $templateName
   *   The name of the template to be loaded
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function loadUserTemplate($templateName) {
    $template = $this->database->select('glazed_user_templates', 't')
      ->fields('t', array('template'))
      ->condition('t.name', $templateName)
      ->execute()
      ->fetchField();

    $this->glazedBuilderService->replaceBaseTokens($template);

    return $template;
  }

  /**
   * Save the given template for the current user
   *
   * @param string $templateName
   *   The name of the template to be saved
   * @param string $templateContents
   *   The contents of the template to be saved
   * @param boolean $templateGlobal
   *   The type of template is global or private.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function saveUserTemplate($templateName, $templateContents) {
    $this->database->insert('glazed_user_templates')
      ->fields(array(
        'uid' => $this->currentUser->id(),
        'name' => $templateName,
        'template' => $this->glazedBuilderService->insertBaseTokens($templateContents),
      ))
      ->execute();

    return new JsonResponse('');
  }

  /**
   * delete the given template for the current user
   *
   * @param string $templateName
   *   The name of the template to be deleted
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function deleteUserTemplate($templateName) {
    $this->database->delete('glazed_user_templates')
      ->condition('name', $templateName)
      ->condition('uid', $this->currentUser->id())
      ->execute();

    return new JsonResponse('');
  }

  /**
   * Creates image url from file ID
   *
   * @param string $templateName
   *   The name of the template to be loaded
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getImageUrl($fileIds, $imageStyle) {
    $images = [];
    foreach ($fileIds as $fid) {
      $file = \Drupal\file\Entity\File::load($fid);
      if ($imageStyle && $imageStyle != 'original') {
        $images[] = file_url_transform_relative(
          ImageStyle::load($imageStyle)->buildUrl($file->getFileUri())
        ) . '?fid=' . $fid;
      }
      else {
        $images[] = file_url_transform_relative(file_create_url($file->getFileUri())) . '?fid=' . $fid;
      }
    }

    return implode(',', $images);
  }

  /**
   * Decodes the given data
   *
   * @param string $encoded
   *   The encoded data
   *
   * @return string
   *   The decoded data
   */
  private function decodeData($encoded) {
    $decoded = "";
    for ($i = 0; $i < strlen($encoded); $i++) {
      $b = ord($encoded[$i]);
      $a = $b ^ 7;
      $decoded .= chr($a);
    }

    return $decoded;
  }

  private function getRequestTime() {
    return REQUEST_TIME;
  }
}
