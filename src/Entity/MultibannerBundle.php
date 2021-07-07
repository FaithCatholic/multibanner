<?php

namespace Drupal\multibanner\Entity;

use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\multibanner\MultibannerBundleInterface;
use Drupal\multibanner\MultibannerInterface;

/**
 * Defines the Multibanner bundle configuration entity.
 *
 * @ConfigEntityType(
 *   id = "multibanner_bundle",
 *   label = @Translation("Multibanner bundle"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\multibanner\MultibannerBundleForm",
 *       "edit" = "Drupal\multibanner\MultibannerBundleForm",
 *       "delete" = "Drupal\multibanner\Form\MultibannerBundleDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\multibanner\MultibannerBundleListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer multibanner bundles",
 *   config_prefix = "bundle",
 *   bundle_of = "multibanner",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "type",
 *     "queue_thumbnail_downloads",
 *     "new_revision",
 *     "third_party_settings",
 *     "type_configuration",
 *     "field_map",
 *     "status",
 *   },
  *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/multibanner/add",
 *     "edit-form" = "/admin/structure/multibanner/manage/{multibanner_bundle}",
 *     "delete-form" = "/admin/structure/multibanner/manage/{multibanner_bundle}/delete",
 *     "collection" = "/admin/structure/multibanner",
 *   }
 * )
 */
class MultibannerBundle extends ConfigEntityBundleBase implements MultibannerBundleInterface, EntityWithPluginCollectionInterface, EntityDescriptionInterface {

  /**
   * The machine name of this multibanner bundle.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the multibanner bundle.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this multibanner bundle.
   *
   * @var string
   */
  public $description;

  /**
   * The type plugin id.
   *
   * @var string
   */
  public $type = 'generic';

  /**
   * Are thumbnail downloads queued.
   *
   * @var bool
   */
  public $queue_thumbnail_downloads = FALSE;

  /**
   * Default value of the 'Create new revision' checkbox of this multibanner bundle.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * The type plugin configuration.
   *
   * @var array
   */
  public $type_configuration = [];

  /**
   * Type lazy plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $typePluginCollection;

  /**
   * Field map. Fields provided by type plugin to be stored as entity fields.
   *
   * @var array
   */
  public $field_map = [];

  /**
   * Default status of this multibanner bundle.
   *
   * @var array
   */
  public $status = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'type_configuration' => $this->typePluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel(MultibannerInterface $multibanner) {
    $bundle = static::load($multibanner->bundle());
    return $bundle ? $bundle->label() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function exists($id) {
    return (bool) static::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeConfiguration() {
    return $this->type_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeConfiguration($configuration) {
    $this->type_configuration = $configuration;
    $this->typePluginCollection = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueThumbnailDownloads() {
    return $this->queue_thumbnail_downloads;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueThumbnailDownloads($queue_thumbnail_downloads) {
    $this->queue_thumbnail_downloads = $queue_thumbnail_downloads;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->typePluginCollection()->get($this->type);
  }

  /**
   * Returns type lazy plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The tag plugin collection.
   */
  protected function typePluginCollection() {
    if (!$this->typePluginCollection) {
      $this->typePluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.multibanner.type'), $this->type, $this->type_configuration);
    }
    return $this->typePluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

}
