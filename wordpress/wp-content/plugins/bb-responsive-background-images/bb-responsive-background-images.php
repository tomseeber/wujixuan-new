<?php

/**
 * Plugin Name: Beaver Builder Responsive Background Images
 * Plugin URI: https://sitespot.dev/resource/beaver-builder-responsive-background-images/
 * Description: A Beaver Builder plugin that allows you to set a different background images, sizing and alignment on tablet and mobile device sizes.
 * Version: 1.5.1
 * Author: Sitespot Dev
 * Author URI: https://sitespot.dev
 * License: GPL2

  Beaver Builder Responsive Background Images is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.
   
  Beaver Builder Responsive Background Images is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
   
  You should have received a copy of the GNU General Public License
  along with Beaver Builder Responsive Background Images. If not, see https://www.gnu.org/licenses/gpl-3.0.en.html.

 */

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://sitespot.dev/wp-content/uploads/bt-updates/Beaver Builder Responsive Background Images.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'bb-responsive-background-images'
);


add_filter( 'fl_builder_register_settings_form', function( $form, $slug ) {
  if ( 'row' === $slug || 'col' === $slug) {
		    
    $form['tabs']['style']['sections']['bg_photo']['fields']['responsive_bg']  = array(
      'type'          => 'select',
      'label'         => __( 'Responsive Backgrounds', 'btbb' ),
      'default'       => 'none',
      'options'       => array(
          'none'      => __( 'None', 'btbb' ),
          'smallmedium'      => __( 'Small and Medium Screens', 'btbb' ),
          'small'      => __( 'Small Screens', 'btbb' ),
          'medium'      => __( 'Medium Screens', 'btbb' )
      ),
      'toggle'        => array(
          'smallmedium'      => array(
              'fields'      => array( 'rbg_image','rbg_repeat', 'rbg_position', 'rbg_attachment', 'rbg_size'),
          ),
          'small'      => array(
              'fields'      => array( 'rbg_image','rbg_repeat', 'rbg_position', 'rbg_attachment', 'rbg_size'),
          ),
          'medium'      => array(
              'fields'      => array( 'rbg_image','rbg_repeat', 'rbg_position', 'rbg_attachment', 'rbg_size'),
          )
      )
    );
          
    $responsiveFields = array(
          'rbg_image' => array(
              'type' => 'photo',
              'label' => 'Responsive Photo',
              'preview' => array(
                  'type' => 'none'
                  ),
              'connections' => array(
                  '0' => 'photo'
                  ),
              ),
          'rbg_repeat' => array(
              'type' => 'select',
              'label' => 'Responsive Repeat',
              'default' => 'none',
              'options' => array(
                  'no-repeat' => 'None',
                  'repeat' => 'Tile',
                  'repeat-x' => 'Horizontal',
                  'repeat-y' => 'Vertical',
                  ),
              'help' => 'Repeat applies to how the image should display in the background. Choosing none will display the image as uploaded. Tile will repeat the image as many times as needed to fill the background horizontally and vertically. You can also specify the image to only repeat horizontally or vertically.',
              'preview' => array(
                  'type' => 'none'
                  ),
              ),
          'rbg_position' => array(
              'type' => 'select',
              'label' => 'Responsive Position',
              'default' => 'center center',
              'options' => array(
                  'left top' => 'Left Top',
                  'left center' => 'Left Center',
                  'left bottom' => 'Left Bottom',
                  'right top' => 'Right Top',
                  'right center' => 'Right Center',
                  'right bottom' => 'Right Bottom',
                  'center top' => 'Center Top',
                  'center center' => 'Center',
                  'center bottom' => 'Center Bottom',
                  ),
              'help' => 'Position will tell the image where it should sit in the background.',
              'preview' => array(
                  'type' => 'none'
                  ),
              ),
          'rbg_attachment' => array(
              'type' => 'select',
              'label' => 'Responsive Attachment',
              'default' => 'scroll',
              'options' => array(
                  'scroll' => 'Scroll',
                  'fixed' => 'Fixed',
                  ),
              'help' => 'Attachment will specify how the image reacts when scrolling a page. When scrolling is selected, the image will scroll with page scrolling. This is the default setting. Fixed will allow the image to scroll within the background if fill is selected in the scale setting.',
              'preview' => array(
                  'type' => 'none',
                  ),
              ),
          'rbg_size' => array(
              'type' => 'select',
              'label' => 'Responsive Scale',
              'default' => 'cover',
              'options' => array(
                  'auto' => 'None',
                  'contain' => 'Fit',
                  'cover' => 'Fill',
                  ),
              'help' => 'Scale applies to how the image should display in the background. You can select either fill or fit to the background.',
              'preview' => array(
                  'type' => 'none',
                  ),
              ),
      );
    
    
    $form['tabs']['style']['sections']['bg_photo']['fields'] = array_merge($form['tabs']['style']['sections']['bg_photo']['fields'], $responsiveFields);

    return $form;
  }

	return $form;
}, 10, 2 );




//render the CSS for the 
add_filter( 'fl_builder_render_css', 'btbb_row_responsive_css', 10, 4 );

function btbb_row_responsive_css($css, $nodes, $global_settings){
	
	$btbb_css = "";
	
	foreach ( $nodes['rows'] as $row ) {
				
		if($row->settings->responsive_bg !== "none")
		{
			$btbb_css .= create_responsive_bg_css($row->node, $row->settings,'row');
		}
	}
	
	foreach ( $nodes['columns'] as $column ) {
		if($column->settings->responsive_bg !== "none")
		{			
			$btbb_css .= create_responsive_bg_css($column->node, $column->settings, 'column');
		}
	}
	return $css . $btbb_css;
  
}


function create_responsive_bg_css($node, $settings,$type){
	
  if($type == 'row')
    $selector = ".fl-row-content-wrap";
  elseif($type == 'column')
    $selector = ".fl-col-content";
  
	ob_start();
	?>
  /* Beaver Team Responsive CSS */

	.fl-node-<?php echo "$node > $selector";?>{
		background-image: url(<?php echo $settings->rbg_image_src;?>);
		background-size: <?php echo $settings->rbg_size;?>;
		background-attachment: <?php echo $settings->rbg_attachment?>;
		background-repeat: <?php echo $settings->rbg_repeat;?>;
		background-position: <?php echo $settings->rbg_position?>;
	}

	<?php	
		
	$returnstyle = ob_get_clean();
  
  //get responsive breakpoints inside the builder settings
  $builderSettings = get_option('_fl_builder_settings');

  $smallSize = $builderSettings->responsive_breakpoint;
  $mediumSize = $builderSettings->medium_breakpoint;
    
  //use BB default breakpoints if user hasnt set up their own values.
  if(!$smallSize)
    $smallSize = '768';
  
  if(!$mediumSize)
    $mediumSize = '992';

  
	$returnstyle = wrapResponsive($returnstyle,$settings->responsive_bg, $smallSize, $mediumSize);
	  
	return $returnstyle;
}


function wrapResponsive($css,$size, $smallSize, $mediumSize){
  

	$small =	"@media only screen and (max-width: ".$smallSize."px) {";	
	$smallmedium =	"@media only screen and (max-width: ".$mediumSize."px) {";
	$medium = "@media only screen and (min-width:".$smallSize."px) and (max-width: ".$mediumSize."px) {";
		
	switch ($size) {
    case "small":
        return $small . $css . "}";
    case "smallmedium":
        return $smallmedium . $css . "}";
    case "medium":
        return $medium . $css . "}";
	}
}