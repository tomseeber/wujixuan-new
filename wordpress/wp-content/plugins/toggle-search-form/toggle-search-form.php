<?php

/**
 * Plugin Name: Toggle Search Form Module
 * Plugin URI: https://www.wpbeaverbuilder.com/shop/toggle-search-form/
 * Description: Adds toggle search form on you site using this awesome Beaver Builder module.
 * Version: 1.3.6
 * Author: WP Beaver World
 * Author URI: https://www.wpbeaverworld.com/
 * Copyright: (c) 2017 Beaver Builder
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: toggle-search-form
 */

//* Prevent direct access to the plugin
if ( !defined( 'ABSPATH' ) ) {
  wp_die( __( "Sorry, you are not allowed to access this page directly.", 'toggle-search-form' ) );
}

define( 'TSF_VERSION', '1.3.6' );
define( 'TSF_FILE', trailingslashit( dirname( __FILE__ ) ) . 'toggle-search-form.php' );
define( 'TSF_DIR', plugin_dir_path( TSF_FILE ) );
define( 'TSF_URL', plugins_url( '/', TSF_FILE ) );

register_activation_hook( __FILE__, 'tsf_activate' );

add_action( 'admin_init',			'tsf_plugin_deactivate' );
add_action( 'switch_theme',			'tsf_plugin_deactivate' );
add_action( 'plugins_loaded', 		'tsf_load', 2003 );
add_action( 'after_setup_theme', 	'tsf_load_files' );
add_action( 'init', 				'tsf_load_module', 2004);

/**
 * Activate the plugin
 */
function tsf_activate()
{
	if ( ! class_exists('FLBuilder') )
	{
		//* Deactivate ourself
		deactivate_plugins( TSF_FILE );
		add_action( 'admin_notices', 'tsf_admin_notice_message' );
		return;	
	}
}

/**
 * This function is triggered when the WordPress theme is changed.
 * It checks if the Beaver Builder plugin is active. If not, it deactivates itself.
 *
 * @since 1.0
 */
function tsf_plugin_deactivate()
{
	if ( ! class_exists('FLBuilder') )
	{
		//* Deactivate ourself
		deactivate_plugins( TSF_FILE );
		add_action( 'admin_notices', 'tsf_admin_notice_message' );
	}
}

/**
 * Shows an admin notice if you're not using the Beaver Builder Plugin.
 */
function tsf_admin_notice_message()
{
	if ( ! is_admin() ) {
		return;
	}
	else if ( ! is_user_logged_in() ) {
		return;
	}
	else if ( ! current_user_can( 'update_core' ) ) {
		return;
	}

	$error = __( 'Sorry, you can\'t use the Toggle Search Form module unless the Beaver Builder Plugin is active. The plugin has been deactivated.', 'toggle-search-form' );

	echo '<div class="error"><p>' . $error . '</p></div>';

	if ( isset( $_GET['activate'] ) )
	{
		unset( $_GET['activate'] );
	}
}

/**
 * Loads plugin.
 */ 
function tsf_load()
{
	//* Load textdomain for translation 
	load_plugin_textdomain( 'toggle-search-form', false, basename( TSF_DIR ) . '/languages' );
}

/**
 * Loads classes and includes.
 */ 
function tsf_load_files()
{
	if ( ! class_exists('FLBuilder') )
		return;

	if( is_admin() ) {
		require_once TSF_DIR . 'classes/class-toggle-search-form-admin.php';
		ToggleSearchFormAdmin::init();

		require_once TSF_DIR . 'classes/class-tsf-updater.php';
		new TSF_Plugin_Updater( 'https://www.wpbeaverworld.com/update-api/', TSF_VERSION );
	}
}

/**
 * Loads Toggle Search Form Menu Module.
 */
function tsf_load_module()
{
	if ( ! class_exists('FLBuilder') )
		return;

	require_once TSF_DIR . 'module/toggle-search-form/toggle-search-form.php';
}