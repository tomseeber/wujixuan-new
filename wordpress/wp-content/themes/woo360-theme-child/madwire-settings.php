<?php
function woo360child_settings_init() {

    register_setting('pluginPage', 'woo360child_settings');

    add_settings_section(
        'woo360child_pluginPage_section',
        __('Lightspeed Settings', 'wordpress'),
        'woo360child_settings_section_callback',
        'pluginPage'
    );

    add_settings_field(
        'woo360child_checkbox_field_0',
        __('Link this site to a Lightspeed URL?', 'wordpress'),
        'woo360child_checkbox_field_0_render',
        'pluginPage',
        'woo360child_pluginPage_section'
    );

    add_settings_field(
        'woo360child_text_field_1',
        __('Lightspeed Shop URL', 'wordpress'),
        'woo360child_text_field_1_render',
        'pluginPage',
        'woo360child_pluginPage_section'
    );

}

function woo360child_checkbox_field_0_render() {

    $options = get_option('woo360child_settings');
    if ($options == false) {
        $options = array();
        //PC::debug($options);
    }

    ?>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<span class="d-inline-block p-1">off</span>
<div class="d-inline-block material-switch mt-3">
    <input type='checkbox' id="lightspeedActive" name='woo360child_settings[woo360child_checkbox_field_0]' <?php if (array_key_exists("woo360child_checkbox_field_0", $options)) {checked($options['woo360child_checkbox_field_0'], 1);}?> value='1'>
    <label for="lightspeedActive"></label>
</div>
<span class="d-inline-block p-1">on</span>

<?php

}

function woo360child_text_field_1_render() {

    $options = get_option('woo360child_settings');
    ?>
<input type='text' id="lightspeed-url" class="" name='woo360child_settings[woo360child_text_field_1]' value='<?php echo $options['woo360child_text_field_1']; ?>'>
<?php

}

function woo360child_settings_section_callback() {

    echo __('Lightspeed POS Store Connection', 'wordpress');

}

function woo360child_options_page() {

    ?>
<form action='options.php' method='post'>

    <?php if (class_exists('WC_LS_Integration')) {?>


    <div class="container mt-3" id="lightspeed-card">
        <div class="card shadow">
            <div class="card-body">
                <?php
settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        submit_button();
        ?>
            </div>
        </div>
    </div>

    <?php } else {?>

        <div class="card shadow col-md-4">
        <h3 class="card-title text-center pt-3">Lightspeed plugin is NOT installed/activated!</h3>
        <div class="card-body">
            <div class="row">
                <div class="col">

                        <ul class="list-group">
                            <li class="list-group-item">

                            <p class="lead">Activate the plugin named:</p>
                            <p class="lead"><strong>"WooCommerce LightSpeed POS"</strong></p>
                            <p class="lead">If this plugin is not installed, please download &amp; install it.</p>

                            <a href="http://woo360-updates.madwirebuild4.com/?action=download&slug=woocommerce-lightspeed-pos" download class="btn btn-outline-info">Download Lightspeed POS Plugin</a>

                        </li>
                        </ul>

                </div>
            </div>
        </div>
    </div>

    <?php
}
    ?>
</form>
<?php
}
?>