<?php

/**
 * ToggleSearchFormAdmin class.
 *
 * @subpackage  classes
 * @package     toggle-search-form
 *
 * @author      WP Beaver World
 * @link        https://www.wpbeaverworld.com
 * @copyright   Copyright (c) 2016 WP Beaver World.
 *
 * @since       1.0
 */
class ToggleSearchFormAdmin {

	/**
	 * Options.
	 *
	 * @author    WP Beaver World
	 * @var       array
	 * @access    public
	 */
	static public $options;

	/**
	 * Action added on the init hook.
	 * and instantiates the ToggleSearchFormAdmin object.
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	static public function init() {

		new ToggleSearchFormAdmin();

	}
  
	/**
	 * Get license key data
	 * Create admin menu pages
	 * Create a settings page
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	function __construct() {
		self::$options = get_option( 'tsf_options' );

		add_action( 'admin_menu', array( $this, 'tsf_register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'tsf_activate_license_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'tsf_admin_enqueue_scripts' ) );
	}

	/**
	 * Register sub menu page
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	public function tsf_register_admin_menu () {
		add_submenu_page( 'options-general.php', __( 'License Key Settings', 'toggle-search-form' ) , __( 'TSF License Key', 'toggle-search-form' ), 'manage_options', 'tsf-activate-key', array( $this, 'render_options_form' ) );
	}

	/**
	 * Action on admin_init hook
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	function tsf_activate_license_settings() {
		register_setting( 'tsf_activate_license', 'tsf_license' );

		add_settings_section(
			'tsf_license_key_section', 
			'<span class="tsf-lkey-heading">' . __( 'License Settings', 'toggle-search-form' ) . '</span>', 
			array( $this, 'tsf_license_callback' ), 
			'tsf_activate_license'
		);

		add_settings_field( 
			'tsf_license_key', 
			__( 'License Key', 'toggle-search-form' ), 
			array( $this, 'tsf_license_key' ), 
			'tsf_activate_license', 
			'tsf_license_key_section' 
		);
	}

	/** 
	 * Callback function
	 *
	 * @author  WP Beaver World
	 *
	 * @since   1.0
	 * @access  public
	 * @return  void    
	 */
	function tsf_license_callback() {
		echo '<p class="description desc">' . "\n";
		echo __( 'The license key is used for automatic upgrades and support.', 'toggle-search-form');
		echo '</p>' . "\n";
	}

	/**
	 * Activate the plugin for auto update & support
	 * Create settings form fields
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	function tsf_license_key() {
		$options      = self::$options;
		$license_key  = $options['tsf_license_key'];
		$tsf_nonce    = wp_create_nonce( 'tsf-activate-key' );
		$class= $style = '';
	?>
		<input type="password" class="regular-text code" id="tsf_license_key" name='tsf_options[tsf_license_key]' value="<?php echo esc_attr( $license_key ); ?>" />
		<?php if( ( get_option('tsf_plugin_activate') == 'no' ) || ( get_option('tsf_plugin_activate') == '' ) ) { $class=''; $style=' style="display:none;"'; ?>
			<input type="button" class="button" id="btn-activate-license" value="<?php _e( 'Activate', 'toggle-search-form' ); ?>" onclick="JavaScript: ActivateTSFPlugin( 'tsf_license_key', 'activate', '<?php echo $tsf_nonce; ?>');" />
			<div class="spinner" id="actplug"></div>
		<?php } ?> 
		<?php if( get_option('tsf_plugin_activate') == 'expired' ) { $class=' error'; $style=' style="display:none;"'; ?>
			<input type="button" class="button" id="btn-reactivate-license" value="<?php _e( 'Reactivate', 'toggle-search-form' ); ?>" onclick="JavaScript: ActivateTSFPlugin( 'tsf_license_key', 'reactivate', '<?php echo $tsf_nonce; ?>');" />
			<div class="spinner"></div>
		<?php } ?>                                              
		<span class="tsf-response<?php echo $class; ?>"<?php echo $style; ?>></span>
		<?php if( get_option('tsf_plugin_activate') == 'expired' ) { ?>
			<div class="update-nag" style="color: #900"> <?php _e( 'Invalid or Expired Key : Please make sure you have entered the correct value and that your key is not expired.', 'toggle-search-form'); ?></div>
	<?php }
	}

	/**  
	 * Render options form
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	function render_options_form() {
	?>
		<div class="wrap tsf-options">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2><?php _e( 'Toggle Search Form Module (TSFM)', 'toggle-search-form' ); ?> v<?php echo TSF_VERSION; ?></h2>
			<form action='options.php' method='post' class="tsf-options-form" id="tsf-options-form">
				<?php
					settings_fields( 'tsf_activate_license' );
					do_settings_sections( 'tsf_activate_license' );
				?>
			</form>
		</div>
	<?php
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @author  WP Beaver World
	 * @since   1.0
	 *
	 * @access  public
	 * @return  void
	 */
	function tsf_admin_enqueue_scripts( $hook ) {
		if( $hook !== 'settings_page_tsf-activate-key' )
			return;

		wp_register_style( 'tsf-admin-css', TSF_URL . 'assets/css/tsf-admin.css', array() );
		wp_enqueue_style( 'tsf-admin-css'   );
		wp_enqueue_script( 'tsf-admin-script', TSF_URL . 'assets/js/activate-plugin.js', array(), '1.0', true );
	}
}