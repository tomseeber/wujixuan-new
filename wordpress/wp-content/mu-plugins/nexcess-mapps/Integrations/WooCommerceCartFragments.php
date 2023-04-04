<?php

/**
 * WooCommerce Cart Fragments.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasAdminPages;
use Nexcess\MAPPS\Concerns\HasHooks;
use Nexcess\MAPPS\Concerns\HasWordPressDependencies;
use Nexcess\MAPPS\Concerns\ManagesGroupedOptions;
use Nexcess\MAPPS\HealthChecks\WooCommerceCartFragments as WooCommerceCartFragmentsHealthCheck;
use Nexcess\MAPPS\Services\Managers\SiteHealthManager;
use Nexcess\MAPPS\Settings;

class WooCommerceCartFragments extends Integration {
	use HasAdminPages;
	use HasHooks;
	use HasWordPressDependencies;
	use ManagesGroupedOptions;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * @var \Nexcess\MAPPS\Services\Managers\SiteHealthManager
	 */
	protected $siteHealthManager;

	/**
	 * The option for disabling cart fragments.
	 */
	const OPTION_NAME = 'nexcess_mapps_woocommerce';

	/**
	 * @param \Nexcess\MAPPS\Settings                            $settings
	 * @param \Nexcess\MAPPS\Services\Managers\SiteHealthManager $site_health_manager
	 */
	public function __construct( Settings $settings, SiteHealthManager $site_health_manager ) {
		$this->settings          = $settings;
		$this->siteHealthManager = $site_health_manager;
	}

	/**
	 * Determine whether or not this integration should be loaded.
	 *
	 * @return bool Whether or not this integration be loaded in this environment.
	 */
	public function shouldLoadIntegration() {
		return $this->isPluginActive( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Set up the integration.
	 */
	public function setup() {
		$this->siteHealthManager->addCheck( WooCommerceCartFragmentsHealthCheck::class, false );

		$this->addHooks();
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [
			[ 'wp_enqueue_scripts', [ $this, 'dequeueCartFragments' ], 20 ],
		];
	}

	/**
	 * Retrieve all filters for the integration.
	 *
	 * @return array[]
	 */
	protected function getFilters() {
		return [
			// Register our toggle-able setting.
			[ 'Nexcess\MAPPS\SettingsPage\RegisterSetting', [ $this, 'registerSetting' ] ],

			// Make sure the settings page shows when we need it.
			[ 'Nexcess\MAPPS\SettingsPage\IsEnabled', '__return_true' ],
		];
	}

	/**
	 * Enable cart fragments.
	 *
	 * These booleans are backwards from what you'd expect, but that is because
	 * we want the option to be 'status' to prevent confusion, but we also want
	 * the option page to be 'turn ON this setting to turn OFF cart fragments'.
	 */
	public function enableCartFragments() {
		$this->getOption()->set( 'cart_fragments_status', false )->save();
	}

	/**
	 * Disable cart fragments.
	 */
	public function disableCartFragments() {
		$this->getOption()->set( 'cart_fragments_status', true )->save();
	}

	/**
	 * Get the current setting for Cart Fragments.
	 *
	 * @return string Either 'enabled' or 'disabled'.
	 */
	public function getCartFragmentsSetting() {
		return $this->getOption()->cart_fragments_status ? 'disabled' : 'enabled';
	}

	/**
	 * Add a toggle to the settings page.
	 *
	 * @param array $settings Current settings.
	 *
	 * @return array Current settings.
	 */
	public function registerSetting( $settings ) {
		$settings[] = [
			'key'  => [ self::OPTION_NAME, 'cart_fragments_status' ],
			'type' => 'checkbox',
			'name' => __( 'Disable WooCommerce Cart Fragments', 'nexcess-mapps' ),
			'desc' => __( "By default, WooCommerce includes a 'cart fragments' script that makes a number of uncached AJAX requests on every page load, which can hurt site performance. It's recommended to disable cart fragments unless absolutely necessary.", 'nexcess-mapps' ),
		];

		return $settings;
	}

	/**
	 * Determine whether or not to dequeue the cart fragments script.
	 *
	 * @return bool Whether or not to dequeue the cart fragments script.
	 */
	public function shouldDequeueCartFragments() {
		return ( 'disabled' === $this->getCartFragmentsSetting() );
	}

	/**
	 * Dequeue the cart fragment JS if needed.
	 */
	public function dequeueCartFragments() {
		if ( wp_script_is( 'wc-cart-fragments', 'enqueued' ) && $this->shouldDequeueCartFragments() ) {
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}
}
