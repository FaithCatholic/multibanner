<?php

namespace Drupal\multibanner\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in a normal banner.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "multibanner",
 *   title = @Translation("Multibanner"),
 *   help = @Translation("Displays rows in a multibanner."),
 *   theme = "views_view_multibanner",
 *   display_types = {"normal"}
 * )
 */
class Multibanner extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['class'] = array('default' => '');
    $options['wrapper_class'] = array('default' => '');
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['wrapper_class'] = array(
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the list element wrapper.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    );
    $form['class'] = array(
      '#title' => $this->t('List class'),
      '#description' => $this->t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    );
  }

}
