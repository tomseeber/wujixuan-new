<?php
// Defines
define('FL_CHILD_THEME_DIR', get_stylesheet_directory());
define('FL_CHILD_THEME_URL', get_stylesheet_directory_uri());

// Classes
require_once 'classes/class-woo360-child-theme.php';

require_once 'classes/class-dynamic-content.php';
new UXI_SEO_Dynamic_Content();

require_once 'madwire-settings.php';

// Actions
add_action('wp_enqueue_scripts', 'Woo360ChildTheme::enqueue_scripts', 1000);

// Set up custom login screen styles
add_action('login_enqueue_scripts', 'Woo360ChildTheme::enqueue_login_styles');

// custom admin styles
add_action('admin_enqueue_scripts', 'Woo360ChildTheme::enqueue_custom_wp_admin_styles');

// create a maintenance/suspended site notice if the lightspeed site for the client is down (checks for 404 on lightspeed url from madwire settings)
add_action('wp_loaded', 'Woo360ChildTheme::maintenance_mode');

// woo360 tracking scripts for adding up
add_action('fl_after_footer', 'Woo360ChildTheme::add_tracking');

// creating markup for settings
add_action('admin_init', 'woo360child_settings_init');

add_action('fl_after_footer', 'Woo360ChildTheme::m360_dynamic_content');

// Custom theme update checker
require 'update-checker/plugin-update-checker.php';
$woo360childchecker = Puc_v4_Factory::buildUpdateChecker(
    'https://updates.madwire.com/files/woo360-theme-child/details.json',
    __FILE__, //Full path to the main plugin file or functions.php.
    'woo360-theme-child'
);
// $woo360childchecker = Puc_v4_Factory::buildUpdateChecker(
//     'http://updates.madwire.com/files/woo360-theme-child/details.json',
//     __FILE__, //Full path to the main plugin file or functions.php.
//     'woo360-theme-child'
// );
// helper class for beuilder functions
if (class_exists('FLBuilder')) {
    require_once 'helper.php';
}

/**
 *
 * @see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.6.1
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */

require_once get_stylesheet_directory() . '/req-plugins/class-tgm-plugin-activation.php';
add_action('tgmpa_register', 'Woo360ChildTheme::register_required_plugins');

// CUSTOM LIGHTSPEED SETTINGS PAGE - MADWIRE
// show settings page only accessible to madwire user specifically

if (is_admin()) {
    $currentUser = wp_get_current_user()->data->user_login;
    if ($currentUser == 'madwire') {
        add_action('admin_menu', 'woo360child_add_admin_menu');
    }
}

function woo360child_add_admin_menu() {
    $page_title = 'Lightspeed Woo360 Options';
    $menu_title = 'Lightspeed Woo360 Options';
    $capability = 'edit_posts';
    $menu_slug  = 'lightspeed-woo360-options';
    $function   = 'woo360child_options_page';

    add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
}

/**
 * Adds a meta box to the post editing screen
 */
function m360_dynamic_meta() {
    add_meta_box('m360_dynamic_meta', __('Dynamic Keyword Insertion', 'm360-dynamic-textdomain'), 'm360_dynamic_meta_callback', 'page', 'side', 'high');
}
add_action('add_meta_boxes', 'm360_dynamic_meta');

/**
 * Outputs the content of the meta box
 */

function m360_dynamic_meta_callback($post) {
    wp_nonce_field(basename(__FILE__), 'm360_dynamic_nonce');
    $m360_dynamic_stored_meta = get_post_meta($post->ID);
    ?>


    <span class="m360-dynamic-row-title"><?php _e('Check if you would like to insert dynamic content based on URL query parameters.', 'm360-dynamic-textdomain');?></span>
    <div class="m360-dynamic-row-content">
        <label for="m360-dynamic-checkbox">
            <input type="checkbox" name="m360-dynamic-checkbox" id="m360-dynamic-checkbox" value="yes" <?php if (isset($m360_dynamic_stored_meta['m360-dynamic-checkbox'])) {
        checked($m360_dynamic_stored_meta['m360-dynamic-checkbox'][0], 'yes');
    }
    ?> />
        </label>
    </div>

    <?php }

/**
 * Saves the custom meta input
 */
function m360_dynamic_meta_save($post_id) {

    // Checks save status - overcome autosave, etc.
    $is_autosave    = wp_is_post_autosave($post_id);
    $is_revision    = wp_is_post_revision($post_id);
    $is_valid_nonce = (isset($_POST['m360_dynamic_nonce']) && wp_verify_nonce($_POST['m360_dynamic_nonce'], basename(__FILE__))) ? 'true' : 'false';

    // Exits script depending on save status
    if ($is_autosave || $is_revision || !$is_valid_nonce) {
        return;
    }

// Checks for input and saves - save checked as yes and unchecked at no
    if (isset($_POST['m360-dynamic-checkbox'])) {
        update_post_meta($post_id, 'm360-dynamic-checkbox', 'yes');
    } else {
        update_post_meta($post_id, 'm360-dynamic-checkbox', 'no');
    }

}
add_action('save_post', 'm360_dynamic_meta_save');

function woo360_heartbeat_settings( $settings ) {
    $settings['interval'] = 90;
    return $settings;
}

add_filter( 'heartbeat_settings', 'woo360_heartbeat_settings' );

/* Whitelabel Beaver builder on theme activation */
function woo360_whitelabel() {
    update_option('_fl_builder_branding', 'Woo360 Builder');
    update_option('_fl_builder_branding_icon', '../wp-content/themes/woo360-theme-child/assets/images/woo360-logo_xs.png');
    update_option('_fl_builder_theme_branding', array(
        'name' => 'Woo360 - Main',
        'description' => 'Woo360 Core Theme for building amazing websites.',
        'company_name' => 'Marketing360',
        'company_url' => 'https://www.marketing360.com',
        'screenshot_url' => '../wp-content/themes/woo360-theme-child/assets/images/woo360-theme-main.jpg'
    ));
}
add_action("after_switch_theme", "woo360_whitelabel", 10);

/* Remove Beaver builder whitelabel on theme deactivation */
function woo360_remove_whitelabel() {
    update_option('_fl_builder_branding', '');
    update_option('_fl_builder_branding_icon', '');
    update_option('_fl_builder_theme_branding', array(
        'name' => '',
        'description' => '',
        'company_name' => '',
        'company_url' => '',
        'screenshot_url' => ''
    ));
}
add_action('switch_theme', 'woo360_remove_whitelabel', 10);

// Add a field for a Houzz link to the theme customizer
function woo360_customizer_social_media_icons( $customizer )
{
    $customizer->add_setting( 'fl-social-houzz', array( 
        'default' => '' 
    ) );
    
    $customizer->add_control(
        new WP_Customize_Control( $customizer, 'fl-social-houzz', array(
            'label'     => 'Houzz',
            'section'   => 'fl-social-links'
        ) )
    );
}
add_action( 'customize_register', 'woo360_customizer_social_media_icons' );

// Add Houzz to the list of social media icons
function woo360_social_media_icons($icons) {
    $icons[] = 'houzz';
    return $icons;
}
add_filter('fl_social_icons', 'woo360_social_media_icons');