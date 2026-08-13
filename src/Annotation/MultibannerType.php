<?php

namespace Drupal\multibanner\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an multibanner type plugin annotation object.
 *
 * @see hook_multibanner_type_info_alter()
 *
 * @Annotation
 */
class MultibannerType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * This will be shown when adding or configuring this display.
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
