<?php

/**
 * The settings tab of the MAPPS Dashboard.
 */

?>
<div class="mapps-primary mapps-settings-page-wrap">
	<br class="clear">
	<form method="POST" action="<?php echo esc_attr( admin_url( 'options-general.php?page=nexcess-mapps#settings' ) ); ?>">
		<table class="widefat striped">
			<thead>
				<tr class="mapps-settings-table-header">
					<th class="mapps-settings-status" id="mapps-settings-table-status">
						<?php esc_attr_e( 'Enabled', 'nexcess-mapps' ); ?>
					</th>
					<th class="mapps-settings-name" id="mapps-settings-table-name">
						<?php esc_attr_e( 'Name', 'nexcess-mapps' ); ?>
					</th>
					<th class="mapps-settings-description" id="mapps-settings-table-description">
						<?php esc_attr_e( 'Description', 'nexcess-mapps' ); ?>
					</th>
				</tr>
			</thead>
			<tbody class="ui-sortable">
				<?php
				/**
				 * Allow adding settings to the settings page.
				 */
				do_action( 'Nexcess\MAPPS\Template\SettingsPage\RenderFields' );
				?>
			</tbody>
		</table>

		<?php wp_nonce_field( 'mapps-settings-save', '_mapps-settings-save-nonce' ); ?>
		<?php submit_button( esc_attr__( 'Save Changes', 'nexcess-mapps' ), 'primary', 'mapps-settings-submit' ); ?>
	</form>
</div>
