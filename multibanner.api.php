<?php

/**
 * @file
 * Hooks related to multibanner and it's plugins.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the multibanner type plugin definitions.
 *
 * @param array $types
 *   The array of type plugins, keyed on the machine-readable name.
 *
 * @see \Drupal\multibanner\Annotation\MultibannerType
 */
function hook_multibanner_type_info_alter(&$types) {
  $types['name']['label'] = t('An alteration.');
}

/**
 * @} End of "addtogroup hooks".
 */
