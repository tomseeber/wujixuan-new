<?php

namespace Nexcess\MAPPS\Commands;

use Kadence_Plugin_API_Manager;
use Kadence_Pro_API_Manager;
use Nexcess\MAPPS\Concerns\MakesHttpRequests;
use Nexcess\MAPPS\Exceptions\IngestionException;
use Nexcess\MAPPS\Exceptions\RequestException;
use Nexcess\MAPPS\Exceptions\WPErrorException;
use Nexcess\MAPPS\Integrations\StoreBuilder as Integration;
use Nexcess\MAPPS\Settings;

/**
 * Commands specific to StoreBuilder sites.
 */
class StoreBuilder extends Command {
	use MakesHttpRequests;

	/**
	 * @var \Nexcess\MAPPS\Integrations\StoreBuilder
	 */
	private $integration;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	private $settings;

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var bool
	 */
	public $force;

	/**
	 * Create a new instance of the command.
	 *
	 * @param \Nexcess\MAPPS\Settings                  $settings    The settings object.
	 * @param \Nexcess\MAPPS\Integrations\StoreBuilder $integration The StoreBuilder integration.
	 */
	public function __construct( Settings $settings, Integration $integration ) {
		$this->settings    = $settings;
		$this->integration = $integration;
	}

	/**
	 * Build out the site based on details from the StoreBuilder app.
	 *
	 * This command will also install and activate the Kadence theme (if it isn't already the current theme).
	 *
	 * ## OPTIONS
	 *
	 * [<site_id>]
	 * : The StoreBuilder site ID. Defaults to $settings->storebuilder_site_id.
	 *
	 * [--force]
	 * : Ingest content, even if the ingestion lock has already been set and/or the store has received orders.
	 *
	 * ## EXAMPLES
	 *
	 *   # Build the store using the UUID provided by SiteWorx
	 *   wp nxmapps storebuilder build
	 *
	 *   # Build the store based on specific UUID
	 *   wp nxmapps storebuilder build 3e973431-56d6-4c86-8278-84a72ad5238b
	 *
	 * @param string[] $args    Positional arguments.
	 * @param string[] $options Associative arguments.
	 */
	public function build( array $args, array $options ) {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return $this->warning( 'Unable to configure StoreBuilder, as WooCommerce is not active on this site.' );
		}

		$this->id    = ! empty( $args[0] ) ? $args[0] : $this->settings->storebuilder_site_id;
		$this->force = ! empty( $options['force'] ) ? (bool) $options['force'] : false;

		if ( empty( $this->id ) ) {
			return $this->error( 'A StoreBuilder site ID is required to proceed, aborting.', 1 );
		}

		if ( ! $this->integration->mayIngestContent() && ! $this->force ) {
			return $this->error( 'StoreBuilder has already been run for this site. You can re-run it with the --force option.', 1 );
		}

		try {
			$deps = $this->getDependencies();
		} catch ( RequestException $e ) {
			return $this->error( sprintf( 'Unable to get StoreBuilder dependencies: %s', $e->getMessage() ), 1 );
		}

		// Time to build up the site!
		$this->setUpPlugins( $deps );
		$this->setUpTheme( $deps );
		$this->setUpOptions();
		$this->updatePlugins();
		$this->ingestContent();
		$this->sendWelcomeEmail();

		$this->success( sprintf( 'Site %s has been built successfully!', $this->id ) );
	}

	/**
	 * Install the plugins for the site.
	 *
	 * @param array $deps The dependencies.
	 */
	protected function setUpPlugins( $deps ) {
		$plugins = apply_filters( 'Nexcess\\Mapps\\StoreBuilder\\PluginsToInstall', [
			'better-reviews-for-woocommerce',
			'spotlight-social-photo-feeds',
			'kadence-blocks',
			'woocommerce', // It's going to be there, but we want to make sure we force all our dependencies.
			'woocommerce-gateway-stripe',
			'woocommerce-pdf-invoices-packing-slips',
			'wp101',
		] );

		// Looping through and installing individually so that a failed install
		// doesn't prevent the others from installing.
		foreach ( $plugins as $plugin ) {
			$this->wp( 'plugin install --activate ' . $plugin );
		}

		if ( isset( $deps['wp101_api_key'] ) ) {
			$wp101_key = $deps['wp101_api_key'];
		} elseif ( isset( $deps['wp101'], $deps['wp101']['key'] ) ) {
			$wp101_key = $deps['wp101']['key'];
		} else {
			$wp101_key = false;
		}

		if ( $wp101_key ) {
			update_option( 'wp101_api_key', sanitize_text_field( $wp101_key ) );
		}
	}

	/**
	 * Updates the plugins for the site. This is to cover for the API giving
	 * out of date plugins. Also update the WooCommerce database.
	 */
	protected function updatePlugins() {
		$this->wp( 'plugin update --all' );
		$this->wp( 'wc update' ); // Update the WooCommerce database.
	}

	/**
	 * Install and license the suite of Kadence theme + plugins.
	 *
	 * @param array $deps The dependencies.
	 */
	protected function setUpTheme( $deps ) {
		$email   = ! empty( $deps['kadence']['email'] ) ? sanitize_text_field( $deps['kadence']['email'] ) : null;
		$license = ! empty( $deps['kadence']['key'] ) ? sanitize_text_field( $deps['kadence']['key'] ) : null;

		// The Kadence theme.
		$this->step( 'Installing and activating Kadence' )
			->wp( 'theme install --activate kadence' );

		// Install the premium plugins.
		if ( isset( $deps['kadence']['zip'] ) ) {
			$this->wp( 'plugin install --activate ' . implode( ' ', array_map( 'escapeshellarg', (array) $deps['kadence']['zip'] ) ) );

			$this->licenseKadence( $email, $license );
			$this->licenseKadenceBlocks( $email, $license );
		}
	}

	/**
	 * License Kadence Blocks Pro.
	 *
	 * @param string|null $email   Email address.
	 * @param string|null $license License key.
	 */
	protected function licenseKadenceBlocks( $email, $license ) {
		try {
			$file = WP_CONTENT_DIR . '/plugins/kadence-blocks-pro/kadence-classes/kadence-activation/class-kadence-plugin-api-manager.php';

			if ( ! file_exists( $file ) ) {
				return;
			}

			require_once $file;

			$instance = Kadence_Plugin_API_Manager::get_instance();
			$instance->on_init();
			$instance->activate( [
				'email'       => $email,
				'licence_key' => $license,
				'product_id'  => 'kadence_gutenberg_pro',
			] );

			update_option( 'kt_api_manager_kadence_gutenberg_pro_data', [
				'api_key'    => $license,
				'api_email'  => $email,
				'product_id' => 'kadence_gutenberg_pro',
			] );

			update_option( 'kadence_gutenberg_pro_activation', 'Activated' );
		} catch ( \Exception $e ) {
			$this->warning( 'Kadence Blocks Pro has been installed, but was not licensed.' );
		}
	}

	/**
	 * License Kadence Pro (the theme add-on plugin).
	 *
	 * @param string|null $email   Email address.
	 * @param string|null $license License key.
	 */
	protected function licenseKadence( $email, $license ) {
		try {
			$file = WP_CONTENT_DIR . '/plugins/kadence-pro/dist/dashboard/class-kadence-pro-dashboard.php';

			if ( ! file_exists( $file ) ) {
				return;
			}

			require_once $file;

			$instance = Kadence_Pro_API_Manager::instance(
				'kadence_pro_activation',
				'kadence_pro_api_manager_instance',
				'kadence_pro_api_manager_activated',
				'kadence_pro',
				'Kadence Pro'
			);

			// Define a manager instance ID, if we don't already have one.
			$manager_instance = get_option( 'kadence_pro_api_manager_instance', '' );

			if ( ! $manager_instance ) {
				$manager_instance = wp_generate_password( 12, false );
				update_option( 'kadence_pro_api_manager_instance', $manager_instance );
			}

			/*
			* Kadence Pro won't fully set up unless `is_admin()` is true, so we'll set some
			* properties ourselves.
			*/
			$instance->kt_instance_id = $manager_instance;
			$instance->kt_domain      = str_ireplace( [ 'http://', 'https://' ], '', home_url() );
			$instance->version        = wp_get_theme()->get( 'Version' );

			// Finally, attempt activation.
			$instance->activate( [
				'email'       => $email,
				'licence_key' => $license, // "licence" is intentionally misspelled.
			] );

			update_option( 'ktp_api_manager', [
				'ktp_api_key'      => $license,
				'activation_email' => $email,
			] );

			update_option( 'kadence_pro_api_manager_activated', 'Activated' );
		} catch ( \Exception $e ) {
			$this->warning( 'Kadence Pro (theme add-on) has been installed, but was not licensed.' );
		}
	}

	/**
	 * Set up default options for the StoreBuilder site.
	 */
	protected function setUpOptions() {
		/**
		 * Set a bunch of default options, including WooCommerce onboarding settings.
		 *
		 * @link https://woocommerce.github.io/woocommerce-admin/#/features/onboarding/
		 */
		update_option( 'woocommerce_allow_tracking', 'no' );
		update_option( 'woocommerce_analytics_enabled', 'yes' );
		update_option( 'woocommerce_demo_store', 'no' );
		update_option( 'woocommerce_marketing_overview_welcome_hidden', 'yes' );
		update_option( 'woocommerce_merchant_email_notifications', 'no' );
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
		update_option( 'woocommerce_extended_task_list_hidden', 'yes' );
		update_option( 'woocommerce_task_list_appearance_complete', true );
		update_option( 'woocommerce_task_list_complete', 'yes' );
		update_option( 'woocommerce_task_list_hidden', 'yes' );
		update_option( 'woocommerce_task_list_prompt_shown', true );
		update_option( 'woocommerce_task_list_welcome_modal_dismissed', 'yes' );

		update_option( 'woocommerce_task_list_tracked_completed_tasks', [
			'store_details',
			'products',
			'payments',
			'tax',
			'shipping',
			'appearance',
		] );

		update_option( 'woocommerce_onboarding_profile', [
			'business_extensions' => [],
			'completed'           => true,
			'setup_client'        => false,
			'industry'            => [ [ 'slug' => 'other' ] ],
			'product_types'       => [ 'physical' ],
			'product_count'       => '0',
			'selling_venues'      => 'no',
			'theme'               => 'kadence',
		] );

		// For woocommerce-pdf-invoices-packing-slips plugin.
		update_option( 'review_notice_dismissed', true );
	}

	/**
	 * Retrieve dependencies from the StoreBuilder API.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\RequestException If the dependencies can't be retrieved.
	 *
	 * @return array[] Details necessary to install all StoreBuilder plugins.
	 */
	protected function getDependencies() {
		$response = wp_remote_get( $this->integration->getAppUrl() . '/api/dependencies', [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->settings->managed_apps_token,
				'Accept'        => 'application/json',
			],
		] );

		try {
			$json = $this->validateHttpResponse( $response );
		} catch ( WPErrorException $e ) {
			throw new RequestException( $e->getMessage(), $e->getCode(), $e );
		}

		$body = json_decode( $json, true );

		if ( ! is_array( $body ) ) {
			throw new RequestException(
				sprintf( 'Received an unexpected response body from the StoreBuilder app: %s', (string) $json )
			);
		}

		return $body;
	}

	/**
	 * Ingest content.
	 *
	 * @throws IngestionException If the content can't be ingested.
	 */
	protected function ingestContent() {
		$this->step( 'Ingesting content from StoreBuilder API' );

		try {
			$this->integration->ingestContent( $this->id, $this->force );
		} catch ( IngestionException $e ) {
			return $this->error( $e->getMessage(), 1 );
		}
	}

	/**
	 * Send the welcome email.
	 */
	protected function sendWelcomeEmail() {
		$this->step( 'Sending welcome email' );

		$admin = get_user_by( 'email', get_option( 'admin_email' ) );

		if ( ! $admin ) {
			$admin = get_user_by( 'ID', 1 );
		}

		if ( $admin ) {
			$this->integration->sendWelcomeEmail( $admin );
		} else {
			$this->warning( 'Welcome email was not sent, could not find email address for user.' );
		}
	}
}
