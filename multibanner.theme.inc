<?php

/**
 * @file
 * Theme functions for the multibanner module.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

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
  /** @var \Drupal\multibanner\MultibannerInterface $multibanner */
  $multibanner = $variables['elements']['#multibanner'];

  $variables['name'] = $multibanner->label();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['attributes']['class'][] = 'multibanner';
  $variables['attributes']['class'][] = Html::getClass('multibanner-' . $multibanner->bundle());
  if (!$multibanner->isPublished()) {
    $variables['attributes']['class'][] = 'unpublished';
  }
  if ($variables['elements']['#view_mode']) {
    $variables['attributes']['class'][] = Html::getClass('view-mode-' . $variables['elements']['#view_mode']);
  }
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
function multibanner_preprocess_views_view(&$variables) {
  $handler  = $variables['view']->style_plugin;

  // Fetch classes from handler options.
  if ($handler->options['class']) {
    $class = explode(' ', $handler->options['class']);
    $class = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $class);
    $variables['list']['attributes'] = new Attribute(array('class' => $class));
  }

  // Fetch wrapper classes from handler options.
  if ($handler->options['wrapper_class']) {
    $wrapper_class = explode(' ', $handler->options['wrapper_class']);
    $variables['attributes']['class'] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $wrapper_class);
  } else {
    $variables['attributes']['class'] = array();
  }

  // Prepare template variables/classes.
  $banner_count = count($variables['view']->result);

  // Assign template variables.
  $variables['banner_count'] = $banner_count;

  // Assign template classes.
  $variables['attributes']['class'][] = 'multibanner';
  $variables['attributes']['class'][] = 'multibanner-count-'. $banner_count;

  // Finish rendering.
  template_preprocess_views_view_unformatted($variables);
}
