<?php

namespace Drupal\multibanner\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'multibanner_thumbnail' formatter.
 *
 * @FieldFormatter(
 *   id = "multibanner_thumbnail",
 *   label = @Translation("Thumbnail"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MultibannerThumbnailFormatter extends ImageFormatter {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   *
   * The parent constructor is not overridden on purpose: its signature has
   * changed across Drupal versions. Extra dependencies are set on the built
   * instance instead.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overriden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $link_types = [
      'content' => $this->t('Content'),
      'multibanner' => $this->t('multibanner'),
    ];
    $element['image_link']['#options'] = $link_types;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $link_types = [
      'content' => $this->t('Linked to content'),
      'multibanner' => $this->t('Linked to multibanner'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $multibanner = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($multibanner)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'multibanner') {
      $link_multibanner = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    /** @var \Drupal\multibanner\MultibannerInterface $multibanner_item */
    foreach ($multibanner as $delta => $multibanner_item) {
      if (isset($link_multibanner)) {
        $url = $multibanner_item->toUrl();
      }

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $multibanner_item->get('thumbnail'),
        '#item_attributes' => [],
        '#image_style' => $image_style_setting,
        '#url' => $url,
      ];

      // Collect cache tags to be added for each item in the field.
      $this->renderer->addCacheableDependency($elements[$delta], $multibanner_item);
    }

    // Collect cache tags related to the image style setting.
    $image_style = $this->imageStyleStorage->load($image_style_setting);
    $this->renderer->addCacheableDependency($elements, $image_style);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // multibanner entities.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return $target_type == 'multibanner';
  }

}
