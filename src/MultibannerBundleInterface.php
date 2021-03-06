<?php

namespace Drupal\multibanner;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a multibanner bundle entity.
 */
interface MultibannerBundleInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Returns the label.
   *
   * @param \Drupal\multibanner\MultibannerInterface $multibanner
   *   The multibanner.
   *
   * @return string|bool
   *   Returns the label of the bundle that entity belongs to.
   */
  public static function getLabel(MultibannerInterface $multibanner);

  /**
   * Checks if the bundle exists.
   *
   * @param int $id
   *   The Multibanner bundle ID.
   *
   * @return bool
   *   TRUE if the bundle with the given ID exists, FALSE otherwise.
   */
  public static function exists($id);

  /**
   * Returns whether thumbnail downloads are queued.
   *
   * @return bool
   *   Returns download now or later.
   */
  public function getQueueThumbnailDownloads();

  /**
   * Sets a flag to indicate that thumbnails should be downloaded via a queue.
   *
   * @param bool $queue_thumbnail_downloads
   *   The queue downloads flag.
   */
  public function setQueueThumbnailDownloads($queue_thumbnail_downloads);

  /**
   * Returns the Multibanner bundle description.
   *
   * @return string
   *   Returns the Multibanner bundle description.
   */
  public function getDescription();

  /**
   * Returns the multibanner type plugin.
   *
   * @return \Drupal\multibanner\MultibannerTypeInterface
   *   The type.
   */
  public function getType();

  /**
   * Returns the multibanner type configuration.
   *
   * @return array
   *   The type configuration.
   */
  public function getTypeConfiguration();

  /**
   * Sets the multibanner type configuration.
   *
   * @param array $configuration
   *   The type configuration.
   */
  public function setTypeConfiguration($configuration);

  /**
   * Returns the multibanner type status.
   *
   * @return bool
   *   The status.
   */
  public function getStatus();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

}
