<?php

namespace Drupal\multibanner\Plugin\DevelGenerate;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\multibanner\MultibannerStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a MultibannerDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "multibanner",
 *   label = @Translation("multibanner"),
 *   description = @Translation("Generate a given number of multibanner entities."),
 *   url = "multibanner",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "name_length" = 4
 *   }
 * )
 */
class MultibannerDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The multibanner storage.
   *
   * @var \Drupal\multibanner\MultibannerStorageInterface
   */
  protected $multibannerStorage;

  /**
   * The multibanner bundle storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $multibannerBundleStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs MultibannerDevelGenerate class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\multibanner\MultibannerStorageInterface $multibanner_storage
   *   The multibanner storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $multibanner_bundle_storage
   *   The multibanner bundle storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MultibannerStorageInterface $multibanner_storage, EntityStorageInterface $user_storage, EntityStorageInterface $multibanner_bundle_storage, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatter $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->multibannerStorage = $multibanner_storage;
    $this->multibannerBundleStorage = $multibanner_bundle_storage;
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $entity_manager->getStorage('multibanner'),
      $entity_manager->getStorage('user'),
      $entity_manager->getStorage('multibanner_bundle'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('url_generator'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $bundles = $this->multibannerBundleStorage->loadMultiple();

    if (empty($bundles)) {
      $create_url = $this->urlGenerator->generateFromRoute('multibanner.bundle_add');
      $this->setMessage($this->t('You do not have any multibanner bundles that can be generated. <a href="@create-bundle">Go create a new multibanner bundle</a>', ['@create-bundle' => $create_url]), 'error', FALSE);
      return [];
    }

    $options = [];
    foreach ($bundles as $bundle) {
      $options[$bundle->id()] = ['bundle' => ['#markup' => $bundle->label()]];
    }

    $form['multibanner_bundles'] = [
      '#type' => 'tableselect',
      '#header' => ['bundle' => $this->t('Multibanner bundle')],
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all multibanner</strong> in these bundles before generating new multibanner.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many multibanner items would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }
    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the multibanner be dated?'),
      '#description' => $this->t('Multibanner creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    $form['name_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in names'),
      '#default_value' => $this->getSetting('name_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];

    $options = [];
    // We always need a language.
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $form['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Set language on multibanner'),
      '#multiple' => TRUE,
      '#description' => $this->t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($values['num'] <= 50) {
      $this->generateMultibanner($values);
    }
    else {
      $this->generateBatchMultibanner($values);
    }
  }

  /**
   * Method for creating multibanner when number of elements is less than 50.
   *
   * @param array $values
   *   Array of values submitted through a form.
   */
  private function generateMultibanner($values) {
    $values['multibanner_bundles'] = array_filter($values['multibanner_bundles']);
    if (!empty($values['kill']) && $values['multibanner_bundles']) {
      $this->multibannerKill($values);
    }

    if (!empty($values['multibanner_bundles'])) {
      // Generate multibanner.
      $this->preGenerate($values);
      $start = time();
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->createMultibannerItem($values);
        // drush_log removed from Drupal 9.
        // if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
        //   $now = time();
        //   drush_log(dt('Completed !feedback multibanner items (!rate multibanner/min)', [
        //     '!feedback' => drush_get_option('feedback', 1000),
        //     '!rate' => (drush_get_option('feedback', 1000) * 60) / ($now - $start),
        //   ]), 'ok');
        //   $start = $now;
        // }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], '1 multibanner created.', 'Finished creating @count multibanner items.'));
  }

  /**
   * Method for creating multibanner when number of elements is greater than 50.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  private function generateBatchMultibanner($values) {
    // Setup the batch operations and save the variables.
    $operations[] = [
      'devel_generate_operation',
      [$this, 'batchPreGenerate', $values],
    ];

    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchMultibannerKill', $values],
      ];
    }

    // Add the operations to create the multibanner.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchCreateMultibannerItem', $values],
      ];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating multibanner'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
    batch_set($batch);
  }

  /**
   * Batch version of preGenerate().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchPreGenerate($vars, &$context) {
    $context['results'] = $vars;
    $context['results']['num'] = 0;
    $this->preGenerate($context['results']);
  }

  /**
   * Batch version of createMultibannerItem().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchCreateMultibannerItem($vars, &$context) {
    $this->createMultibannerItem($context['results']);
    $context['results']['num']++;
  }

  /**
   * Batch version of multibannerKill().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchMultibannerKill($vars, &$context) {
    $this->multibannerKill($context['results']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args, array $options = []) {
    $add_language = drush_get_option('languages');
    if (!empty($add_language)) {
      $add_language = explode(',', str_replace(' ', '', $add_language));
      // Intersect with the enabled languages to make sure the language args
      // passed are actually enabled.
      $values['values']['add_language'] = array_intersect($add_language, array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL)));
    }

    $values['kill'] = drush_get_option('kill');
    $values['name_length'] = drush_get_option('name_length', 6);
    $values['num'] = array_shift($args);
    $selected_bundles = \Drush\StringUtils::csvToArray(drush_get_option('bundles', []));

    if (empty($selected_bundles)) {
      return drush_set_error('DEVEL_GENERATE_NO_MULTIBANNER_BUNDLES', dt('No multibanner bundles available'));
    }

    $values['multibanner_bundles'] = array_combine($selected_bundles, $selected_bundles);

    return $values;
  }

  /**
   * Deletes all multibanner of given multibanner bundles.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function multibannerKill($values) {
    $mids = $this->multibannerStorage->getQuery()
      ->condition('bundle', $values['multibanner_bundles'], 'IN')
      ->execute();

    if (!empty($mids)) {
      $multibanner = $this->multibannerStorage->loadMultiple($mids);
      $this->multibannerStorage->delete($multibanner);
      $this->setMessage($this->t('Deleted %count multibanner items.', ['%count' => count($mids)]));
    }
  }

  /**
   * Code to be run before generating items.
   *
   * Returns the same array passed in as parameter, but with an array of uids
   * for the key 'users'.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function preGenerate(&$results) {
    // Get user id.
    $users = $this->userStorage->getQuery()
      ->range(0, 50)
      ->execute();
    $users = array_merge($users, ['0']);
    $results['users'] = $users;
  }

  /**
   * Create one multibanner item. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function createMultibannerItem(&$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }
    $users = $results['users'];

    $bundle = array_rand(array_filter($results['multibanner_bundles']));
    $uid = $users[array_rand($users)];

    $multibanner = $this->multibannerStorage->create([
      'bundle' => $bundle,
      'name' => $this->getRandom()->sentences(mt_rand(1, $results['name_length']), TRUE),
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'status' => TRUE,
      'created' => \Drupal::time()->getRequestTime() - mt_rand(0, $results['time_range']),
      'langcode' => $this->getLangcode($results),
    ]);

    // A flag to let hook implementations know that this is a generated item.
    $multibanner->devel_generate = $results;

    // Populate all fields with sample values.
    $this->populateFields($multibanner);

    $multibanner->save();
  }

  /**
   * Determine language based on $results.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function getLangcode($results) {
    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    return $langcode;
  }

}
