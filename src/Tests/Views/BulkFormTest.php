<?php

namespace Drupal\multibanner\Tests\Views;

use Drupal\multibanner\Tests\MultibannerTestTrait;
use Drupal\multibanner\Entity\Multibanner;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests a multibanner bulk form.
 *
 * @group multibanner
 */
class BulkFormTest extends ViewTestBase {

  use MultibannerTestTrait;

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = ['multibanner_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_multibanner_bulk_form'];

  /**
   * The test multibanner bundle.
   *
   * @var \Drupal\multibanner\MultibannerBundleInterface
   */
  protected $testBundle;

  /**
   * The test multibanner entities.
   *
   * @var \Drupal\multibanner\MultibannerInterface[]
   */
  protected $multibannerEntities;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['multibanner_test_views']);
    }

    $this->testBundle = $this->drupalCreateMultibannerBundle();

    // Create some test multibanner entities.
    $this->multibannerEntities = [];
    for ($i = 1; $i <= 5; $i++) {
      $multibanner = Multibanner::create([
        'bundle' => $this->testBundle->id(),
        'name' => $this->randomMachineName(),
      ]);
      $multibanner->save();

      $this->multibannerEntities[] = $multibanner;
    }

    // Check that all created entities are present in the test view.
    $view = Views::getView('test_multibanner_bulk_form');
    $view->execute();
    $this->assertEqual(count($view->result), 5, 'All created multibanner entities are present in the view.');

    $this->adminUser = $this->drupalCreateUser([
      'view multibanner',
      'update any multibanner',
      'delete any multibanner',
    ]);
    $this->drupalLogin($this->adminUser);

    // Check the operations are accessible to the logged in user.
    $this->drupalGet('test-multibanner-entity-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    // Current available actions: Delete, Save, Publish, Unpublish.
    $this->assertIdentical(count($elements), 4, 'All multibanner operations are found.');
  }

  /**
   * Tests the multibanner bulk form.
   */
  public function testBulkForm() {

    // Test unpublishing in bulk.
    $edit = [
      'multibanner_bulk_form[0]' => TRUE,
      'multibanner_bulk_form[1]' => TRUE,
      'multibanner_bulk_form[2]' => TRUE,
      'action' => 'multibanner_unpublish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText("Unpublish multibanner was applied to 3 items");
    $multibanner1_status = $this->loadMultibanner(1)->isPublished();
    $this->assertEqual(FALSE, $multibanner1_status, 'First multibanner was unpublished correctly.');
    $multibanner2_status = $this->loadMultibanner(2)->isPublished();
    $this->assertEqual(FALSE, $multibanner2_status, 'Second multibanner was unpublished correctly.');
    $multibanner3_status = $this->loadMultibanner(3)->isPublished();
    $this->assertEqual(FALSE, $multibanner3_status, 'Third multibanner was unpublished correctly.');

    // Test publishing in bulk.
    $edit = [
      'multibanner_bulk_form[0]' => TRUE,
      'multibanner_bulk_form[1]' => TRUE,
      'action' => 'multibanner_publish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText("Publish multibanner was applied to 2 items");
    $multibanner1_status = $this->loadMultibanner(1)->isPublished();
    $this->assertEqual(TRUE, $multibanner1_status, 'First multibanner was published back correctly.');
    $multibanner2_status = $this->loadMultibanner(2)->isPublished();
    $this->assertEqual(TRUE, $multibanner2_status, 'Second multibanner was published back correctly.');

    // Test deletion in bulk.
    $edit = [
      'multibanner_bulk_form[0]' => TRUE,
      'multibanner_bulk_form[1]' => TRUE,
      'action' => 'multibanner_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    $this->assertText("Are you sure you want to delete these items?");
    $label1 = $this->loadMultibanner(1)->label();
    $this->assertRaw('<li>' . $label1 . '</li>');
    $label2 = $this->loadMultibanner(2)->label();
    $this->assertRaw('<li>' . $label2 . '</li>');

    $this->drupalPostForm(NULL, [], t('Delete'));

    $multibanner = $this->loadMultibanner(1);
    $this->assertNull($multibanner, 'Multibanner 1 has been correctly deleted.');
    $multibanner = $this->loadMultibanner(2);
    $this->assertNull($multibanner, 'Multibanner 2 has been correctly deleted.');

    $this->assertText('Deleted 2 multibanner entities.');
  }

  /**
   * Load the specified multibanner from the storage.
   *
   * @param int $id
   *   The multibanner identifier.
   *
   * @return \Drupal\multibanner\MultibannerInterface
   *   The loaded multibanner.
   */
  protected function loadMultibanner($id) {
    /** @var \Drupal\multibanner\MultibannerStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('multibanner');
    return $storage->loadUnchanged($id);
  }

}
