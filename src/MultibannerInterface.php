<?php

namespace Drupal\multibanner;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides an interface defining a multibanner.
 */
interface MultibannerInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface {

  /**
   * Returns the multibanner creation timestamp.
   *
   * @return int
   *   Creation timestamp of the multibanner.
   */
  public function getCreatedTime();

  /**
   * Sets the multibanner creation timestamp.
   *
   * @param int $timestamp
   *   The multibanner creation timestamp.
   *
   * @return \Drupal\multibanner\MultibannerInterface
   *   The called multibanner.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets a flag to indicate the thumbnail will be retrieved via a queue.
   */
  public function setQueuedThumbnailDownload();

  /**
   * Returns the multibanner publisher user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The author user entity.
   */
  public function getPublisher();

  /**
   * Returns the multibanner publisher user ID.
   *
   * @return int
   *   The author user ID.
   */
  public function getPublisherId();

  /**
   * Sets the multibanner publisher user ID.
   *
   * @param int $uid
   *   The author user id.
   *
   * @return \Drupal\multibanner\MultibannerInterface
   *   The called multibanner.
   */
  public function setPublisherId($uid);

  /**
   * Returns the multibanner published status indicator.
   *
   * Unpublished multibanner are only visible to their authors and to administrators.
   *
   * @return bool
   *   TRUE if the multibanner is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a multibanner.
   *
   * @param bool $published
   *   TRUE to set this multibanner to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\multibanner\MultibannerInterface
   *   The called multibanner.
   */
  public function setPublished($published);

  /**
   * Returns the multibanner type.
   *
   * @return \Drupal\multibanner\MultibannerTypeInterface
   *   The multibanner type.
   */
  public function getType();

  /**
   * Automatically determines the most appropriate thumbnail and sets
   * "thumbnail" field.
   */
  public function automaticallySetThumbnail();

}
