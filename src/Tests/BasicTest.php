<?php

namespace Drupal\multibanner\Tests;

use Drupal\multibanner\Entity\Multibanner;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that basic functions work correctly.
 *
 * @group multibanner
 */
class BasicTest extends WebTestBase {

  use MultibannerTestTrait;

  /**
   * The test multibanner bundle.
   *
   * @var \Drupal\multibanner\MultibannerBundleInterface
   */
  protected $testBundle;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node', 'multibanner'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->testBundle = $this->drupalCreateMultibannerBundle();
  }

  /**
   * Tests creating a multibanner bundle programmatically.
   */
  public function testMultibannerBundleCreation() {
    $bundle = $this->drupalCreateMultibannerBundle();
    /** @var \Drupal\multibanner\MultibannerBundleInterface $bundle_storage */
    $bundle_storage = $this->container->get('entity.manager')->getStorage('multibanner_bundle');

    $bundle_exists = (bool) $bundle_storage->load($bundle->id());
    $this->assertTrue($bundle_exists, 'The new multibanner bundle has been created in the database.');

    // Test default bundle created from default configuration.
    $this->container->get('module_installer')->install(['multibanner_test_bundle']);
    $test_bundle = $bundle_storage->load('test');
    $this->assertTrue((bool) $test_bundle, 'The multibanner bundle from default configuration has been created in the database.');
    $this->assertEqual($test_bundle->get('label'), 'Test bundle', 'Correct label detected.');
    $this->assertEqual($test_bundle->get('description'), 'Test bundle.', 'Correct description detected.');
    $this->assertEqual($test_bundle->get('type'), 'generic', 'Correct plugin ID detected.');
    $this->assertEqual($test_bundle->get('type_configuration'), [], 'Correct plugin configuration detected.');
    $this->assertEqual($test_bundle->get('field_map'), [], 'Correct field map detected.');
  }

  /**
   * Tests creating a multibanner programmatically.
   */
  public function testMultibannerEntityCreation() {
    $multibanner = Multibanner::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
    ]);
    $multibanner->save();

    $multibanner_not_exist = (bool) Multibanner::load(rand(1000, 9999));
    $this->assertFalse($multibanner_not_exist, 'The multibanner does not exist.');

    $multibanner_exists = (bool) Multibanner::load($multibanner->id());
    $this->assertTrue($multibanner_exists, 'The new multibanner has been created in the database.');
    $this->assertEqual($multibanner->bundle(), $this->testBundle->id(), 'The multibanner was created with correct bundle.');
    $this->assertEqual($multibanner->label(), 'Unnamed', 'The multibanner was corrected with correct name.');

    // Test the creation of a multibanner without user-defined label and check if a
    // default name is provided.
    $multibanner = Multibanner::create([
      'bundle' => $this->testBundle->id(),
    ]);
    $multibanner->save();
    $expected_name = 'multibanner' . ':' . $this->testBundle->id() . ':' . $multibanner->uuid();
    $this->assertEqual($multibanner->bundle(), $this->testBundle->id(), 'The multibanner was created with correct bundle.');
    $this->assertEqual($multibanner->label(), $expected_name, 'The multibanner was correctly created with a default name.');

  }

  /**
   * Runs basic tests for multibanner_access function.
   */
  public function testMultibannerAccess() {
    // Create users and roles.
    $admin = $this->drupalCreateUser(['administer multibanner'], 'editor');
    $user = $this->drupalCreateUser([], 'user');

    $permissions = [
      'view multibanner',
      'create multibanner',
      'update multibanner',
      'update any multibanner',
      'delete multibanner',
      'delete any multibanner',
    ];

    $roles = [];
    foreach ($permissions as $permission) {
      $roles[$permission] = $this->createRole([$permission]);
    }

    // Create multibanner.
    $multibanner = Multibanner::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
    ]);
    $multibanner->save();

    $user_multibanner = Multibanner::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
      'uid' => $user->id(),
    ]);
    $user_multibanner->save();

    // Test 'administer multibanner' permission.
    $this->drupalLogin($admin);
    $this->drupalGet('multibanner/' . $user_multibanner->id());
    $this->assertResponse(200);
    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/edit');
    $this->assertResponse(200);
    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/delete');
    $this->assertResponse(200);

    // Test 'view multibanner' permission.
    $this->drupalLogin($user);
    $this->drupalGet('multibanner/' . $multibanner->id());
    $this->assertResponse(403);

    $user->addRole($roles['view multibanner']);
    $user->save();

    $this->drupalGet('multibanner/' . $multibanner->id());
    $this->assertResponse(200);

    // Test 'create multibanner' permissions.
    $this->drupalLogin($user);
    $this->drupalGet('multibanner/add/' . $this->testBundle->id());
    $this->assertResponse(403);

    $user->addRole($roles['create multibanner']);
    $user->save();

    $this->drupalGet('multibanner/add/' . $this->testBundle->id());
    $this->assertResponse(200);

    // Test 'update multibanner' and 'delete multibanner' permissions.
    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/edit');
    $this->assertResponse(403);

    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/delete');
    $this->assertResponse(403);

    $user->addRole($roles['update multibanner']);
    $user->addRole($roles['delete multibanner']);
    $user->save();

    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/edit');
    $this->assertResponse(200);

    $this->drupalGet('multibanner/' . $user_multibanner->id() . '/delete');
    $this->assertResponse(200);

    // Test 'update any multibanner' and 'delete any multibanner' permissions.
    $this->drupalGet('multibanner/' . $multibanner->id() . '/edit');
    $this->assertResponse(403);

    $this->drupalGet('multibanner/' . $multibanner->id() . '/delete');
    $this->assertResponse(403);

    $user->addRole($roles['update any multibanner']);
    $user->addRole($roles['delete any multibanner']);
    $user->save();

    $this->drupalGet('multibanner/' . $multibanner->id() . '/edit');
    $this->assertResponse(200);

    $this->drupalGet('multibanner/' . $multibanner->id() . '/delete');
    $this->assertResponse(200);
  }

}
