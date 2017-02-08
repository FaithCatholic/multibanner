<?php

namespace Drupal\multibanner\Tests;

use Drupal\Component\Utility\Xss;
use Drupal\multibanner\Entity\Multibanner;
use Drupal\multibanner\MultibannerInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that multibanner UI work correctly.
 *
 * @group multibanner
 */
class MultibannerUITest extends WebTestBase {

  use MultibannerTestTrait;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * A non-admin test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $nonAdminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'multibanner',
    'field_ui',
    'views_ui',
    'node',
    'block',
    'entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer multibanner',
      'administer multibanner fields',
      'administer multibanner form display',
      'administer multibanner display',
      'administer multibanner bundles',
      // multibanner permissions.
      'view multibanner',
      'create multibanner',
      'update multibanner',
      'update any multibanner',
      'delete multibanner',
      'delete any multibanner',
      'access multibanner overview',
      // Other permissions.
      'administer views',
      'access content overview',
      'view all revisions',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->nonAdminUser = $this->drupalCreateUser([
      // multibanner permissions.
      'view multibanner',
      'create multibanner',
      'update multibanner',
      'update any multibanner',
      'delete multibanner',
      'delete any multibanner',
      'access multibanner overview',
      // Other permissions.
      'administer views',
      'access content overview',
    ]);
  }

  /**
   * Tests a multibanner bundle administration.
   */
  public function testMultibannerBundles() {
    $this->container->get('module_installer')->install(['multibanner_test_type']);

    // Test and create one multibanner bundle.
    $bundle = $this->createMultibannerBundle();
    $bundle_id = $bundle['id'];
    unset($bundle['id']);

    // Check if all action links exist.
    $this->assertLinkByHref('admin/structure/multibanner/add');
    $this->assertLinkByHref('admin/structure/multibanner/manage/' . $bundle_id . '/fields');
    $this->assertLinkByHref('admin/structure/multibanner/manage/' . $bundle_id . '/form-display');
    $this->assertLinkByHref('admin/structure/multibanner/manage/' . $bundle_id . '/display');

    // Assert that fields have expected values before editing.
    $this->drupalGet('admin/structure/multibanner/manage/' . $bundle_id);
    $this->assertFieldByName('label', $bundle['label'], 'Label field has correct value.');
    $this->assertFieldByName('description', $bundle['description'], 'Description field has a correct value.');
    $this->assertFieldByName('type', $bundle['type'], 'Generic plugin is selected.');
    $this->assertNoFieldChecked('edit-options-new-revision', 'Revision checkbox is not checked.');
    $this->assertFieldChecked('edit-options-status', 'Status checkbox is checked.');
    $this->assertNoFieldChecked('edit-options-queue-thumbnail-downloads', 'Queue thumbnail checkbox is not checked.');
    $this->assertText('Create new revision', 'Revision checkbox label found.');
    $this->assertText('Automatically create a new revision of multibanner entities. Users with the Administer multibanner permission will be able to override this option.', 'Revision help text found');
    $this->assertText('Download thumbnails via a queue.', 'Queue thumbnails help text found');
    $this->assertText('Entities will be automatically published when they are created.', 'Published help text found');
    $this->assertText("This type provider doesn't need configuration.");
    $this->assertText('No metadata fields available.');
    $this->assertText('Multibanner type plugins can provide metadata fields such as title, caption, size information, credits, ... multibanner can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.');

    // Try to change multibanner type and check if new configuration sub-form appears.
    $commands = $this->drupalPostAjaxForm(NULL, ['type' => 'test_type'], 'type');
    // WebTestBase::drupalProcessAjaxResponse() won't correctly execute our ajax
    // commands so we have to do it manually. Code below is based on the logic
    // in that function.
    $content = $this->content;
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new \DOMXPath($dom);
    foreach ($commands as $command) {
      if ($command['command'] == 'insert' && $command['method'] == 'replaceWith') {
        $wrapperNode = $xpath->query('//*[@id="' . ltrim($command['selector'], '#') . '"]')->item(0);
        $newDom = new \DOMDocument();
        @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
        $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
        $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
        $content = $dom->saveHTML();
        $this->setRawContent($content);
      }
    }
    $this->assertFieldByName('type_configuration[test_type][test_config_value]', 'This is default value.');
    $this->assertText('Field 1', 'First metadata field found.');
    $this->assertText('Field 2', 'Second metadata field found.');
    $this->assertFieldByName('field_mapping[field_1]', '_none', 'First metadata field is not mapped by default.');
    $this->assertFieldByName('field_mapping[field_2]', '_none', 'Second metadata field is not mapped by default.');

    // Test if the edit machine name button is disabled.
    $elements = $this->xpath('//*[@id="edit-label-machine-name-suffix"]/span[@class="admin-link"]');
    $this->assertTrue(empty($elements), 'Edit machine name not found.');

    // Edit and save multibanner bundle form fields with new values.
    $bundle['label'] = $this->randomMachineName();
    $bundle['description'] = $this->randomMachineName();
    $bundle['type'] = 'test_type';
    $bundle['type_configuration[test_type][test_config_value]'] = 'This is new config value.';
    $bundle['field_mapping[field_1]'] = 'name';
    $bundle['options[new_revision]'] = TRUE;
    $bundle['options[status]'] = FALSE;
    $bundle['options[queue_thumbnail_downloads]'] = TRUE;

    $this->drupalPostForm(NULL, $bundle, t('Save multibanner bundle'));

    // Test if edit worked and if new field values have been saved as expected.
    $this->drupalGet('admin/structure/multibanner/manage/' . $bundle_id);
    $this->assertFieldByName('label', $bundle['label'], 'Label field has correct value.');
    $this->assertFieldByName('description', $bundle['description'], 'Description field has correct value.');
    $this->assertFieldByName('type', $bundle['type'], 'Test type is selected.');
    $this->assertFieldChecked('edit-options-new-revision', 'Revision checkbox is checked.');
    $this->assertFieldChecked('edit-options-queue-thumbnail-downloads', 'Queue thumbnail checkbox is checked.');
    $this->assertNoFieldChecked('edit-options-status', 'Status checkbox is not checked.');
    $this->assertFieldByName('type_configuration[test_type][test_config_value]', 'This is new config value.');
    $this->assertText('Field 1', 'First metadata field found.');
    $this->assertText('Field 2', 'Second metadata field found.');
    $this->assertFieldByName('field_mapping[field_1]', 'name', 'First metadata field is mapped to the name field.');
    $this->assertFieldByName('field_mapping[field_2]', '_none', 'Second metadata field is not mapped.');

    /** @var \Drupal\multibanner\MultibannerBundleInterface $loaded_bundle */
    $loaded_bundle = $this->container->get('entity_type.manager')
      ->getStorage('multibanner_bundle')
      ->load($bundle_id);
    $this->assertEqual($loaded_bundle->id(), $bundle_id, 'Multibanner bundle ID saved correctly.');
    $this->assertEqual($loaded_bundle->label(), $bundle['label'], 'Multibanner bundle label saved correctly.');
    $this->assertEqual($loaded_bundle->getDescription(), $bundle['description'], 'Multibanner bundle description saved correctly.');
    $this->assertEqual($loaded_bundle->getType()->getPluginId(), $bundle['type'], 'Multibanner bundle type saved correctly.');
    $this->assertEqual($loaded_bundle->getType()->getConfiguration()['test_config_value'], $bundle['type_configuration[test_type][test_config_value]'], 'Multibanner bundle type configuration saved correctly.');
    $this->assertTrue($loaded_bundle->shouldCreateNewRevision(), 'New revisions are configured to be created.');
    $this->assertTrue($loaded_bundle->getQueueThumbnailDownloads(), 'Thumbnails are created through queues.');
    $this->assertFalse($loaded_bundle->getStatus(), 'Default status is unpublished.');
    $this->assertEqual($loaded_bundle->field_map, ['field_1' => $bundle['field_mapping[field_1]']], 'Field mapping was saved correctly.');

    // Test that a multibanner being created with default status to "FALSE" will be
    // created unpublished.
    /** @var MultibannerInterface $unpublished_multibanner */
    $unpublished_multibanner = Multibanner::create(['name' => 'unpublished test multibanner', 'bundle' => $loaded_bundle->id()]);
    $this->assertFalse($unpublished_multibanner->isPublished(), 'Unpublished multibanner correctly created.');

    // Tests multibanner bundle delete form.
    $this->clickLink(t('Delete'));
    $this->assertUrl('admin/structure/multibanner/manage/' . $bundle_id . '/delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertUrl('admin/structure/multibanner');
    $this->assertRaw(t('The multibanner bundle %name has been deleted.', ['%name' => $bundle['label']]));
    $this->assertNoRaw(Xss::filterAdmin($bundle['description']));
    // Test bundle delete prevention when there is existing multibanner.
    $bundle2 = $this->createMultibannerBundle();
    $multibanner = Multibanner::create(['name' => 'lorem ipsum', 'bundle' => $bundle2['id']]);
    $multibanner->save();
    $this->drupalGet('admin/structure/multibanner/manage/' . $bundle2['id']);
    $this->clickLink(t('Delete'));
    $this->assertUrl('admin/structure/multibanner/manage/' . $bundle2['id'] . '/delete');
    $this->assertNoFieldById('edit-submit');
    $this->assertRaw(t('%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', ['%type' => $bundle2['label']]));
  }

  /**
   * Tests the multibanner actions (add/edit/delete).
   */
  public function testMultibannerWithOnlyOneBundle() {
    /** @var \Drupal\multibanner\MultibannerBundleInterface $bundle */
    $bundle = $this->drupalCreateMultibannerBundle();

    // Assert that multibanner item list is empty.
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(200);
    $this->assertText('No content available.');

    $this->drupalGet('multibanner/add');
    $this->assertResponse(200);
    $this->assertUrl('multibanner/add/' . $bundle->id());
    $this->assertFieldChecked('edit-revision', 'New revision should always be created when a new entity is being created.');

    // Tests multibanner item add form.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'revision_log' => $this->randomString(),
    ];
    $this->drupalPostForm('multibanner/add', $edit, t('Save and publish'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    $multibanner_id = $this->container->get('entity.query')->get('multibanner')->execute();
    $multibanner_id = reset($multibanner_id);
    /** @var \Drupal\multibanner\MultibannerInterface $multibanner */
    $multibanner = $this->container->get('entity_type.manager')
      ->getStorage('multibanner')
      ->loadUnchanged($multibanner_id);
    $this->assertEqual($multibanner->getRevisionLogMessage(), $edit['revision_log'], 'Revision log was saved.');

    // Test if the multibanner list contains exactly 1 multibanner bundle.
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(200);
    $this->assertText($edit['name[0][value]']);

    // Tests multibanner edit form.
    $this->drupalGet('multibanner/' . $multibanner_id . '/edit');
    $this->assertNoFieldChecked('edit-revision', 'New revisions are disabled by default.');
    $edit['name[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');

    // Assert that the multibanner list updates after an edit.
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(200);
    $this->assertText($edit['name[0][value]']);

    // Test that there is no empty vertical tabs element, if the container is
    // empty (see #2750697).
    // Make the "Publisher ID" and "Created" fields hidden.
    $edit = [
      'fields[created][parent]' => 'hidden',
      'fields[uid][parent]' => 'hidden',
    ];
    $this->drupalPostForm('/admin/structure/multibanner/manage/' . $bundle->id . '/form-display', $edit, t('Save'));
    // Assure we are testing with a user without permission to manage revisions.
    $this->drupalLogout();
    $this->drupalLogin($this->nonAdminUser);
    // Check the container is not present.
    $this->drupalGet('multibanner/' . $multibanner_id . '/edit');
    // An empty tab container would look like this.
    $raw_html = '<div data-drupal-selector="edit-advanced" data-vertical-tabs-panes><input class="vertical-tabs__active-tab" data-drupal-selector="edit-advanced-active-tab" type="hidden" name="advanced__active_tab" value="" />' . "\n" . '</div>';
    $this->assertNoRaw($raw_html);
    // Continue testing as admin.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Enable revisions by default.
    $bundle->setNewRevision(TRUE);
    $bundle->save();
    $this->drupalGet('multibanner/' . $multibanner_id . '/edit');
    $this->assertFieldChecked('edit-revision', 'New revisions are disabled by default.');
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'revision_log' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    /** @var \Drupal\multibanner\MultibannerInterface $multibanner */
    $multibanner = $this->container->get('entity_type.manager')
      ->getStorage('multibanner')
      ->loadUnchanged($multibanner_id);
    $this->assertEqual($multibanner->getRevisionLogMessage(), $edit['revision_log'], 'Revision log was saved.');

    // Tests multibanner delete form.
    $this->drupalPostForm('multibanner/' . $multibanner_id . '/delete', [], t('Delete'));
    $multibanner_id = \Drupal::entityQuery('multibanner')->execute();
    $this->assertFalse($multibanner_id);

    // Assert that the multibanner list is empty after deleting the multibanner item.
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(200);
    $this->assertNoText($edit['name[0][value]']);
    $this->assertText('No content available.');
  }

  /**
   * Tests the views wizards provided by the multibanner module.
   */
  public function testMultibannerViewsWizard() {
    $bundle = $this->drupalCreateMultibannerBundle();
    $data = [
      'name' => $this->randomMachineName(),
      'bundle' => $bundle->id(),
      'type' => 'Unknown',
      'uid' => $this->adminUser->id(),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'status' => Multibanner::PUBLISHED,
    ];
    $multibanner = Multibanner::create($data);
    $multibanner->save();

    // Test the Multibanner wizard.
    $this->drupalPostForm('admin/structure/views/add', [
      'label' => 'multibanner view',
      'id' => 'multibanner_test',
      'show[wizard_key]' => 'multibanner',
      'page[create]' => 1,
      'page[title]' => 'multibanner_test',
      'page[path]' => 'multibanner_test',
    ], t('Save and edit'));

    $this->drupalGet('multibanner_test');
    $this->assertText($data['name']);

    user_role_revoke_permissions('anonymous', ['access content']);
    $this->drupalLogout();
    $this->drupalGet('multibanner_test');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);

    // Test the MultibannerRevision wizard.
    $this->drupalPostForm('admin/structure/views/add', [
      'label' => 'multibanner revision view',
      'id' => 'multibanner_revision',
      'show[wizard_key]' => 'multibanner_revision',
      'page[create]' => 1,
      'page[title]' => 'multibanner_revision',
      'page[path]' => 'multibanner_revision',
    ], t('Save and edit'));

    $this->drupalGet('multibanner_revision');
    // Check only for the label of the changed field as we want to only test
    // if the field is present and not its value.
    $this->assertText($data['name']);

    user_role_revoke_permissions('anonymous', ['view revisions']);
    $this->drupalLogout();
    $this->drupalGet('multibanner_revision');
    $this->assertResponse(403);
  }

  /**
   * Tests the "multibanner/add" and "admin/content/multibanner" pages.
   *
   * Tests if the "multibanner/add" page gives you a selecting option if there are
   * multiple multibanner bundles available.
   */
  public function testMultibannerWithMultipleBundles() {
    // Test access to multibanner overview page.
    $this->drupalLogout();
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');

    // Test there is a multibanner tab in the menu.
    $this->clickLink('Multibanner');
    $this->assertResponse(200);
    $this->assertText('No content available.');

    // Tests and creates the first multibanner bundle.
    $first_multibanner_bundle = $this->createMultibannerBundle();

    // Test and create a second multibanner bundle.
    $second_multibanner_bundle = $this->createMultibannerBundle();

    // Test if multibanner/add displays two multibanner bundle options.
    $this->drupalGet('multibanner/add');

    // Checks for the first multibanner bundle.
    $this->assertRaw($first_multibanner_bundle['label']);
    $this->assertRaw(Xss::filterAdmin($first_multibanner_bundle['description']));

    // Checks for the second multibanner bundle.
    $this->assertRaw($second_multibanner_bundle['label']);
    $this->assertRaw(Xss::filterAdmin($second_multibanner_bundle['description']));

    // Continue testing multibanner bundle filter.
    $this->doTestMultibannerBundleFilter($first_multibanner_bundle, $second_multibanner_bundle);
  }

  /**
   * Creates and tests a new multibanner bundle.
   *
   * @return array
   *   Returns the multibanner bundle fields.
   */
  public function createMultibannerBundle() {
    // Generates and holds all multibanner bundle fields.
    $name = $this->randomMachineName();
    $edit = [
      'id' => strtolower($name),
      'label' => $name,
      'type' => 'generic',
      'description' => $this->randomMachineName(),
    ];

    // Create new multibanner bundle.
    $this->drupalPostForm('admin/structure/multibanner/add', $edit, t('Save multibanner bundle'));
    $this->assertText('The multibanner bundle ' . $name . ' has been added.');

    // Check if multibanner bundle is successfully created.
    $this->drupalGet('admin/structure/multibanner');
    $this->assertResponse(200);
    $this->assertRaw($edit['label']);
    $this->assertRaw(Xss::filterAdmin($edit['description']));

    return $edit;
  }

  /**
   * Creates a multibanner item in the multibanner bundle that is passed along.
   *
   * @param array $multibanner_bundle
   *   The multibanner bundle the multibanner item should be assigned to.
   *
   * @return array
   *   Returns the
   */
  public function createMultibannerItem($multibanner_bundle) {
    // Define the multibanner item name.
    $name = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $name,
    ];
    // Save it and retrieve new multibanner item ID, then return all information.
    $this->drupalPostForm('multibanner/add/' . $multibanner_bundle['id'], $edit, t('Save and publish'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    $multibanner_id = \Drupal::entityQuery('multibanner')->execute();
    $multibanner_id = reset($multibanner_id);
    $edit['id'] = $multibanner_id;

    return $edit;
  }

  /**
   * Tests the multibanner list filter functionality.
   */
  public function doTestMultibannerBundleFilter($first_multibanner_bundle, $second_multibanner_bundle) {
    // Assert that the list is not empty and contains at least 2 multibanner items
    // with each a different multibanner bundle.
    (is_array($first_multibanner_bundle) && is_array($second_multibanner_bundle) ?: $this->assertTrue(FALSE));

    $first_multibanner_item = $this->createMultibannerItem($first_multibanner_bundle);
    $second_multibanner_item = $this->createMultibannerItem($second_multibanner_bundle);

    // Go to multibanner item list.
    $this->drupalGet('admin/content/multibanner');
    $this->assertResponse(200);
    $this->assertLink('Add multibanner');

    // Assert that all available multibanner items are in the list.
    $this->assertText($first_multibanner_item['name[0][value]']);
    $this->assertText($first_multibanner_bundle['label']);
    $this->assertText($second_multibanner_item['name[0][value]']);
    $this->assertText($second_multibanner_bundle['label']);

    // Filter for each bundle and assert that the list has been updated.
    $this->drupalGet('admin/content/multibanner', ['query' => ['provider' => $first_multibanner_bundle['id']]]);
    $this->assertResponse(200);
    $this->assertText($first_multibanner_item['name[0][value]']);
    $this->assertText($first_multibanner_bundle['label']);
    $this->assertNoText($second_multibanner_item['name[0][value]']);

    $this->drupalGet('admin/content/multibanner', ['query' => ['provider' => $second_multibanner_bundle['id']]]);
    $this->assertResponse(200);
    $this->assertNoText($first_multibanner_item['name[0][value]']);
    $this->assertText($second_multibanner_item['name[0][value]']);
    $this->assertText($second_multibanner_bundle['label']);

    // Filter all and check for all items again.
    $this->drupalGet('admin/content/multibanner', ['query' => ['provider' => 'All']]);
    $this->assertResponse(200);
    $this->assertText($first_multibanner_item['name[0][value]']);
    $this->assertText($first_multibanner_bundle['label']);
    $this->assertText($second_multibanner_item['name[0][value]']);
    $this->assertText($second_multibanner_bundle['label']);
  }

}
