<?php

/**
 * Zend OPcache integration for Nexcess MAPPS.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\AdminBar;
use Nexcess\MAPPS\Concerns\HasHooks;
use Nexcess\MAPPS\Support\AdminNotice;

class OPcache extends Integration {
	use HasHooks;

	/**
	 * @var \Nexcess\MAPPS\AdminBar
	 */
	protected $adminBar;

	/**
	 * @param \Nexcess\MAPPS\AdminBar $admin_bar
	 *
	 * @return self
	 */
	public function __construct( AdminBar $admin_bar ) {
		$this->adminBar = $admin_bar;
	}

	/**
	 * Determine whether or not this integration should be loaded.
	 *
	 * @return bool Whether or not this integration be loaded in this environment.
	 */
	public function shouldLoadIntegration() {
		return self::isOPcacheActive();
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		// phpcs:disable WordPress.Arrays
		return [
			[ 'admin_action_nexcess-mapps-flush-opcache', [ $this, 'adminBarFlushOPcache' ] ],
			[ 'admin_post_nexcess-mapps-flush-opcache',   [ $this, 'adminBarFlushOPcache' ] ],
		];
		// phpcs:enable WordPress.Arrays
	}

	/**
	 * Register the admin bar menu item.
	 */
	public function registerAdminBarMenu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->adminBar->addMenu(
			'flush-opcache',
			AdminBar::getActionPostForm(
				'nexcess-mapps-flush-opcache',
				_x( 'Flush PHP OPcache', 'admin bar menu title', 'nexcess-mapps' )
			)
		);
	}

	/**
	 * Callback for requests to flush the OPcache via the Admin Bar.
	 */
	public function adminBarFlushOPcache() {
		if ( ! AdminBar::validateActionNonce( 'nexcess-mapps-flush-opcache' ) ) {
			return $this->adminBar->addNotice( new AdminNotice(
				__( 'We were unable to flush the OPcache, please try again.', 'nexcess-mapps' ),
				'error',
				true
			) );
		}

		self::flushOPcache();

		$this->adminBar->addNotice( new AdminNotice(
			__( 'The PHP OPcache has been flushed successfully!', 'nexcess-mapps' ),
			'success',
			true
		) );

		// If we have a referrer, we likely came from the front-end of the site.
		$referrer = wp_get_referer();

		if ( $referrer ) {
			return wp_safe_redirect( $referrer );
		}
	}

	/**
	 * Determine if the Zend OPcache is active.
	 *
	 * @return bool
	 */
	public static function isOPcacheActive() {
		if ( ! function_exists( 'opcache_get_status' ) || ! function_exists( 'opcache_reset' ) ) {
			return false;
		}

		$status = opcache_get_status();

		if ( false === $status ) {
			return false;
		}

		return isset( $status['opcache_enabled'] ) ? $status['opcache_enabled'] : false;
	}

	/**
	 * Flush the Zend OPcache.
	 *
	 * Calling opcache_reset() multiple times will produce different results (TRUE the first time,
	 * FALSE on subsequent calls) so this method helps normalize the behavior.
	 *
	 * @return bool True if the OPcache is pending a restart or the OPcache is not active (and thus
	 *              does not require a restart), false otherwise.
	 */
	public static function flushOPcache() {
		// No OPcache means there's nothing we need to do.
		if ( ! self::isOPcacheActive() ) {
			return true;
		}

		$status = opcache_get_status();

		if ( false === $status ) {
			return false;
		}

		// There's already an OPcache restart pending.
		if ( isset( $status['restart_pending'] ) && $status['restart_pending'] ) {
			return true;
		}

		return opcache_reset();
	}
}
