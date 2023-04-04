<?php
/**
 * Helper class for child theme functions.
 *
 * @class FLChildTheme
 */
final class Woo360ChildTheme {

    /**
     * Enqueues scripts and styles.
     *
     * @return void
     */
    static public function enqueue_scripts() {
        // main css
        wp_enqueue_style('fl-child-theme', FL_CHILD_THEME_URL . '/assets/css/main.css');

        //mobile specific styles
        wp_enqueue_style('fl-child-theme-mobile', FL_CHILD_THEME_URL . '/assets/css/style-mobile.css');

        // gravity forms styles
        wp_enqueue_style('fl-child-theme-gravity-forms', FL_CHILD_THEME_URL . '/assets/css/gravity-forms.css');

        // main custom styles
        wp_enqueue_style('fl-child-theme', FL_CHILD_THEME_URL . '/style.css');

        // dynamic content m360 script
        wp_enqueue_script('fl-child-m360-dynamic-content', FL_CHILD_THEME_URL . '/assets/js/m360-dynamic-content.js');

    }

    public static function enqueue_login_styles() {
        wp_enqueue_style('custom-login', get_stylesheet_directory_uri() . '/assets/css/login.css');
    }

    public static function enqueue_custom_wp_admin_styles() {

        // wp_register_style('admin-bootstrap', get_stylesheet_directory_uri() . '/assets/css/bootstrap.min.css', false, null);
        wp_enqueue_style('admin-bootstrap');

        wp_register_style('custom_wp_admin_css', get_stylesheet_directory_uri() . '/assets/css/admin.css', false, '1.0.0');
        wp_enqueue_style('custom_wp_admin_css');
    }

    static public function register_required_plugins() {
        /*
         * Array of plugin arrays. Required keys are name and slug.
         * If the source is NOT from the .org repo, then source is also required.
         */
        $plugins = array(

            // This is an example of how to include a plugin from an arbitrary external source in your theme.
            array(
                'name'     => 'Woo360 Modules', // The plugin name.
                'slug'     => 'woo360-modules', // The plugin slug (typically the folder name).
                'source'   => 'http://woo360-updates.madwirebuild4.com/?action=download&slug=woo360-modules', // The plugin source.
                'required' => true, // If false, the plugin is only 'recommended' instead of required.
            ),

            array(
                'name'     => 'MCE Table Buttons',
                'slug'     => 'mce-table-buttons',
                'required' => true,
            ),
        );

        /*
         * Array of configuration settings. Amend each line as needed.
         * Only uncomment the strings in the config array if you want to customize the strings.
         */

        $config = array(
            'id'           => 'tgmpa', // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '', // Default absolute path to bundled plugins.
            'menu'         => 'tgmpa-install-plugins', // Menu slug.
            'parent_slug'  => 'themes.php', // Parent menu slug.
            'capability'   => 'edit_theme_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices'  => true, // Show admin notices or not.
            'dismissable'  => false,
            'dismiss-msg'  => 'For Admin Page Spider Use license key: 5925793e5f59345545f65a74b5e49f5b',
            'is_automatic' => true,
            'message'      => '<p>For Admin Page Spider Use license key: 5925793e5f59345545f65a74b5e49f5b</p>',
        );
        tgmpa($plugins, $config);
    }

    public static function maintenance_mode() {
        // get options and check if the checkbox option exists
        $options = get_option('woo360child_settings');
        if ($options == false) {
            $isLightspeedActive = false;
            //PC::debug($options);
        } else {
            $isLightspeedActive = array_key_exists("woo360child_checkbox_field_0", $options);
        }

        if ($isLightspeedActive !== false) {

            try {
                $handle = curl_init($options['woo360child_text_field_1']);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
                /* Get the HTML or whatever is linked in $url. */
                $response = curl_exec($handle);

                /* Check for 404 (file not found). */
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                if ($httpCode == 404) {
                    $siteSuspended = true;
                } else {
                    $siteSuspended = false;
                }
                curl_close($handle);

            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }

            global $pagenow;

            if ($pagenow !== 'wp-login.php' && !current_user_can('manage_options') && !is_admin() && $siteSuspended !== false && $options['woo360child_checkbox_field_0'] == '1') {
                if (file_exists(get_stylesheet_directory() . '/templates/maintenance.php')) {

                    require_once get_stylesheet_directory() . '/templates/maintenance.php';
                }
                die();
            }
        }
    }

    public static function add_tracking() {
        echo '<script type="text/javascript" src="https://conversions.marketing360.com/wc/M360.js"></script>';
    }

    public static function m360_dynamic_content() {

        if (is_page()) {
            // woo360 tracking scripts for adding up

            $pageID                    = get_the_ID();
            $isPageUsingDynamicContent = get_post_meta($pageID, 'm360-dynamic-checkbox', true);

            if (!is_array($isPageUsingDynamicContent) && $isPageUsingDynamicContent === 'yes') {
                echo "<script>
                    window.addEventListener('load', function(){
                        if (typeof m360dc === 'object' && typeof m360dc.replaceContent === 'function') {
                            m360dc.replaceContent();
                        }
                    });
                </script>";
            }
        }
    }

}