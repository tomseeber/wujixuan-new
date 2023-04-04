<?php
/**
 * Plugin Name: Nexcess Managed Apps
 * Plugin URI:  https://www.nexcess.net
 * Description: Functionality to support the Nexcess Managed Apps WordPress and WooCommerce platforms.
 * Version:     1.26.1
 * Author:      Nexcess
 * Author URI:  https://www.nexcess.net
 * Text Domain: nexcess-mapps
 * Awesome:     Yes.
 *
 * For details on how to customize the MU plugin behavior, please see nexcess-mapps/README.md.
 */

namespace Nexcess\MAPPS;

use Nexcess\MAPPS\Support\Branding;
use Nexcess\MAPPS\Support\PlatformRequirements;

// At this time, the MU plugin doesn't need to do anything if WordPress is currently installing.
if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

// The version of the Nexcess Managed Apps plugin.
define( __NAMESPACE__ . '\PLUGIN_VERSION', '1.26.1' );
define( __NAMESPACE__ . '\PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( __NAMESPACE__ . '\VENDOR_DIR', __DIR__ . '/nexcess-mapps/vendor/' );

// Initialize the plugin.
try {
	require_once VENDOR_DIR . 'autoload.php';

	// Check for anything that might prevent the plugin from loading.
	$requirements = new PlatformRequirements();

	if ( ! $requirements->siteMeetsMinimumRequirements() ) {
		return $requirements->renderUnsupportedWordPressVersionNotice();
	}

	// Finish loading files that should be explicitly required.
	require_once __DIR__ . '/nexcess-mapps/Support/Compat.php';
	require_once __DIR__ . '/nexcess-mapps/vendor/stevegrunwell/wp-admin-tabbed-settings-pages/wp-admin-tabbed-settings-pages.php';

	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$plugin = ( new Container() )->get( Plugin::class );
	$plugin->bootstrap();
} catch ( Exceptions\IsNotNexcessSiteException $e ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	trigger_error( esc_html( sprintf(
		'The %1$s plugin may only be loaded on the %2$s platform.',
		Branding::getCompanyName(),
		Branding::getPlatformName()
	) ), E_USER_NOTICE );
} catch ( \Exception $e ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	trigger_error( esc_html( sprintf(
		'%1$s Error: %2$s',
		Branding::getPlatformName(),
		$e->getMessage()
	) ), E_USER_WARNING );
}
