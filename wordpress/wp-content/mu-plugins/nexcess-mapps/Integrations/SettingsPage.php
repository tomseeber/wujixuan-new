<?php

/**
 * The Nexcess MAPPS dashboard settings page.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasAdminPages;
use Nexcess\MAPPS\Concerns\HasHooks;
use Nexcess\MAPPS\Concerns\ManagesGroupedOptions;
use Nexcess\MAPPS\Settings;

class SettingsPage extends Integration {
	use HasAdminPages;
	use HasHooks;
	use ManagesGroupedOptions;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * This option isn't used, as we pass specific option names to the
	 * grouped options manager, but it's here to make sure it's defined.
	 */
	const OPTION_NAME = 'nexcess_mapps_settings_page';

	/**
	 * @param \Nexcess\MAPPS\Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Determine whether or not this integration should be loaded.
	 *
	 * @return bool Whether or not this integration be loaded in this environment.
	 */
	public function shouldLoadIntegration() {
		/**
		 * Determine whether the "Settings" section of the Nexcess Dashboard should be available.
		 *
		 * @param bool $enabled True if the section should be present, false otherwise.
		 */
		return (bool) apply_filters( 'Nexcess\MAPPS\SettingsPage\IsEnabled', true );
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [
			[ 'admin_init', [ $this, 'registerSettingsPage' ], 500 ], // Set to 500 so that it's the last of the functionality tabs.
			[ 'admin_init', [ $this, 'processSettingsFormSubmission' ], 70 ],

			// Render the fields on the settings page template.
			[ 'Nexcess\MAPPS\Template\SettingsPage\RenderFields', [ $this, 'renderFields' ] ],
		];
	}

	/**
	 * Register the "SettingsPage" settings section.
	 */
	public function registerSettingsPage() {
		add_settings_section(
			'settings',
			esc_attr_x( 'Settings', 'settings section', 'nexcess-mapps' ),
			function () {
				$this->renderTemplate( 'settings-page', [
					'settings' => $this->settings,
				] );
			},
			Dashboard::ADMIN_MENU_SLUG
		);
	}

	/**
	 * Get an array of valid settings fields to render on the settings page.
	 *
	 * @see Nexcess\MAPPS\SettingsPage\RegisterSetting
	 *
	 * @todo Update this to support more than checkboxes.
	 *
	 * @return array Valid settings fields. See the filter 'Nexcess\MAPPS\SettingsPage\RegisterSetting' for more structure.
	 */
	public function getValidSettingsFields() {
		/**
		 * Get the registered settings for the settings section.
		 *
		 * @return array[] {
		 *
		 *   @type   string|array $key  Option name. String for normal option, array for grouped option. Grouped options are [ 'group_nam', 'key_name' ].
		 *   @type   string       $type Option type. Currently, only 'checkbox' is supported.
		 *   @type   string       $name Field label.
		 *   @type   string       $desc Description.
		 *  }
		 */
		$registered_settings = apply_filters( 'Nexcess\MAPPS\SettingsPage\RegisterSetting', [] );

		// Make sure we have some settings to render.
		if ( empty( $registered_settings ) || ! is_array( $registered_settings ) ) {
			return [];
		}

		// Build up an array of valid fields to avoid having to safety check values
		// in every method that uses them.
		$valid_fields = [];

		foreach ( $registered_settings as $field ) {
			// Make sure we have a key for the field and a type for it.
			if ( ! isset( $field['key'] ) || ! isset( $field['type'] ) ) {
				continue;
			}

			// Right now, only the checkbox type is supported.
			// this is seperate from our normal checks for key/type
			// because we'll want to modify this to support other types
			// as we add them.
			if ( 'checkbox' === $field['type'] ) {
				$valid_fields[] = $field;
			}
		}

		return $valid_fields;
	}

	/**
	 * Loop through the valid settings fields and render them.
	 *
	 * @todo Update this to support more than checkboxes.
	 */
	public function renderFields() {
		foreach ( (array) $this->getValidSettingsFields() as $field ) {

			// Safety checking for the field key.
			$id_from_key = $this->getSanitizedKey( $field['key'] );

			// Right now, we only support the checkbox type, so we're only going to render those.
			if ( 'checkbox' === $field['type'] ) {
				$this->renderToggle(
					$id_from_key,
					$field['key'],
					isset( $field['name'] ) ? $field['name'] : $id_from_key, // Defaults to just the field ID, which is better than nothing.
					isset( $field['desc'] ) ? $field['desc'] : '',
					isset( $field['default'] ) ? $field['default'] : false
				);
			}
		}
	}

	/**
	 * Return HTML markup for a checkbox field for a specific option.
	 *
	 * @param string       $name    Field name.
	 * @param string|array $key     Option key. Array for grouped option, string for normal key.
	 * @param bool         $default Default value.
	 *
	 * @return string HTML markup of checkbox.
	 */
	public function renderCheckboxInput( $name, $key, $default = null ) {
		return sprintf(
			// Make sure to keep the concatenation in place if editing.
			'<input type="checkbox" id="%1$s" name="%1$s" %2$s>'
			. '<label for="%1$s">%3$s</label>',
			esc_attr( $name ),
			checked( $this->getSavedOption( $key, $default ), true, false ),
			esc_attr__( 'Active', 'nexcess-mapps' )
		);
	}

	/**
	 * Render a settings page toggleable option.
	 *
	 * @param string       $field_id   Field ID for form markup.
	 * @param string|array $option_key Option key, Array for grouped option, string for normal key.
	 * @param string       $title      Field title.
	 * @param string       $desc       Field description.
	 * @param bool         $default    Default value.
	 */
	public function renderToggle( $field_id, $option_key, $title, $desc = '', $default = null ) {
		$extra_classes = '';

		// If the url has "highlight=<setting_id>, then we want to highlight that section.
		if ( isset( $_GET['highlight'] ) && sanitize_title_with_dashes( $title ) === $_GET['highlight'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$extra_classes = ' highlight';
		}

		printf(
			// Make sure to keep the concatenation in place if editing.
			'<tr class="mapps-toggle-switch%4$s">'
				. '<td class="status">%1$s</td>'
				. '<td class="name">%2$s</td>'
				. '<td class="description">%3$s</td>'
			. '</tr>',
			$this->renderCheckboxInput( $field_id, $option_key, $default ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_attr( $title ),
			esc_html( $desc ),
			esc_attr( $extra_classes )
		);
	}

	/**
	 * From either a string or an array, generate a sanitized key to use for a field.
	 *
	 * When registering a field, rather than having to pass in a field ID that is going
	 * to be the same as the key, we just use the option key as the field ID,
	 * with a special check for it being an array. We also make sure to sanitize the key.
	 *
	 * @param string|array $key Option key.
	 *
	 * @return string Sanitized key.
	 */
	public function getSanitizedKey( $key ) {
		// Generate a unique identifier for the option when it's an array.
		if ( is_array( $key ) ) {
			return sanitize_key( implode( '_', $key ) . '_' . md5( maybe_serialize( $key ) ) );
		}

		return sanitize_key( $key );
	}

	/**
	 * Get an option value from either a grouped option or a normal DB option.
	 *
	 * @param string|array $key     Option key. Array for grouped option, string for normal key.
	 * @param mixed        $default Default value to return if the option is not set.
	 *
	 * @return mixed Option value.
	 */
	public function getSavedOption( $key, $default = null ) {
		// If it's not a grouped option, just do it normally.
		if ( ! is_array( $key ) ) {
			return get_option( $key, $default );
		}

		// We have a grouped option, so let's use our array values to grab it.
		$saved = self::getOptionByName( $key[0] )->{$key[1]};

		// If we have a value, then return it.
		if ( ! is_null( $saved ) ) {
			return $saved;
		}

		return $default;
	}

	/**
	 * Process the POST submission and save the data.
	 */
	public function processSettingsFormSubmission() {
		// Safety check before saving.
		if (
			! isset( $_POST['mapps-settings-submit'] ) // Submit button.
			|| ! isset( $_POST['_mapps-settings-save-nonce'] ) // Nonce.
			|| ! is_admin()
			|| ! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce( $_POST['_mapps-settings-save-nonce'], 'mapps-settings-save' ) // Verify nonce.
		) {
			return;
		}

		foreach ( (array) $this->getValidSettingsFields() as $field ) {
			// Grab our sanitized key to compare against the POST data.
			$key = $this->getSanitizedKey( $field['key'] );

			// If we did get a post value, save it.
			$new_value = false;
			if ( isset( $_POST[ $key ] ) && 'on' === $_POST[ $key ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$new_value = true;
			}

			if ( is_array( $field['key'] ) ) {
				// Save the grouped option.
				self::getOptionByName( $field['key'][0] )->set( $field['key'][1], $new_value )->save();
			} else {
				/*
				 * Due to the way that update_option() works, attempting to create a new option
				 * with `update_option($field['key'], false)` will fail, as
				 * `get_option($field['key'])` will return false, and update_option() will see this
				 * as an unnecessary update and instead will return early.
				 */
				if ( null === get_option( $field['key'], null ) ) {
					add_option( $field['key'], $new_value, '', false );
				} else {
					update_option( $field['key'], $new_value );
				}
			}
		}

		// Puts us back into the same url rather than options-general.php.
		wp_safe_redirect( admin_url( 'admin.php?page=nexcess-mapps#settings' ) );
	}
}
