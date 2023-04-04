<?php

/**
 * WP Redis.
 *
 * @link https://wordpress.org/plugins/wp-redis/
 */

namespace Nexcess\MAPPS\Plugins;

use Nexcess\MAPPS\Integrations\ObjectCache;
use Nexcess\MAPPS\Services\DropIn;
use Nexcess\MAPPS\Services\WPConfig;
use Nexcess\MAPPS\Settings;

class WPRedis extends Plugin {

	/**
	 * @var \Nexcess\MAPPS\Services\WPConfig
	 */
	protected $config;

	/**
	 * @var \Nexcess\MAPPS\Services\DropIn
	 */
	protected $dropIn;

	/**
	 * @var \Nexcess\MAPPS\Integrations\ObjectCache
	 */
	protected $objectCache;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * Construct the plugin instance.
	 *
	 * @param \Nexcess\MAPPS\Settings                 $settings
	 * @param \Nexcess\MAPPS\Services\DropIn          $drop_in
	 * @param \Nexcess\MAPPS\Services\WPConfig        $config
	 * @param \Nexcess\MAPPS\Integrations\ObjectCache $object_cache
	 */
	public function __construct( Settings $settings, DropIn $drop_in, WPConfig $config, ObjectCache $object_cache ) {
		$this->settings    = $settings;
		$this->dropIn      = $drop_in;
		$this->config      = $config;
		$this->objectCache = $object_cache;
	}

	/**
	 * Actions to perform upon plugin activation.
	 *
	 * @param bool $network_wide Optional. Is the plugin being activated network-wide?
	 *                           Default is false.
	 */
	public function activate( $network_wide = false ) {
		if ( ! $this->settings->redis_host || ! $this->settings->redis_port ) {
			return;
		}

		if ( ! $this->dropIn->install( 'object-cache.php', $this->pluginDir . '/object-cache.php' ) ) {
			return;
		}

		$this->config->setVariable( 'redis_server', [
			'host' => $this->settings->redis_host,
			'port' => $this->settings->redis_port,
		] );
	}

	/**
	 * Actions to perform upon plugin deactivation.
	 *
	 * @param bool $network_wide Optional. Is the plugin being deactivated network-wide?
	 *                           Default is false.
	 */
	public function deactivate( $network_wide = false ) {
		if ( $this->dropIn->remove( 'object-cache.php', $this->pluginDir . '/object-cache.php' ) ) {
			$this->objectCache->installObjectCacheDropIn();
		}

		$this->config->removeVariable( 'redis_server' );
	}
}
