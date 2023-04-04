<?php

/**
 * Responsible for setting up the plugin
 *
 * @since 1.0
 */
final class ToggleSearchFormLoader {
	/**
	 * Sets up the plugins_loaded action to load the plugin.
	 *
	 * @since 1.0
	 * @return void
	 */ 
	static public function init()
	{
		self::define_constants();
		
		register_activation_hook( TSF_FILE, __CLASS__ . '::tsf_activate' );
		
		add_action( 'admin_init',		__CLASS__ . '::tsf_plugin_deactivate' );
		add_action( 'switch_theme',		__CLASS__ . '::tsf_plugin_deactivate' );
		add_action( 'plugins_loaded', 	__CLASS__ . '::tsf_load', 2003 );
		add_action( 'init', 			__CLASS__ . '::tsf_load_module', 2004);
	}

	/**
	 * Define constants.
	 *
	 * @since 1.0
	 * @return void
	 */ 
	static private function define_constants()
	{
		define( 'TSF_VERSION', '1.3.1' );
		define( 'TSF_FILE', trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'toggle-search-form.php' );
		define( 'TSF_DIR', plugin_dir_path( TSF_FILE ) );
		define( 'TSF_URL', plugins_url( '/', TSF_FILE ) );
	}

	/**
	 * Activate the plugin
	 *
	 * @since 1.0
	 * @return void
	 */ 
	static public function tsf_activate()
	{
		if ( ! class_exists('FLBuilder') )
		{
			//* Deactivate ourself
			deactivate_plugins( TSF_FILE );
			add_action( 'admin_notices', __CLASS__ . '::tsf_admin_notice_message' );
			return;	
		}
	}

	/**
	 * This function is triggered when the WordPress theme is changed.
	 * It checks if the Beaver Builder plugin is active. If not, it deactivates itself.
	 *
	 * @since 1.0
	 */
	static public function tsf_plugin_deactivate()
	{
		if ( ! class_exists('FLBuilder') )
		{
			//* Deactivate ourself
			deactivate_plugins( TSF_FILE );
			add_action( 'admin_notices', __CLASS__ . '::tsf_admin_notice_message' );
		}
	}

	/**
	 * Shows an admin notice if you're not using the Beaver Builder Plugin.
	 *
	 * @since 1.0
	 */
	static public function tsf_admin_notice_message()
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
	 *
	 * @since 1.0
	 * @return void
	 */ 
	static public function tsf_load()
	{
		//* Load textdomain for translation 
		load_plugin_textdomain( 'toggle-search-form', false, basename( TSF_DIR ) . '/languages' );
		self::tsf_load_files();
	}

	/**
	 * Loads classes and includes.
	 *
	 * @since 1.0
	 * @return void
	 */ 
	static private function tsf_load_files()
	{
		if( is_admin() ) {
			require_once TSF_DIR . 'classes/class-toggle-search-form-admin.php';
			add_action( 'after_setup_theme', array( 'ToggleSearchFormAdmin', 'init' ) );

			require_once TSF_DIR . 'classes/class-tsf-updater.php';
			new TSF_Plugin_Updater( 'https://www.wpbeaverworld.com/update-api/', TSF_VERSION );
		}
	}

	/**
	 * Loads Toggle Search Form Menu Module.
	 *
	 * @since 1.0
	 * @return void
	 */
	static public function tsf_load_module() {
		require_once TSF_DIR . 'module/toggle-search-form/toggle-search-form.php';
	}
}

ToggleSearchFormLoader::init();