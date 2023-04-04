<?php

/**
 * The StoreBuilder welcome screen.
 */

use Nexcess\MAPPS\Integrations\StoreBuilder;

?>
<div class="updated mapps-storebuilder-welcome">
	<div class="mapps-storebuilder-welcome-text">
		<h2><?php esc_attr_e( 'Get Ready to Sell With StoreBuilder by Nexcess', 'nexcess-mapps' ); ?></h2>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexcess-mapps&highlight=hide-welcome-panel-in-the-dashboard#settings' ) ); ?>" aria-label="<?php esc_attr_e( 'Dismiss Welcome Panel', 'nexcess-mapps' ); ?>" class="dismiss-link">
			<span class="dismiss"><?php esc_attr_e( 'Dismiss', 'nexcess-mapps' ); ?></span>
		</a>
	</div>

	<div class="mapps-storebuilder-welcome-wrapper">
		<div class="mapps-storebuilder-welcome-intro">
			<iframe
				src="https://www.youtube.com/embed/0o1el3u_WPc?modestbranding=1&rel=0&modestbranding1"
				title="<?php esc_attr_e( 'Welcome To StoreBuilder', 'nexcess-mapps' ); ?>"
				allow="encrypted-media;"
				allowfullscreen
				width="500"
				height="280"
				loading="lazy">
			</iframe>
			<a href="<?php echo esc_url( home_url() ); ?>" class="mapps-storebuilder-welcome-start-button button">
				<?php esc_attr_e( 'Visit your store', 'nexcess-mapps' ); ?>
			</a>
		</div>

		<div class="mapps-storebuilder-welcome-steps-wrap">
			<div class="mapps-storebuilder-welcome-steps">

				<?php
				StoreBuilder::renderWelcomeStep(
					'dashicons-welcome-write-blog',
					admin_url( 'edit.php?post_type=page' ),
					__( 'Update Content', 'nexcess-mapps' ),
					__( 'Edit your pages to suit your new store.', 'nexcess-mapps' )
				);

				StoreBuilder::renderWelcomeStep(
					'dashicons-bank',
					admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe' ),
					__( 'Accept Payments', 'nexcess-mapps' ),
					__( 'Set up a processor to accept credit cards on your store.', 'nexcess-mapps' )
				);

				StoreBuilder::renderWelcomeStep(
					'dashicons-cart',
					admin_url( 'post-new.php?post_type=product&tutorial=true' ),
					__( 'Add Products', 'nexcess-mapps' ),
					__( 'Create your first product and add it to your store.', 'nexcess-mapps' )
				);

				StoreBuilder::renderWelcomeStep(
					'dashicons-archive',
					admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=1' ),
					__( 'Set up Shipping', 'nexcess-mapps' ),
					__( 'Set your shipping methods & prices.', 'nexcess-mapps' )
				);

				StoreBuilder::renderWelcomeStep(
					'dashicons-cover-image',
					StoreBuilder::getDesignLink(),
					__( 'Change Design', 'nexcess-mapps' ),
					__( 'Change the look & feel of the store to match your style.', 'nexcess-mapps' )
				);

				StoreBuilder::renderWelcomeStep(
					'dashicons-groups',
					admin_url( 'users.php' ),
					__( 'Add Your Team', 'nexcess-mapps' ),
					__( 'Set up accounts for team members and collaborators.', 'nexcess-mapps' )
				);
				?>
			</div>
		</div>
	</div>
</div>
