<?php

namespace Drupal\multibanner\Plugin\MultibannerEntity\Type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\multibanner\MultibannerInterface;
use Drupal\multibanner\MultibannerTypeBase;

/**
 * Provides generic multibanner type.
 *
 * @MultibannerType(
 *   id = "generic",
 *   label = @Translation("Generic multibanner"),
 *   description = @Translation("Image multibanner type.")
 * )
 */
class Generic extends MultibannerTypeBase {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MultibannerInterface $multibanner, $name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MultibannerInterface $multibanner) {
    return $this->config->get('icon_base') . '/generic.png';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'markup',
      '#markup' => $this->t("This type provider doesn't need configuration."),
    ];

    return $form;
  }

}
