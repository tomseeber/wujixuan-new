<?php 

/**
 * @class BBMESearchModule
 */
class ToggleSearchFormModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct()
	{
		parent::__construct(array(
			'name'          	=> __('Toggle Search Form', 'toggle-search-form'),
			'description'   	=> __('Toggle Search Form', 'toggle-search-form'),
			'category'      	=> __('WP Beaver World', 'toggle-search-form'),
			'group'      		=> __('WP Beaver World', 'toggle-search-form'),
			'dir' 				=> TSF_DIR . 'module/toggle-search-form/',
			'url' 				=> TSF_URL . 'module/toggle-search-form/',
			'partial_refresh'	=> true,
			'icon' 				=> 'icon.svg'
		));
	}
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('ToggleSearchFormModule', [
	'general' 		=> [
		'title' 		=> __('General', 'toggle-search-form'),
		'sections' 		=> [
			'general'       => [
				'title'         => '',
				'fields'        => [
					'placeholder'	=> [
						'type'          => 'text',
						'label'         => __('Placeholder', 'toggle-search-form'),
						'default' 		=> __('Search', 'toggle-search-form'),
						'placeholder' 	=> __('Search', 'toggle-search-form')
					],

					'icon'			=> [
						'type'          => 'icon',
						'label'         => __('Search Icon', 'toggle-search-form'),
						'default' 		=> 'fa fa-search',
						'show_remove' 	=> true 
					],

					'button_height'	=> [
						'type'          => 'text',
						'label'         => __('Button Height', 'toggle-search-form'),
						'default' 		=> '30',
						'description' 	=> 'px',
						'size'			=> 5,
						'maxlength' 	=> 3
					],

					'text_field_width'	=> [
						'type'          => 'text',
						'label'         => __('Input Field Width', 'toggle-search-form'),
						'default' 		=> '280',
						'description' 	=> 'px',
						'size'			=> 5,
						'maxlength' 	=> 4
					],

					'text_field_slide'	=> [
						'type'          => 'select',
						'label'         => __('Input Box Slide', 'toggle-search-form'),
						'default' 		=> 'left',
						'options' 		=> [
							'left' => __( 'From Right to Left', 'toggle-search-form' ),
							'right' => __( 'From Left to Right', 'toggle-search-form' ),
						]
					],

					'animation_speed'	=> [
						'type'          	=> 'text',
						'label'         	=> __('Animation Speed', 'toggle-search-form'),
						'default' 			=> '1.1',
						'description' 		=> 's',
						'size'				=> 7,
						'maxlength' 		=> 5
					],
				]
			],

			'style'       => [
				'title'         => __('Style', 'toggle-search-form'),
				'fields'        => [
					'btn_bg_color' 	=> [
						'type'          	=> 'color',
						'default' 			=> '333333',
						'show_reset'   		=> true,
						'label'         	=> __('Button Background Color', 'toggle-search-form')
					],

					'btn_bg_hover_color' 	=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> '000000',
						'label'         	=> __('Button Background Hover Color', 'toggle-search-form')
					],

					'icon_color'		=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> 'ffffff',
						'label'         	=> __('Icon Color', 'toggle-search-form') 
					],

					'icon_hover_color'		=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> 'f9f9f9',
						'label'         	=> __('Icon Hover Color', 'toggle-search-form') 
					],

					'if_bg_color'		=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> 'ffffff',
						'label'         	=> __('Text Field Background Color', 'toggle-search-form') 
					],

					'if_bg_focus_color'		=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> 'efefef',
						'label'         	=> __('Text Field Focus Color', 'toggle-search-form') 
					],

					'if_txt_color' 		=> [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> '333333',
						'label'         	=> __('Text Color', 'toggle-search-form') 
					],

					'if_placeholder_color' => [
						'type'          	=> 'color',
						'show_reset'   		=> true,
						'default' 			=> '999999',
						'label'         	=> __('Placeholder Color', 'toggle-search-form') 
					],					
				]
			],

			'fs'       => [
				'title'         => __('Font Size', 'toggle-search-form'),
				'fields'        => [
					'icon_font_size'     => [
						'type'          => 'select',
						'label'         => __('Icon Font Size', 'toggle-search-form'),
						'default'       => 'custom',
						'options'       => [
							'default'       =>  __('Default', 'toggle-search-form'),
							'custom'        =>  __('Custom', 'toggle-search-form')
						],
						'toggle'        => [
							'custom'        => [
								'fields'        => ['icon_custom_font_size']
							]
						]
					],
					'icon_custom_font_size' => array(
						'type'          => 'text',
						'label'         => __('Custom Icon Font Size', 'toggle-search-form'),
						'default'       => '22',
						'maxlength'     => '3',
						'size'          => '4',
						'description'   => 'px'
					),

					'font_size'     => [
						'type'          => 'select',
						'label'         => __('Text Font Size', 'toggle-search-form'),
						'default'       => 'default',
						'options'       => [
							'default'       =>  __('Default', 'toggle-search-form'),
							'custom'        =>  __('Custom', 'toggle-search-form')
						],
						'toggle'        => [
							'custom'        => [
								'fields'        => ['custom_font_size']
							]
						]
					],

					'custom_font_size' => array(
						'type'          => 'text',
						'label'         => __('Custom Font Size', 'toggle-search-form'),
						'default'       => '14',
						'maxlength'     => '3',
						'size'          => '4',
						'description'   => 'px'
					),
				]
			],
		]
	]
]);