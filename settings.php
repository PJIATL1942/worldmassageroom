<?php
if (!defined('ABSPATH'))
    exit;  // Exit if access directly

global $wpdb;

if (isset($_POST['vss_mab']) && !empty($_POST['vss_mab'])) {
    $settings = filter_var_array($_POST['vss_mab']);
    
    if (isset($_POST['vss_mab_upload_file_hidden']) && !empty($_POST['vss_mab_upload_file_hidden'])) {
        $settings['google_login_settings']['google_json'] = sanitize_text_field($_POST['vss_mab_upload_file_hidden']);
    }
} else {
    //Get Mobile App Builder settings from database
    $settings = get_option('wmab_settings');
    if (isset($settings) && !empty($settings)) {
        $settings = unserialize($settings);
    }
    
    //print_r($settings); die;
    
    //Get Slideshow Details
    $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_banner` WHERE banner_id = %d", $settings['slideshow_id']);
    $slideshow_details = $wpdb->get_row($sql);
    
    //Get Banner Details
    $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_banner` WHERE banner_id = %d", $settings['banner_id']);
    $banner_details = $wpdb->get_row($sql);
    
    //Get Slides
    $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_banner_image` WHERE banner_id = %d", $settings['slideshow_id']);
    $slide_details = $wpdb->get_results($sql);
    
    //Get Banners
    $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_banner_image` WHERE banner_id = %d", $settings['banner_id']);
    $banner_image_details = $wpdb->get_results($sql);
    
    //Get Information Page details
    $sql = "SELECT * FROM `{$wpdb->prefix}mab_cms_page_data`";
    $information_pages = $wpdb->get_results($sql);
    
    /*BOC neeraj.kumar@velsof.com Module Upgrade V2 Get Home layouts details */
    $sql = "SELECT * FROM `{$wpdb->prefix}mab_mobileapp_layouts`";
    $home_layout_details = $wpdb->get_results($sql);    
    /** EOC */
    $information_page_list = array();
    if (isset($information_pages) && !empty($information_pages)) {
        foreach ($information_pages as $information_page) {
            $information_page_list[$information_page->link_to] = $information_page;
        }
    }
    
    /** BOC neeraj.kumar@velsof.com Tab Bar Layout Changes : Module Upgrade V2 **/    
    $sql = "SELECT * FROM `{$wpdb->prefix}mab_mobileapp_tab_bar`";
    $tab_bar_layout_details = $wpdb->get_results($sql);   
    
}

//Error Icon Path
$error_icon_path = plugins_url('/', __FILE__) . 'images/error_icon.gif';

//Get Pages List
$args = array(
	'sort_order' => 'asc',
	'sort_column' => 'post_title',
	'hierarchical' => 1,
	'exclude' => '',
	'include' => '',
	'meta_key' => '',
	'meta_value' => '',
	'authors' => '',
	'child_of' => 0,
	'parent' => -1,
	'exclude_tree' => '',
	'number' => '',
	'offset' => 0,
	'post_type' => 'page',
	'post_status' => 'publish'
); 
$pages = get_pages($args);

//Get Product Categories
$cat_args = array(
    'orderby'    => 'name',
    'order'      => 'asc',
    'hide_empty' => false,
); 
$product_categories = get_terms( 'product_cat', $cat_args );

//Get All Products
$args = array(
    'status' => 'publish',
    'orderby'  => 'name',
    'limit' => -1
);
$products = wc_get_products( $args );

//Get Push Notifications History
$customPageHTML = "";
$sql = "SELECT * FROM `{$wpdb->prefix}mab_push_notification_history`";
$total_query = "SELECT COUNT(1) FROM (${sql}) AS combined_table";
$total = $wpdb->get_var( $total_query );
$items_per_page = 20;
$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
$offset = ( $page * $items_per_page ) - $items_per_page;
$push_notification_history = $wpdb->get_results( $sql . " ORDER BY notification_id DESC LIMIT ${offset}, ${items_per_page}" );
$totalPage = ceil($total / $items_per_page);

//$sql = "SELECT * FROM `{$wpdb->prefix}mab_push_notification_history`";
//$push_notification_history = $wpdb->get_results($sql);
	
if ((isset($_SESSION['wmab_google_error']) && $_SESSION['wmab_google_error']) || (isset($_SESSION['wmab_fb_error']) && $_SESSION['wmab_google_error'])) {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'Please check the form carefully for errors.', 'woocommerce-mobile-app-builder' ); ?></p>
    </div>
    <?php
}

?>
<style>
    #ui-datepicker-div{
        padding: 20px !important;
        background-color: white !important;
    }
    
</style>
<div class="wrap nosubsub">
    <h1 class="wp-heading-inline"><?php echo _e('Mobile App Builder', 'woocommerce-mobile-app-builder'); ?></h1>
    <div id="col-container" class="wp-clearfix">
        <div id="wmab-col-left">
            <div class="list-group">
                <a class="list-group-item <?php if (!isset($_GET['wmab_tab']) && !isset($_GET['wmab_home_layout_success']) && !isset($_GET['wmab_tab_bar_success']) && !isset($_GET['wmab_tab_bar_error'])) { ?>active<?php } ?>" id="link-general_settings" onclick="change_tab(this.id, 'general_settings');"><?php echo _e('General Settings', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <a class="list-group-item" id="link-push_notification_settings" onclick="change_tab(this.id, 'push_notification_settings');"><?php echo _e('Push Notification Settings', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="position: absolute; right: 10px; top: 10px; display: inline;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <a class="list-group-item <?php if (isset($_GET['wmab_tab'])) { ?>active<?php } ?>" id="link-push_notification_history" onclick="change_tab(this.id, 'push_notification_history');"><?php echo _e('Push Notification History', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <a class="list-group-item" id="link-payment_methods" onclick="change_tab(this.id, 'payment_methods');"><?php echo _e('Payment Methods', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <!--BOC neeraj.kumar@velsof.com 20-Dec-2019 Module Upgrade V2 added home layout page-->
                <a class="list-group-item <?php if(isset($_GET['wmab_home_layout_success'])){ echo 'active'; }?>" id="home-layout-page" onclick="change_tab(this.id, 'home_layout_tab');"><?php echo _e('Home Page Layout', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <a class="list-group-item" id="link-information_pages" onclick="change_tab(this.id, 'information_pages');"><?php echo _e('Information Pages', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
                <!--                contact us tab - saurav.chaudhary@velsof.com 29-aug-2020-->
                <a class="list-group-item" id="link-contact-us" onclick="change_tab(this.id, 'contact_us');"><?php echo _e('Contact Us', 'woocommerce-mobile-app-builder'); ?>
                    <label class="velsof_error_label"><img id="velsof_error_icon" class="velsof_error_tab_img" style="display:none; position:absolute; right:10px; top:10px;" src="<?php echo $error_icon_path; ?>"></label>
                </a>
            </div>
        </div>
        <div id="wmab-col-right">
            <div class="col-wrap">
                <div class="form-wrap">
                    <form method="post" action="admin.php?page=mobile-app-builder" onsubmit="return mabsubmission()" enctype="multipart/form-data">
                        <!--General Settings Section-->
                        <div id="general_settings" class="wmab-panel" <?php if (isset($_GET['wmab_tab']) || isset($_GET['wmab_home_layout_success']) || isset($_GET['wmab_tab_bar_success']) || isset($_GET['wmab_tab_bar_error'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('General Settings', 'woocommerce-mobile-app-builder'); ?> <span class="mobile_preview_button" title="<?php echo _e('Hide/Show Preview', 'woocommerce-mobile-app-builder'); ?>"><i class="fa fa-eye"></i></span></h2>
                            <div class="form-field form-required">
                                <label><?php _e('Enable/Disable', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][enabled]" type="checkbox" value="1" <?php echo !empty($settings['general']['enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable entire mobile app functionality.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <!-- Spin and Win Status -->
                            <div class="form-field form-required">
                                <label><?php _e('Enable Spin and Win', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][spin_win_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['spin_win_status']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('It can be enabled only if module is installed.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <div class="form-field form-required">
                                <label><?php _e('Enable Request Log Reporting', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][log_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['log_status']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable error log reporting for every request to Web Services of the module.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable Live Chat Support', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][live_chat]" id="live_chat" type="checkbox" value="1" <?php echo !empty($settings['general']['live_chat']) ? 'checked' : ''; ?> onclick="mab_show_chat_api_box()" />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable live chat support option in mobile app.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required" id="live_chat_support" <?php if (empty($settings['general']['live_chat'])) { ?>style="display: none;"<?php } ?>>
                                <label><?php _e('Live Chat API Key', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][live_chat_api_key]" type="text" value="<?php echo !empty($settings['general']['live_chat_api_key']) ? $settings['general']['live_chat_api_key'] : ''; ?>" />
                                <p><?php _e('Enter API Key.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Redirect on Cart Page when Add To Cart', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][cart_option_redirect_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['cart_option_redirect_status']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('redirect to cart page or keep on the product page when add to cart is clicked.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Display Short Description', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][display_short_desc]" type="checkbox" value="1" <?php echo !empty($settings['general']['display_short_desc']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable Logo', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][logo_status]" id="logo_status" type="checkbox" value="1" <?php echo !empty($settings['general']['logo_status']) ? 'checked' : ''; ?> onclick="mab_logo_upload_image()"/>
                                    <span class="slider"></span>
                                </label>
                                <p></p>
                            </div>
                            
                            <div class="form-field form-required" id="logo_upload_image" <?php if (empty($settings['general']['logo_status'])) { ?>style="display: none;"<?php } ?>>
                                <label><?php _e('Image for logo', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <div class="mab-center" id="logo_image_div" <?php if (empty($settings['general']['vss_mab_app_logo_image_path'])) { ?><?php } ?>>
                                    <center> <img src="<?php if(!empty($settings['general']['vss_mab_app_logo_image_path'])){ echo plugins_url('/', __FILE__) .'images/'.$settings['general']['vss_mab_app_logo_image_path']; }?>" id="logo_image" <?php if (!isset($settings['general']['vss_mab_app_logo_image_path']) || empty($settings['general']['vss_mab_app_logo_image_path'])) { ?> style="width: 40%;height: auto;display:none;" <?php }else{ ?>style="width: 180px;height: auto;" <?php } ?> />
                                    <br><input name="vss_mab_app_logo_image_path" type="file" value="" onchange="mab_logo_change_image(this)" style="margin-left: 9%;"/>
                                   <?php if (isset($_SESSION['wmab_app_logo_error']) && $_SESSION['wmab_app_logo_error']) {
                                    ?><p class="mab_validation_error"><?php echo _e('Invalid File.', 'woocommerce-mobile-app-builder'); ?></p>
                                    <?php unset($_SESSION['wmab_app_logo_error']);
                                    } ?>
                                    </center>
                                </div>
								<input type="hidden" name="vss_mab[general][image_logo_hidden]" value="<?php echo !empty($settings['general']['vss_mab_app_logo_image_path']) ? $settings['general']['vss_mab_app_logo_image_path'] : NULL; ?>" />
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable Whatsapp Chat Support', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][whatsup_chat_support_status]" type="checkbox" id="whatsup_chat_support_status" value="1" <?php echo !empty($settings['general']['whatsup_chat_support_status']) ? 'checked' : ''; ?> onclick="mab_show_whatup_chat_box()"/>
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable WHATSAPP chat support option in mobile app.', 'woocommerce-mobile-app-builder'); ?></p>                                
                            </div>
                            <div class="form-field form-required" id="whatsup_chat_support" <?php if (empty($settings['general']['whatsup_chat_support_status'])) { ?>style="display: none;"<?php } ?>>
                                <label><?php _e('Chat Number', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][whatsup_chat_number_key]" type="text" value="<?php echo !empty($settings['general']['whatsup_chat_number_key']) ? $settings['general']['whatsup_chat_number_key'] : ''; ?>" />
                                <p><?php _e('Enter Country code with Chat number as well.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable/Disable Fingerprint Login', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][fingerprint_login_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['fingerprint_login_status']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable Fingerprint login in Mobile App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable/Disable Phone Number Registration', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][phone_number_registration_status]" id="phone_number_registration_status" type="checkbox" value="1" <?php echo !empty($settings['general']['phone_number_registration_status']) ? 'checked' : ''; ?> onclick="mab_show_mandatory_number_box()"/>
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable customer registration and login via phone number verification.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                             <div class="form-field form-required" id="number_mandatory_div" <?php if (empty($settings['general']['phone_number_mandatory_status'])) { ?>style="display: none;"<?php } ?>>
                                <label><?php _e('Mandatory Phone number at registration', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][phone_number_mandatory_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['phone_number_mandatory_status']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will set the Phone number field as optional or required.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Enable Url Encoding Of Image Links', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][image_url_encoding_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['image_url_encoding_status']) ? 'checked' : ''; ?>/>
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable Url encoding of all image links.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Select Font of the App', 'woocommerce-mobile-app-builder'); ?><span class="mab_error">*</span></label>
                              <?php  $font_options = array(
                                                array(
                                                    'id'=> 'BaskOldFace.TTF',
                                                    'name'=> 'BaskOldFace.TTF',
                                                ),
                                                array(
                                                    'id'=> 'BritannicBold.TTF',
                                                    'name'=> 'BritannicBold.TTF',
                                                ),
                                                array(
                                                    'id'=> 'BrunoAceSC-Regular.ttf',
                                                    'name'=> 'BrunoAceSC-Regular.ttf',
                                                ),
                                                array(
                                                    'id'=> 'Calibri.ttf',
                                                    'name'=> 'Calibri.ttf',
                                                ),
                                                array(
                                                    'id'=> 'Comme-Regular.ttf',
                                                    'name'=> 'Comme-Regular.ttf',
                                                ),
                                                array(
                                                    'id'=> 'Constantia.ttf',
                                                    'name'=> 'Constantia.ttf',
                                                ),
                                                array(
                                                    'id'=> 'CourierNewPSMT.ttf',
                                                    'name'=> 'CourierNewPSMT.ttf',
                                                ),
                                                array(
                                                    'id'=> 'FranklinGothic-Demi.TTF',
                                                    'name'=> 'FranklinGothic-Demi.TTF',
                                                ),
                                                array(
                                                    'id'=> 'HarlowSolid.TTF',
                                                    'name'=> 'HarlowSolid.TTF',
                                                ),
                                                array(
                                                    'id'=> 'LucidaCalligraphy-Italic.TTF',
                                                    'name'=> 'LucidaCalligraphy-Italic.TTF',
                                                ),
                                                array(
                                                    'id'=> 'LucidaHandwriting-Italic.TTF',
                                                    'name'=> 'LucidaHandwriting-Italic.TTF',
                                                ),
                                                array(
                                                    'id'=> 'Magneto-Bold.TTF',
                                                    'name'=> 'Magneto-Bold.TTF',
                                                ),
                                                array(
                                                    'id'=> 'MaturaMTScriptCapitals.TTF',
                                                    'name'=> 'MaturaMTScriptCapitals.TTF',
                                                ),
                                                array(
                                                    'id'=> 'Mistral.TTF',
                                                    'name'=> 'Mistral.TTF',
                                                ),
                                                array(
                                                    'id'=> 'MonotypeCorsiva.TTF',
                                                    'name'=> 'MonotypeCorsiva.TTF',
                                                ),
                                                array(
                                                    'id'=> 'OCRAExtended.TTF',
                                                    'name'=> 'OCRAExtended.TTF',
                                                ),
                                                array(
                                                    'id'=> 'OldEnglishTextMT.TTF',
                                                    'name'=> 'OldEnglishTextMT.TTF',
                                                ),
                                                array(
                                                    'id'=> 'Parchment-Regular.TTF',
                                                    'name'=> 'Parchment-Regular.TTF',
                                                ),
                                                array(
                                                    'id'=> 'Pristina-Regular.TTF',
                                                    'name'=> 'Pristina-Regular.TTF',
                                                ),
                                                array(
                                                    'id'=> 'ScriptMTBold.TTF',
                                                    'name'=> 'ScriptMTBold.TTF',
                                                ),
                                                array(
                                                    'id'=> 'SegoePrint.ttf',
                                                    'name'=> 'SegoePrint.ttf',
                                                ),
                                                array(
                                                    'id'=> 'WhitneyHTF-Book.ttf',
                                                    'name'=> 'WhitneyHTF-Book.ttf',
                                                ),
                                            );                              
                                             ?>
                                <select name="vss_mab[general][app_fonts]">
                                    <?php 
                                    foreach ($font_options as $font_option) {
                                        ?>
                                    <option value="<?php echo $font_option['id']; ?>" <?php if (isset($settings['general']['app_fonts']) && $settings['general']['app_fonts'] == $font_option['id']) { echo "selected"; }?>><?php echo $font_option['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>                                
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e(' Select layout for Home page', 'woocommerce-mobile-app-builder'); ?><span class="mab_error">*</span></label>
                              <?php 
                                if(isset($home_layout_details) && !empty($home_layout_details)){
                                    foreach($home_layout_details as $key_home_layout => $value_home_layout){
                                        $layout_options[] =   array(
                                                    'id'=> $value_home_layout->id_layout,
                                                    'name'=> $value_home_layout->layout_name,                                                                                               
                                            );
                                    }
                                }                                                           
                                ?>
                                <select name="vss_mab[general][home_page_layout]">
                                    <?php 
                                    foreach ($layout_options as $layout_option) {
                                        ?>
                                    <option value="<?php echo $layout_option['id']; ?>" <?php if (isset($settings['general']['home_page_layout']) && $settings['general']['home_page_layout'] == $layout_option['id']) { echo "selected"; }?>><?php echo $layout_option['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>                                
                            </div>
                            <!--Color Picker-->
                            
                            <div class="form-field form-required">
                                <label><?php _e('App Button Color', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <div class="input-group" id="app_button_color">
									<input type="text" name="vss_mab[general][app_button_color]" id="color_1" value="<?php echo !empty($settings['general']['app_button_color']) ? $settings['general']['app_button_color'] : '#304ffe'; ?>"  /> 
                                </div>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('App Theme Colour', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <div class="input-group" id="app_theme_color">
									<input type="text" name="vss_mab[general][app_theme_color]" id="color_2" value="<?php echo !empty($settings['general']['app_theme_color']) ? $settings['general']['app_theme_color'] : '#ff9757'; ?>"  />
                                </div>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('App Background Color', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <div class="input-group" id="app_background_color">
                                    <input type="text" name="vss_mab[general][app_background_color]" id="color_3" value="<?php echo !empty($settings['general']['app_background_color']) ? $settings['general']['app_background_color'] : '#f8f8f8'; ?>"  />          
                                </div>
                            </div>
                            
                            <div class="form-field form-required">
                                <label><?php _e('Enable Tab Bar', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][tab_bar_status]" type="checkbox" value="1" <?php echo !empty($settings['general']['tab_bar_status']) ? 'checked' : ''; ?>/>
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable tab bar option.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <!-- EOC neeraj.kumar@velsof.com 20-Dec-2019 Module Upgrade V2-->
                            <div class="form-field form-required">
                                <label style="float:clear;"><?php _e('Custom CSS', 'woocommerce-mobile-app-builder'); ?></label>
                                <textarea rows="5" name="vss_mab[general][custom_css]"><?php echo !empty($settings['general']['custom_css']) ? $settings['general']['custom_css'] : ''; ?></textarea>
                                <p><?php _e('Add custom CSS. Please do not add style tags.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Category Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][category_image_width]" type="text" value="<?php echo !empty($settings['general']['category_image_width']) ? $settings['general']['category_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Category Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][category_image_height]" type="text" value="<?php echo !empty($settings['general']['category_image_height']) ? $settings['general']['category_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][product_image_width]" type="text" value="<?php echo !empty($settings['general']['product_image_width']) ? $settings['general']['product_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][product_image_height]" type="text" value="<?php echo !empty($settings['general']['product_image_height']) ? $settings['general']['product_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field">
                                <label><?php _e('Start date from which the prodcuts will be considered as New', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][product_new_date]" class="mab_product_new_date" type="text" value="<?php echo $settings['general']['product_new_date']; ?>" />
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Number of products to be considered as New', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[general][product_new_number]" type="text" value="<?php echo !empty($settings['general']['product_new_number']) ? $settings['general']['product_new_number'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <!--<div class="form-field form-required">
                                <label><?php _e('Show Price', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[general][show_price]" type="checkbox" value="1" <?php echo !empty($settings['general']['show_price']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>-->
							
							<!--Mobile Preview Starts Here - changes added by Harsh Agarwal on 03-Jun-2020-->
							<div id="general_settings_mobile_preview" class="general_settings_mobile_preview" style="display:block">
								<div class="front_preview">
									<div class="layout_gallery">
										<div class="topHeader" <?php if (!empty($settings['general']['app_theme_color'])) { ?>style="background-color:<?php echo $settings['general']['app_theme_color']; ?>"<?php } ?>>
											<div class="leftmenu">
												<span class="toggleMenu"><i class="fa fa-bars"></i></span>
											</div>											
											<?php 
											if(!empty($settings['general']['vss_mab_app_logo_image_path'])){
											?>
											<div class="logo">
												<img src="<?php echo plugins_url('/', __FILE__) .'images/'.$settings['general']['vss_mab_app_logo_image_path']; ?>" class="mCS_img_loaded">
												<p style="display:none;"><?php _e('Home', 'woocommerce-mobile-app-builder'); ?></p>
											</div>
											<?php													
											} else {		
											?>
											<div class="logo" style="margin-right: 2.5em;">
												<p><?php _e('Home', 'woocommerce-mobile-app-builder'); ?></p>
												<img src="" class="mCS_img_loaded" style="display:none;">
											</div>
											<?php
											}
											?>	
											<div class="cartSection">
												<span class="cartIcon"><i class="fa fa-shopping-cart"></i></span>
											</div>
											<div class="searchBar">
												<span class="searchicon"><i class="fa fa-search"></i></span>
											</div>	
										</div>
														
										<img src="<?php echo plugins_url('/', __FILE__); ?>images/General_Settings_GIF.gif" alt="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" title="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" />
										<div class="chatBoxIcon" style="<?php if(!empty($settings['general']['whatsup_chat_support_status'])) { ?>display: block; <?php } else { ?>display:none;<?php } ?> <?php if (!empty($settings['general']['app_theme_color'])) { ?>background-color:<?php echo $settings['general']['app_theme_color']; } ?>">
											<img src="<?php echo plugins_url('/', __FILE__); ?>images/chat_icon.png" alt="Chat Icon" title="Chat Icon" />
										</div>
									</div>
								</div>			
							</div>
							<!--Mobile Preview Ends Here-->
                        </div>
                        
                        <!--Push Notification Settings Section-->
                        <div id="push_notification_settings" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Push Notification Settings', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field form-required">
                                <label><?php _e('Firebase Server Key', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[push_notification_settings][firebase_server_key]" type="text" value="<?php echo !empty($settings['push_notification_settings']['firebase_server_key']) ? $settings['push_notification_settings']['firebase_server_key'] : ''; ?>" />
                                <p><?php _e('Enter Server Key of Firebase.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <h4><?php echo _e('Order Success Notification', 'woocommerce-mobile-app-builder'); ?></h4>
                            <div class="form-field form-required">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[push_notification_settings][order_success_enabled]" type="checkbox" value="1" <?php echo !empty($settings['push_notification_settings']['order_success_enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable order success push notification functionality.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Title', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][order_success_notification_title]" type="text" value="<?php echo !empty($settings['push_notification_settings']['order_success_notification_title']) ? $settings['push_notification_settings']['order_success_notification_title'] : ''; ?>" />
                                <p><?php _e('Enter Title for Order Success Notification.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Message', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][order_success_notification_msg]" type="text" value="<?php echo !empty($settings['push_notification_settings']['order_success_notification_msg']) ? $settings['push_notification_settings']['order_success_notification_msg'] : ''; ?>" />
                                <p><?php _e('Enter message for Order Success Notification.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <h4><?php echo _e('Order Status Update Notification', 'woocommerce-mobile-app-builder'); ?></h4>
                            <div class="form-field form-required">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[push_notification_settings][order_status_enabled]" type="checkbox" value="1" <?php echo !empty($settings['push_notification_settings']['order_status_enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable Order status change push notification functionality.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Title', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][order_status_notification_title]" type="text" value="<?php echo !empty($settings['push_notification_settings']['order_status_notification_title']) ? $settings['push_notification_settings']['order_status_notification_title'] : ''; ?>" />
                                <p><?php _e('Enter title for Order status change Notification. You can use {{STATUS}} placeholder to use status value.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Message', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][order_status_notification_msg]" type="text" value="<?php echo !empty($settings['push_notification_settings']['order_status_notification_msg']) ? $settings['push_notification_settings']['order_status_notification_msg'] : ''; ?>" />
                                <p><?php _e('Enter message for Status Change Notification. You can use {{STATUS}} placeholder to use status value.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <h4><?php echo _e('Abandoned Cart Notification', 'woocommerce-mobile-app-builder'); ?></h4>
                            <div class="form-field form-required">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[push_notification_settings][abandoned_cart_enabled]" type="checkbox" value="1" <?php echo !empty($settings['push_notification_settings']['abandoned_cart_enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable abandoned cart push notification functionality.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Title', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][abandoned_cart_notification_title]" type="text" value="<?php echo !empty($settings['push_notification_settings']['abandoned_cart_notification_title']) ? $settings['push_notification_settings']['abandoned_cart_notification_title'] : ''; ?>" />
                                <p><?php _e('Enter title for Abandoned Cart Notification.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Notification Message', 'woocommerce-mobile-app-builder'); ?></label>
                                <input name="vss_mab[push_notification_settings][abandoned_cart_notification_msg]" type="text" value="<?php echo !empty($settings['push_notification_settings']['abandoned_cart_notification_msg']) ? $settings['push_notification_settings']['abandoned_cart_notification_msg'] : ''; ?>" />
                                <p><?php _e('Enter message for Abandoend Cart Notification.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Time interval to check Abandoned Cart', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[push_notification_settings][abandoned_cart_time_interval]" type="text" value="<?php echo !empty($settings['push_notification_settings']['abandoned_cart_time_interval']) ? $settings['push_notification_settings']['abandoned_cart_time_interval'] : '1'; ?>" />
                                <p><?php _e('In Hours', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <div class="well"><b><?php _e('Cron Instructions', 'woocommerce-mobile-app-builder'); ?></b><br>
                                <?php _e('Add the cron to your store via control panel/putty to send the push notifications to your Mobile App users for Abandoned Carts.', 'woocommerce-mobile-app-builder'); ?>
                                <br>
                                <b><?php _e('URL to Add to Cron via Control Panel', 'woocommerce-mobile-app-builder'); ?></b><br>
                                <?php echo site_url() . '/wp-json/wmab/v1.1/appSendAbandonedCartPushNotification/'; ?>
                                <br>
                                <b><?php _e('Cron setup via SSH', 'woocommerce-mobile-app-builder'); ?></b><br>
                                0/30 * * * * wget -O /dev/null <?php echo site_url() . '/wp-json/wmab/v1.1/appSendAbandonedCartPushNotification/'; ?>
                            </div>
                        </div>
                        
                        <!--Push Notification History Section-->
                        <div id="push_notification_history" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: block;"<?php } ?>>
                            <h2><?php echo _e('Push Notification History', 'woocommerce-mobile-app-builder'); ?></h2>
                            
                            <p class="submit mab-right">
                                <input type="button" name="send_notification" id="send_notification" class="button" value="<?php echo _e('Send Notification', 'woocommerce-mobile-app-builder'); ?>">
                                <input type="button" name="send_notification_close" id="send_notification_close" class="button" value="<?php echo _e('Close', 'woocommerce-mobile-app-builder'); ?>">
                            </p>
                            
                            <!--Send Notification Fields-->
                            <div id="send_notification_panel">
                                <h2><?php echo _e('Send Push Notification', 'woocommerce-mobile-app-builder'); ?></h2>
                                <div class="form-field form-required">
                                    <label><?php _e('Title', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <input name="notification_title" type="text" />
                                </div>
                                <div class="form-field form-required">
                                    <label><?php _e('Message', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <textarea name="notification_msg"></textarea>
                                </div>
                                <div class="form-field form-required">
                                    <label><?php _e('Image', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <input name="notification_image" type="file" />
                                </div>
                                <div class="form-field form-required">
                                    <label><?php _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <select name="notification_redirect_type" onchange="wmab_set_redirect_type(this.value)">
                                        <option value="home"><?php _e('Home', 'woocommerce-mobile-app-builder'); ?></option>
                                        <option value="category"><?php _e('Category', 'woocommerce-mobile-app-builder'); ?></option>
                                        <option value="product"><?php _e('Product', 'woocommerce-mobile-app-builder'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field form-required" id="notification_category_list" style="display: none;">
                                    <label><?php _e('Category', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <select name="notification_category">
                                        <?php
                                        if (isset($product_categories) && !empty($product_categories)) {
                                            foreach ($product_categories as $product_category) {
                                                echo '<option value="' . $product_category->term_id . '">' . $product_category->name . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-field form-required" id="notification_product_list" style="display: none;">
                                    <label><?php _e('Product', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                    <select name="notification_product">
                                        <?php
                                        if (isset($products) && !empty($products)) {
                                            foreach ($products as $product) {
                                                echo '<option value="' . $product->id . '">' . $product->name . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-field" style="text-align: center;">
                                    <input class="button button-primary" type="submit" name="send_notification_submit" id="send_notification_submit" value="<?php _e('Send Notification', 'woocommerce-mobile-app-builder'); ?>" />
                                </div>
                            </div>
                            <!--Ends-->
                            
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr>
                                        <th class="mab-left"><?php echo _e('Notification ID', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Title', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-center"><?php echo _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-center"><?php echo _e('Sent On', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Details', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($push_notification_history) && !empty($push_notification_history)) {
                                        $notification_counter = 1;
                                        foreach ($push_notification_history as $push_notification_history) {
                                            $class = '';
                                            if ($notification_counter%2 > 0) {
                                                $class = 'mab-table-odd';
                                            }
                                            //Get Category
                                            $category_name = '';
                                            if (isset($push_notification_history->category_id) && !empty($push_notification_history->category_id)) {
                                                $category_name = get_term_by( 'id', $push_notification_history->category_id, 'product_cat' );
                                            }
                                            
                                            //Get Product
                                            $product_name = '';
                                            if (isset($push_notification_history->product_id) && !empty($push_notification_history->product_id)) {
                                                $product = wc_get_product( $push_notification_history->product_id );
                                                $product_name = $product->get_name();
                                            }
                                            ?>
                                    <tr class="<?php echo $class; ?>">
                                        <td class="mab-left"><?php echo $push_notification_history->notification_id; ?></td>
                                        <td class="mab-left"><?php echo $push_notification_history->title; ?></td>
                                        <td class="mab-center"><?php echo ucwords($push_notification_history->redirect_activity); ?></td>
                                        <td class="mab-left"><?php echo !empty($category_name->name) ? $category_name->name : ''; ?></td>
                                        <td class="mab-left"><?php echo !empty($product_name) ? $product_name : ''; ?></td>
                                        <td class="mab-center"><?php echo date("d-M-Y", strtotime($push_notification_history->date_added)); ?></td>
                                        <td class="mab-left"><a href="javascript://" id="link_<?php echo $push_notification_history->notification_id; ?>" onclick="wmab_show_push_notification_details('<?php echo $push_notification_history->notification_id; ?>')"><?php _e('View Details', 'woocommerce-mobile-app-builder'); ?></a></td>
                                    </tr>
                                    <tr class="<?php echo $class; ?>" id="wmab_push_notification_details_<?php echo $push_notification_history->notification_id; ?>" style="display: none;">
                                        <td colspan="7">
                                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                                <tr>
                                                    <th class="mab-center"><?php echo _e('Image', 'woocommerce-mobile-app-builder'); ?></th>
                                                    <th class="mab-center"><?php echo _e('Message', 'woocommerce-mobile-app-builder'); ?></th>
                                                </tr>
                                                <tr>
                                                    <td class="mab-center">
														<?php
														$push_image = wp_get_image_editor( plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/push_notifications/' . $push_notification_history->image_url );
														$push_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/push_notifications/' . $push_notification_history->image_url;
														if ( ! is_wp_error( $push_image ) ) {
															$push_image->resize( 100, 100, false );
															$push_image->save( WP_PLUGIN_DIR  . '/' . plugin_basename(dirname(__FILE__)) . '/images/push_notifications/100X100-' . $push_notification_history->image_url );
															$push_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/push_notifications/100X100-' . $push_notification_history->image_url;
														}
														?>
                                                        <img src="<?php echo $push_image_display;?>" width="100" height="100" />
                                                    </td>
                                                    <td class="mab-center">
                                                        <?php echo $push_notification_history->message; ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                            <?php
                                            $notification_counter++;
                                        }
                                    } else {
                                    ?>
                                    <tr class="mab-table-odd">
                                        <td colspan="7" class="mab-center"><?php echo _e('No History Found.', 'woocommerce-mobile-app-builder'); ?></td>
                                    </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <?php
                            if($totalPage > 1){
                                $customPageHTML =  '<div class="wmab_pagination"><div class="wmab_pagination_left">Page '.$page.' of '.$totalPage.'</div><div class="wmab_pagination_right">'.paginate_links( array(
                                    'base' => add_query_arg( array( 'cpage' => '%#%', 'wmab_tab' => 'history' ) ),
                                    'format' => '',
                                    'prev_text' => __('&laquo;'),
                                    'next_text' => __('&raquo;'),
                                    'total' => $totalPage,
                                    'current' => $page
                                )).'</div></div><div class="wmab_clear"></div>';
                            }
                            echo $customPageHTML;
                            ?>
                        </div>
                        
                        <!--Slideshow Settings Section-->
                        <input type="hidden" name="sort_order_duplicate_error" id="sort_order_duplicate_error" value="<?php _e('Sort order cannot be same.', 'woocommerce-mobile-app-builder'); ?>" />
                        <input type="hidden" name="vss_mab[slideshow_id]" value="<?php echo !empty($settings['slideshow_id']) ? $settings['slideshow_id'] : ''; ?>" />
                        <div id="slideshow_settings" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Slideshow Settings', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field form-required">
                                <label><?php _e('Slideshow Name', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[slideshow_settings][slideshow_name]" type="text" value="<?php echo !empty($slideshow_details->name) ? $slideshow_details->name : ''; ?>" />
                            </div>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[slideshow_settings][enabled]" type="checkbox" value="1" <?php echo !empty($slideshow_details->status) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[slideshow_settings][limit]" type="text" value="<?php echo !empty($slideshow_details->banner_limit) ? $slideshow_details->banner_limit : ''; ?>" />
                                <p><?php _e('Maximum limit is 5.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[slideshow_settings][image_width]" type="text" value="<?php echo !empty($slideshow_details->image_width) ? $slideshow_details->image_width : ''; ?>" />
                                <p><?php _e('Value must be between 600 and 999. Width and Height should be in ratio 3:1 for better results.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[slideshow_settings][image_height]" type="text" value="<?php echo !empty($slideshow_details->image_height) ? $slideshow_details->image_height : ''; ?>" />
                                <p><?php _e('Value must be between 200 and 999. Width and Height should be in ratio 3:1 for better results.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _e('Slideshow Title', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Link Type', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Link To', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Slideshow Image', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Sort Order', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($slide_details) && !empty($slide_details)) {
                                        foreach ($slide_details as $slide_detail) {
                                            ?>
                                    <tr class="mab-table-odd mab-table-bottom-border" id="slide_<?php echo $slide_detail->banner_image_id; ?>">
                                        <td>
                                            <input type="hidden" name="slide_hidden_image[]" value="<?php echo $slide_detail->banner_image_id; ?>" />
                                            <input type="text" name="slide_name[]" value="<?php echo $slide_detail->banner_title; ?>" />
                                        </td>
                                        <td>
                                            <select name="slide_link_type[]">
                                                <option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option>
                                                <option value="1" <?php if ($slide_detail->link_type == '1') { echo 'selected'; } ?>><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option>
                                                <option value="2" <?php if ($slide_detail->link_type == '2') { echo 'selected'; } ?>><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="slide_link_to[]">
                                                <option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option>
                                                <?php
                                                if ($slide_detail->link_type == '1') {
                                                    if (isset($product_categories) && !empty($product_categories)) {
                                                        foreach ($product_categories as $product_category) {
                                                            if ($slide_detail->link_to == $product_category->term_id) {
                                                                echo '<option selected value="' . $product_category->term_id . '">' . $product_category->name . '</option>';
                                                            } else {
                                                                echo '<option value="' . $product_category->term_id . '">' . $product_category->name . '</option>';
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    if (isset($products) && !empty($products)) {
                                                        foreach ($products as $product) {
                                                            if ($slide_detail->link_to == $product->id) {
                                                                echo '<option selected value="' . $product->id . '">' . $product->name . '</option>';
                                                            } else {
                                                                echo '<option value="' . $product->id . '">' . $product->name . '</option>';
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td class="mab-center">
                                            <?php
											$banner_image = wp_get_image_editor( plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/' . $slide_detail->image );
											$banner_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/' . $slide_detail->image;
											if ( ! is_wp_error( $banner_image ) ) {
												$banner_image->resize( 100, 100, false );
												$banner_image->save( WP_PLUGIN_DIR  . '/' . plugin_basename(dirname(__FILE__)) . '/images/banners/100X100-' . $slide_detail->image );
												$banner_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/100X100-' . $slide_detail->image;
											}
											?>
                                            <img src="<?php echo $banner_image_display; ?>" width="100" height="100" />
                                        </td>
                                        <td>
                                            <input type="text" name="slide_sort_order[]" value="<?php echo $slide_detail->sort_order; ?>" />
                                        </td>
                                        <td>
                                            <input type="button" name="remove_slideshow[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_delete_banner('<?php echo $slide_detail->banner_image_id; ?>', 'slide_<?php echo $slide_detail->banner_image_id; ?>')" />
                                        </td>
                                    </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    
                                    <tr id="slideshow_last_row">
                                        <td colspan="5"></td>
                                        <td>
                                            <input type="button" name="add_slideshow" id="add_slideshow" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        
                        <!--BOC neeraj.kumar@velsof.com 20-Dec-2019 added form layout page.-->                        
                        <!--Home Layout Section-->
                        <div id="home_layout_tab" class="wmab-panel <?php if (isset($_GET['wmab_home_layout_success']) && $_GET['wmab_home_layout_success']) { echo 'active';}?>" <?php if (isset($_GET['wmab_tab']) || !isset($_GET['wmab_home_layout_success'])) { ?>style="display: none;"<?php } ?>>
                            <h2 class="bottom-line"><?php echo _e('Home Page Layout', 'woocommerce-mobile-app-builder'); ?>
                                <input type="button" name="add_layout_options" id="add_layout_options" style="float:right;" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                            </h2>                                                        
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr class="thead-heading">
                                        <th><?php echo _e('S.No', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Layout ID', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Layouts', 'woocommerce-mobile-app-builder'); ?></th>                                        
                                        <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($home_layout_details) && !empty($home_layout_details)) {
                                        $count = 0;
                                        $last_layout_id = 0;
                                        foreach ($home_layout_details as $home_layout) { $count++; $last_layout_id = $home_layout->id_layout;
                                            ?>
                                    <tr class="mab-table-odd mab-table-bottom-border" id="home_layout_<?php echo $home_layout->id_layout; ?>">
                                        <td><?php echo $count; ?></td> 
                                        <td>                                            
                                            <input type="text" name="layout_id_<?php echo $home_layout->id_layout; ?>" id="layout_id_<?php echo $home_layout->id_layout; ?>" readonly="true" value="<?php echo $home_layout->id_layout; ?>" />
                                        </td>
                                        <td>
                                            <input type="hidden" id="<?php echo $home_layout->id_layout; ?>" name="<?php echo $home_layout->id_layout; ?>" value="<?php echo $home_layout->id_layout; ?>"/>
                                            <input type="text" id="layout_name_<?php echo $home_layout->id_layout; ?>" name="layout_name_<?php echo $home_layout->id_layout; ?>" value="<?php echo $home_layout->layout_name; ?>" />
                                        </td>                                                                                
                                        <td>
                                            <input class="form_layout_action_btn" type="button" name="save_layout" style="width: 30% !important;" value="<?php echo _e('Save', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_change_layout_details('<?php if(isset($home_layout->id_layout)){echo $home_layout->id_layout;}else{echo '';} ?>', <?php if(isset($home_layout->layout_name)){echo $banner_detail->layout_name;}else{echo '';} ?>)" />
                                            <input class="form_layout_action_btn" type="button" name="edit_layout" style="width: 30% !important;" value="<?php echo _e('Edit', 'woocommerce-mobile-app-builder'); ?>" onclick="window.location.href = '<?php echo admin_url() ?>admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $home_layout->id_layout; ?>';" />
                                            <input class="form_layout_action_btn" type="button" name="save_layout" style="width: 30% !important;" value="<?php echo _e('Delete', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_delete_layout('<?php if(isset($home_layout->id_layout)){echo $home_layout->id_layout;}else{echo '';} ?>')" />
                                        </td>
                                    </tr>
                                            <?php
                                        }
                                    }else{
                                    ?>                                    
                                    <tr id="banner_last_row">
                                        <td colspan="4">No Layout Found</td>                                        
                                    </tr>
                                    <?php } ?>
                                    <tr id="tr_last_row" style="display:none;"></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!--EOC -->
                        
                        <!--BOC neeraj.kumar@velsof.com 20-Dec-2019 added form tab bar page.-->                        
                        <!--Tab Bar Layout Section-->
                        <div id="tab-bar-layout-tab" class="wmab-panel" <?php if(isset($_GET['wmab_tab_bar_success']) || isset($_GET['wmab_tab_bar_error'])){ ?>style="display: block;"<?php }else{ ?>style="display: none;"<?php }  ?> >
                            <!--Allow only 5 tab bar to add-->
                            <?php
                                    $show_tab_bar = true;
                                    if (isset($tab_bar_layout_details) && !empty($tab_bar_layout_details) && count($tab_bar_layout_details) >= 5) {
                                        $show_tab_bar = false;
                                    }
                            ?>
                            
                            <h2 class="bottom-line"><?php echo _e('Tab Bar Layout', 'woocommerce-mobile-app-builder'); ?>
                                <?php if($show_tab_bar){ ?>
                                <input type="button" name="add_tab_bar_options" id="add_tab_bar_options" style="float:right;" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" onclick="tab_bar_setting(true)" />
                                <?php } ?>
                            </h2>                     
                            
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr class="thead-heading">
                                        <th><?php echo _e('S.No', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Icon ID', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Tab Icon Text', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody >
                                    <?php
                                    if (isset($tab_bar_layout_details) && !empty($tab_bar_layout_details)) {
                                        $count = 0;
                                        $last_tab_bar_id = 0;
                                        foreach ($tab_bar_layout_details as $tab_bar_layout) { $count++; $last_tab_bar_id = $tab_bar_layout->tab_icon_id;
                                            ?>
                                    <tr class="mab-table-odd mab-table-bottom-border text-align-center" id="tab_bar_layout_<?php echo $tab_bar_layout->tab_icon_id; ?>">
                                        <td><?php echo $count; ?></td> 
                                        <td>                                            
                                            <?php echo $tab_bar_layout->tab_icon_id; ?>
                                        </td>
                                        <td>
                                            <?php echo $tab_bar_layout->tab_icon_text; ?>                                            
                                        </td>  
                                        <td>
                                            <?php echo ucfirst(str_replace("_", " ", $tab_bar_layout->tab_icon_redirect_activity)); ?>
                                        </td>
                                        <td>                                            
                                            <input class="form_layout_action_btn" type="button" name="edit_tab_bar" style="width: 40% !important;" value="<?php echo _e('Edit', 'woocommerce-mobile-app-builder'); ?>" onclick="tab_bar_setting(false,<?php echo $tab_bar_layout->tab_icon_id; ?>)" />
                                            <input class="form_layout_action_btn" type="button" name="delete_tab_bar" style="width: 40% !important;" value="<?php echo _e('Delete', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_delete_tab_bar('<?php if(isset($tab_bar_layout->tab_icon_id)){echo $tab_bar_layout->tab_icon_id;}else{echo '';} ?>')" />
                                        </td>
                                    </tr>
                                            <?php
                                        }
                                    }else{
                                    ?>                                    
                                    <tr id="tab_bar_last_row">
                                        <td colspan="5">No tab bar layout found</td>                                        
                                    </tr>
                                    <?php } ?>                                    
                                </tbody>
                            </table>
                            <div class="custom-card-view">
                            <!--Tab Bar - General Settings-->                            
                                <div class="panel" id="fieldset_0_6">
                                    <div class="panel-heading">
                                        Tab Bar Settings
                                    </div>
                                    <div class="form-wrapper">
                                        <div class="form-field form-required">
                                            <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                            <label class="switch">
                                                <input name="vss_mab[tab_bar_settings][tab_bar_status]" type="checkbox" value="1" <?php echo !empty($settings['tab_bar_settings']['tab_bar_status']) ? 'checked' : ''; ?> />
                                                <span class="slider"></span>
                                            </label>
                                            <p><?php _e('This setting will enable/disable the tab bar in mobile app.', 'woocommerce-mobile-app-builder'); ?></p>
                                        </div>
                                        <div class="form-field form-required">
                                            <label><?php _e('Tab Bar Background Color', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                            <div class="input-group color-picker-width" id="tab_bar_background_color">
                                               <span style="cursor:pointer;" id="icp_color_4" class="mColorPickerTrigger input-group-addon" data-mcolorpicker="true"><img src="<?php echo plugins_url('/', __FILE__) .'images/color.png'; ?>" style="border:0;margin:0 0 0 3px" align="absmiddle"></span>
                                                <input type="text" data-hex="true" name="vss_mab[tab_bar_settings][tab_bar_background_color]" id="color_4" value="<?php echo !empty($settings['tab_bar_settings']['tab_bar_background_color']) ? $settings['tab_bar_settings']['tab_bar_background_color'] : '#d6ff80'; ?>" class="form-control"  class="mColorPicker form-control" readonly <?php if(!empty($settings['tab_bar_settings']['tab_bar_background_color'])){ ?>style="background-color: <?php echo $settings['tab_bar_settings']['tab_bar_background_color']; ?>" <?php }else{ ?> style="background-color:#d6ff80;color:black;"<?php } ?>/> 
                                            </div>
                                        </div>                                        
                                        <div class="form-field form-required">
                                            <label><?php _e('Tab Bar Tint Color', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                            <div class="input-group color-picker-width" id="tab_bar_tint_background_color">
                                               <span style="cursor:pointer;" id="icp_color_5" class="mColorPickerTrigger input-group-addon" data-mcolorpicker="true"><img src="<?php echo plugins_url('/', __FILE__) .'images/color.png'; ?>" style="border:0;margin:0 0 0 3px" align="absmiddle"></span>
                                                <input type="text" data-hex="true" name="vss_mab[tab_bar_settings][tab_bar_tint_color]" id="color_5" value="<?php echo !empty($settings['tab_bar_settings']['tab_bar_tint_color']) ? $settings['tab_bar_settings']['tab_bar_tint_color'] : '#ffebeb'; ?>" class="form-control"  class="mColorPicker form-control" readonly <?php if(!empty($settings['tab_bar_settings']['tab_bar_tint_color'])){ ?>style="background-color: <?php echo $settings['tab_bar_settings']['tab_bar_tint_color']; ?>" <?php }else{ ?> style="background-color:#ffebeb;color:black;"<?php } ?>/> 
                                            </div>
                                        </div>
                                        <div class="form-field form-required">
                                            <label><?php _e('Disabled Icon Color', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                            <div class="input-group color-picker-width" id="disable_icon_background_color">
                                               <span style="cursor:pointer;" id="icp_color_6" class="mColorPickerTrigger input-group-addon" data-mcolorpicker="true"><img src="<?php echo plugins_url('/', __FILE__) .'images/color.png'; ?>" style="border:0;margin:0 0 0 3px" align="absmiddle"></span>
                                                <input type="text" data-hex="true" name="vss_mab[tab_bar_settings][tab_bar_disable_icon_color]" id="color_6" value="<?php echo !empty($settings['tab_bar_settings']['tab_bar_disable_icon_color']) ? $settings['tab_bar_settings']['tab_bar_disable_icon_color'] : '#ffffff'; ?>" class="form-control"  class="mColorPicker form-control" readonly <?php if(!empty($settings['tab_bar_settings']['tab_bar_disable_icon_color'])){ ?>style="background-color: <?php echo $settings['tab_bar_settings']['tab_bar_disable_icon_color']; ?>" <?php }else{ ?> style="background-color:white;color:black;"<?php } ?>/> 
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>   
                            </div>
                        </div>
                        
                        <!--EOC -->
                        
                        <!--Banners Settings Section-->
                        <input type="hidden" name="vss_mab[banner_id]" value="<?php echo !empty($settings['banner_id']) ? $settings['banner_id'] : ''; ?>" />
                        <div id="banners_settings" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Banners Settings', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field form-required">
                                <label><?php _e('Banner Name', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[banners_settings][banner_name]" type="text" value="<?php echo !empty($banner_details->name) ? $banner_details->name : ''; ?>" />
                            </div>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[banners_settings][enabled]" type="checkbox" value="1" <?php echo !empty($banner_details->status) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[banners_settings][limit]" type="text" value="<?php echo !empty($banner_details->banner_limit) ? $banner_details->banner_limit : ''; ?>" />
                                <p><?php _e('Maximum limit is 5.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[banners_settings][image_width]" type="text" value="<?php echo !empty($banner_details->image_width) ? $banner_details->image_width : ''; ?>" />
                                <p><?php _e('Value must be between 600 and 999. Width and Height should be in ratio 3:1 for better results.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[banners_settings][image_height]" type="text" value="<?php echo !empty($banner_details->image_height) ? $banner_details->image_height : ''; ?>" />
                                <p><?php _e('Value must be between 200 and 999. Width and Height should be in ratio 3:1 for better results.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _e('Banner Title', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Link Type', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Link To', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Banner Image', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Sort Order', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($banner_image_details) && !empty($banner_image_details)) {
                                        foreach ($banner_image_details as $banner_detail) {
                                            ?>
                                    <tr class="mab-table-odd mab-table-bottom-border" id="banner_<?php echo $banner_detail->banner_image_id; ?>">
                                        <td>
                                            <input type="hidden" name="banner_hidden_image[]" value="<?php echo $banner_detail->banner_image_id; ?>" />
                                            <input type="text" name="banner_name[]" value="<?php echo $banner_detail->banner_title; ?>" />
                                        </td>
                                        <td>
                                            <select name="banner_link_type[]">
                                                <option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option>
                                                <option value="1" <?php if ($banner_detail->link_type == '1') { echo 'selected'; } ?>><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option>
                                                <option value="2" <?php if ($banner_detail->link_type == '2') { echo 'selected'; } ?>><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="banner_link_to[]">
                                                <option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option>
                                                <?php
                                                if ($banner_detail->link_type == '1') {
                                                    if (isset($product_categories) && !empty($product_categories)) {
                                                        foreach ($product_categories as $product_category) {
                                                            if ($banner_detail->link_to == $product_category->term_id) {
                                                                echo '<option selected value="' . $product_category->term_id . '">' . $product_category->name . '</option>';
                                                            } else {
                                                                echo '<option value="' . $product_category->term_id . '">' . $product_category->name . '</option>';
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    if (isset($products) && !empty($products)) {
                                                        foreach ($products as $product) {
                                                            if ($banner_detail->link_to == $product->id) {
                                                                echo '<option selected value="' . $product->id . '">' . $product->name . '</option>';
                                                            } else {
                                                                echo '<option value="' . $product->id . '">' . $product->name . '</option>';
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td class="mab-center">
                                            <?php
											$banner_image = wp_get_image_editor( plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/' . $banner_detail->image );
											$banner_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/' . $banner_detail->image;
											if ( ! is_wp_error( $banner_image ) ) {
												$banner_image->resize( 100, 100, false );
												$banner_image->save( WP_PLUGIN_DIR  . '/' . plugin_basename(dirname(__FILE__)) . '/images/banners/100X100-' . $banner_detail->image );
												$banner_image_display = plugin_dir_url('/', __FILE__) . plugin_basename(dirname(__FILE__)) . '/images/banners/100X100-' . $banner_detail->image;
											}
											?>
                                            <img src="<?php echo $banner_image_display; ?>" width="100" height="100" />
                                        </td>
                                        <td>
                                            <input type="text" name="banner_sort_order[]" value="<?php echo $banner_detail->sort_order; ?>" />
                                        </td>
                                        <td>
                                            <input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_delete_banner('<?php echo $banner_detail->banner_image_id; ?>', 'banner_<?php echo $banner_detail->banner_image_id; ?>')" />
                                        </td>
                                    </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    
                                    <tr id="banner_last_row">
                                        <td colspan="5"></td>
                                        <td>
                                            <input type="button" name="add_banner" id="add_banner" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!--Payment Method Section-->
                        <div id="payment_methods" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Payment Method', 'woocommerce-mobile-app-builder'); ?></h2>
                            
                            <h4><?php echo _e('PayPal', 'woocommerce-mobile-app-builder'); ?></h4>
                            <div class="form-field form-required">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <label class="switch">
                                    <input name="vss_mab[payment_methods][paypal_enabled]" type="checkbox" value="1" <?php echo !empty($settings['payment_methods']['paypal_enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable paypal on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Payment Method Name', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[payment_methods][payment_method_name]" type="text" value="<?php echo !empty($settings['payment_methods']['payment_method_name']) ? $settings['payment_methods']['payment_method_name'] : ''; ?>" />
                                <p><?php _e('Enter Payment Method Name to be displayed on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <!--<div class="form-field form-required">
                                <label><?php _e('Payment Method Code', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[payment_methods][payment_method_code]" type="text" value="<?php echo !empty($settings['payment_methods']['payment_method_code']) ? $settings['payment_methods']['payment_method_code'] : ''; ?>" />
                                <p><?php _e('Enter Payment Method Code to be displayed on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>-->
			    <input name="vss_mab[payment_methods][payment_method_code]" type="hidden" value="paypal" />
                            <div class="form-field form-required">
                                <label><?php _e('Select Mode', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <select name="vss_mab[payment_methods][payment_method_mode]">
                                    <option value="1" <?php if (isset($settings['payment_methods']['payment_method_mode']) && $settings['payment_methods']['payment_method_mode'] == "1") { ?> selected="selected" <?php } ?>><?php echo _e("Live", 'woocommerce-mobile-app-builder'); ?></option>
                                    <option value="0" <?php if (isset($settings['payment_methods']['payment_method_mode']) && $settings['payment_methods']['payment_method_mode'] == "0") { ?> selected="selected" <?php } ?>><?php echo _e("Sandbox", 'woocommerce-mobile-app-builder'); ?></option>
                                </select>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Client ID', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[payment_methods][client_id]" type="text" value="<?php echo !empty($settings['payment_methods']['client_id']) ? $settings['payment_methods']['client_id'] : ''; ?>" />
                            </div>
                            
                            <h4><?php echo _e('Cash On Delivery', 'woocommerce-mobile-app-builder'); ?></h4>
                            <div class="form-field form-required">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <label class="switch">
                                    <input name="vss_mab[payment_methods][cod_enabled]" type="checkbox" value="1" <?php echo !empty($settings['payment_methods']['cod_enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                                <p><?php _e('This setting will enable/disable Cash On Delivery on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Payment Method Name', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[payment_methods][cod_payment_method_name]" type="text" value="<?php echo !empty($settings['payment_methods']['cod_payment_method_name']) ? $settings['payment_methods']['cod_payment_method_name'] : ''; ?>" />
                                <p><?php _e('Enter Payment Method Name to be displayed on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <!--<div class="form-field form-required">
                                <label><?php _e('Payment Method Code', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[payment_methods][cod_payment_method_code]" type="text" value="<?php echo !empty($settings['payment_methods']['cod_payment_method_code']) ? $settings['payment_methods']['cod_payment_method_code'] : ''; ?>" />
                                <p><?php _e('Enter Payment Method Code to be displayed on App.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>-->
			    <input name="vss_mab[payment_methods][cod_payment_method_code]" type="hidden" value="cod" />
                        </div>
                        
                        <!--Featured Section-->
                        <div id="featured" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Featured', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[featured][enabled]" type="checkbox" value="1" <?php echo !empty($settings['featured']['enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[featured][limit]" type="text" value="<?php echo !empty($settings['featured']['limit']) ? $settings['featured']['limit'] : ''; ?>" />
                                <p><?php _e('Maximum limit is 40.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[featured][product_image_width]" type="text" value="<?php echo !empty($settings['featured']['product_image_width']) ? $settings['featured']['product_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[featured][product_image_height]" type="text" value="<?php echo !empty($settings['featured']['product_image_height']) ? $settings['featured']['product_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!--Specials Section-->
                        <div id="specials" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Specials', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[specials][enabled]" type="checkbox" value="1" <?php echo !empty($settings['specials']['enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[specials][limit]" type="text" value="<?php echo !empty($settings['specials']['limit']) ? $settings['specials']['limit'] : ''; ?>" />
                                <p><?php _e('Maximum limit is 40.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[specials][product_image_width]" type="text" value="<?php echo !empty($settings['specials']['product_image_width']) ? $settings['specials']['product_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[specials][product_image_height]" type="text" value="<?php echo !empty($settings['specials']['product_image_height']) ? $settings['specials']['product_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!--Best Sellers Section-->
                        <div id="bestsellers" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Best Sellers', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[bestsellers][enabled]" type="checkbox" value="1" <?php echo !empty($settings['bestsellers']['enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[bestsellers][limit]" type="text" value="<?php echo !empty($settings['bestsellers']['limit']) ? $settings['bestsellers']['limit'] : ''; ?>" />
                                <p><?php _e('Maximum limit is 40.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[bestsellers][product_image_width]" type="text" value="<?php echo !empty($settings['bestsellers']['product_image_width']) ? $settings['bestsellers']['product_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[bestsellers][product_image_height]" type="text" value="<?php echo !empty($settings['bestsellers']['product_image_height']) ? $settings['bestsellers']['product_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!--Latest Section-->
                        <div id="latest" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Latest', 'woocommerce-mobile-app-builder'); ?></h2>
                            <div class="form-field">
                                <label><?php _e('Status', 'woocommerce-mobile-app-builder'); ?></label>
                                <label class="switch">
                                    <input name="vss_mab[latest][enabled]" type="checkbox" value="1" <?php echo !empty($settings['latest']['enabled']) ? 'checked' : ''; ?> />
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Limit', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[latest][limit]" type="text" value="<?php echo !empty($settings['latest']['limit']) ? $settings['latest']['limit'] : ''; ?>" />
                                <p><?php _e('Maximum limit is 40.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Width', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[latest][product_image_width]" type="text" value="<?php echo !empty($settings['latest']['product_image_width']) ? $settings['latest']['product_image_width'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                            <div class="form-field form-required">
                                <label><?php _e('Product Image Height', 'woocommerce-mobile-app-builder'); ?> <span class="mab_error">*</span></label>
                                <input name="vss_mab[latest][product_image_height]" type="text" value="<?php echo !empty($settings['latest']['product_image_height']) ? $settings['latest']['product_image_height'] : ''; ?>" />
                                <p><?php _e('Value must be between 1 and 999.', 'woocommerce-mobile-app-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!--Information Pages Section-->
                        <div id="information_pages" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Information Pages', 'woocommerce-mobile-app-builder'); ?></h2>
                            
                            <p class="submit mab-right">
                                <input type="button" name="add_new_page" id="add_new_page" class="button" value="<?php echo _e('Add New Page', 'woocommerce-mobile-app-builder'); ?>" onclick="window.location.href='post-new.php?post_type=page'">
                            </p>
                            
                            <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                                <thead>
                                    <tr>
                                        <th class="mab-left"><?php echo _e('Page Title', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Link To Information Page', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Status', 'woocommerce-mobile-app-builder'); ?></th>
                                        <th class="mab-left"><?php echo _e('Sort Order', 'woocommerce-mobile-app-builder'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pages_list = $pages;
                                    if (isset($pages) && !empty($pages)) {
                                        $page_counter = 1;
                                        foreach ($pages as $page) {
                                            $list_class = '';
                                            if ($page_counter%2 > 0) {
                                                $list_class = 'mab-table-odd';
                                            }
                                            ?>
                                    <tr class="<?php echo $list_class; ?>">
                                        <td class="mab-left"><input type="text" name="page_title[<?php echo $page->ID; ?>]" value="<?php echo isset($information_page_list[$page->ID]->page_title) ? $information_page_list[$page->ID]->page_title : $page->post_title; ?>"/></td>
                                        <td class="mab-left">
                                            <select name="information_page[<?php echo $page->ID; ?>]">
                                                <?php
                                                foreach ($pages_list as $list) {
                                                    ?>
                                                <option value="<?php echo $list->ID; ?>" <?php if (isset($information_page_list[$page->ID]->link_to) && $information_page_list[$page->ID]->link_to == $list->ID) { echo "selected"; } else if ($page->ID == $list->ID) { echo "selected"; } ?>><?php echo $list->post_title; ?></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td class="mab-left">
                                            <label class="switch">
                                                <input name="page_status[<?php echo $page->ID; ?>]" type="checkbox" value="1" <?php echo !empty($information_page_list[$page->ID]->status) ? 'checked' : ''; ?> />
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td class="mab-left"><input type="text" name="page_sort_order[<?php echo $page->ID; ?>]" value="<?php if (isset($information_page_list[$page->ID]->sort_order)) { echo $information_page_list[$page->ID]->sort_order; } else { echo '0'; } ?>" /></td>
                                    </tr>
                                            <?php
                                            $page_counter++;
                                        }
                                    } else {
                                    ?>
                                    <tr class="mab-table-odd">
                                        <td colspan="4" class="mab-center"><?php echo _e('No Record Found.', 'woocommerce-mobile-app-builder'); ?></td>
                                    </tr>
				    <?php
				    }
				    ?>
                                </tbody>
                            </table>
                            
                        </div>
                        
                        <!-- contact us page section -->
                        <div id="contact_us" class="wmab-panel" <?php if (isset($_GET['wmab_tab'])) { ?>style="display: none;"<?php } ?>>
                            <h2><?php echo _e('Contact Us', 'woocommerce-mobile-app-builder'); ?></h2>
                            <p>Click <a href="https://www.knowband.com/blog/user-manual/woocommerce-mobile-app-builder/">here</a> for User Manual. In case of any issue, <a href="https://knowband.com/create-ticket">raise a ticket</a> OR email us at support@knowband.com.</p>
                        </div>
                        
                        <div id="submit_button" class="wmab-submit-btn">
                            <?php submit_button(); ?>
                        </div>
                    </form>                  
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal loder-modal" style="height: 100%;"><!-- Place at bottom of page --></div>
<!--BOC neeraj.kumar@velsof.com 24-Dec-2019 added color picker library-->
<input type="hidden" name="colorpicker_image_path" id="colorpicker_image_path" value="<?php echo plugins_url('/', __FILE__). '/images/'; ?>" /> 
<script type="text/javascript">
function insertSampleData() {
    jQuery("#insert_sample_data").val("<?php echo _e('Please Wait...', 'woocommerce-mobile-app-builder'); ?>");
    
    //Add code to send ajax request for validating FaceBook App ID
    jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
            'action': 'wmab_insert_sample_data'
        },
        dataType: 'json',
        success: function (response) {
            jQuery("#insert_sample_data").val("<?php echo _e('Insert Sample Data', 'woocommerce-mobile-app-builder'); ?>");
            if (!response) {
                jQuery("#insert_sample_data_response").html("<span class='mab_validation_error'><?php echo _e('Some error occurred.', 'woocommerce-mobile-app-builder'); ?></span>");
            } else {
                jQuery("#insert_sample_data_response").html("<span class='text-success'><?php echo _e('Sample Data Inserted successfully.', 'woocommerce-mobile-app-builder'); ?></span>");
            }
        }
    });
}

//function to send ajax request and get category or products listing based on input
function get_link_to_options(val, ele) {
    
    if (val > 0) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'wmab_get_link_to_options',
                'key' : val
            },
            dataType: 'html',
            success: function (html) {
                html = '<option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option>'+html;
                jQuery(ele).parent().next().children('select').html(html);
            }
        });
    } else {
        jQuery(ele).parent().next().children().html('');
    }
}

//Add New Slideshow
jQuery("#add_slideshow").click(function() {
    var html = '<tr class="mab-table-odd mab-table-bottom-border"><td><input type="text" name="slide_name[]" value="" /></td><td><select name="slide_link_type[]" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="slide_link_to[]"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><input type="file" name="slide_image[]" value="" /></td><td><input type="text" name="slide_sort_order[]" value="" /></td><td><input type="button" name="remove_slideshow[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td></tr>';
    
    jQuery(html).insertBefore("#slideshow_last_row");
});

//Add New Banner
jQuery("#add_banner").click(function() {
    var html = '<tr class="mab-table-odd mab-table-bottom-border"><td><input type="text" name="banner_name[]" value="" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><input type="file" name="banner_image[]" value="" /></td><td><input type="text" name="banner_sort_order[]" value="" /></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td></tr>';
    
    jQuery(html).insertBefore("#banner_last_row");
});

//Add New Layout Option
jQuery("#add_layout_options").click(function() {
    var html = '<tr class="mab-table-odd mab-table-bottom-border" id="home_layout_" ><td><?php $count++; $new_layout_id = $last_layout_id+1; echo $count; ?></td><td><input type="text" name="layout_id_" id="" readonly="true" value="" /></td><td><input type="text" id="layout_name_<?php echo $new_layout_id;?>" name="layout_name_" value="" /></td><td><center><input type="button" name="save_layout" style="width:50%;"  value="Save" onclick="wmab_change_layout_details(<?php echo $new_layout_id;?>)" /></center></td></tr>';   
    jQuery(html).insertBefore("#tr_last_row");
    jQuery('#banner_last_row').hide();
    jQuery("#add_layout_options").hide();
});


//Function to delete banner/slideshow row from database
function wmab_delete_banner(id, row_id) {
    if (id != '') {
        if (confirm("<?php echo _e('Are you sure to delete ?', 'woocommerce-mobile-app-builder'); ?>")) {
            //Send Ajax Request to delete the image and banner/slide details
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    'action': 'wmab_delete_banner',
                    'key' : id
                },
                dataType: 'json',
                success: function (response) {
                    if (response) {
                        jQuery("#"+row_id).remove();
                    }
                }
            });
        }
    }
}

/**
    * BOC neeraj.kumar@velsof.com Module Upgrade V2
    * @date : 20-Dec-2019
    * @param : KEY : layout id in case of edit layout , name : new/edit layout details
    */
function wmab_change_layout_details(id = '', layout_name = '') {            
            //Send Ajax Request to add/edit the layout details
            var layout_id = jQuery("#layout_id_"+id).val();
            var layout_name = jQuery("#layout_name_"+id).val();                
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    'action': 'wmab_change_layout_details',
                    'layout_id' : layout_id,
                    'layout_name': layout_name
                },
                dataType: 'json',
                success: function (response) {                    
                    if (response) {
                        window.location.href = "<?php echo admin_url().'admin.php?page=mobile-app-builder&wmab_home_layout_success=1'; ?>";
                    }
                }
        });    
}
//EOC 
//Function to remove banner/slideshow row
function wmab_remove_banner(ele) {
    if (ele != '') {
        jQuery(ele).parent().parent().remove();
    }
}

//Function to show Push Notification Details
function wmab_show_push_notification_details(id) {
    //jQuery("tr[id^=wmab_push_notification_details_").hide();
    jQuery("#wmab_push_notification_details_"+id).slideToggle();
    jQuery("#link_"+id).text('<?php echo _e('Hide Details', 'woocommerce-mobile-app-builder'); ?>');
}

//Delete Layout Functionality
function wmab_delete_layout($id){
    //Send Ajax Request to add/edit the layout details
    jQuery(".loder-modal").show();
            var layout_id = $id;                    
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    'action': 'wmab_delete_layout',
                    'layout_id' : layout_id,                    
                },
                dataType: 'json',
                success: function (response) {
                    jQuery(".loder-modal").hide();
                    if (response) {
                        window.location.href = "<?php echo admin_url().'admin.php?page=mobile-app-builder&wmab_home_layout_success=1'; ?>";
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
              jQuery(".loder-modal").hide();
            }
    }); 
}

velovalidation.setErrorLanguage({
    only_alphabet: '<?php echo _e('Only alphabets are allowed.', 'woocommerce-mobile-app-builder'); ?>',
    alphanumeric: '<?php echo _e('Field should be alphanumeric.', 'woocommerce-mobile-app-builder'); ?>',
    empty_field: '<?php echo _e('Field cannot be empty.', 'woocommerce-mobile-app-builder'); ?>',
    number_field: '<?php echo _e('You can enter only numbers.', 'woocommerce-mobile-app-builder'); ?>',
    positive_number: '<?php echo _e('Number should be greater than 0.', 'woocommerce-mobile-app-builder'); ?>',
    maxchar_field: '<?php echo _e('Field cannot be greater than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    minchar_field: '<?php echo _e('Field cannot be less than # character(s).', 'woocommerce-mobile-app-builder'); ?>',
    empty_email: '<?php echo _e('Please enter Email.', 'woocommerce-mobile-app-builder'); ?>',
    validate_email: '<?php echo _e('Please enter a valid Email.', 'woocommerce-mobile-app-builder'); ?>',
    invalid_date: '<?php echo _e('Invalid date format.', 'woocommerce-mobile-app-builder'); ?>',
    validate_range: '<?php echo _e('Number is not in the valid range. It should be between # and !!', 'woocommerce-mobile-app-builder'); ?>',
    invalid_phone: '<?php echo _e('Phone number is invalid.', 'woocommerce-mobile-app-builder'); ?>',
    empty_phone: '<?php echo _e('Please enter phone number.', 'woocommerce-mobile-app-builder'); ?>',
    minchar_phone: '<?php echo _e('Phone number cannot be less than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    maxchar_phone: '<?php echo _e('Phone number cannot be greater than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    invalid_url: '<?php echo _e('Invalid URL format.', 'woocommerce-mobile-app-builder'); ?>',
    empty_url: '<?php echo _e('Please enter URL.', 'woocommerce-mobile-app-builder'); ?>',
    valid_amount: '<?php echo _e('Field should be numeric.', 'woocommerce-mobile-app-builder'); ?>',
    valid_decimal: '<?php echo _e('Field can have only upto two decimal values.', 'woocommerce-mobile-app-builder'); ?>',
    max_email: '<?php echo _e('Email cannot be greater than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    specialchar_zip: '<?php echo _e('Zip should not have special characters.', 'woocommerce-mobile-app-builder'); ?>',
    specialchar_sku: '<?php echo _e('SKU should not have special characters.', 'woocommerce-mobile-app-builder'); ?>',
    max_url: '<?php echo _e('URL cannot be greater than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    valid_percentage: '<?php echo _e('Percentage should be in number.', 'woocommerce-mobile-app-builder'); ?>',
    between_percentage: '<?php echo _e('Percentage should be between 0 and 100.', 'woocommerce-mobile-app-builder'); ?>',
    positive_amount: '<?php echo _e('Field should be positive.', 'woocommerce-mobile-app-builder'); ?>',
    maxchar_color: '<?php echo _e('Color could not be greater than # characters.', 'woocommerce-mobile-app-builder'); ?>',
    invalid_color: '<?php echo _e('Color is not valid.', 'woocommerce-mobile-app-builder'); ?>',
    specialchar: '<?php echo _e('Special characters are not allowed.', 'woocommerce-mobile-app-builder'); ?>',
    script: '<?php echo _e('Script tags are not allowed.', 'woocommerce-mobile-app-builder'); ?>',
    style: '<?php echo _e('Style tags are not allowed.', 'woocommerce-mobile-app-builder'); ?>',
    iframe: '<?php echo _e('Iframe tags are not allowed.', 'woocommerce-mobile-app-builder'); ?>',
    not_image: '<?php echo _e('Uploaded file is not an image', 'woocommerce-mobile-app-builder'); ?>',
    image_size: '<?php echo _e('Uploaded file size must be less than #.', 'woocommerce-mobile-app-builder'); ?>',
    html_tags: '<?php echo _e('Field should not contain HTML tags.', 'woocommerce-mobile-app-builder'); ?>',
    number_pos: '<?php echo _e('You can enter only positive numbers.', 'woocommerce-mobile-app-builder'); ?>',
    invalid_separator:'<?php echo _e('Invalid comma (#) separated values.', 'woocommerce-mobile-app-builder'); ?>',
    live_whatsup_chat:'<?php echo _e('Only one chat option can be enabled at a time.', 'woocommerce-mobile-app-builder'); ?>',
});
</script>

<style>
    .loder-modal {
            display:    none;
            position:   fixed;
            z-index:    1000;
            top:        0;
            left:       0;
            height:     100%;
            width:      100%;
            background: rgba( 255, 255, 255, .8 ) 
                        url('<?php echo plugins_url('/', __FILE__)."images/loader.gif"; ?>') 
                        50% 50% 
                        no-repeat;
        }

</style>
<!--Tab-Bar Layout-->
<div id="tab_bar_modal" class="modal modal_extra_styles">
    <div class="modal-content">
      <div class="modal-header">
          <span class="close" id="tab-bar-modal-close">&times;</span>
        <h4 class="modal-title">Manage Tab Bar Layout</h4>       
     </div>                   
            <div class="modal-body">
                <form id="tab_bar_form" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="">
                    <input type="hidden" name="tab_bar_id" value="" id="tab_bar_id" />
                    <div class="panel" id="fieldset_0">
                        <div class="panel-heading">
                            Tab Icon Settings
                        </div>
                        <div class="form-wrapper">
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the Activity where you have to redirect the app after click on tab icon.
                                          ">
                                        Select Redirect Activity
                                    </span>
                                </label>
                                <div class="col-lg-9">
                                    <select name="tab_icon_redirect_type" class="chosen-dropdown fixed-width-xl" id="tab_icon_redirect_type" onchange="setLoginTextMessage(this)">
                                        <option value="home">Home</option>
                                        <option value="cart">Cart</option>
                                        <option value="my_account">My Account</option>
                                        <option value="category">Category</option>
                                        <option value="search">Search</option>
                                        <option value="wishlist">Wishlist</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter text to be written below the icon
                                          ">
                                        Text of icon
                                    </span>
                                </label>
                                <div class="col-lg-9">
                                    <div class="form-group">
                                        <div class="translatable-field lang-1">
                                            <div class="col-lg-9">
                                                <input type="text" id="tab_icon_text_1" name="tab_icon_text_1" value="" required="required">
                                            </div>                                            
                                        </div>                                                                                
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter text to be written below the icon
                                          ">Icon Image:</span>
                                </label>
                                <div class="col-lg-9">
                                    <div class="form-group">
                                        <div class="col-lg-12" id="tabiconuploadfile-images-thumbnails">
                                            <div>
                                                <img id="tabiconimage" class="category_image_class" src="" style="max-width: 200px;height:100px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-6">
                                            <input id="tab_bar_images" type="file" name="tab_bar_images" class="hide">
                                            <div class="dummyfile input-group" id="image-upload-div-1">
                                                <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                                <input id="tab_bar_images-name" type="text" name="filename_1" readonly="" value="">
                                                <span class="input-group-btn">
                                                    <button id="tab_bar_images-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                        <i class="icon-folder-open"></i> Add file				</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <script type="text/javascript">

                                      jQuery(document).ready(function(){
                                              jQuery('#tab_bar_images-selectbutton').click(function(e) {
                                                      jQuery('#tab_bar_images').trigger('click');
                                              });

                                              jQuery('#tab_bar_images-name').click(function(e) {
                                                      jQuery('#tab_bar_images').trigger('click');
                                              });

                                              jQuery('#tab_bar_images-name').on('dragenter', function(e) {
                                                      e.stopPropagation();
                                                      e.preventDefault();
                                              });

                                              jQuery('#tab_bar_images-name').on('dragover', function(e) {
                                                      e.stopPropagation();
                                                      e.preventDefault();
                                              });

                                              jQuery('#tab_bar_images-name').on('drop', function(e) {
                                                      e.preventDefault();
                                                      var files = e.originalEvent.dataTransfer.files;
                                                      jQuery('#tab_bar_images')[0].files = files;
                                                      jQuery(this).val(files[0].name);
                                              });

                                              jQuery('#tab_bar_images').change(function(e) {                                                  
                                                      if (jQuery(this)[0].files !== undefined)
                                                      {
                                                              var files = jQuery(this)[0].files;
                                                              var name  = '';

                                                              jQuery.each(files, function(index, value) {
                                                                      name += value.name+', ';
                                                              });

                                                              jQuery('#tab_bar_images-name').val(name.slice(0, -2));
                                                              var reader = new FileReader();
                                                                reader.onload = function (f) {                                                                    
                                                                    jQuery('#tabiconimage').attr('src', f.target.result);                                                    
                                                                }                                                            
                                                            reader.readAsDataURL(jQuery(this)[0].files[0]);
                                                      }
                                                      else // Internet Explorer 9 Compatibility
                                                      {
                                                            var name = jQuery(this).val().split(/[\\/]/);
                                                            jQuery('#tab_bar_images-name').val(name[name.length-1]);
                                                      }
                                              });

//                                              if (typeof slideruploadedfile_1_max_files !== 'undefined')
//                                              {
//                                                      jQuery('#tab_bar_images').closest('form').on('submit', function(e) {
//                                                              if (jQuery('#tab_bar_images')[0].files.length > slideruploadedfile_1_max_files) {
//                                                                      e.preventDefault();
//                                                                      alert('You can upload a maximum of 5 tabs');
//                                                              }
//                                                      });
//                                              }
                                      });
                              </script>
                                    <p class="help-block">
                                        Please upload the image with clear background.	
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" id="savetabbarformbutton" class="btn btn-default btn btn-default pull-right kb_layout_setting_btn" name="tab_bar_form_submit" value="tab_bar_form_submit" onclick="return veloValidateTabIconForm(this)">Save</button>
                        </div>
                    </div>
                </form>                
            </div>                 
    </div>
</div>
<script>
    //Tab Bar Modal Show : Add form persistence in case of edit tab bar else create new one. 
    function tab_bar_setting(new_tab_bar = true, tab_bar_id = ''){
        jQuery('#tab-bar-modal-close').click(function(){        
                jQuery("#tab_bar_modal").hide();
            });
        if(new_tab_bar){
            jQuery('#tab_icon_redirect_type').val("");
            jQuery('#tab_icon_text_1').val("");
            jQuery("#tabiconimage").attr("src","");
            jQuery('#tab_bar_images-name').val("");
            jQuery('#tab_bar_id').val("");
            jQuery('#tab_bar_modal').show();
        } else if(!new_tab_bar && tab_bar_id !== ''){    
            jQuery(".loder-modal").show();
            jQuery('#tab_bar_id').val(tab_bar_id);
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    'action': 'wmab_get_tab_bar_detail',
                    'tab_bar_id' : tab_bar_id,                    
                },
                dataType: 'json',
                success: function (response) {                        
                    if (response.response) {                       
                          jQuery('#tab_icon_redirect_type').val(response.tab_icon_redirect_activity);
                          jQuery('#tab_icon_text_1').val(response.tab_icon_text);
                          jQuery("#tabiconimage").attr("src","<?php echo  plugins_url('/', __FILE__); ?>images/home_page_layout/"+response.tab_icon_image);                          
                          jQuery('#tab_bar_images-name').val(response.tab_icon_image);
                    } else{
                        jQuery('#tab_icon_redirect_type').val("");
                        jQuery('#tab_icon_text_1').val("");
                        jQuery("#tabiconimage").attr("src","");
                        jQuery('#tab_bar_images-name').val("");
                    }
                    jQuery('#tab_bar_modal').show();
                    jQuery(".loder-modal").hide();
              },
                error: function (xhr, ajaxOptions, thrownError) {
                  alert("Somethings wents wrong. Please Reload page to fix.");
                    jQuery('#tab_icon_redirect_type').val("");
                    jQuery('#tab_icon_text_1').val("");
                    jQuery("#tabiconimage").attr("src","");
                    jQuery('#tab_bar_images-name').val("");
                    jQuery("#tab_bar_modal").hide();
                    jQuery(".loder-modal").hide();
                }
             });                    
        }                   
    }
    //Delete Tab Layout functionality
function wmab_delete_tab_bar(tab_bar_id = ''){
        if(tab_bar_id !== '' && confirm("<?php echo _e('Are you sure to delete ?', 'woocommerce-mobile-app-builder'); ?>")){
            jQuery(".loder-modal").show();
             jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        'action': 'wmab_delete_tab_bar',
                        'tab_bar_id' : tab_bar_id,                    
                    },
                    dataType: 'json',
                    success: function (response) { 
                        jQuery(".loder-modal").hide();
                        if (response.response) {                       
                            window.location.href = "<?php echo admin_url().'admin.php?page=mobile-app-builder&wmab_tab_bar_success=1'; ?>";
                        } else{
                            window.location.href = "<?php echo admin_url().'admin.php?page=mobile-app-builder&wmab_tab_bar_error=1'; ?>";
                        }                   
                  },
                    error: function (xhr, ajaxOptions, thrownError) {
                        //Handle Error
                        jQuery(".loder-modal").hide();
                    }
                 }); 
        }       
    }
function setLoginTextMessage (element){
    //my_account 
    var text_login_msg = "<?php echo _e('&quot;Text of icon&quot; would be visible only when logged in otherwise &quot;Log In&quot; will be shown.', 'woocommerce-mobile-app-builder'); ?>";
        if(element.value == 'my_account'){         
            jQuery('#tab_icon_text_1').parent().append('<span class="mab_validation_error"><br>' + text_login_msg + '</span>');            
        } else {
            jQuery('#tab_icon_text_1').parent().find('.mab_validation_error').remove();
        }            
    }
</script>