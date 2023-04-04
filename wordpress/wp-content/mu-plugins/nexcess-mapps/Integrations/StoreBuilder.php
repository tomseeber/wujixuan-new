<?php
/**
 * Dynamically generate a new store for customers.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasAdminPages;
use Nexcess\MAPPS\Concerns\HasAssets;
use Nexcess\MAPPS\Concerns\HasHooks;
use Nexcess\MAPPS\Concerns\MakesHttpRequests;
use Nexcess\MAPPS\Concerns\ManagesGroupedOptions;
use Nexcess\MAPPS\Concerns\QueriesWooCommerce;
use Nexcess\MAPPS\Exceptions\ContentOverwriteException;
use Nexcess\MAPPS\Exceptions\IngestionException;
use Nexcess\MAPPS\Exceptions\WPErrorException;
use Nexcess\MAPPS\Services\AdminBar;
use Nexcess\MAPPS\Services\Importers\AttachmentImporter;
use Nexcess\MAPPS\Services\Importers\WooCommerceProductImporter;
use Nexcess\MAPPS\Settings;
use Nexcess\MAPPS\Support\AdminNotice;
use Nexcess\MAPPS\Support\Helpers;
use WP_Term;
use WP_User;

use const Nexcess\MAPPS\PLUGIN_VERSION;

class StoreBuilder extends Integration {
	use ManagesGroupedOptions;
	use HasAdminPages;
	use HasAssets;
	use HasHooks;
	use MakesHttpRequests;
	use QueriesWooCommerce;

	/**
	 * @var mixed[]
	 */
	protected $contentPlaceholders = [];

	/**
	 * @var \Nexcess\MAPPS\Services\AdminBar
	 */
	protected $adminBar;

	/**
	 * @var \Nexcess\MAPPS\Services\Importers\AttachmentImporter
	 */
	protected $attachmentImporter;

	/**
	 * @var \Nexcess\MAPPS\Services\Importers\WooCommerceProductImporter
	 */
	protected $productImporter;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * The option that gets set once we've already ingested once.
	 */
	const INGESTION_LOCK_OPTION_NAME = '_storebuilder_created_on';

	/**
	 * The post meta key that gets set on generated content.
	 */
	const GENERATED_AT_POST_META_KEY = '_storebuilder_generated_at';

	/**
	 * The grouped setting option name.
	 */
	const OPTION_NAME = '_nexcess_quickstart';

	/**
	 * @param \Nexcess\MAPPS\Settings                                      $settings
	 * @param \Nexcess\MAPPS\Services\Importers\AttachmentImporter         $attachment_importer
	 * @param \Nexcess\MAPPS\Services\Importers\WooCommerceProductImporter $product_importer
	 * @param \Nexcess\MAPPS\Services\AdminBar                             $admin_bar
	 */
	public function __construct(
		Settings $settings,
		AttachmentImporter $attachment_importer,
		WooCommerceProductImporter $product_importer,
		AdminBar $admin_bar
	) {
		$this->settings           = $settings;
		$this->attachmentImporter = $attachment_importer;
		$this->productImporter    = $product_importer;
		$this->adminBar           = $admin_bar;
	}

	/**
	 * Determine whether or not this integration should be loaded.
	 *
	 * @return bool Whether or not this integration be loaded in this environment.
	 */
	public function shouldLoadIntegration() {
		return $this->settings->is_storebuilder || get_option( self::INGESTION_LOCK_OPTION_NAME, false );
	}

	/**
	 * Perform any necessary setup for the integration.
	 *
	 * This method is automatically called as part of Plugin::registerIntegration(), and is the
	 * entry-point for all integrations.
	 */
	public function setup() {
		$this->addHooks();
		$this->removeDefaultHooks();
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		// phpcs:disable WordPress.Arrays
		return [
			[ 'admin_init',            [ $this, 'warnUsersAboutFailedIngestion' ] ],
			[ 'admin_enqueue_scripts', [ $this, 'enqueueScripts'                ] ],
			[ 'admin_menu',            [ $this, 'removeMenuPages'               ], 999 ],
			[ 'admin_notices',         [ $this, 'renderWelcomePanel'            ], 100 ],
			[ 'wp_dashboard_setup',    [ $this, 'registerWidgets'               ] ],
			[ 'plugins_loaded',        [ $this, 'filterSpotlightUpsells'        ] ],
		];
		// phpcs:enable WordPress.Arrays
	}

	/**
	 * Retrieve all filters for the integration.
	 *
	 * @return array[]
	 */
	protected function getFilters() {
		// phpcs:disable WordPress.Arrays
		$filters = [
			// Kadence configuration.
			[ 'kadence_theme_options_defaults', [ $this, 'setKadenceDefaults' ] ],

			// Filters for metaboxes.
			[ 'postbox_classes_dashboard_mapps-storebuilder-support', [ $this, 'filterMetaboxClasses' ] ],
			[ 'default_hidden_meta_boxes',                            [ $this, 'filterMetaboxDefaults' ], 199 ],
			[ 'get_user_option_meta-box-order_dashboard',             [ $this, 'filterMetaboxOrder' ] ],

			// Enable & add settings to the settings page.
			[ 'Nexcess\MAPPS\SettingsPage\IsEnabled', '__return_true' ],
			[ 'Nexcess\MAPPS\SettingsPage\RegisterSetting', [ $this, 'registerWelcomeHideSetting' ] ],

			// Set up the simple admin menu.
			[ 'pre_option__nexcess_simple_admin_menu', [ $this, 'setUpSimpleAdminMenu' ] ],

			// Filter the admin bar environment colors.
			[ 'where_env_styles', [ $this, 'filterEnvironmentColors' ] ],

			// Add the launch content to the Go Live widget.
			[ 'Nexcess\MAPPS\DomainChange\Before', [ $this, 'renderLaunchContentBefore' ] ],
			[ 'Nexcess\MAPPS\DomainChange\After',  [ $this, 'renderLaunchContentAfter' ] ],

			// Filter the kadence license.
			[ 'pre_option_kt_api_manager_kadence_gutenberg_pro_data', [ $this, 'filterKadenceLicense' ] ],
		];
		// phpcs:enable WordPress.Arrays

		// Add the filters. They're broken out for readability.
		return array_merge( $filters, $this->getWCFilters() );
	}

	/**
	 * Remove hooks.
	 */
	protected function removeDefaultHooks() {
		// Remove the default WordPress welcome panel.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		// Remove WooCommerce tracking for the site admin.
		if ( class_exists( 'WC_Site_Tracking' ) ) {
			remove_action( 'init', [ 'WC_Site_Tracking', 'init' ] );
		}
	}

	/**
	 * Modify WooCommerce.
	 *
	 * @return array The filters.
	 */
	protected function getWCFilters() {
		// phpcs:disable WordPress.Arrays
		$filters = [
			// Filter different parts of WooCommerce.
			[ 'woocommerce_admin_features',           [ $this, 'filterWCFeatures' ], PHP_INT_MAX ],
			[ 'woocommerce_admin_get_feature_config', [ $this, 'filterWCFeaturesConfig' ], PHP_INT_MAX ],

			// Diable tracking and upsells.
			[ 'pre_option_woocommerce_allow_tracking',               [ Helpers::class, 'returnNo' ], PHP_INT_MAX ],
			[ 'pre_option_woocommerce_merchant_email_notifications', [ Helpers::class, 'returnNo' ] ],
			[ 'pre_option_woocommerce_show_marketplace_suggestions', [ Helpers::class, 'returnNo' ] ],

			// Disable more tracking and upsells.
			[ 'woocommerce_allow_payment_recommendations', '__return_false' ],
			[ 'woocommerce_allow_marketplace_suggestions', '__return_false' ],
			[ 'woocommerce_apply_tracking',                '__return_false' ],
			[ 'woocommerce_apply_user_tracking',           '__return_false' ],
			[ 'woocommerce_show_addons_page',              '__return_false' ],
			[ 'woocommerce_admin_onboarding_themes', '__return_empty_array' ],
		];
		// phpcs:enable WordPress.Arrays

		if ( class_exists( 'WC_Site_Tracking' ) ) {
			// Remove user tracking in wp-admin.
			$filters = array_merge( $filters, [ [ 'admin_footer', [ 'WC_Site_Tracking', 'add_tracking_function' ], 24 ] ] );
		}

		return $filters;
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueueScripts() {
		$this->enqueueScript( 'nexcess-mapps-storebuilder', 'storebuilder.js' );
	}

	/**
	 * Add inline styles.
	 */
	public function addInlineStyles() {
		wp_add_inline_style(
			'nexcess-mapps-storebuilder',
			'.woocommerce-stats-overview__install-jetpack-promo {
				display: none !important;
				visibility: hidden !important;
			}

			.wp-submenu .fs-submenu-item.pricing.upgrade-mode {
				color: unset;
			}

			body.appearance_page_kadence .license-section {
				display: none;
			}'
		);
	}

	/**
	 * Filter the admin bar colors.
	 *
	 * @param array $envs Environment settings.
	 *
	 * @return array Environment settings.
	 */
	public function filterEnvironmentColors( array $envs ) {
		$envs['production']['color'] = '#0073aa';

		return $envs;
	}

	/**
	 * Dynamically hide the license information for the Kadence settings page.
	 *
	 * @param null $option The option.
	 *
	 * @return mixed Filtered array or passed in option.
	 */
	public function filterKadenceLicense( $option ) {
		// If we're on license activation page, then don't show the actual license.
		if ( isset( $_GET['page'] ) && ( 'kadence' === $_GET['page'] || 'kadence_plugin_activation' === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return [
				'api_key'   => '******',
				'api_email' => '******',
			];
		}

		return $option;
	}

	/**
	 * Add .mapps-storebuilder-widget to our custom widgets.
	 *
	 * @param array $classes Classes to be applied to the post boxes.
	 *
	 * @return array The filtered $classes array.
	 */
	public function filterMetaboxClasses( array $classes ) {
		$classes[] = 'mapps-storebuilder-widget';

		return $classes;
	}

	/**
	 * Filter the default dashboard meta boxes shown to users.
	 *
	 * Once a user shows or hides a meta box, their selections are saved and will be used for
	 * subsequent page loads.
	 *
	 * @param array $meta_boxes An array of meta box keys that should be hidden by default.
	 *
	 * @return array The filtered $meta_boxes array.
	 */
	public function filterMetaboxDefaults( array $meta_boxes ) {
		return array_unique( array_merge( $meta_boxes, [
			'dashboard_activity',
			'dashboard_primary',
			'dashboard_rediscache',
			'dashboard_quick_press',
			'dashboard_right_now',
			'dashboard_site_health',
			'woocommerce_dashboard_recent_reviews',
			'wc_admin_dashboard_setup',
		] ) );
	}

	/**
	 * Filter the default ordering of dashboard meta boxes unless the user has set their own.
	 *
	 * @param array $order A user-defined ordering, in the form of "column_name:id1,id2".
	 *
	 * @return string[] Either the $order array or an array of reasonable defaults if $order is empty.
	 */
	public function filterMetaboxOrder( $order ) {
		if ( ! empty( $order ) ) {
			return $order;
		}

		return [
			'normal'  => 'mapps-storebuilder-advanced-steps,mapps-storebuilder-support',
			'side'    => 'mapps-storebuilder-tools,mapps-change-domain',
			'column3' => 'woocommerce_dashboard_status',
		];
	}

	/**
	 * Modify some of the freemius opt-in and upselling.
	 */
	public function filterSpotlightUpsells() {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName
		global  $sliFreemius;

		if ( ! isset( $sliFreemius ) ) {
			return;
		}

		$sliFreemius->add_filter( 'is_extensions_tracking_allowed', '__return_false' );
		$sliFreemius->add_filter( 'redirect_on_activation', '__return_false' );
		$sliFreemius->add_filter( 'show_admin_notice', '__return_false' );
		$sliFreemius->add_filter( 'show_customizer_upsell', '__return_false' );
		$sliFreemius->add_filter( 'show_deactivation_feedback_form', '__return_false' );
		$sliFreemius->add_filter( 'show_trial', '__return_false' );
		// phpcs:enable WordPress.NamingConventions.ValidVariableName
	}

	/**
	 * Get the URL for the StoreBuilder API.
	 *
	 * @return string The StoreBuilder API URL, without a trailing slash.
	 */
	public function getAppUrl() {
		return defined( 'NEXCESS_MAPPS_STOREBUILDER_URL' ) ? untrailingslashit( NEXCESS_MAPPS_STOREBUILDER_URL ) : 'https://storebuilder.app';
	}

	/**
	 * Get the link for the 'Design Invoices' link.
	 *
	 * @return string Link url.
	 */
	public static function getDesignInvoicesLink() {
		// If the Invoices plugin is active, link to that.
		if ( is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packing-slips.php' ) ) {
			return admin_url( 'admin.php?page=wpo_wcpdf_options_page' );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_email_customer_invoice' );
	}

	/**
	 * Get the link for the 'design it' widget.
	 *
	 * @return string Link url.
	 */
	public static function getDesignLink() {
		$theme = wp_get_theme();

		// If the theme is 'kadence' or a child theme of it, then link to the Kadence settings.
		// 'kadence-tab' is used by our JS to click on the 'Getting Started' tab.
		if ( 'kadence' === strtolower( $theme->get_template() ) ) {
			return admin_url( 'themes.php?page=kadence&kadence-tab=tab-panel-0-help' );
		}

		return admin_url( 'themes.php' );
	}

	/**
	 * Get the URL for the "Page performance" link.
	 *
	 * @return string The link for page speed.
	 */
	public static function getPageSpeedLink() {
		// If the performance monitor integration loaded, then we can point to that page.
		if ( did_action( 'Nexcess\MAPPS\Plugin\Loaded\Nexcess\MAPPS\Integrations\PerformanceMonitor' ) ) {
			return admin_url( 'admin.php?page=nexcess-mapps#priority-pages' );
		}

		// Fall back to the page cache.
		return admin_url( 'admin.php?page=mapps-page-cache' );
	}

	/**
	 * Modify feature flags of WooCommerce.
	 *
	 * @param array $features WC Features.
	 *
	 * @return array Features.
	 */
	public function filterWCFeatures( $features ) {
		unset(
			$features['marketing'],
			$features['mobile-app-banner'],
			$features['onboarding'],
			$features['payment-gateway-suggestions'],
			$features['remote-extensions-list'],
			$features['remote-inbox-notifications']
		);

		return $features;
	}

	/**
	 * Modify feature flags of WooCommerce.
	 *
	 * @param array $features WC Features.
	 *
	 * @return array Features.
	 */
	public function filterWCFeaturesConfig( $features ) {
		$features['mobile-app-banner']           = false;
		$features['payment-gateway-suggestions'] = false;
		$features['remote-extensions-list']      = false;
		$features['remote-inbox-notifications']  = false;

		return $features;
	}

	/**
	 * Modify menu pages in the admin.
	 */
	public function removeMenuPages() {
		remove_submenu_page( 'wp101', 'wp101-settings' );
		remove_submenu_page( 'wp101', 'wp101-addons' );
		remove_submenu_page( 'options-general.php', 'kadence_plugin_activation' );
	}

	/**
	 * Register the StoreBuilder widgets.
	 */
	public function registerWidgets() {
		// No need to pass any additional args, as we filter order of widgets anyway.
		wp_add_dashboard_widget(
			'mapps-storebuilder-support',
			_x( 'StoreBuilder Support', 'widget title', 'nexcess-mapps' ),
			function () {
				$this->renderTemplate( 'widgets/storebuilder-support' );
			}
		);

		wp_add_dashboard_widget(
			'mapps-storebuilder-advanced-steps',
			_x( 'Advanced Steps for Your Store', 'widget title', 'nexcess-mapps' ),
			[ $this, 'renderAdvancedSteps' ]
		);

		wp_add_dashboard_widget(
			'mapps-storebuilder-tools',
			_x( "Tools You'll Need", 'widget title', 'nexcess-mapps' ),
			[ $this, 'renderToolsYouNeedContent' ]
		);
	}

	/**
	 * Display the "Advanced Steps" widget content.
	 */
	public function renderAdvancedSteps() {
		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
		$this->renderTemplate( 'icon-link-list', [ 'icon_links' => [
			[
				'icon' => 'dashicons-tickets-alt',
				'href' => admin_url( 'edit.php?post_type=shop_coupon' ),
				'text' => __( 'Create Coupons', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-admin-site-alt2',
				'href' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
				'text' => __( 'Set up Shipping', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-email-alt',
				'href' => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				'text' => __( 'Configure Email', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-text-page',
				'href' => self::getDesignInvoicesLink(),
				'text' => __( 'Design Invoices', 'nexcess-mapps' ),
			],
		] ] ); // phpcs:ignore WordPress.Arrays
	}

	/**
	 * Display the "Tools you'll need" widget content.
	 */
	public function renderToolsYouNeedContent() {
		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
		$this->renderTemplate( 'icon-link-list', [ 'icon_links' => [
			[
				'icon' => 'dashicons-analytics',
				'href' => admin_url( 'admin.php?page=wc-admin&path=/analytics/overview' ),
				'text' => __( 'Analytics', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-performance',
				'href' => self::getPageSpeedLink(),
				'text' => __( 'Page Speed', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-welcome-learn-more',
				'href' => admin_url( 'admin.php?page=wp101' ),
				'text' => __( 'WP 101', 'nexcess-mapps' ),
			],
			[
				'icon' => 'dashicons-admin-settings',
				'href' => admin_url( 'admin.php?page=wc-settings' ),
				'text' => __( 'Store Settings', 'nexcess-mapps' ),
			],
		] ] ); // phpcs:ignore WordPress.Arrays
	}

	/**
	 * Render content inside the launch widget.
	 */
	public function renderLaunchContentBefore() {
		$this->renderTemplate( 'widgets/storebuilder-launch-before' );
	}

	/**
	 * Render content inside the launch widget.
	 */
	public function renderLaunchContentAfter() {
		$this->renderTemplate( 'widgets/storebuilder-launch-after' );
	}

	/**
	 * Render the welcome panel on the WP Admin dashboard.
	 */
	public function renderWelcomePanel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If the user has hidden the welcome panel, don't show it.
		if ( $this->getOption()->welcome_panel_is_hidden ) {
			return;
		}

		// Only want to show on the main dashboard page.
		$screen = get_current_screen();

		if ( isset( $screen->id ) && 'dashboard' === $screen->id ) {
			$this->renderTemplate( 'widgets/storebuilder-welcome' );
		}
	}

	/**
	 * Render a step for the welcome panel.
	 *
	 * @param string $icon  Dashicon icon name, such as "dashicons-admin-site".
	 * @param string $url   URL to link to.
	 * @param string $title Title of the step.
	 * @param string $text  Text to display.
	 */
	public static function renderWelcomeStep( $icon = '', $url = '', $title = '', $text = '' ) {
		printf( '<a href="%2$s" class="card mapps-storebuilder-step">
				<span class="icon dashicons-before %1$s"></span>
				<span class="content"> <h3>%3$s</h3> <p>%4$s</p> </span>
			</a>',
			esc_attr( $icon ),
			esc_url( $url ),
			esc_html( $title ),
			esc_html( $text )
		);
	}

	/**
	 * Add the setting to hide/show the Welcome panel.
	 *
	 * @param array $settings Current registered settings.
	 *
	 * @return array Settings with our new checkbox.
	 */
	public function registerWelcomeHideSetting( array $settings ) {
		$settings[] = [
			'key'  => [ self::OPTION_NAME, 'welcome_panel_is_hidden' ],
			'type' => 'checkbox',
			'name' => __( 'Hide "Welcome" panel in the Dashboard', 'nexcess-mapps' ),
			'desc' => __( 'Whether or not the WordPress dashboard should hide the "Welcome to StoreBuilder" panel.', 'nexcess-mapps' ),
		];

		return $settings;
	}

	/**
	 * Set up the simple admin menu.
	 *
	 * @param array $option Option value.
	 *
	 * @return array Option value.
	 */
	public function setUpSimpleAdminMenu( $option ) {
		// phpcs:disable WordPress.Arrays
		$option['menuSections'] = Helpers::makeSimpleAdminMenu( [
			'dashboard',
			'__nexcess-mapps',
			[ __( 'Content', 'nexcess-mapps' ), 'admin-page', [
				'posts',
				'media',
				'pages'
			] ],
			[ __( 'Store', 'nexcess-mapps' ), 'cart', [
				'posts-product',
				[ '__woo-better-reviews', _x( 'Reviews', 'Dashboard sidebar menu', 'nexcess-mapps' ),  'admin-comments' ],
				'__wc-admin&path=/analytics/overview',
				[ '__woocommerce',        _x( 'Settings', 'Dashboard sidebar menu', 'nexcess-mapps' ), 'admin-generic' ],
			] ],
			[ __( 'Site', 'nexcess-mapps' ), 'cover-image', [
				'appearance',
				'plugins',
				'users',
			] ],
			'__wp101',
		] );
		// phpcs:enable WordPress.Arrays

		return $option;
	}

	/**
	 * Retrieve content from the app and ingest it into WordPress.
	 *
	 * @param string $site_id The StoreBuilder site ID.
	 * @param bool   $force   Optional. Whether or not to run the ingestion regardless of
	 *                        mayIngestContent(). Default is false.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\ContentOverwriteException If ingesting content would cause
	 *                                                             content to be overwritten.
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException        If content cannot be ingested.
	 */
	public function ingestContent( $site_id, $force = false ) {
		if ( ! $this->mayIngestContent() && ! $force ) {
			throw new ContentOverwriteException(
				__( 'StoreBuilder layouts have already been imported for this store, abandoning in order to prevent overwriting content.', 'nexcess-mapps' )
			);
		}

		// Retrieve content from the StoreBuilder app.
		$response = wp_remote_get( sprintf( '%s/api/stores/%s', $this->getAppUrl(), $site_id ), [
			'headers' => [
				'Accept' => 'application/json',
			],
			'timeout' => 45,
		] );

		try {
			$layout = json_decode( $this->validateHttpResponse( $response ), true );
		} catch ( WPErrorException $e ) {
			throw new IngestionException(
				sprintf( 'Unable to retrieve content from StoreBuilder: %1$s', $e->getMessage() ),
				$e->getCode(),
				$e
			);
		}

		// Verify that the content matches what we're expecting.
		if ( empty( $layout ) || empty( $layout['pages'] ) ) {
			throw new IngestionException( __( 'StoreBuilder response body was empty or malformed.', 'nexcess-mapps' ) );
		}

		// Finally, ingest content.
		try {
			if ( ! empty( $layout['attachments'] ) ) {
				$this->createAttachments( (array) $layout['attachments'] );
			}

			if ( ! empty( $layout['links'] ) ) {
				$this->createLinks( (array) $layout['links'] );
			}

			if ( ! empty( $layout['taxonomies'] ) ) {
				$this->createTerms( (array) $layout['taxonomies'] );
			}

			if ( ! empty( $layout['terms'] ) ) {
				$this->registerTermPlaceholders( (array) $layout['terms'] );
			}

			if ( ! empty( $layout['pages'] ) ) {
				$this->createPages( (array) $layout['pages'] );
			}

			if ( ! empty( $layout['menus'] ) ) {
				$this->createMenus( (array) $layout['menus'] );
			}

			if ( ! empty( $layout['store'] ) ) {
				$this->setThemeOptions( (array) $layout['store'] );
			}

			if ( ! empty( $layout['products'] ) ) {
				$this->createProducts( (string) $layout['products'] );
			}
		} catch ( IngestionException $e ) {
			throw new IngestionException(
			/* Translators: %1$s is the previous exception's message. */
				sprintf( __( 'An error occurred while ingesting content: %1$s', 'nexcess-mapps' ), $e->getMessage() ),
				$e->getCode(),
				$e
			);
		}

		// Fire off an action to allow for other integrations to easily hook in.
		do_action( 'Nexcess\MAPPS\StoreBuilder\IngestContent', $site_id, $layout );

		// Prevent the StoreBuilder from being run again.
		update_option( self::INGESTION_LOCK_OPTION_NAME, [
			'mapps_version' => PLUGIN_VERSION,
			'timestamp'     => time(),
		] );
	}

	/**
	 * Determine if the store is eligible to ingest content.
	 *
	 * @return bool True if the store is allowed to ingest content, false otherwise.
	 */
	public function mayIngestContent() {
		return ! get_option( self::INGESTION_LOCK_OPTION_NAME, false )
			&& ! $this->storeHasOrders();
	}

	/**
	 * Send the welcome email to the given user through the SaaS.
	 *
	 * @param \WP_User $user The user to receive the welcome email.
	 */
	public function sendWelcomeEmail( WP_User $user ) {
		wp_remote_post( $this->getAppUrl() . '/api/notifications/storebuilder-setup-complete', [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->settings->managed_apps_token,
			],
			'body'    => [
				'store_id'  => $this->settings->storebuilder_site_id,
				'email'     => $user->user_email,
				'login_url' => wp_login_url(),
				'reset_url' => Helpers::getResetPasswordUrl( $user ),
				'username'  => $user->user_login,
			],
		] );
	}

	/**
	 * Overwrite some default settings for Kadence.
	 *
	 * @param mixed[] $options Default theme options for Kadence.
	 *
	 * @return mixed[] The filtered $options array.
	 */
	public function setKadenceDefaults( $options ) {
		// Overwrite the default Kadence footer.
		$options['footer_html_content'] = sprintf(
			'{copyright} {year} {site-title} — %s',
			_x( 'StoreBuilder by Nexcess', 'StoreBuilder theme footer', 'nexcess-mapps' )
		);

		// Use un-boxed styles by default.
		$options['page_content_style'] = 'unboxed';

		// Set default header assignments.
		if ( ! isset( $options['header_desktop_items']['main'] ) ) {
			$options['header_desktop_items']['main'] = [];
		}

		$options['header_desktop_items']['main'] = array_merge( $options['header_desktop_items']['main'], [
			'main_left'  => [
				'logo',
			],
			'main_right' => [
				'navigation',
			],
		] );

		return $options;
	}

	/**
	 * Warn users if they're on a StoreBuilder plan but have no UUID and/or ingestion has not yet
	 * been completed.
	 */
	public function warnUsersAboutFailedIngestion() {
		$should_warn = apply_filters( 'Nexcess\MAPPS\StoreBuilder\SkipFailedIngestionWarning', false );

		if ( get_option( self::INGESTION_LOCK_OPTION_NAME, $should_warn ) ) {
			return;
		}

		$message  = sprintf(
			'<strong>%s</strong>',
			__( 'Something seems to have gone wrong setting up your StoreBuilder shop.', 'nexcess-mapps' )
		);
		$message .= PHP_EOL;
		$message .= __( 'Our apologies, but it seems there was a hiccup building your new store. <a href="https://www.nexcess.net/storebuilder">Please reach out to StoreBuilder chat support</a> and we\'ll get things up-and-running for you.', 'nexcess-mapps' );

		$notice = new AdminNotice( $message, 'warning', false, 'storebuilder-setup-incomplete' );
		$notice->setCapability( 'manage_options' );

		$this->adminBar->addNotice( $notice );
	}

	/**
	 * Set the homepage content based on content from the StoreBuilder app.
	 *
	 * @param int $page_id The ID of the page to make the homepage.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If content cannot be imported.
	 */
	protected function setHomepage( $page_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $page_id );
	}

	/**
	 * Import attachments that have been specified by the import.
	 *
	 * @param array[] $attachments An array of attachments, keyed by their placeholder ID.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If one or more attachments cannot be
	 *                                                      imported into WordPress.
	 */
	protected function createAttachments( array $attachments ) {
		$errors = [];

		foreach ( $attachments as $id => $attachment ) {
			try {
				if ( empty( $attachment['url'] ) ) {
					continue;
				}

				$attachment_id  = $this->attachmentImporter->import( $attachment['url'], $attachment );
				$attachment_src = wp_get_attachment_url( $attachment_id );

				if ( ! $attachment_src ) {
					continue;
				}

				// With the attachment loaded, add it to our mapping array.
				$this->contentPlaceholders[ '%' . $id . '%' ]     = $attachment_src;
				$this->contentPlaceholders[ '%' . $id . ':id%' ]  = $attachment_id;
				$this->contentPlaceholders[ '%' . $id . ':url%' ] = $attachment_src;
			} catch ( \Exception $e ) {
				$errors[] = $e->getMessage();
			}
		}

		// Gather up any errors into a single exception.
		if ( ! empty( $errors ) ) {
			throw new IngestionException( sprintf(
				"The following errors occurred while creating attachments:\n- %s",
				implode( "\n- ", $errors )
			) );
		}
	}

	/**
	 * Add links to $this->contentPlaceholders.
	 *
	 * @param array[] $links Links defined by the StoreBuilder app.
	 */
	protected function createLinks( array $links ) {
		foreach ( $links as $hash => $link ) {
			if ( empty( $link['slug'] ) ) {
				continue;
			}

			$url = site_url( $link['slug'] );

			$this->contentPlaceholders[ '%' . $hash . '%' ]     = $url;
			$this->contentPlaceholders[ '%' . $hash . ':url%' ] = $url;
		}
	}

	/**
	 * Ingest menus defined by the StoreBuilder app.
	 *
	 * @param array[] $menus Menus provided by StoreBuilder.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If one or more menus could not be created.
	 */
	protected function createMenus( array $menus ) {
		$locations = [];
		$errors    = [];

		foreach ( $menus as $menu ) {
			try {
				$menu_id = $this->createMenu( $menu );

				if ( ! empty( $menu['display_location'] ) ) {
					$locations[ $menu['display_location'] ] = $menu_id;
				}
			} catch ( \Exception $e ) {
				$errors[] = $e->getMessage();
			}
		}

		// Assign the newly-created menus to their display locations.
		if ( ! empty( $locations ) ) {
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		// Gather up any errors into a single exception.
		if ( ! empty( $errors ) ) {
			throw new IngestionException( sprintf(
				"The following errors occurred while creating menus:\n- %s",
				implode( "\n- ", $errors )
			) );
		}
	}

	/**
	 * Create a single menu.
	 *
	 * @param mixed[] $menu {
	 *
	 * Details about the menu being created.
	 *
	 *   @type string  $display_location The theme location for the menu.
	 *   @type array[] $items            Individual menu items.
	 *   @type string  $label            The menu name.
	 * }
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If any menu items cannot be created.
	 * @throws \Nexcess\MAPPS\Exceptions\WPErrorException   If the nav menu could not be created.
	 *
	 * @return int The newly-created menu ID.
	 */
	protected function createMenu( array $menu ) {
		$menu_object = wp_get_nav_menu_object( addslashes( $menu['label'] ) );

		if ( ! $menu_object ) {
			$menu_id = wp_create_nav_menu( addslashes( $menu['label'] ) );

			if ( is_wp_error( $menu_id ) ) {
				throw new WPErrorException( $menu_id );
			}

			$menu_object = wp_get_nav_menu_object( $menu_id );
		}

		if ( ! $menu_object ) {
			throw new IngestionException(
				sprintf( 'Unable to create the "%s" menu.', $menu['label'] )
			);
		}

		// Remove any existing menu items, if they exist.
		$menu_items = get_objects_in_term( $menu_object->term_id, 'nav_menu' );

		if ( ! empty( $menu_items ) && ! is_wp_error( $menu_items ) ) {
			array_map( 'wp_delete_post', $menu_items );
		}

		// Add the menu items.
		foreach ( (array) $menu['items'] as $item ) {
			$this->createMenuItem( $item, $menu_object );
		}

		return $menu_object->term_id;
	}

	/**
	 * Create a single menu item.
	 *
	 * The type of menu item can be any of the following:
	 * 1. A link to a page object.
	 * 2. A link to a product category.
	 * 3. A custom URL.
	 *
	 * For post + term objects, the menu item will not be created if the target does not exist.
	 *
	 * @param string[] $item {
	 *
	 *   Details about the menu item.
	 *
	 *   @type string $label The label to use for the menu item. Only used custom URLs.
	 *   @type string $name  The link target object's name. Used for posts + terms.
	 *   @type string $type  The type of menu item (page|product_cat|custom).
	 *   @type string $url   The link URL. Only used for custom URLs.
	 * }
	 *
	 * @param \WP_Term $menu The menu object to which the item should be assigned.
	 */
	protected function createMenuItem( array $item, WP_Term $menu ) {
		switch ( $item['type'] ) {
			// A page.
			case 'page':
				$page = get_page_by_path( $item['name'], 'page' );

				if ( empty( $page ) ) {
					return;
				}

				$menu_item = [
					'menu-item-object-id' => $page->ID,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
				];
				break;

			// A product category.
			case 'product_cat':
				$term = get_term_by( 'slug', $item['name'], 'product_cat', OBJECT );

				if ( ! $term instanceof WP_Term ) {
					return;
				}

				$menu_item = [
					'menu-item-title'     => $term->name,
					'menu-item-object-id' => $term->term_id,
					'menu-item-object'    => 'product_cat',
					'menu-item-type'      => 'taxonomy',
					'menu-item-url'       => get_term_link( $term ),
				];
				break;

			// A custom URL.
			case 'custom':
				$menu_item = [
					'menu-item-title' => $item['label'],
					'menu-item-url'   => $item['url'],
					'menu-item-type'  => 'custom',
				];
				break;
			default:
				return;
		}

		wp_update_nav_menu_item( $menu->term_id, 0, array_merge( [
			'menu-item-status' => 'publish',
		], $menu_item ) );
	}

	/**
	 * Ingest pages defined by the StoreBuilder app.
	 *
	 * @param array[] $pages Pages provided by the StoreBuilder app.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If one or more pages can't be created.
	 */
	protected function createPages( array $pages ) {
		$errors = [];

		foreach ( $pages as $page ) {
			try {
				$this->createPage( $page );
			} catch ( IngestionException $e ) {
				$errors[] = $e;
			}
		}

		// Gather up any errors into a single exception.
		if ( ! empty( $errors ) ) {
			throw new IngestionException( sprintf(
				"The following errors occurred while creating pages:\n- %s",
				implode( "\n- ", $errors )
			) );
		}
	}

	/**
	 * Create a single page within WordPress.
	 *
	 * @param mixed[] $page An array of details about the page.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If the page cannot be created.
	 *
	 * @return int The post ID of the newly-created page.
	 */
	protected function createPage( array $page ) {
		if ( empty( $page['label'] ) ) {
			throw new IngestionException( 'A page title must be provided.' );
		}

		$content = ! empty( $page['content'] ) ? trim( (string) $page['content'] ) : '';

		// Update any placeholders in the block content.
		if ( ! empty( $this->contentPlaceholders ) ) {
			$content = str_replace(
				array_keys( $this->contentPlaceholders ),
				array_values( $this->contentPlaceholders ),
				$content
			);
		}

		// Assemble post meta.
		$meta = array_merge( [
			self::GENERATED_AT_POST_META_KEY => time(),
			'_kad_post_content_style'        => 'unboxed',
			'_kad_post_title'                => 'hide',
			'_kad_post_layout'               => 'fullwidth',
		], isset( $page['meta'] ) ? (array) $page['meta'] : [] );

		// See if we have an existing page with this slug.
		$post_id = null;

		if ( ! empty( $page['name'] ) ) {
			$existing = get_page_by_path( $page['name'], OBJECT, 'page' );
			$post_id  = $existing ? $existing->ID : null;
		}

		// Insert the page.
		$page_id = wp_insert_post( [
			'ID'           => $post_id,
			'post_title'   => $page['label'],
			'post_name'    => ! empty( $page['name'] ) ? $page['name'] : null,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'meta_input'   => $meta,
		] );

		if ( is_wp_error( $page_id ) ) {
			throw new IngestionException( sprintf(
				'Error creating page "%1$s": %2$s',
				$page['label'],
				$page_id->get_error_message()
			) );
		}

		if ( 'home' === $page['name'] ) {
			$this->setHomepage( $page_id );
		}

		return $page_id;
	}

	/**
	 * Ingest taxonomy terms from the StoreBuilder app.
	 *
	 * The $terms array groups terms by their taxonomy in nested arrays:
	 *
	 *     [
	 *         'product_cat' => [
	 *             [
	 *                 'name'  => 'first-cat',
	 *                 'label' => 'First Category',
	 *             ],
	 *             [
	 *                 'name'  => 'second-cat',
	 *                 'label' => 'Second Category',
	 *             ],
	 *         ],
	 *         'category'    => [
	 *             // ...
	 *         ],
	 *     ]
	 *
	 * @param array[] $terms An array of terms, grouped by taxonomy.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If one or more terms can't be created.
	 */
	protected function createTerms( $terms ) {
		$errors = [];

		foreach ( $terms as $taxonomy => $taxonomy_terms ) {
			foreach ( $taxonomy_terms as $term ) {
				try {
					$this->createTerm( $taxonomy, $term );
				} catch ( IngestionException $e ) {
					$errors[] = $e->getMessage();
				}
			}
		}

		// Gather up any errors into a single exception.
		if ( ! empty( $errors ) ) {
			throw new IngestionException( sprintf(
				"The following errors occurred while creating taxonomy terms:\n- %s",
				implode( "\n- ", $errors )
			) );
		}
	}

	/**
	 * Create a single taxonomy term.
	 *
	 * @param string  $taxonomy  The taxonomy name.
	 * @param mixed[] $args      An array of term data.
	 * @param ?int    $parent_id Optional. The taxonomy term parent. Default is null.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If a term cannot be created.
	 * @throws \Nexcess\MAPPS\Exceptions\WPErrorException   If a term cannot be created.
	 */
	protected function createTerm( $taxonomy, $args, $parent_id = null ) {
		// Do nothing if the term array is invalid.
		if ( empty( $args['name'] ) || empty( $args['label'] ) ) {
			return;
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			throw new IngestionException( sprintf( 'Taxonomy "%s" does not exist.', $taxonomy ) );
		}

		// Create the term, re-using exiting terms when available.
		try {
			$term = term_exists( $args['name'], $taxonomy, $parent_id );

			/*
			 * If term_exists() returned anything but an array of term ID and taxonomy term ID,
			 * we should attempt to create it.
			 */
			if ( ! is_array( $term ) ) {
				$term = wp_insert_term( $args['label'], $taxonomy, [
					'parent' => (int) $parent_id,
					'slug'   => $args['name'],
				] );

				if ( is_wp_error( $term ) ) {
					throw new WPErrorException( $term );
				}
			}

			$term_id = current( $term );
		} catch ( \Exception $e ) {
			throw new IngestionException( sprintf(
				'Unable to create term "%1$s" in taxonomy "%2$s": %3$s',
				$args['label'],
				$taxonomy,
				$e->getMessage()
			), $e->getCode(), $e );
		}

		// Recursively create children, if defined.
		if ( empty( $args['children'] ) ) {
			return;
		}

		$errors = [];

		foreach ( $args['children'] as $child ) {
			try {
				$this->createTerm( $taxonomy, $child, $term_id );
			} catch ( IngestionException $e ) {
				$errors[] = $e->getMessage();
			}
		}

		if ( ! empty( $errors ) ) {
			throw new IngestionException( sprintf(
				"The following errors occurred while creating \"%1\$s\" terms for parent \"%2\$s\" (ID %3\$d):\n- %4\$s",
				$taxonomy,
				$args['label'],
				$term_id,
				implode( "\n- ", $errors )
			) );
		}
	}

	/**
	 * Ingest products defined by the StoreBuilder app.
	 *
	 * This works in the same way as the WooCommerce core CSV importer, using a CSV file provided
	 * by the StoreBuilder app.
	 *
	 * @param string $url The URL for a CSV containing sample products.
	 *
	 * @throws \Nexcess\MAPPS\Exceptions\IngestionException If import fails.
	 */
	protected function createProducts( $url ) {
		$this->productImporter->importFromUrl( $url );
	}

	/**
	 * Add terms to $this->contentPlaceholders.
	 *
	 * @param array[] $terms Terms defined by the StoreBuilder app.
	 */
	protected function registerTermPlaceholders( $terms ) {
		foreach ( $terms as $hash => $term ) {
			$object = get_term_by( 'slug', $term['slug'], $term['taxonomy'], OBJECT );

			if ( ! $object || ! $object instanceof WP_Term ) {
				continue;
			}

			$url = get_term_link( $object );

			$this->contentPlaceholders[ '%' . $hash . '%' ]     = $url;
			$this->contentPlaceholders[ '%' . $hash . ':id%' ]  = $object->term_id;
			$this->contentPlaceholders[ '%' . $hash . ':url%' ] = $url;
		}
	}

	/**
	 * Apply theme modifications.
	 *
	 * @param mixed[] $mods {
	 *
	 *   Theme modifications to be applied. All keys are optional.
	 *
	 *   @type bool $search Whether or not to add search to the main sidebar. Default is false.
	 * }
	 */
	protected function setThemeOptions( array $mods = [] ) {
		$option = 'theme_mods_kadence';
		$mods   = wp_parse_args( $mods, [
			'search' => false,
		] );

		$kadence_options = $this->setKadenceDefaults( get_option( $option, [] ) );

		// If 'search' is true, add a search widget to the main sidebar.
		// Avoid duplicate entries in the main right header section while allowing the primary nav to remain.
		if ( $mods['search'] && ! in_array( 'search', $kadence_options['header_desktop_items']['main']['main_right'], true ) ) {
			array_push( $kadence_options['header_desktop_items']['main']['main_right'], 'search' );
		}

		update_option( $option, $kadence_options );
	}
}
