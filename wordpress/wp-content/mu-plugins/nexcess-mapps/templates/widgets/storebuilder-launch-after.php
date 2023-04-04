<?php

/**
 * The "Going Live with StoreBuilder" widget.
 */

use Nexcess\MAPPS\Support\Branding;

?>

<p class="mapps-change-domain-help-notice">
	<span class="mapps-storebuilder-inline-help-title">
		<?php esc_attr_e( 'Need any help?', 'nexcess-mapps' ); ?>
	</span>
	<span>
		<?php
		echo wp_kses_post(
			sprintf(
				/* Translators: %1$s is the help URL, %2$s is the support URL. */
				__( 'Check out <a href="%1$s">our guide on going live</a> or <a href="%2$s">reach out to us</a> and we\'ll be happy to help.', 'nexcess-mapps' ),
				'https://www.nexcess.net/storebuilder/resources/going-live-with-your-store/',
				Branding::getSupportUrl()
			)
		);
		?>
	</span>
</p>
