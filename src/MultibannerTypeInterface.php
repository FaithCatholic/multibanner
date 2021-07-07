<?php

namespace Drupal\multibanner;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for multibanner types.
 */
interface MultibannerTypeInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface {

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Gets list of fields provided by this plugin.
   *
   * @return array
   *   Associative array with field names as keys and descriptions as values.
   */
  public function providedFields();

  /**
   * Gets a multibanner-related field/value.
   *
   * @param MultibannerInterface $multibanner
   *   Multibanner object.
   * @param string $name
   *   Name of field to fetch.
   *
   * @return mixed
   *   Field value or FALSE if data unavailable.
   */
  public function getField(MultibannerInterface $multibanner, $name);

  /**
   * Attaches type-specific constraints to multibanner.
   *
   * @param MultibannerInterface $multibanner
   *   multibanner.
   */
  public function attachConstraints(MultibannerInterface $multibanner);

  /**
   * Gets thumbnail image.
   *
   * Multibanner type plugin is responsible for returning URI of the generic thumbnail
   * if no other is available. This functions should always return a valid URI.
   *
   * @param MultibannerInterface $multibanner
   *   Multibanner.
   *
   * @return string
   *   URI of the thumbnail.
   */
  public function thumbnail(MultibannerInterface $multibanner);

  /**
   * Gets the default thumbnail image.
   *
   * @return string
   *   Uri of the default thumbnail image.
   */
  public function getDefaultThumbnail();

  /**
   * Provide a default name for the multibanner.
   *
   * Plugins defining multibanner bundles are suggested to override this method and
   * provide a default name, to be used when there is no user-defined label
   * available.
   *
   * @param \Drupal\multibanner\MultibannerInterface $multibanner
   *   The multibanner object.
   *
   * @return string
   *   The string that should be used as default multibanner name.
   */
  public function getDefaultName(MultibannerInterface $multibanner);

}
