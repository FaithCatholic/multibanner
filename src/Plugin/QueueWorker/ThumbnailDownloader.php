<?php

namespace Drupal\multibanner\Plugin\QueueWorker;

use Drupal\multibanner\Entity\Multibanner;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Download images.
 *
 * @QueueWorker(
 *   id = "multibanner_thumbnail",
 *   title = @Translation("Thumbnail downloader"),
 *   cron = {"time" = 60}
 * )
 */
class ThumbnailDownloader extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($entity = Multibanner::load($data['id'])) {
      // Indicate that the entity is being processed from a queue and that
      // thumbnail images should be downloaded.
      $entity->setQueuedThumbnailDownload();
      $entity->save();
    }
  }

}
