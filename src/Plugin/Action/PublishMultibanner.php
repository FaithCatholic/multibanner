<?php

namespace Drupal\multibanner\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\multibanner\Entity\Multibanner;

/**
 * Publishes a multibanner.
 *
 * @Action(
 *   id = "multibanner_publish_action",
 *   label = @Translation("Publish multibanner"),
 *   type = "multibanner"
 * )
 */
class PublishMultibanner extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(Multibanner $entity = NULL) {
    $entity->setPublished(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\multibanner\MultibannerInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
