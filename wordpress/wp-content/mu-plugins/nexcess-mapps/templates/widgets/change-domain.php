<?php

/**
 * The "Go Live!" domain change widget.
 *
 * @global string $dns_help_url Documentation for configuring DNS records.
 */

?>

<?php do_action( 'Nexcess\MAPPS\DomainChange\Before' ); ?>

<p>
<?php
echo wp_kses_post( sprintf(
	/* Translators: %1$s is the DNS help documentation URL. */
	__( 'Are you ready to take your site live? All you need to do is enter the domain name below (<a href="%1$s" target="_blank" rel="noopener">after making sure it\'s pointing to this site</a>) and press "Go Live" and we\'ll do all the work.', 'nexcess-mapps' ),
	esc_url( isset( $dns_help_url ) ? $dns_help_url : 'https://help.nexcess.net/74095-wordpress/how-to-edit-or-add-an-a-host-dns-record-to-go-live-with-your-site' )
) );
?>
</p>

<div id="mapps-change-domain-form"></div>

<?php do_action( 'Nexcess\MAPPS\DomainChange\After' ); ?>
