<?php

/**
 * The "Going Live with StoreBuilder" widget.
 */

?>

<p>
	<?php esc_attr_e( 'Depending on where you bought your domain, the steps to go live are slightly different, so please check out their instructions.', 'nexcess-mapps' ); ?>
</p>

<p>
	<?php esc_attr_e( 'To set up your real domain, you will need to set your nameservers to the following:', 'nexcess-mapps' ); ?>
</p>

<p>
	<details class="mapps-storebuilder-welcome-details">
		<summary>
			<?php esc_attr_e( 'View nameservers', 'nexcess-mapps' ); ?>
		</summary>
		<ul>
			<li>
				<code>ns1.nexcess.net</code>
			</li>
			<li>
				<code>ns2.nexcess.net</code>
			</li>
			<li>
				<code>ns3.nexcess.net</code>
			</li>
			<li>
				<code>ns4.nexcess.net</code>
			</li>
		</ul>
	</details>
</p>
