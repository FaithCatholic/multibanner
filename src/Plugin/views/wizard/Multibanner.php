<?php

namespace Drupal\multibanner\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Tests creating multibanner views with the wizard.
 *
 * @ViewsWizard(
 *   id = "multibanner",
 *   base_table = "multibanner_field_data",
 *   title = @Translation("Multibanner")
 * )
 */
class Multibanner extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'multibanner_field_data-created';

  /**
   * Set default values for the filters.
   */
  protected $filters = [
    'status' => [
      'value' => TRUE,
      'table' => 'multibanner_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'entity_type' => 'multibanner',
      'entity_field' => 'status',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function getAvailableSorts() {
    return [
      'multibanner_field_data-name:DESC' => $this->t('Multibanner name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access content';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    // Add the name field, so that the display has content if the user switches
    // to a row style that uses fields.
    /* Field: Multibanner: Name */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'multibanner_field_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'multibanner';
    $display_options['fields']['name']['entity_field'] = 'multibanner';
    $display_options['fields']['name']['label'] = '';
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['settings']['link_to_entity'] = 1;
    $display_options['fields']['name']['plugin_id'] = 'field';

    return $display_options;
  }

}
