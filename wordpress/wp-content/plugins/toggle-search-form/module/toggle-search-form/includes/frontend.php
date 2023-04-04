<div id="fl-builder-inline-search" class="fl-builder-inline-search slide-<?php echo $settings->text_field_slide; ?>">
	<form method="get" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr_x( 'Type and press Enter to search.', 'Search form mouse hover title.', 'toggle-search-form' ); ?>">
		<input type="text" id="inline-search" name="s" class="fl-inline-search inline-search" value="<?php echo $settings->placeholder; ?>" onfocus="if (this.value == '<?php echo $settings->placeholder; ?>') { this.value = ''; }" onblur="if (this.value == '') this.value='<?php echo $settings->placeholder; ?>';">  
		<?php if( ! empty( $settings->icon ) ) : ?>
			<span class="fa-search-icon">
				<i class="<?php echo $settings->icon; ?>" aria-hidden="true"></i>
			</span>
		<?php endif; ?>
	</form>
</div>