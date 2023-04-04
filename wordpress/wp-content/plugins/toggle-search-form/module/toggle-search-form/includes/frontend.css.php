.fl-node-<?php echo $id; ?> .fa-search-icon {
	<?php if( ! empty( $settings->btn_bg_color ) ): ?>
	background: #<?php echo $settings->btn_bg_color; ?>;
	<?php endif; ?>
	<?php if( ! empty( $settings->icon_color ) ): ?>
	color: #<?php echo $settings->icon_color; ?>;
	<?php endif; ?>

	<?php if($settings->icon_font_size == 'custom') : ?>
	font-size: <?php echo $settings->icon_custom_font_size; ?>px;
	<?php endif; ?>
}

<?php if( ! empty( $settings->icon_color ) ): ?>
.fl-node-<?php echo $id; ?> .fa-search-icon i,
.fl-node-<?php echo $id; ?> .fa-search-icon i:before {
	color: #<?php echo $settings->icon_color; ?>;
}
<?php endif; ?>
<?php if( ! empty( $settings->icon_color ) ): ?>
.fl-node-<?php echo $id; ?> .fa-search-icon i:hover,
.fl-node-<?php echo $id; ?> .fa-search-icon i:hover:before {
	color: #<?php echo $settings->icon_hover_color; ?>;
}
<?php endif; ?>

.fl-node-<?php echo $id; ?> .fa-search-icon:hover,
.fl-node-<?php echo $id; ?> .fa-search-icon:focus,
.fl-node-<?php echo $id; ?> .fa-search-icon:active {
	<?php if( ! empty( $settings->btn_bg_hover_color ) ): ?>
	background: #<?php echo $settings->btn_bg_hover_color; ?>;
	<?php endif; ?>
	<?php if( ! empty( $settings->icon_color ) ): ?>
	color: #<?php echo $settings->icon_hover_color; ?>;
	<?php endif; ?>
}

.fl-node-<?php echo $id; ?> input[type="search"]:-webkit-autofill {
	<?php if( ! empty( $settings->if_bg_color ) ): ?>
	background: #<?php echo $settings->if_bg_color; ?>;
	<?php endif; ?>
	<?php if( ! empty( $settings->if_txt_color ) ): ?>
	color: #<?php echo $settings->if_txt_color; ?>;
	<?php endif; ?>

	<?php if($settings->font_size == 'custom') : ?>
	font-size: <?php echo $settings->custom_font_size; ?>px;
	<?php endif; ?>
}

.fl-node-<?php echo $id; ?> input[type="search"],
.fl-node-<?php echo $id; ?> #inline-search {
	<?php if( ! empty( $settings->if_bg_color ) ): ?>
	background: #<?php echo $settings->if_bg_color; ?>;
	<?php endif; ?>
	<?php if( ! empty( $settings->if_txt_color ) ): ?>
	color: #<?php echo $settings->if_txt_color; ?>;
	<?php endif; ?>

	<?php if($settings->font_size == 'custom') : ?>
	font-size: <?php echo $settings->custom_font_size; ?>px;
	<?php endif; ?>
}

<?php if( ! empty( $settings->if_bg_focus_color ) ): ?>
.fl-node-<?php echo $id; ?> #inline-search:focus,
.fl-node-<?php echo $id; ?> #inline-search:-webkit-autofill {
	background: #<?php echo $settings->if_bg_focus_color; ?>;
}
<?php endif; ?>

<?php if( ! empty( $settings->if_placeholder_color ) ): ?>
.fl-node-<?php echo $id; ?> #inline-search::-webkit-input-placeholder {
    color: #<?php echo $settings->if_placeholder_color; ?>;
}
 
.fl-node-<?php echo $id; ?> #inline-search:-moz-placeholder {
    color: #<?php echo $settings->if_placeholder_color; ?>;
}
 
.fl-node-<?php echo $id; ?> #inline-search::-moz-placeholder {
    color: #<?php echo $settings->if_placeholder_color; ?>;
}
 
.fl-node-<?php echo $id; ?> #inline-search:-ms-input-placeholder {
    color: #<?php echo $settings->if_placeholder_color; ?>;
}
<?php endif; ?>

<?php if( ! empty( $settings->button_height ) ): ?>
.fl-node-<?php echo $id; ?> .fl-builder-inline-search {
	height: <?php echo $settings->button_height; ?>px;
}
.fl-node-<?php echo $id; ?> #inline-search {
	height: <?php echo $settings->button_height; ?>px;
}

.fl-node-<?php echo $id; ?> .fa-search-icon {
	width: <?php echo $settings->button_height; ?>px;
	height: <?php echo $settings->button_height; ?>px;
	line-height: <?php echo $settings->button_height; ?>px;
}
<?php endif; ?>

<?php if( ! empty( $settings->text_field_width ) ) : ?>
.fl-node-<?php echo $id; ?> .fl-builder-inline-search.inline-search-open #inline-search,
.fl-node-<?php echo $id; ?> .fl-builder-inline-search.inline-search-open {
	width: <?php echo $settings->text_field_width;?>px;
}
<?php endif; ?>

<?php if( ! empty( $settings->text_field_slide ) && $settings->text_field_slide == "right" ) : ?>
.fl-node-<?php echo $id; ?> .fl-builder-inline-search #inline-search {
	left: <?php echo $settings->button_height; ?>px;
	right: auto;
	padding: 0;
}
<?php endif; ?>
<?php if( ! empty( $settings->animation_speed ) ) : ?>
.fl-builder-inline-search #inline-search,
.fl-builder-inline-search {
	-webkit-transition: width <?php echo $settings->animation_speed; ?>s;
	-moz-transition: width <?php echo $settings->animation_speed; ?>s;
	transition: width <?php echo $settings->animation_speed; ?>s;

	-webkit-appearance: none;
	-webkit-border-radius: 0px;

	width: 0;
}
<?php endif; ?>