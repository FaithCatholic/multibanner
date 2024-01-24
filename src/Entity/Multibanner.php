<?php

namespace Drupal\multibanner\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\multibanner\MultibannerInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\UserInterface;

/**
 * Defines the multibanner class.
 *
 * @ContentEntityType(
 *   id = "multibanner",
 *   label = @Translation("Multibanner"),
 *   bundle_label = @Translation("Multibanner bundle"),
 *   handlers = {
 *     "storage" = "Drupal\multibanner\MultibannerStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\multibanner\MultibannerAccessController",
 *     "form" = {
 *       "default" = "Drupal\multibanner\MultibannerForm",
 *       "delete" = "Drupal\multibanner\Form\MultibannerDeleteForm",
 *       "edit" = "Drupal\multibanner\MultibannerForm"
 *     },
 *     "inline_form" = "Drupal\multibanner\Form\MultibannerInlineForm",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\multibanner\MultibannerViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "multibanner",
 *   data_table = "multibanner_field_data",
 *   revision_table = "multibanner_revision",
 *   revision_data_table = "multibanner_field_revision",
 *   translatable = TRUE,
 *   render_cache = TRUE,
 *   entity_keys = {
 *     "id" = "mid",
 *     "revision" = "vid",
 *     "bundle" = "bundle",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "multibanner_bundle",
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer multibanner",
 *   field_ui_base_route = "entity.multibanner_bundle.edit_form",
  *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "add-page" = "/multibanner/add",
 *     "add-form" = "/multibanner/add/{multibanner_bundle}",
 *     "canonical" = "/multibanner/{multibanner}",
 *     "delete-form" = "/multibanner/{multibanner}/delete",
 *     "edit-form" = "/multibanner/{multibanner}/edit",
 *     "admin-form" = "/admin/structure/multibanner/manage/{multibanner_bundle}"
 *   }
 * )
 */
class Multibanner extends ContentEntityBase implements MultibannerInterface {

  use EntityChangedTrait;

  /**
   * Value that represents the multibanner being published.
   */
  const PUBLISHED = 1;

  /**
   * Value that represents the multibanner being unpublished.
   */
  const NOT_PUBLISHED = 0;

  /**
   * A queue based multibanner operation to download thumbnails is being performed.
   *
   * @var boolean
   */
  protected $queued_thumbnail_download = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? Multibanner::PUBLISHED : Multibanner::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublisher() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueuedThumbnailDownload() {
    $this->queued_thumbnail_download = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublisherId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublisherId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle->entity->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the multibanner owner the
    // revision author.
    if (!$this->get('revision_uid')->entity) {
      $this->set('revision_uid', $this->getPublisherId());
    }

    // Set thumbnail.
    if (!$this->get('thumbnail')->entity || !empty($this->queued_thumbnail_download)) {
      $this->automaticallySetThumbnail();
    }

    // Try to set fields provided by type plugin and mapped in bundle
    // configuration.
    foreach ($this->bundle->entity->field_map as $source_field => $destination_field) {
      // Only save value in entity field if empty. Do not overwrite existing
      // data.
      // @TODO We might modify that in the future but let's leave it like this
      // for now.
      if ($this->hasField($destination_field) && $this->{$destination_field}->isEmpty() && ($value = $this->getType()->getField($this, $source_field))) {
        $this->set($destination_field, $value);
      }
    }

    // Try to set a default name for this multibanner, if there is no label provided.
    if (empty($this->label())) {
      $this->set('name', $this->getType()->getDefaultName($this));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (!$update && $this->bundle->entity->getQueueThumbnailDownloads()) {
      $queue = \Drupal::queue('multibanner_thumbnail');
      $queue->createItem(['id' => $this->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function automaticallySetThumbnail() {
    /** @var \Drupal\multibanner\MultibannerBundleInterface $bundle */
    if ($this->bundle->entity->getQueueThumbnailDownloads() && $this->isNew()) {
      $thumbnail_uri = $this->getType()->getDefaultThumbnail();
    }
    else {
      $thumbnail_uri = $this->getType()->thumbnail($this);
    }
    $existing = \Drupal::entityQuery('file')
      ->accessCheck(TRUE)
      ->condition('uri', $thumbnail_uri)
      ->execute();

    if ($existing) {
      $this->thumbnail->target_id = reset($existing);
    }
    else {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager()->getStorage('file')->create(['uri' => $thumbnail_uri]);
      if ($publisher = $this->getPublisher()) {
        $file->setOwner($publisher);
      }
      $file->setPermanent();
      $file->save();
      $this->thumbnail->target_id = $file->id();
    }

    // TODO - We should probably use something smarter (tokens, ...).
    $this->thumbnail->alt = t('Thumbnail');
    $this->thumbnail->title = $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing node without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->getType()->attachConstraints($this);
    return parent::validate();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Multibanner ID'))
      ->setDescription(t('The multibanner ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The multibanner UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The multibanner revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The multibanner bundle.'))
      ->setSetting('target_type', 'multibanner_bundle')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The multibanner language code.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The primary title or heading text.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['thumbnail'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail'))
      ->setDescription(t('The thumbnail of the multibanner.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => 1,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Publisher ID'))
      ->setDescription(t('The user ID of the multibanner publisher.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('Drupal\multibanner\Entity\Multibanner::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the multibanner is published.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the multibanner was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the multibanner was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision publisher ID'))
      ->setDescription(t('The user ID of the publisher of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision Log'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->revision_timestamp->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->revision_timestamp->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->revision_uid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUser(UserInterface $account) {
    $this->revision_uid->entity = $account;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUserId() {
    return $this->revision_user->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($user_id) {
    $this->revision_user->target_id = $user_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLogMessage() {
    return $this->revision_log->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->revision_log->value = $revision_log_message;
    return $this;
  }

}
