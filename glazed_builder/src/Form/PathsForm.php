<?php

namespace Drupal\glazed_builder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PathsForm extends ConfirmFormBase {

  const ITEMS_PER_BATCH = 10;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'glazed_builder_token_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to Convert paths?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This process will convert all paths stored by Glazed Builder to relative paths. We updated Glazed Builder to use relative paths to improve reliability in all hosting environments. It is recommended to run this batch process and update all your paths if you have content in your website that was last saved before updating to version 8.x-1.4.3. Please back-up your database before running this process.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Convert paths');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('glazed_builder.admin_root');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field_data = [];

    $entity_definitions = \Drupal::service('entity_type.manager')->getDefinitions();
    foreach (array_keys($entity_definitions) as $entity_type) {
      // Only act on fieldable entity types
      if ($entity_definitions[$entity_type]->get('field_ui_base_route')) {
        $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        if ($bundle_info) {
          foreach (array_keys($bundle_info) as $bundle) {
            $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
            foreach ($fields as $field_id => $field_info) {
              $view_display = \Drupal::service('entity_type.manager')->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
              if ($view_display) {
                $renderer = $view_display->getRenderer($field_id);
                if ($renderer && $renderer->getBaseId() == 'glazed_builder_text') {
                  $field_data[$entity_type . '|' . $bundle . '|' . $field_id] = [
                    'entity_type' => $entity_type,
                    'bundle' => $bundle,
                    'field' => $field_id,
                  ];
                }
              }
            }
          }
        }
      }
    }

    $batch = [
      'title' => $this->t('Expanding tokens'),
      'operations' => [],
      'progress_message' => static::t('Expanding tokens. Completed: @percentage% (@current of @total).'),
    ];

    foreach ($field_data as $field_info) {
      $batch['operations'][] = [[__CLASS__, 'expandTokens'], [$field_info]];
    }

    batch_set($batch);

    \Drupal::messenger()->addMessage($this->t('Paths were converted.'));
  }

  public static function expandTokens($field_info, &$context) {
    $fields = [];

    $iteration_count = isset($context['sandbox']['iteration']) ? $context['sandbox']['iteration'] : 0;
    $context['sandbox']['iteration'] = $iteration_count + 1;

    $entity_type_definition = \Drupal::entityTypeManager()->getDefinition($field_info['entity_type']);

    $query_base = \Drupal::database()->select($entity_type_definition->getBaseTable(), 'entity_table')
      ->fields('entity_table', array($entity_type_definition->getKey('id')))
      ->condition('entity_table.' . $entity_type_definition->getKey('bundle'), $field_info['bundle']);

    if (!isset($context['sandbox']['total'])) {
      $count_query = clone $query_base;
      $context['sandbox']['total'] = $count_query->countQuery()->execute()->fetchField();
      $context['sandbox']['progress'] = 0;
    }

    if ($context['sandbox']['total']) {
      $entity_ids = $query_base->range($iteration_count * self::ITEMS_PER_BATCH, self::ITEMS_PER_BATCH)
        ->orderBy($entity_type_definition->getKey('id'))
        ->execute()
        ->fetchCol();

      $storage = \Drupal::entityTypeManager()->getStorage($field_info['entity_type']);
      $entities = $storage->loadMultiple($entity_ids);

      $glazed_service = \Drupal::service('glazed_builder.service');
      foreach($entities as $entity) {
        $context['sandbox']['progress']++;
        $languages = $entity->getTranslationLanguages();
        foreach ($languages as $language) {
          $translated_entity = $entity->getTranslation($language->getId());
          $content = $translated_entity->get($field_info['field'])->value;
          $glazed_service->replaceBaseTokens($content);
          $translated_entity->get($field_info['field'])->set(0, $content);
          $translated_entity->save();
        }
      }

      if($context['sandbox']['progress'] < $context['sandbox']['total']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
      }
    }
  }
}
