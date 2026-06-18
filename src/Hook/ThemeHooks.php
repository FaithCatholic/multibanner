<?php

namespace Drupal\multibanner\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\Attribute;
use Drupal\views\Hook\ViewsThemeHooks;

/**
 * Theme hook implementations for the multibanner module.
 */
class ThemeHooks {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'multibanner' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessMultibanner',
      ],
      'views_view_multibanner' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessViewsViewMultibanner',
      ],
    ];
  }

  /**
   * Prepares variables for multibanner templates.
   *
   * Default template: multibanner.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An array of elements to display in view mode.
   *   - multibanner: The multibanner object.
   *   - view_mode: View mode; e.g., 'full', 'teaser', etc.
   */
  public function preprocessMultibanner(array &$variables): void {
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

    // Banner layout vars.
    $variables['layout'] = 'default';
    if ($node = $this->routeMatch->getParameter('node')) {
      if ($node->hasField('field_multibanner_layout')) {
        $variables['layout'] = $node->get('field_multibanner_layout')->getValue()[0]['value'];
      }
    }
  }

  /**
   * Prepares variables for views multibanner templates.
   *
   * @param array $variables
   *   An associative array containing:
   *   - view: A View object.
   */
  public function preprocessViewsViewMultibanner(array &$variables): void {
    $this->_preprocessViewsViewMultibanner($variables);
    // ViewsThemeHooks only exists on Drupal 11, where this method runs. Fetched
    // lazily (not injected) so the class stays instantiable on Drupal 10, whose
    // procedural path uses template_preprocess_views_view_unformatted() instead.
    // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
    \Drupal::service(ViewsThemeHooks::class)->preprocessViewsViewUnformatted($variables);
  }

  /**
   * Shared multibanner-specific preparation for views multibanner templates.
   *
   * Extracted so both the OOP and the procedural back-compat preprocess paths
   * can reuse it.
   *
   * @param array $variables
   *   An associative array containing:
   *   - view: A View object.
   *
   * @internal
   *
   * @see template_preprocess_views_view_multibanner()
   */
  public function _preprocessViewsViewMultibanner(array &$variables): void {
    $handler = $variables['view']->style_plugin;

    // Fetch classes from handler options.
    if ($handler->options['class']) {
      $class = explode(' ', $handler->options['class']);
      $class = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $class);
      $variables['list']['attributes'] = new Attribute(['class' => $class]);
    }

    // Fetch wrapper classes from handler options.
    if ($handler->options['wrapper_class']) {
      $wrapper_class = explode(' ', $handler->options['wrapper_class']);
      $variables['attributes']['class'] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $wrapper_class);
    }
    else {
      $variables['attributes']['class'] = [];
    }

    // Banner layout vars.
    $variables['layout'] = 'default';
    if ($node = $this->routeMatch->getParameter('node')) {
      if ($node->hasField('field_multibanner_layout')) {
        $variables['layout'] = $node->get('field_multibanner_layout')->getValue()[0]['value'];
      }
    }

    // Banner count class vars.
    $banner_count = count($variables['view']->result);
    $variables['banner_count'] = $banner_count;
    $variables['attributes']['class'][] = 'multibanner';
    $variables['attributes']['class'][] = 'multibanner-total-' . $banner_count;
  }

}
