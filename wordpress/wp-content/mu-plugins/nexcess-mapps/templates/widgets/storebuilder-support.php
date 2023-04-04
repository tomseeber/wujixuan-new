<?php

/**
 * The "StoreBuilder Support" widget.
 */

?>

<h3><?php esc_html_e( 'Need Help With Your Store?', 'nexcess-mapps' ); ?></h3>

<p>
	<?php echo wp_kses_post( sprintf( __( 'Looking to learn more about WordPress and WooCommerce? Or have a specific question about a particular feature? We have you covered. Check out our educational resources:', 'nexcess-mapps' ) ) ); ?>
</p>

<ul class="mapps-storebuilder-welcome-support-links">
	<li>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %1$s: opening <strong> tag, %2$s: closing </strong> tag, %3$s: opening <a> tag, %4$s: closing </a> tag */
				__( '%1$sWooCommerce Videos from WP101%2$s: Watch helpful tutorials from %3$sWP101’s WooCommerce Course%4$s to learn everything you need to know about your new store.', 'nexcess-mapps' ),
				'<strong>',
				'</strong>',
				'<a href="' . admin_url( 'admin.php?page=wp101' ) . '">',
				'</a>'
			)
		);
		?>
	</li>
	<li>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %1$s: opening <strong> tag, %2$s: closing </strong> tag, %3$s: opening <a> tag, %4$s: closing </a> tag */
				__( '%1$sThe Nexcess Knowledge Base%2$s: Check out these %3$sarticles and guides from our Knowledge Base%4$s to learn more about how you can bring your store online.	', 'nexcess-mapps' ),
				'<strong>',
				'</strong>',
				'<a href="https://help.nexcess.net/wp-quickstart">',
				'</a>'
			)
		);
		?>
	</li>
	<li>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %1$s: opening <strong> tag, %2$s: closing </strong> tag, %3$s: opening <a> tag, %4$s: closing </a> tag */
				__( '%1$sThe WooCommerce Resource Library%2$s: Take your eCommerce store to the next level with these %3$shelpful WooCommerce tips & tricks%4$s.', 'nexcess-mapps' ),
				'<strong>',
				'</strong>',
				'<a href="https://www.nexcess.net/resources/woocommerce/">',
				'</a>'
			)
		);
		?>
	</li>
</ul>

<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* Translators: %1$s: Opening link tag, %2$s: Closing link tag. */
			__( 'Can\'t find your answer? Get help from the eCommerce experts: %1$sour support team at Nexcess%2$s.', 'nexcess-mapps' ),
			'<a href="' . admin_url( 'admin.php?page=nexcess-mapps#support' ) . '">',
			'</a>'
		)
	);
	?>
</p>
