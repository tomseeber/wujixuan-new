<?php

$text_class = 'fl-icon-text';

if ( empty( $settings->link ) ) {
	$text_class .= ' fl-icon-text-wrap';
}
if ( empty( $settings->text ) ) {
	$text_class .= ' fl-icon-text-empty';
}

$text_link_class = 'fl-icon-text-link';

if ( ! empty( $settings->link ) ) {
	$text_link_class .= ' fl-icon-text-wrap';
}
?>
<?php if ( ! isset( $settings->exclude_wrapper ) || ( isset( $settings->exclude_wrapper ) && ! $settings->exclude_wrapper ) ) : ?>
<div class="fl-icon-wrap">
<?php endif; ?>

	<span class="fl-icon">
		<?php if ( ! empty( $settings->link ) ) : ?>
			<?php if ( ! empty( $settings->text ) ) : ?>
			<a href="<?php echo $settings->link; ?>" target="<?php echo $settings->link_target; ?>" tabindex="-1" aria-hidden="true" aria-labelledby="fl-icon-text-<?php echo $module->node; ?>"<?php echo $module->get_rel(); ?>>
			<?php else : ?>
			<a href="<?php echo $settings->link; ?>" target="<?php echo $settings->link_target; ?>" aria-label="link to <?php echo $settings->link; ?>"<?php echo $module->get_rel(); ?>>
			<?php endif; ?>
		<?php endif; ?>
		<i class="<?php echo $settings->icon; ?>" aria-hidden="true"></i>
		<?php if ( ! empty( $settings->link ) ) : ?>
		</a>
		<?php endif; ?>
	</span>
	<?php
	/* WOO360 ADA COMPLIANCE EDIT 10/11/19 */
	if ( isset( $settings->text ) && false != $settings->text ) : ?>
		<div id="fl-icon-text-<?php echo $module->node; ?>" class="<?php echo $text_class; ?>">
			<?php if ( ! empty( $settings->link ) ) : ?>
			<a href="<?php echo $settings->link; ?>" target="<?php echo $settings->link_target; ?>" class="<?php echo $text_link_class; ?>"<?php echo $module->get_rel(); ?>>
			<?php endif; ?>
			<?php echo $settings->text; ?>
			<?php if ( ! empty( $settings->link ) ) : ?>
			</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php if ( ! isset( $settings->exclude_wrapper ) || ( isset( $settings->exclude_wrapper ) && ! $settings->exclude_wrapper ) ) : ?>
</div>
<?php endif; ?>
