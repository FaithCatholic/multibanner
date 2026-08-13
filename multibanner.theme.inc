<?php

/**
 * @file
 * Theme functions for the multibanner module.
 */

use Drupal\Core\Link;
use Drupal\multibanner\Hook\ThemeHooks;

/**
 * Prepares variables for list of available multibanner bundles.
 *
 * Default template: multibanner-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of content types.
 */
function template_preprocess_multibanner_add_list(&$variables) {
  $variables['bundles'] = [];
  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $bundle) {
      /** @var \Drupal\multibanner\MultibannerBundleInterface $bundle */
      $variables['bundles'][$bundle->id()] = [
        'type' => $bundle->id(),
        'add_link' => Link::createFromRoute($bundle->label(), 'multibanner.add', ['multibanner_bundle' => $bundle->id()]),
        'description' => [
          '#markup' => $bundle->getDescription(),
        ],
      ];
    }
  }
}

/**
 * Prepares variables for multibanner templates.
 *
 * Default template: multibanner.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - multibanner: An individual multibanner for display.
 */
function template_preprocess_multibanner(&$variables) {
  \Drupal::service(ThemeHooks::class)->preprocessMultibanner($variables);
}

/**
 * Prepares variables for views multibanner templates.
 *
 * Default template: views-view-multibanner.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A View object.
 */
function template_preprocess_views_view_multibanner(&$variables) {
  \Drupal::service(ThemeHooks::class)->doPreprocessViewsViewMultibanner($variables);
  // Finish rendering. Calling the deprecated procedural function is required
  // for Drupal 10 back-compat. On Drupal 11 this whole shim is unused: the
  // 'initial preprocess' entry in ThemeHooks::theme() makes the theme registry
  // skip template_preprocess_HOOK (see \Drupal\Core\Theme\Registry), so the
  // OOP ThemeHooks::preprocessViewsViewMultibanner() path runs instead.
  // @phpstan-ignore function.deprecated
  template_preprocess_views_view_unformatted($variables);
}
