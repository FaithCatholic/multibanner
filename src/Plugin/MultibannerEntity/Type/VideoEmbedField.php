<?php

namespace Drupal\multibanner\Plugin\MultibannerEntity\Type;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\multibanner\MultibannerInterface;
use Drupal\multibanner\MultibannerTypeBase;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Provides multibanner type plugin for video embed field.
 *
 * @MultibannerType(
 *   id = "video_embed_field",
 *   label = @Translation("Video multibanner"),
 *   description = @Translation("Enables video_embed_field integration with multibanner.")
 * )
 */
class VideoEmbedField extends MultibannerTypeBase {

  /**
   * The name of the field on the multibanner entity.
   */
  const VIDEO_EMBED_FIELD_DEFAULT_NAME = 'field_multibanner_video';

  /**
   * The video provider manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The multibanner settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $multibannerSettings;

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MultibannerInterface $multibanner) {
    if ($provider = $this->loadProvider($multibanner)) {
      $provider->downloadThumbnail();
      return $provider->getLocalThumbnailUri();
    }
    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('multibanner', $form_state->getFormObject()->getEntity()->id()) as $field_name => $field) {
      if ($field->getType() == 'video_embed_field') {
        $options[$field_name] = $field->getLabel();
      }
    }
    if (empty($options)) {
      $form['summary']['#markup'] = $this->t('A video embed field will be created on this multibanner bundle when you save this form. You can return to this configuration screen to alter the video field used for this bundle, or you can use the one provided.');
    }
    if (!empty($options)) {
      $form['source_field'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Source Video Field'),
        '#description' => $this->t('The field on the multibanner entity that contains the video URL.'),
        '#default_value' => empty($this->configuration['source_field']) ? VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'],
        '#options' => $options,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MultibannerInterface $multibanner, $name) {
    if (!$url = $this->getVideoUrl($multibanner)) {
      return FALSE;
    }
    $provider = $this->providerManager->loadProviderFromInput($url);
    $definition = $this->providerManager->loadDefinitionFromInput($url);
    switch ($name) {
      case 'id':
        return $provider->getIdFromInput($url);

      case 'source':
        return $definition['id'];

      case 'source_name':
        return $definition['id'];

      case 'image_local':
      case 'image_local_uri':
        return $this->thumbnail($multibanner);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'id' => $this->t('Video ID.'),
      'source' => $this->t('Video source machine name.'),
      'source_name' => $this->t('Video source human name.'),
      'image_local' => $this->t('Copies thumbnail image to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MultibannerInterface $multibanner) {
    if ($provider = $this->loadProvider($multibanner)) {
      return $this->loadProvider($multibanner)->getName();
    }
    return parent::getDefaultThumbnail();
  }

  /**
   * Load a video provider given a multibanner entity.
   *
   * @param \Drupal\multibanner\MultibannerInterface $multibanner
   *   The multibanner entity.
   *
   * @return \Drupal\video_embed_field\ProviderPluginInterface
   *   The provider plugin.
   */
  protected function loadProvider(MultibannerInterface $multibanner) {
    $video_url = $this->getVideoUrl($multibanner);
    return !empty($video_url) ? $this->providerManager->loadProviderFromInput($video_url) : FALSE;
  }

  /**
   * Get the video URL from a multibanner entity.
   *
   * @param \Drupal\multibanner\MultibannerInterface $multibanner
   *   The multibanner entity.
   *
   * @return string|bool
   *   A video URL or FALSE on failure.
   */
  protected function getVideoUrl(MultibannerInterface $multibanner) {
    $field_name = empty($this->configuration['source_field']) ? VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'];
    $video_url = $multibanner->{$field_name}->value;
    return !empty($video_url) ? $video_url : FALSE;
  }

  /**
   * The function that is invoked during the insert of multibanner bundles.
   *
   * @param string $multibanner_bundle_id
   *   The ID of the multibanner bundle.
   */
  public static function createVideoEmbedField($multibanner_bundle_id) {
    if (!$storage = FieldStorageConfig::loadByName('multibanner', static::VIDEO_EMBED_FIELD_DEFAULT_NAME)) {
      FieldStorageConfig::create([
        'field_name' => static::VIDEO_EMBED_FIELD_DEFAULT_NAME,
        'entity_type' => 'multibanner',
        'type' => 'video_embed_field',
      ])->save();
    }
    FieldConfig::create([
      'entity_type' => 'multibanner',
      'field_name' => static::VIDEO_EMBED_FIELD_DEFAULT_NAME,
      'label' => 'Video URL',
      'required' => TRUE,
      'bundle' => $multibanner_bundle_id,
    ])->save();
    // Make the field visible on the form display.
    $form_display = EntityDisplayRepositoryInterface::getFormDisplay('multibanner', $multibanner_bundle_id, 'default');
    $form_display->setComponent(static::VIDEO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'video_embed_field_textfield',
    ])->save();
    // Make the field visible on the multibanner entity itself.
    $disaply = EntityDisplayRepositoryInterface::getViewDisplay('multibanner', $multibanner_bundle_id, 'default');
    $dispaly->setComponent(static::VIDEO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'video_embed_field_video',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->multibannerSettings->get('icon_base') . '/video.png';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Config $config, ProviderManagerInterface $provider_manager, Config $multibanner_settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);
    $this->providerManager = $provider_manager;
    $this->multibannerSettings = $multibanner_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')->get('multibanner.settings'),
      $container->get('video_embed_field.provider_manager'),
      $container->get('config.factory')->get('multibanner.settings')
    );
  }

}
