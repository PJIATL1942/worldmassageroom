<?php
wp_head();
?>
<style>
    .velsof_button {
        background-color: <?php echo '#'.$settings['lookandfeel']['button_color_wheel'] . ' !important'; ?>;
    }
    .cancel_button {
        color: <?php echo '#'.$settings['lookandfeel']['background_color_cancel'] . ' !important'; ?>;
        text-align: right;  
        cursor: pointer;
    }
    
    #velsof_wheel_main_container {
        background-color: <?php echo '#'.$settings['lookandfeel']['background_color_wheel'] . ' !important'; ?>;
        height: 100% !important;
    }
    
    .wheelslices {
        color: <?php echo '#'.$settings['lookandfeel']['text_color_wheel'] . ' !important'; ?>;
    }
    body, button, input, textarea {
        color: #43454b;
        font-family: "Source Sans Pro", "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
        line-height: 1.618;
        text-rendering: optimizeLegibility;
        font-weight: 400;
    }
    header{
        display:none;
    }
    
    <?php 
    //Custom CSS
    if (isset($settings['general']['custom_css']) && !empty($settings['general']['custom_css'])) { 
        echo $settings['general']['custom_css'];
    } ?>
</style>

<div id="pull_out" class="spin_toggle" style="display: none;">
    <img src="<?php echo plugins_url('/', __FILE__) . 'images/gift.png'; ?>" alt="slide" style="width:50px; height: 50px;">
</div>

<?php

if (isset($show_wheel_on_page) && $show_wheel_on_page && isset($settings['general']['enabled']) && $settings['general']['enabled']) {  
?>
<!--Spin and Wheel HTML-->
<div id="velsof_wheel_container" style="height: 100%; position: fixed; left: 0px; bottom: 0px; top: 0px; z-index: 100000; display: none;">

    <div id="velsof_wheel_model"> </div>
    <div id="velsof_wheel_main_container">
        <?php 
        if (isset($settings['lookandfeel']['theme']) && $settings['lookandfeel']['theme'] == '1') {
            ?>
            <div id="velsoftop" class="velsoftheme xmas1"></div>
            <div id="velsofbottom" class="velsoftheme xmas1"> </div>
            <?php
        } else if (isset($settings['lookandfeel']['theme']) && $settings['lookandfeel']['theme'] == '2') {
            ?>
            <div id="velsoftop" class="velsoftheme xmas2"></div>
            <div id="velsofbottom" class="velsoftheme xmas2"> </div>
            <?php
        }
        ?>
       
        <div id="velsof_offer_container">
            <?php
            if (isset($settings['lookandfeel']['show_image']) && !empty($settings['lookandfeel']['show_image'])) {
                
                if (isset($settings['lookandfeel']['front_image_upload']) && file_exists(plugin_dir_path(__FILE__) . 'images/' . $settings['lookandfeel']['front_image_upload'])) {
                    $image_path = plugins_url('/', __FILE__) . 'images/' . $settings['lookandfeel']['front_image_upload'];
                    str_replace(plugin_basename(dirname(__FILE__)), 'woocommerce-spin-and-win', $image_path);
                    echo '<div id="spin_wheel_logo_container"><img src="' . $image_path . '" alt="Logo" id="spin_wheel_logo"></div>';
                }
            }
            ?>
            <div id="velsof_offer_main_container">
                <div id="main_title" class="velsof_main_title"><?php echo $settings['text']['title_text']; ?></div>
                <div id="suc_msg" style="display: none;" class="velsof_main_title"></div>
                <div>
                    <div id="velsof_success_description" class="velsof_subtitle" style="padding-bottom:10px ;display: none;"></div>
                    <div id="velsof_description" class="velsof_subtitle" style="padding-bottom:10px;"><?php echo $settings['text']['subtitle_text']; ?></div>

                    <?php
                    $rules_text_array = preg_split('/\n|\r\n/', $settings['text']['rules_text']);
                    ?>
                    <ul class="velsof_ul">
                        <?php
                        if (isset($rules_text_array) && !empty($rules_text_array)) {
                            foreach ($rules_text_array as $key => $value) {
                                echo '<li>' . $value . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
                <div>
                    <input type="hidden" name="empty_field" id="empty_field" value="<?php echo _e('Field cannot be empty.', 'woocommerce-spin-and-win'); ?>" />
                    <input type="hidden" name="validate_email" id="validate_email" value="<?php echo _e('Please enter a valid Email.', 'woocommerce-spin-and-win'); ?>" />
                    <input id="velsof_spin_wheel" type="text" name="spin_wheel_email" class="velsof_input_field" placeholder="<?php echo _e('Enter your email', 'woocommerce-spin-and-win'); ?>" value="">
                    <div class="saving velsof_button" style="display:none;"><span> </span><span> </span><span> </span><span> </span><span> </span></div>
                    <input id="rotate_btn" type="button" class="velsof_button" name="Rotate" value="<?php echo _e('Try your luck', 'woocommerce-spin-and-win'); ?>" onclick="onRotateWheel()">
                    <div id="exit" class="velsof_subtitle cancel_button"><?php echo _e('No, I do not feel lucky', 'woocommerce-spin-and-win'); ?> <span style="font-size: 20px; display: inline-table; vertical-align: middle !important">X</span></div>
                    <div id="continue_btn" class="velsof_button exit_button" style="display:none;"><?php _e('Continue', 'woocommerce-spin-and-win'); ?></div>
                </div>
            </div>
            <div class="before_loader" id="velsof_offer_main_container" style="display:none;"><img id="spin_after_loader" src="<?php echo plugins_url('/', __FILE__) . 'images/loader.gif'; ?>" alt="loader"> </div>  
            <div class="coupon_result"></div>

        </div>

        <div id="velsof_spinner_container">
            <div id="velsof_spinners">
                <div class="velsof_shadow"></div>
                <div id="velsof_spinner" class="velsof_spinner<?php echo $settings['lookandfeel']['wheel_design']; ?>">


                    <div class="wheelslices" style="transform: rotate(-0deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_1']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-30deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_2']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-60deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_3']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-90deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_4']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-120deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_5']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-150deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_6']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-180deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_7']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-210deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_8']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-240deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_9']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-270deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_10']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-300deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_11']['label']; ?>
                    </div>

                    <div class="wheelslices" style="transform: rotate(-330deg) translate(0px, -50%);">
                        <?php echo $settings['slice_settings']['slice_12']['label']; ?>
                    </div>

                </div>
            </div>
            <img id="velsof_wheel_pointer" class="velsof_wheel_pointer2" src="<?php echo plugins_url('/', __FILE__) . 'images/pointer2.png'; ?>" alt="Ponter">
        </div>
    </div>
</div>
<?php
}

?>
<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url('admin-ajax.php') ?>"+"?is_wheel_used=1";
</script>
<script type="text/javascript">
    var show_pull_out = '<?php echo $settings['general']['pull_out']; ?>';
    var email_recheck = '<?php echo $settings['general']['email_recheck']; ?>';
    var hide_after = '<?php echo $hide_after; ?>';
    var min_screen_size = '<?php echo $settings['display']['screen_size']; ?>';
    var time_display = '<?php echo $time_display; ?>';
    var scroll_display = '<?php echo $scroll_display; ?>';
    var exit_display = '<?php echo $exit_display; ?>';
    var show_fireworks = '<?php echo $settings['general']['show_fireworks']; ?>';
    var wheel_device = '<?php echo $mobile_class; ?>';
    var email_check = '<?php echo _e('This email has been used already.', 'woocommerce-spin-and-win'); ?>';
    var display_option = '<?php echo $coupon_display_option; ?>';
    var wheel_design = '<?php echo $settings['lookandfeel']['wheel_design']; ?>';
    var copy_msg = '<?php echo _e('Code Copied.', 'woocommerce-spin-and-win'); ?>';
    var email_only_msg = '<?php echo _e('Coupon code has been sent to your email id.', 'woocommerce-spin-and-win'); ?>';
    
    function onRotateWheel() {

        var email = $("input[name='spin_wheel_email']").val();        
        var email_error = checkEnteredEmail(email);
        if (email_error === false) {
            console.log(email_recheck);
            if (email_recheck == 1) {
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        'action': 'email_recheck',
                        'email' : email
                    },
                    dataType: 'json',
                    success: function (json) {                          
                        if (!json) {
                            error = true;
                            $('#velsof_spin_wheel').tooltipster('content', email_check);
                            $('#velsof_spin_wheel').tooltipster('show');
                            setTimeout(function () {
                                $('#velsof_spin_wheel').tooltipster('destroy');
                            }, 2000);
                        } else {
                            var email = $("input[name='spin_wheel_email']").val().trim();
                            $.ajax({
                                url: ajaxurl,
                                type: 'post',
                                data: {
                                    'action': 'spin_wheel_ajax',
                                    'email' : email,
                                    'wheel_device' : wheel_device
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    $('.saving').show();
                                    $('#rotate_btn').hide();
                                },
                                success: function (json) {
                                    var code = json['code'];
                                    winningCode = json['code'];
                                    var slice_no = json['slice_no'];
                                    var winningangle = parseInt(720 + ((slice_no - 1) * 30));
                                    document.getElementById('velsof_spinner').style.animationName = 'spinwheel_' + slice_no;
                                    rotateWheel(winningangle, 9000);

//                                    setCookie('velsof_wheel_used', 2);
                                    $('#suc_msg').html(json['suc_msg']);
                                    $('#velsof_success_description').html(json['suc_desc']);

                                    if (display_option == '1') {

                                    } else {
                                        if (json['code'] !== '') {
                                            $.ajax({
                                                url: ajaxurl,
                                                type: 'post',
                                                data: {
                                                    'action': 'send_email',
                                                    'email' : email,
                                                    'code' : code,
                                                    'slice_no' : slice_no
                                                },
                                                dataType: 'json',
                                                success: function (json) {
                                                }
                                            });
                                        }
                                    }
                                },
                                complete: function () {
                                    $('.saving').show();
                                    $('#rotate_btn').hide();
                                    window.location = "<?php echo $site_url; ?>/wp-json/wmab/v1.2/appGetSpinWin?version=1.2&content_only=1&is_wheel_used=1";
                                }
                            });
                        }
                    }
                });
            } else {
                var email = $("input[name='spin_wheel_email']").val().trim();
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        'action': 'spin_wheel_ajax',
                        'email' : email,
                        'wheel_device' : wheel_device
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        $('.saving').show();
                        $('#rotate_btn').hide();
                    },
                    success: function (json) {                        
                        var code = json['code'];
                        winningCode = json['code'];
                        var slice_no = json['slice_no'];
                        var winningangle = parseInt(720 + ((slice_no - 1) * 30));
                        rotateWheel(winningangle, 9000);
                        setCookie('velsof_wheel_used', 2);
                        $('#suc_msg').html(json['suc_msg']);
                        $('#velsof_success_description').html(json['suc_desc']);

                        if (display_option == '1') {

                        } else {
                            if (json['code'] !== '') {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'post',
                                    data: {
                                        'action': 'send_email',
                                        'email' : email,
                                        'code' : code,
                                        'slice_no' : slice_no
                                    },
                                    dataType: 'json',
                                    success: function (json) {
                                    }
                                });
                            }
                        }
                    },
                    complete: function () {
                        $('.saving').show();
                         window.location = "<?php echo $site_url; ?>/wp-json/wmab/v1.2/appGetSpinWin?version=1.2&content_only=1&is_wheel_used=1";
                    }
                });
            }
        }
    }

    function wheelAction(data, email) {
        $('.before_loader').hide();
        if (data['type'] === 'Win') {
            var code = data['code'];
            var slice_no = data['slice_no'];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    'action': 'send_email',
                    'email' : email,
                    'code' : code,
                    'slice_no' : slice_no
                },
                dataType: 'json',
                success: function (json) {
                    
                }
            });
            var code = data['code'];
            var label = data['label'];
        }
    }
   
    //Custom JS
    <?php 
    if (isset($settings['general']['include_jquery']) && !empty($settings['general']['include_jquery'])) { 
        echo stripslashes($settings['general']['include_jquery']);
    }
    ?>
</script>
