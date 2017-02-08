<?php

/**
 * @file
 * Builds placeholder replacement tokens for multibanner-related data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\multibanner\Entity\MultibannerBundle;

/**
 * Implements hook_token_info().
 */
function multibanner_token_info() {
  $type = [
    'name' => t('Multibanner'),
    'description' => t('Tokens related to individual multibanner items.'),
    'needs-data' => 'multibanner',
  ];

  // Core tokens for multibanner.
  $multibanner['mid'] = [
    'name' => t("Multibanner ID"),
    'description' => t('The unique ID of the multibanner item.'),
  ];
  $multibanner['uuid'] = [
    'name' => t("Multibanner UUID"),
    'description' => t('The unique UUID of the multibanner item.'),
  ];
  $multibanner['vid'] = [
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the multibanner's latest revision."),
  ];
  $multibanner['bundle'] = [
    'name' => t("Multibanner bundle"),
  ];
  $multibanner['bundle-name'] = [
    'name' => t("Multibanner bundle name"),
    'description' => t("The human-readable name of the multibanner bundle."),
  ];
  $multibanner['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the multibanner is written in.'),
  ];
  $multibanner['name'] = [
    'name' => t('Name'),
    'description' => t('The name of this multibanner.'),
  ];
  $multibanner['type'] = [
    'name' => t("Type"),
    'description' => t("The type of this multibanner."),
  ];
  $node['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];
  $multibanner['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the multibanner."),
  ];
  $multibanner['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the multibanner's edit page."),
  ];

  // Chained tokens for multibanner.
  $multibanner['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $multibanner['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the multibanner was most recently updated."),
    'type' => 'date',
  ];

  return [
    'types' => ['multibanner' => $type],
    'tokens' => ['multibanner' => $multibanner],
  ];
}

/**
 * Implements hook_tokens().
 */
function multibanner_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = [];

  if ($type == 'multibanner' && !empty($data['multibanner'])) {
    /** @var \Drupal\multibanner\MultibannerInterface $multibanner */
    $multibanner = \Drupal::service('entity.repository')->getTranslationFromContext($data['multibanner'], $langcode, ['operation' => 'multibanner_tokens']);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the multibanner.
        case 'mid':
          $replacements[$original] = $multibanner->id();
          break;

        case 'uuid':
          $replacements[$original] = $multibanner->uuid();
          break;

        case 'vid':
          $replacements[$original] = $multibanner->getRevisionId();
          break;

        case 'bundle':
          $replacements[$original] = $sanitize ? Html::escape($multibanner->bundle()) : $multibanner->bundle();
          break;

        case 'bundle-name':
          $bundle_name = MultibannerBundle::load($multibanner->bundle())->label();
          $replacements[$original] = $sanitize ? Html::escape($bundle_name) : $bundle_name;
          break;

        case 'langcode':
          $replacements[$original] = $sanitize ? Html::escape($multibanner->language()->getId()) : $multibanner->language()->getId();
          break;

        case 'name':
          $multibanner_name = $multibanner->get('name')->value;
          $replacements[$original] = $sanitize ? Html::escape($multibanner_name) : $multibanner_name;
          break;

        case 'type':
          $multibanner_type = $multibanner->get('name')->value;
          $replacements[$original] = $sanitize ? Html::escape($multibanner_type) : $multibanner_type;
          break;

        case 'url':
          $replacements[$original] = $multibanner->toUrl('canonical', $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = $multibanner->toUrl('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          /** @var \Drupal\user\UserInterface $account */
          $account = $multibanner->get('uid')->entity;
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $sanitize ? Html::escape($account->label()) : $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')
            ->format($multibanner->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')
            ->format($multibanner->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $account = $multibanner->get('uid')->entity;
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $account], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $multibanner->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $multibanner->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
