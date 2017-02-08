<?php

namespace Drupal\multibanner\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\multibanner\Entity\Multibanner;

/**
 * Unpublishes a multibanner.
 *
 * @Action(
 *   id = "multibanner_unpublish_action",
 *   label = @Translation("Unpublish multibanner"),
 *   type = "multibanner"
 * )
 */
class UnpublishMultibanner extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(Multibanner $entity = NULL) {
    $entity->setPublished(FALSE)->save();
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
