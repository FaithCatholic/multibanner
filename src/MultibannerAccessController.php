<?php

namespace Drupal\multibanner;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the multibanner.
 */
class MultibannerAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer multibanner')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($account->id() && $account->id() == $entity->getPublisherId()) ? TRUE : FALSE;
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('view multibanner') && $entity->status->value);

      case 'update':
        return AccessResult::allowedIf(($account->hasPermission('update multibanner') && $is_owner) || $account->hasPermission('update any multibanner'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf(($account->hasPermission('delete multibanner') && $is_owner) ||  $account->hasPermission('delete any multibanner'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    // No opinion.
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create multibanner');
  }

}
