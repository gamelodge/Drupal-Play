<?php

/**
 * @file
 * Contains \Drupal\plugin\PluginOperationsProviderInterface.
 */

namespace Drupal\plugin;

/**
 * Defines a plugin operations provider.
 *
 * Classes may also implement any of the following interfaces:
 * - \Drupal\Core\DependencyInjection\ContainerInjectionInterface
 */
interface PluginOperationsProviderInterface {

  /**
   * Gets plugin operations.
   *
   * @param string $plugin_id
   *   The ID of the plugin the operations are for.
   *
   * @return array[]
   *   An array with the same structure as
   *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations()' return
   *   value.
   */
  public function getOperations($plugin_id);

}
