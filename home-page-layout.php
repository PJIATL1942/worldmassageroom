<?php
if (!defined('ABSPATH'))
    exit;  // Exit if access directly

global $wpdb;

//Get Mobile App Builder settings from database
$settings = get_option('wmab_settings');
if (isset($settings) && !empty($settings)) {
	$settings = unserialize($settings);
}

//Include file to import wmab category class
require_once( plugin_dir_path( __DIR__ ) . 'api/wmab-home.php');
//Create class object
$wmab_home_page = new WmabHome('HomePageLayout');

?>
<style>
    .countdownBackground {
        background-repeat: no-repeat !important; 
    }
    #ui-datepicker-div{
        padding: 20px !important;
        background-color: white !important;
    }
    body{
        background-color: #f1f1f1 !important;
    }
    .mab-table input, .mab-table select{
        width : unset !important;
    }
    .input-group-addon{
        padding-right: 30px !important;
    }
    .bootstrap-datetimepicker-widget{
        left : 1% !important;
    }
    .help-block{
        font-style: italic;
    }
    li.slide{
        width:100%;
    }
    .ui-datepicker {
        z-index: 10000 !important;
    }
</style>

<?php
//Get Upload folder path and URL
$default_upload_dirs = wp_upload_dir();
$upload_directory = '';
$upload_directory_url = '';
if (!empty($default_upload_dirs['basedir'])) {
	$upload_directory = $default_upload_dirs['basedir'] . '/knowband';
	$upload_directory_url = $default_upload_dirs['baseurl'] . '/knowband/';
}
//Ends

//Save modal form date into table
if (isset($_POST['submitHomePageLayout']) && !empty($_POST['submitHomePageLayout'])) {
    $validate = true;
    $edit = false;
    $error_message = '';
	
	//Create Home page images upload folder inside default WP uploads directory
	if(!is_dir($upload_directory)) {
		wp_mkdir_p($upload_directory);
		chmod($upload_directory, 0755); //Set write permission of Knowband directory
	}
	//Ends
	
    //If modal form : Product square , grid , horizontal
    if ($_POST['submitHomePageLayout'] == 'submitProductsOptions') {
        $post_data = filter_var_array($_POST);
        $category_type = '';
        //Validation for checked is component id is set or number of product value some value.
        if (!isset($post_data['mab_component_id']) || (isset($post_data['mab_component_id']) && empty($post_data['mab_component_id']))) {
            $validate = false;
            $error_message = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
        }
        if (!isset($post_data['number_of_products']) || (isset($post_data['number_of_products']) && empty($post_data['number_of_products']))) {
            $validate = false;
            $error_message = _e('Number of product empty.', 'woocommerce-mobile-app-builder');
        }
        //Image Contents
        if (!isset($post_data['image_content_mode']) || (isset($post_data['image_content_mode']) && empty($post_data['image_content_mode']))) {
            $validate = false;
            $error_message = _e('Image Content Mode empty.', 'woocommerce-mobile-app-builder');
        }        
        if ($validate) {
            //Update Component Title :  
            if (isset($post_data['component_title'])) {
                //Update Component Title :                                                                  
                $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_title` = %s WHERE `wmmlc`.`id_component` = %s;", $post_data['component_title'], $post_data['mab_component_id']);
                $wpdb->query($heading_sql);
            }
            //get category_id of a corresponding component id
            $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE `id_component` = %d", sanitize_text_field($post_data['mab_component_id']));
            $result_row = $wpdb->get_row($sql);
            if (isset($result_row->id_component_type) && !empty($result_row->id_component_type)) {
                $category_type = $result_row->id_component_type;
            } else {
                $validate = false;
                $error_message = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
            }
        }
        //Check we need to add new entries or update Entries by category_component id
        if ($validate) {
            //get category_id of a corresponding component id
            $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d", sanitize_text_field($post_data['mab_component_id']));
            $result_row = $wpdb->get_row($sql);
            if (isset($result_row) && !empty($result_row)) {
                $edit = true;
            } else {
                $edit = false;
            }
        }
        //If form validate then saved entries into wp_mab_mobileapp_layout_component and wp_mab_mobileapp_product_data tables 
        if ($validate) {
            //Category_id
            $category_id = '';
            $category_product = '';
            if(isset($post_data['category_id']) && !empty($post_data['category_id'])){
                $category_id = $post_data['category_id'];
            }
            //Category Products
            if(isset($post_data['category_products']) && !empty($post_data['category_products'])){
                $category_product = implode(",",$post_data['category_products']);
            }
            //Product List 
            if(isset($post_data['product_list']) && !empty($post_data['product_list'])){
                $product_ids = implode(",",$post_data['product_list']);
            }
            //Saved Component heading into DB
            if (isset($post_data['component_heading_1'])) {
                $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_heading` = %s WHERE `wmmlc`.`id_component` = %s;", $post_data['component_heading_1'], $post_data['mab_component_id']);
                $wpdb->query($heading_sql);
            }

            if ($edit) {
                $sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_product_data` as wmmpd SET `number_of_products` = %s, `product_type` = %s, `image_content_mode` = %s , `category_products` = %s,`custom_products` = %s,`id_category` = %s WHERE wmmpd.`id_component` = %s;", $post_data['number_of_products'], $post_data['product_type'], $post_data['image_content_mode'], $category_product,$product_ids,$category_id,$post_data['mab_component_id']);
            } else {
                $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_product_data` (`product_type`,`image_content_mode`, `number_of_products`,`id_component`,`category_products`,`custom_products`,`id_category`) VALUES (%s,%s, %s,%s,%s,%s,%s);", $post_data['product_type'], $post_data['image_content_mode'], $post_data['number_of_products'], $post_data['mab_component_id'],$category_product,$product_ids,$category_id);
            }            
            //Execute Sql query
            $wpdb->query($sql);
            
            //Through Success Message
            $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
        } else {
            //Through Error
            $_SESSION['wmab_form_save_error'] = $error_message;
        }
    } else if ($_POST['submitHomePageLayout'] == 'submitTopCategoryForms') {
        $post_data = filter_var_array($_POST);
        $validate = true;
        //Check component id is present or not
        if (!isset($post_data['mab_component_id']) || (isset($post_data['mab_component_id']) && empty($post_data['mab_component_id']))) {
            $validate = false;
        }
        //If component id is not empty
        if ($validate) {
            if (isset($post_data['component_title'])) {
                //Update Component Title :                                                                  
                $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_title` = %s WHERE `wmmlc`.`id_component` = %s;", $post_data['component_title'], $post_data['mab_component_id']);
                $wpdb->query($heading_sql);
            }
            //Deleted Existing Entries and insert new one
            $deleted_sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_top_category WHERE id_component = %d", sanitize_text_field($post_data['mab_component_id']));
            $wpdb->query($deleted_sql);
            //Iterate Loop Upto last category if we found category_id not set then exit loop
            for ($iterate = 1; $iterate <= 8; $iterate++) {
                if (isset($post_data['category_id_' . $iterate]) && !empty($post_data['category_id_' . $iterate])) {
                    $category_heading = '';
                    //default file status set as false
                    $file_status = false;
                    //If heading not empty then saved entries into DB
                    if (isset($post_data['category_heading_' . $iterate]) && !empty($post_data['category_heading_' . $iterate])) {
                        $category_heading = $post_data['category_heading_' . $iterate];
                    }
                    //Check Image
                    if (isset($_FILES['slideruploadedfile_' . $iterate]['name']) && !empty($_FILES['slideruploadedfile_' . $iterate]['name'])) {
                        $file_status = true;
                    }
                    $document_path = '';
                    //Image Move to images folder
                    if ($file_status) {
                        $document_path = $upload_directory . '/'; //plugin_dir_path(__FILE__) . 'images/home_page_layout/';
                        $ext = pathinfo(sanitize_text_field($_FILES['slideruploadedfile_' . $iterate]['name']), PATHINFO_EXTENSION);
                        $upload_file_name = 'top_category_' . $iterate . '_' . time() . '.' . $ext;
                        $upload_file_path = $document_path . $upload_file_name;
                        if (move_uploaded_file($_FILES['slideruploadedfile_' . $iterate]["tmp_name"], $upload_file_path)) {
                            //Insert New Entries
                            $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_top_category` SET id_component = %d, id_category = %d, image_url = %s, image_content_mode = %s, category_heading = %s", $post_data['mab_component_id'], $post_data['category_id_' . $iterate], $upload_file_name, $post_data['image_content_mode'], $category_heading);
                            $wpdb->query($sql);
                        }
                    } else {
                        $file_name = '';
                        if (isset($post_data['hiddensliderimage_' . $iterate]) && !empty($post_data['hiddensliderimage_' . $iterate])) {
                            $file_name = $post_data['hiddensliderimage_' . $iterate];
                        }
                        //Insert new entries
                        $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_top_category` SET id_component = %d, id_category = %d, image_url = %s, image_content_mode = %s, category_heading = %s", $post_data['mab_component_id'], $post_data['category_id_' . $iterate], $file_name, $post_data['image_content_mode'], $category_heading);
                        $wpdb->query($sql);
                    }
                } else {
                    break;
                }
            }
            //Through Success Message
            if (!isset($_SESSION['wmab_form_save_success'])) {
                $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
            }
        } else {
            //Through Error
            $_SESSION['wmab_form_save_error'] = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
        }
    }
    //Banner Options
    else if ($_POST['submitHomePageLayout'] == 'submitBannerOptions') {
        $post_data = filter_var_array($_POST);

        $validate = true;
        if (isset($post_data['mab_component_id']) && !empty($post_data['mab_component_id'])) {

            //Update Component Heading : 
            //Deleted Existing Entries and insert new one
            $deleted_sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d", sanitize_text_field($post_data['mab_component_id']));
            $wpdb->query($deleted_sql);
            $file_read_index = 0;
            foreach ($post_data['banner_heading_name'] as $keyBannerPost => $valueBannerPost) {
                $banner_heading_name = '';
                $component_heading_name = $post_data['component_heading_name'][0];
                $banner_link_type = '';
                $image_content_mode = '';
                $product_category_id = '';
                $component_id = $post_data['mab_component_id'];
                $product_id = '';
                $category_id = '';
                if (isset($post_data['banner_heading_name'][$keyBannerPost]) && !empty($post_data['banner_heading_name'][$keyBannerPost])) {
                    $banner_heading_name = $post_data['banner_heading_name'][$keyBannerPost];
                } else {
                    //Banner heading Not mandatory
//                        $validate = false;
                }

                //Banner Link : Category = 1 : Product = 2
                if (isset($post_data['banner_link_type'][$keyBannerPost]) && !empty($post_data['banner_link_type'][$keyBannerPost])) {
                    if ($post_data['banner_link_type'][$keyBannerPost] == 1) {
                        $banner_link_type = 'category';
                        $product_id = NULL;
                        $category_id = $post_data['banner_link_to'][$keyBannerPost];
                    } else if ($post_data['banner_link_type'][$keyBannerPost] == 2) {
                        $banner_link_type = 'product';
                        $category_id = NULL;
                        $product_id = $post_data['banner_link_to'][$keyBannerPost];
                    } else {
                        $validate = false;
                    }
                } else {
                    $validate = false;
                }

                //Link to : product_id , category_id
//                    if(isset($post_data['banner_link_to'][$keyBannerPost]) && !empty($post_data['banner_link_to'][$keyBannerPost])){
//                        $product_category_id = $post_data['banner_link_to'][$keyBannerPost];
//                    }else{
//                        $validate = false;
//                    }
                //Image Content Mode
                if (isset($post_data['image_content_mode'][$keyBannerPost]) && !empty($post_data['image_content_mode'][$keyBannerPost])) {
                    $image_content_mode = $post_data['image_content_mode'][$keyBannerPost];
                } else {
                    $validate = false;
                }
                if ($validate) {
                    //Saved Component heading into DB                       
                    $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_heading` = %s WHERE `wmmlc`.`id_component` = %s;", $component_heading_name, $component_id);
                    $wpdb->query($heading_sql);
                    //Check in case of edit form if image not set then image not replace with existing image
                    if (isset($post_data['image_banner_upload_edit'][$keyBannerPost]) && !empty($post_data['image_banner_upload_edit'][$keyBannerPost])) {
                        //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                        $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $post_data['image_banner_upload_edit'][$keyBannerPost], $image_content_mode, $banner_heading_name);
                        $wpdb->query($sql);
                    }
                    //Check Uploaded File
                    else {
                        if (isset($_FILES['banner_image']['name'][$file_read_index]) && !empty($_FILES['banner_image']['name'][$file_read_index])) {
                            //Only Saved when Image uploaded because it is mandatory
                            $document_path = $upload_directory . '/'; //plugin_dir_path(__FILE__) . 'images/home_page_layout/';
                            $ext = pathinfo(sanitize_text_field($_FILES['banner_image']['name'][$file_read_index]), PATHINFO_EXTENSION);
                            $upload_file_name = 'banner_options_' . $file_read_index . '_' . time() . '.' . $ext;
                            $upload_file_path = $document_path . $upload_file_name;
                            if (move_uploaded_file($_FILES['banner_image']['tmp_name'][$file_read_index], $upload_file_path)) {
                                //If successfully move then saved entries into DB
                                //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                                $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $upload_file_name, $image_content_mode, $banner_heading_name);
                                $wpdb->query($sql);
                            }
                            $file_read_index++;
                        }
                        //Saved File from Banner Image
                        else {
                            //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                            $upload_file_name = '';
                            $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $upload_file_name, $image_content_mode, $banner_heading_name);
                            $wpdb->query($sql);
                        }
                    }
                } else {
                    //Through Error - Something wents wrongs                        
                }
            }
            if ($validate) {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_success'])) {
                    $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
                }
            } else {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_error'])) {
                    //Through Error
                    $_SESSION['wmab_form_save_error'] = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
                }
            }
        }
    }
    //Banner Countdown Options
    else if ($_POST['submitHomePageLayout'] == 'submitBannerCountDownOptions') {
        $post_data = filter_var_array($_POST);
        $validate = true;
        if (isset($post_data['mab_component_id']) && !empty($post_data['mab_component_id'])) {
            //Update Component Heading : 
            //Deleted Existing Entries and insert new one
            $deleted_sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d", sanitize_text_field($post_data['mab_component_id']));
            $wpdb->query($deleted_sql);
            $file_read_index = 0;
            foreach ($post_data['banner_heading_name'] as $keyBannerPost => $valueBannerPost) {
                $banner_heading_name = '';
                $component_heading_name = $post_data['component_heading_name'][0];
                $banner_link_type = '';
                $image_content_mode = '';
                $product_category_id = '';
                $component_id = $post_data['mab_component_id'];
                $product_id = '';
                $category_id = '';
                $timer_validity = '';
                $background_color_status = 0;
                $timer_background_color = '#ffffff';
                $timer_text_color = '';
                if (isset($post_data['banner_heading_name'][$keyBannerPost]) && !empty($post_data['banner_heading_name'][$keyBannerPost])) {
                    $banner_heading_name = $post_data['banner_heading_name'][$keyBannerPost];
                } else {
                    //Banner header Not mandatory
                    //$validate = false;
                }

                //Banner Link : Category = 1 : Product = 2
                if (isset($post_data['banner_link_type'][$keyBannerPost]) && !empty($post_data['banner_link_type'][$keyBannerPost])) {
                    if ($post_data['banner_link_type'][$keyBannerPost] == 1) {
                        $banner_link_type = 'category';
                        $product_id = NULL;
                        $category_id = $post_data['banner_link_to'][$keyBannerPost];
                    } else if ($post_data['banner_link_type'][$keyBannerPost] == 2) {
                        $banner_link_type = 'product';
                        $category_id = NULL;
                        $product_id = $post_data['banner_link_to'][$keyBannerPost];
                    } else {
                        $validate = false;
                    }
                } else {
                    $validate = false;
                }
                //Image Content Mode
                if (isset($post_data['image_content_mode'][$keyBannerPost]) && !empty($post_data['image_content_mode'][$keyBannerPost])) {
                    $image_content_mode = $post_data['image_content_mode'][$keyBannerPost];
                } else {
                    $validate = false;
                }

                //Timer Validity validation
                if (isset($post_data['timer_validity'][$keyBannerPost]) && !empty($post_data['timer_validity'][$keyBannerPost])) {
                    $timer_validity = date("Y-m-d H:i:s", strtotime($post_data['timer_validity'][$keyBannerPost]));
                } else {
                    $validate = false;
                }

                //Background Color Status
                if (isset($post_data['background_color_status'][$keyBannerPost]) && !empty($post_data['background_color_status'][$keyBannerPost])) {
                    if (isset($post_data['timer_background_color'][$keyBannerPost]) && !empty($post_data['timer_background_color'][$keyBannerPost])) {
                        $timer_background_color = $post_data['timer_background_color'][$keyBannerPost];
                        $background_color_status = 1;
                    } else {
                        $validate = false;
                    }
                } else {
                    $background_color_status = 0;
                }
                //Timer Text Color
                if (isset($post_data['timer_text_color'][$keyBannerPost]) && !empty($post_data['timer_text_color'][$keyBannerPost])) {
                    $timer_text_color = $post_data['timer_text_color'][$keyBannerPost];
                } else {
                    $validate = false;
                }

                if ($validate) {
                    //Saved Component heading into DB                       
                    $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_heading` = %s WHERE `wmmlc`.`id_component` = %s;", $component_heading_name, $component_id);
                    $wpdb->query($heading_sql);
                    //Check in case of edit form if image not set then image not replace with existing image
                    if (isset($post_data['image_banner_countdown_upload_edit'][$keyBannerPost]) && !empty($post_data['image_banner_countdown_upload_edit'][$keyBannerPost])) {
                        //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                        $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s,countdown = %s , background_color = %s , is_enabled_background_color = %s , text_color = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $post_data['image_banner_countdown_upload_edit'][$keyBannerPost], $image_content_mode, $banner_heading_name, $timer_validity, $timer_background_color, $background_color_status, $timer_text_color);
                        $wpdb->query($sql);
                    }
                    //Check Uploaded File
                    else {
                        if (isset($_FILES['banner_image']['name'][$file_read_index]) && !empty($_FILES['banner_image']['name'][$file_read_index])) {
                            //Only Saved when Image uploaded because it is mandatory
                            $document_path = $upload_directory . '/'; //plugin_dir_path(__FILE__) . 'images/home_page_layout/';
                            $ext = pathinfo(sanitize_text_field($_FILES['banner_image']['name'][$file_read_index]), PATHINFO_EXTENSION);
                            $upload_file_name = 'banner_countdown_' . $file_read_index . '_' . time() . '.' . $ext;
                            $upload_file_path = $document_path . $upload_file_name;
                            if (move_uploaded_file($_FILES['banner_image']['tmp_name'][$file_read_index], $upload_file_path)) {
                                //If successfully move then saved entries into DB
                                //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                                $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s,countdown = %s , background_color = %s , is_enabled_background_color = %s , text_color = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $upload_file_name, $image_content_mode, $banner_heading_name, $timer_validity, $timer_background_color, $background_color_status, $timer_text_color);
                                $wpdb->query($sql);
                            }
                            $file_read_index++;
                        }
                    }
                } else {
                    //Through Error - Something wents wrongs                        
                }
            }
            if ($validate) {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_success'])) {
                    $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
                }
            } else {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_error'])) {
                    //Through Error
                    $_SESSION['wmab_form_save_error'] = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
                }
            }
        }
    }

// BOC neeraj.kumar@velsof.com : Custom Banner Added 
    //Banner Options
    else if ($_POST['submitHomePageLayout'] == 'submitBannerCustomOptions') {
        $post_data = filter_var_array($_POST);

        $validate = true;
        if (isset($post_data['mab_component_id']) && !empty($post_data['mab_component_id'])) {

            //Update Component Heading : 
            //Deleted Existing Entries and insert new one
            $deleted_sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d", sanitize_text_field($post_data['mab_component_id']));
            $wpdb->query($deleted_sql);
            $file_read_index = 0;
            foreach ($post_data['banner_heading_name'] as $keyBannerPost => $valueBannerPost) {
                $banner_heading_name = '';
                $component_heading_name = $post_data['component_heading_name'][0];
                $banner_link_type = '';
                $image_content_mode = '';
                $product_category_id = '';
                $component_id = $post_data['mab_component_id'];
                $product_id = '';
                $category_id = '';

                //BOC neeraj.kumar@velsof.com 7-Jan-2019 Added new columns
                $inset_top = NULL;
                $inset_bottom = NULL;
                $inset_left = NULL;
                $inset_right = NULL;
                $banner_width = NULL;
                $banner_height = NULL;
                $banner_background_color = NULL;

                if (isset($post_data['banner_heading_name'][$keyBannerPost]) && !empty($post_data['banner_heading_name'][$keyBannerPost])) {
                    $banner_heading_name = $post_data['banner_heading_name'][$keyBannerPost];
                }

                //Banner Link : Category = 1 : Product = 2
                if (isset($post_data['banner_link_type'][$keyBannerPost]) && !empty($post_data['banner_link_type'][$keyBannerPost])) {
                    if ($post_data['banner_link_type'][$keyBannerPost] == 1) {
                        $banner_link_type = 'category';
                        $product_id = NULL;
                        $category_id = $post_data['banner_link_to'][$keyBannerPost];
                    } else if ($post_data['banner_link_type'][$keyBannerPost] == 2) {
                        $banner_link_type = 'product';
                        $category_id = NULL;
                        $product_id = $post_data['banner_link_to'][$keyBannerPost];
                    }  
                    else if ($post_data['banner_link_type'][$keyBannerPost] == 3) {
                        $banner_link_type = 'login';
                        $category_id = NULL;
                        $product_id = NULL;                        
                    } else if ($post_data['banner_link_type'][$keyBannerPost] == 4) {
                        $banner_link_type = 'search';
                        $category_id = NULL;
                        $product_id = NULL;                        
                    }
                    else if ($post_data['banner_link_type'][$keyBannerPost] == 5) {
                        $banner_link_type = 'home';
                        $category_id = NULL;
                        $product_id = NULL;                        
                    }
                    else {
                        $validate = false;
                    }
                } else {
                    $validate = false;
                }
                //Image Content Mode
                if (isset($post_data['image_content_mode'][$keyBannerPost]) && !empty($post_data['image_content_mode'][$keyBannerPost])) {
                    $image_content_mode = $post_data['image_content_mode'][$keyBannerPost];
                } else {
                    $validate = false;
                }
                //Banner Width
                if (isset($post_data['banner_width'][$keyBannerPost]) && !empty($post_data['banner_width'][$keyBannerPost])) {
                    $banner_width = $post_data['banner_width'][$keyBannerPost];
                }
                //Banner Height 
                if (isset($post_data['banner_height'][$keyBannerPost]) && !empty($post_data['banner_height'][$keyBannerPost])) {
                    $banner_height = $post_data['banner_height'][$keyBannerPost];
                }
                //Inset Top
                if (isset($post_data['inset_top'][$keyBannerPost]) && !empty($post_data['inset_top'][$keyBannerPost])) {
                    $inset_top = $post_data['inset_top'][$keyBannerPost];
                }
                //Inset Bottom
                if (isset($post_data['inset_bottom'][$keyBannerPost]) && !empty($post_data['inset_bottom'][$keyBannerPost])) {
                    $inset_bottom = $post_data['inset_bottom'][$keyBannerPost];
                }
                //Inset Left
                if (isset($post_data['inset_left'][$keyBannerPost]) && !empty($post_data['inset_left'][$keyBannerPost])) {
                    $inset_left = $post_data['inset_left'][$keyBannerPost];
                }//Inset Right
                if (isset($post_data['inset_right'][$keyBannerPost]) && !empty($post_data['inset_right'][$keyBannerPost])) {
                    $inset_right = $post_data['inset_right'][$keyBannerPost];
                }
                //Banner Background Color
                if (isset($post_data['banner_custom_background_color'][$keyBannerPost]) && !empty($post_data['banner_custom_background_color'][$keyBannerPost])) {
                    $banner_background_color = $post_data['banner_custom_background_color'][$keyBannerPost];
                }


                if ($validate) {
                    //Saved Component heading into DB                       
                    $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_heading` = %s WHERE `wmmlc`.`id_component` = %s;", $component_heading_name, $component_id);
                    $wpdb->query($heading_sql);
                    //Check in case of edit form if image not set then image not replace with existing image
                    if (isset($post_data['image_banner_upload_edit'][$keyBannerPost]) && !empty($post_data['image_banner_upload_edit'][$keyBannerPost])) {
                        //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                        $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s , banner_custom_background_color = %s , inset_top = %s , inset_bottom = %s , inset_left = %s ,inset_right = %s ,banner_width = %s ,banner_height = %s ", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $post_data['image_banner_upload_edit'][$keyBannerPost], $image_content_mode, $banner_heading_name, $banner_background_color, $inset_top, $inset_bottom, $inset_left, $inset_right, $banner_width, $banner_height);
                        $wpdb->query($sql);
                    }
                    //Check Uploaded File
                    else {
                        if (isset($_FILES['banner_image']['name'][$file_read_index]) && !empty($_FILES['banner_image']['name'][$file_read_index])) {
                            //Only Saved when Image uploaded because it is mandatory
                            $document_path = $upload_directory . '/'; //plugin_dir_path(__FILE__) . 'images/home_page_layout/';
                            $ext = pathinfo(sanitize_text_field($_FILES['banner_image']['name'][$file_read_index]), PATHINFO_EXTENSION);
                            $upload_file_name = 'banner_options_' . $file_read_index . '_' . time() . '.' . $ext;
                            $upload_file_path = $document_path . $upload_file_name;
                            if (move_uploaded_file($_FILES['banner_image']['tmp_name'][$file_read_index], $upload_file_path)) {
                                //If successfully move then saved entries into DB
                                //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                                $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s , banner_custom_background_color = %s , inset_top = %s , inset_bottom = %s , inset_left = %s ,inset_right = %s ,banner_width = %s ,banner_height = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $upload_file_name, $image_content_mode, $banner_heading_name, $banner_background_color, $inset_top, $inset_bottom, $inset_left, $inset_right, $banner_width, $banner_height);
                                $wpdb->query($sql);
                            }
                            $file_read_index++;
                        }
                        //Saved File from Banner Image
                        else {
                            //Saved entries in these column : category_id,component_id (product_name , category_name), image content mode,banner heading
                            $upload_file_name = '';
                            $sql = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` SET id_component = %d,id_banner_type = %d, product_id = %d, category_id = %d, redirect_activity = %s, image_url = %s, image_contentMode = %s, banner_heading = %s, banner_custom_background_color = %s , inset_top = %s , inset_bottom = %s , inset_left = %s ,inset_right = %s ,banner_width = %s ,banner_height = %s", $component_id, $post_data['banner_link_type'][$keyBannerPost], $product_id, $category_id, $banner_link_type, $upload_file_name, $image_content_mode, $banner_heading_name, $banner_background_color, $inset_top, $inset_bottom, $inset_left, $inset_right, $banner_width, $banner_height);                            
                            $wpdb->query($sql);
                        }
                    }
                } else {
                    //Through Error - Something wents wrongs                        
                }
            }
            if ($validate) {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_success'])) {
                    $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
                }
            } else {
                //Through Success Message
                if (!isset($_SESSION['wmab_form_save_error'])) {
                    //Through Error
                    $_SESSION['wmab_form_save_error'] = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
                }
            }
        }
    }

    //Saved Component Title 
    //Banner Options
    else if ($_POST['submitHomePageLayout'] == 'submitComponentTitle') {
        $post_data = filter_var_array($_POST);
        $validate = false;
        if (isset($post_data['mab_component_id']) && !empty($post_data['mab_component_id']) && isset($post_data['edit_component_title']) && !empty($post_data['edit_component_title'])) {
            $validate = true;
            $component_title = $post_data['edit_component_title'];
            $mab_component_id = $post_data['mab_component_id'];
            //Update Component Title :                                                                  
            $heading_sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc SET `component_title` = %s WHERE `wmmlc`.`id_component` = %s;", $component_title, $mab_component_id);
            $wpdb->query($heading_sql);
        }
        if ($validate) {
            //Through Success Message
            if (!isset($_SESSION['wmab_form_save_success'])) {
                $_SESSION['wmab_form_save_success'] = _e('Data saved successfully.', 'woocommerce-mobile-app-builder');
            }
        } else {
            //Through Success Message
            if (!isset($_SESSION['wmab_form_save_error'])) {
                //Through Error
                $_SESSION['wmab_form_save_error'] = _e('Somethings wents wrong.', 'woocommerce-mobile-app-builder');
            }
        }
    }
}

/* BOC neeraj.kumar@velsof.com Module Upgrade V2 Get Home layouts details */
$sql = "SELECT * FROM `{$wpdb->prefix}mabmobileapp_component_types` ORDER BY component_name ASC";
$components_details = $wpdb->get_results($sql);
//Get category
//Get Product Categories
$cat_args = array(
    'orderby' => 'name',
    'order' => 'asc',
    'hide_empty' => false,
);
$product_categories = get_terms('product_cat', $cat_args);
/** EOC */
if (isset($_GET['layout_id']) && !empty($_GET['layout_id'])) {
    $layout_id = sanitize_text_field($_GET['layout_id']);
    $component_details = array();
    $sql = $wpdb->prepare("SELECT wmmlc.id_component as id_component,id_layout,id_component_type,position,component_name,component_title, component_heading FROM `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc "
            . "INNER JOIN {$wpdb->prefix}mabmobileapp_component_types as wmct WHERE wmct.id = wmmlc.id_component_type and"
            . " id_layout = %d ORDER by position asc", sanitize_text_field($_GET['layout_id']));
    $component_details = $wpdb->get_results($sql);
} else {
    $layout_id = '';
}
/**
 * 
 */
?>

<input type="hidden" name="hidden_plugin_url" id="hidden_plugin_url" value="<?php echo plugins_url('/', __FILE__) . 'images/home_page_layout/'; ?>" />
<div class="wrap nosubsub">
    <h1 class="wp-heading-inline"><?php echo _e('Mobile App Builder', 'woocommerce-mobile-app-builder'); ?>        
    </h1>
    <input class="back_btn_layout_page" type="button" name="back_button" id="back_button" style="float:right;" value="<?php echo _e('Cancel', 'woocommerce-mobile-app-builder'); ?>" onclick="window.location.href = '<?php echo admin_url(); ?>admin.php?page=mobile-app-builder';"/>
    <div class="container style-home-layout">
        <div class="productTabs col-lg-3 col-md-3 col-sm-6">
            <div class="list-group">
<?php if (isset($components_details) && !empty($components_details)) { ?>
    <?php foreach ($components_details as $keyCategory => $valueCategory) { ?>
                        <?php if (trim($valueCategory->component_name) == 'Top Categories') { ?>
                            <a id="top_category" class="list-group-item"><?php echo trim($valueCategory->component_name); ?> <i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Banner-Custom') { ?>
                            <a id="banner_custom" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Banner-Square') { ?>
                            <a id="banner_square" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Banner-Horizontal Sliding') { ?>
                            <a id="banner_HS" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Banner-Grid') { ?>
                            <a id="banner_grid" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Banner-With Countdown Timer') { ?>
                            <a id="banner_countdown" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Products-Square') { ?>
                            <a id="product_square" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Products-Horizontal Sliding') { ?>
                            <a id="product_HS" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Products-Grid') { ?>
                            <a id="product_grid" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>
                        <?php } ?>
                        <?php if (trim($valueCategory->component_name) == 'Products-Last Accessed') { ?>
                            <a id="product_LA" class="list-group-item"><?php echo trim($valueCategory->component_name); ?><i class="fa fa-plus"></i></a>			
                        <?php } ?>
                    <?php }
                } ?>
            </div>
        </div>
        <div class="col-lg-5 col-md-4 col-sm-6">
            <div class="panel panel-default" style="min-height:400px;">
                <ul class="slides <?php if (isset($component_details) && !empty($component_details)) {
                    echo 'ui-sortabl';
                } ?>">
                    <?php
                    if (isset($component_details) && !empty($component_details)) {
                        foreach ($component_details as $keyComponent => $valueComponent) {
                            $image_url = '';
                            //component title comes from component table  if its not empty
                            $component_title = '';
                            if (isset($valueComponent->component_title) && !empty($valueComponent->component_title)) {
                                $component_title = stripslashes($valueComponent->component_title);
                            } else {
                                $component_title = stripslashes($valueComponent->component_name);
                            }
                            if (trim($valueComponent->component_name) == 'Top Categories') {
                                $image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_01.jpg';
								
								//Get Top Categories for selected Layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_top_category` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));        
								$category_rows = $wpdb->get_results($sql);									
                                ?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>                
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('top-category',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="top_category_input productTabContent" type="hidden" name="top_category[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_01.jpg'; ?>"/>-->
										<div class="topCategories">
											<ul>
												<?php
												if(isset($category_rows) && !empty($category_rows)){ 
													$top_categories_counter = 1;
													foreach ($category_rows as $keyTopCategory => $valueTopCategory){
														if ($top_categories_counter > 4) {
															break;
														}
														$top_categories_counter++;
														?>
												<li>
													<span class="catSection">
														<?php
														if (file_exists($upload_directory . '/' . $valueTopCategory->image_url)) {
															$uploaded_image_path = $upload_directory_url . $valueTopCategory->image_url;
														} else {
															$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueTopCategory->image_url;
														}
														?>
														<img src="<?php echo $uploaded_image_path; ?>">
														<?php if (!empty($valueTopCategory->category_heading)) { ?>
														<p><?php echo $valueTopCategory->category_heading; ?></p>
														<?php } ?>
													</span>	
												</li>
														<?php
													}
												} else {
												?>
												<li>
													<span class="catSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/1.jpg' ?>">
														<p><?php _e('Bag', 'woocommerce-mobile-app-builder'); ?></p>
													</span>	
												</li>
												<li>
													<span class="catSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/2.jpg' ?>">
														<p><?php _e('Shoes', 'woocommerce-mobile-app-builder'); ?></p>
													</span>	
												</li>
												<li>
													<span class="catSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/3.jpg' ?>">
														<p><?php _e('Shirt', 'woocommerce-mobile-app-builder'); ?></p>
													</span>	
												</li>
												<li>
													<span class="catSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/4.jpg' ?>">
														<p><?php _e('Watch', 'woocommerce-mobile-app-builder'); ?></p>
													</span>	
												</li>
												<?php } ?>
											</ul>
										</div>
                                    </div>
                                </li>
                                <?php
                                //Added Banner-Custom option neeraj.kumar 7-jan-2020
                            } else if (trim($valueComponent->component_name) == 'Banner-Custom') {
                                $image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg';
								
								//Get Custom banners for selected layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));
								$custom_banners_rows = $wpdb->get_results($sql);
                                ?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-custom',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="banner_square[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg'; ?> "/>-->
										<div class="bannerSquare">
											<?php if (!empty($valueComponent->component_heading)) { ?>											
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
											<h4 class="comp_heading"><?php _e('Banner Custom', 'woocommerce-mobile-app-builder'); ?></h4>
											<?php
											}
											if(isset($custom_banners_rows) && !empty($custom_banners_rows)){ 
												foreach ($custom_banners_rows as $keyBanner => $valueBanner){
													//Set Banner Width
													$banner_width = 'width:100% !important;';
													if (!empty($valueBanner->banner_width)) {
														$banner_width = 'width:'.$valueBanner->banner_width.'% !important;';
													}
													//Set Banner Height
													$banner_height = '';
													if (!empty($valueBanner->banner_height)) {
														$banner_height = 'height:'.$valueBanner->banner_height.'% !important;';
													}
													//Set Banner Top Margin
													$banner_top_margin = '';
													if (!empty($valueBanner->inset_top)) {
														//$banner_top_margin = 'margin-top:'.$valueBanner->inset_top.'% !important;';
													}
													//Set Banner Right Margin
													$banner_right_margin = '';
													if (!empty($valueBanner->inset_right)) {
														//$banner_right_margin = 'margin-right:'.$valueBanner->inset_right.'% !important;';
													}
													//Set Banner Bottom Margin
													$banner_bottom_margin = '';
													if (!empty($valueBanner->inset_bottom)) {
														//$banner_bottom_margin = 'margin-bottom:'.$valueBanner->inset_bottom.'% !important;';
													}
													//Set Banner Left Margin
													$banner_left_margin = '';
													if (!empty($valueBanner->inset_left)) {
														//$banner_left_margin = 'margin-left:'.$valueBanner->inset_left.'% !important;';
													}
											?>
											<div class="bannerSquareList" style="float: left;<?php echo $banner_width . ' ' . $banner_height . ' ' . $banner_top_margin . ' ' . $banner_right_margin . ' ' . $banner_bottom_margin . ' ' . $banner_left_margin ; ?>">
												<span class="BSSection">
													<?php
													if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
														$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
													} else {
														$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueBanner->image_url;
													}
													?>
													<img src="<?php echo $uploaded_image_path; ?>">
													<?php if (!empty($valueBanner->banner_heading)) { ?>
                                                                                                        <h5 class="elem_heading"><?php echo stripslashes($valueBanner->banner_heading); ?></h5>
													<?php } ?>
												</span>
											</div>
											<?php
												}
											} else {										
											?>											
											<div class="bannerSquareList">
												<span class="BSSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/banner-square_1.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Custom Banner', 'woocommerce-mobile-app-builder'); ?></h5>
												</span>
											</div>
											<?php } ?>
										</div>
                                    </div>
                                </li>
                            <?php
                            } else if (trim($valueComponent->component_name) == 'Banner-Square') {
                                $image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg';
								
								//Get Square banners for selected layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));
								$square_banners_rows = $wpdb->get_results($sql);
                                ?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-sqaure',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="banner_square[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg'; ?> "/>-->
										<div class="bannerSquare">
										<?php
											if(isset($square_banners_rows) && !empty($square_banners_rows)){ 
												if (!empty($valueComponent->component_heading)) {
											?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php }
												foreach ($square_banners_rows as $keyBanner => $valueBanner){
											?>
											<div class="bannerSquareList">
												<span class="BSSection">
													<?php
													if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
														$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
													} else {
														$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueBanner->image_url;
													}
													?>
													<img src="<?php echo $uploaded_image_path; ?>">
													<?php if (!empty($valueBanner->banner_heading)) { ?>
                                                                                                        <h5 class="elem_heading"><?php echo stripslashes($valueBanner->banner_heading); ?></h5>
													<?php } ?>
												</span>
											</div>
											<?php 
												}
											} else {
											?>
											<h4 class="comp_heading"><?php _e('Square Banner', 'woocommerce-mobile-app-builder'); ?></h4>
											<div class="bannerSquareList">
												<span class="BSSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/banner-square_1.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Square Design Banner', 'woocommerce-mobile-app-builder'); ?></h5>
												</span>
											</div>
											<?php } ?>
										</div>
                                    </div>
                                </li>
							<?php } else if (trim($valueComponent->component_name) == 'Banner-Horizontal Sliding') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_03.jpg';
								
								//Get Horizontal banners for selected layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));
								$horizontal_banners_rows = $wpdb->get_results($sql);
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-horizontal-sliding',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="banner_horizontal_sliding[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_03.jpg'; ?>"/>-->
										<div class="bannerHorizontalSlide">
											<?php if (!empty($valueComponent->component_heading)) { ?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
											<h4 class="comp_heading"><?php _e('Horizontal Sliding Banner', 'woocommerce-mobile-app-builder'); ?></h4>	
											<?php } ?>
											<div class="slidingBannersScroll">
												<div class="slidingBanners">
													<?php
													if(isset($horizontal_banners_rows) && !empty($horizontal_banners_rows)){ 
														$horizontal_banners_counter = 1;
														foreach ($horizontal_banners_rows as $keyBanner => $valueBanner){
															if ($horizontal_banners_counter > 2) {
																break;
															}
															$horizontal_banners_counter++;
															?>
													<div class="bannerHorizontalSlideList">
														<span class="BHSSection">
														<?php
														if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
															$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
														} else {
															$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueBanner->image_url;
														}
														?>
														<img src="<?php echo $uploaded_image_path; ?>">
														<?php if (!empty($valueBanner->banner_heading)) { ?>
                                                                                                                <h5 class="elem_heading"><?php echo stripslashes($valueBanner->banner_heading); ?></h5>
														<?php } ?>
														</span>
													</div>
															<?php
														}
													} else {										
													?>
													<div class="bannerHorizontalSlideList">
														<span class="BHSSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/deal_1.jpg'; ?>">
														<h5 class="elem_heading"><?php _e('Horizontal Design banner1', 'woocommerce-mobile-app-builder'); ?></h5>
														</span>
													</div>
													<div class="bannerHorizontalSlideList">
														<span class="BHSSection">
														<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/deal_2.jpg'; ?>">
														<h5 class="elem_heading"><?php _e('Horizontal Design banner2', 'woocommerce-mobile-app-builder'); ?></h5>
														</span>
													</div>
													<?php } ?>
												</div>
											</div>
										</div>
                                    </div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Banner-Grid') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_04.jpg';
								//Get Grid banners for selected layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));
								$grid_banners_rows = $wpdb->get_results($sql);
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-grid',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="banner_grid[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>" />
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_04.jpg'; ?>"/>-->
										<div class="bannerGrid">	
										<?php if (!empty($valueComponent->component_heading)) { ?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
										<?php } else { ?>
											<h4 class="comp_heading"><?php _e('Grid Banner', 'woocommerce-mobile-app-builder'); ?></h4>
										<?php } ?>
										<div class="bannerGridRow">
										<?php
											if(isset($grid_banners_rows) && !empty($grid_banners_rows)){ 
												foreach ($grid_banners_rows as $keyBanner => $valueBanner){
											?>
											<div class="bannerGridList">
												<span class="BSSection">
													<?php
													if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
														$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
													} else {
														$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueBanner->image_url;
													}
													?>
													<img src="<?php echo $uploaded_image_path; ?>">
													<?php if (!empty($valueBanner->banner_heading)) { ?>
                                                                                                        <h5 class="elem_heading"><?php echo stripslashes($valueBanner->banner_heading); ?></h5>
													<?php } ?>
												</span>
											</div>
											<?php 
												}
											} else {
											?>											
												<div class="bannerGridList">
													<span class="BGSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Banner1', 'woocommerce-mobile-app-builder'); ?></h5>
													</span>
												</div>
												<div class="bannerGridList">
													<span class="BGSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS2.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Banner2', 'woocommerce-mobile-app-builder'); ?></h5>
													</span>
												</div>
												<div class="bannerGridList">
													<span class="BGSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS3.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Banner3', 'woocommerce-mobile-app-builder'); ?></h5>
													</span>
												</div>
												<div class="bannerGridList">
													<span class="BGSection">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS4.jpg'; ?>">
													<h5 class="elem_heading"><?php _e('Banner4', 'woocommerce-mobile-app-builder'); ?></h5>
													</span>
												</div>
											<?php } ?>
											</div>
										</div>
                                    </div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Banner-With Countdown Timer') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_05.jpg';
								
								//Get Countdown banners for selected layout
								$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", sanitize_text_field($valueComponent->id_component));
								$countdown_banners_rows = $wpdb->get_results($sql);
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-countdown-timer',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="banner_with_countdown[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_06.jpg'; ?> "/>-->
										<?php if (!empty($valueComponent->component_heading)) { ?>
                                        <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
										<?php } else { ?>
											<h4 class="comp_heading"><?php _e('Countdown Banner', 'woocommerce-mobile-app-builder'); ?></h4>
										<?php } ?>
										
										<?php
											if(isset($countdown_banners_rows) && !empty($countdown_banners_rows)){ 
												foreach ($countdown_banners_rows as $keyBanner => $valueBanner){
											?>
											<div class="countdownlist">
											<div class="countdownlistContent">
												<?php
												if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
													$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
												} else {
													$uploaded_image_path = plugins_url('/', __FILE__) . 'images/home_page_layout/'.$valueBanner->image_url;
												}
												?>
												<div class="countdownBackground" style="background:url('<?php echo $uploaded_image_path; ?>'); background-size: 100%;">
													<!--Please add bottom: 5px; in style tag if there is no banner title-->
													<div id="days"></div>
													<div class="countDownTimer" style="background:transparent;">
														<span class="timer">23 <?php _e('Hours', 'woocommerce-mobile-app-builder'); ?></span>
														<span class="timer">21 <?php _e('Minutes', 'woocommerce-mobile-app-builder'); ?></span>
														<span class="timer">49 <?php _e('Seconds', 'woocommerce-mobile-app-builder'); ?></span>
													</div>
												</div>
												<?php if (!empty($valueBanner->banner_heading)) { ?>
                                                                                            <h5 class="elem_heading"><?php echo stripslashes($valueBanner->banner_heading); ?></h5>
												<?php } ?>
											</div>
										</div>
											<?php 
												}
											} else {
											?>
										<div class="countdownlist">
											<div class="countdownlistContent">
												<div class="countdownBackground" style="background:url('<?php echo plugins_url('/', __FILE__) . 'images/layout/flash-sale.jpg'; ?>'); background-size: 100%;">
													<!--Please add bottom: 5px; in style tag if there is no banner title-->
													<div id="days"></div>
													<div class="countDownTimer" style="background:transparent;">
														<span class="timer">23 <?php _e('Hours', 'woocommerce-mobile-app-builder'); ?></span>
														<span class="timer">21 <?php _e('Minutes', 'woocommerce-mobile-app-builder'); ?></span>
														<span class="timer">49 <?php _e('Seconds', 'woocommerce-mobile-app-builder'); ?></span>
													</div>
												</div>
												<h5 class="elem_heading"><?php _e('Countdown1', 'woocommerce-mobile-app-builder'); ?></h5>
											</div>
										</div>
											<?php } ?>
									</div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Products-Square') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_06.jpg';
								$products_square_data = array();
								$products_square_sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC",$valueComponent->id_component);
								$products_square_details = $wpdb->get_results($products_square_sql);                                          
								foreach($products_square_details as $key_square_product => $value_square_product){                               
									if(isset($value_square_product->product_type) && !empty($value_square_product->product_type)){
										//Get product based on product type
										switch ($value_square_product->product_type){
											case 'new_products':
												//Pass General settings , number of products
												$products_square_data = $wmab_home_page->getRecentProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'best_seller':
												$products_square_data = $wmab_home_page->bestSellerProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'special_products':                                             
											   $products_square_data = $wmab_home_page->specialProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);                                            
												break;
											case 'featured_products':
												$products_square_data = $wmab_home_page->featuresProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break; 
											//BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
											case 'category_products':
												if(isset($value_square_product->category_products) && !empty($value_square_product->category_products)){
													$product_ids = explode(",", $value_square_product->category_products);
													$products_square_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												}
												break; 
											case 'custom_products':
													$product_ids = explode(",", $value_square_product->custom_products);
													$products_square_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												break; 
											//EOC
										}
									}
								}
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-square',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="product_square[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_07.jpg'; ?>"/>-->
										<div class="productSquare">
										
											<?php if (!empty($valueComponent->component_heading)) { ?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
												<h4 class="comp_heading"><?php _e('Products Square', 'woocommerce-mobile-app-builder'); ?></h4>
											<?php } ?>
											
											<?php
											if(isset($products_square_data) && !empty($products_square_data)){ 
												foreach ($products_square_data as $product_data){
											?>
											<div class="productSquareList">
												<img src="<?php echo $product_data['src']; ?>">
												<div class="productContent">
													<div class="productInfo">
														<h5><?php echo $product_data['name']; ?></h5>
														<h6 class="productPrice"><?php echo $product_data['price']; ?></h6>
													</div>
												</div>
											</div>
											<?php 
												}
											} else {
											?>
											<div class="productSquareList">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/new-products_1.jpg'; ?>">
												<div class="productContent">
													<div class="productInfo">
														<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
														<h6 class="productPrice">$100</h6>
													</div>
												</div>
											</div>
											<?php } ?>
										</div>
                                    </div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Products-Horizontal Sliding') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_07.jpg';
								$products_horizontal_data = array();
								$products_horizontal_sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC",$valueComponent->id_component);
								$products_horizontal_details = $wpdb->get_results($products_horizontal_sql);                                          
								foreach($products_horizontal_details as $key_square_product => $value_square_product){                               
									if(isset($value_square_product->product_type) && !empty($value_square_product->product_type)){
										//Get product based on product type
										switch ($value_square_product->product_type){
											case 'new_products':
												//Pass General settings , number of products
												$products_horizontal_data = $wmab_home_page->getRecentProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'best_seller':
												$products_horizontal_data = $wmab_home_page->bestSellerProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'special_products':                                             
											   $products_horizontal_data = $wmab_home_page->specialProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);                                            
												break;
											case 'featured_products':
												$products_horizontal_data = $wmab_home_page->featuresProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break; 
											//BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
											case 'category_products':
												if(isset($value_square_product->category_products) && !empty($value_square_product->category_products)){
													$product_ids = explode(",", $value_square_product->category_products);
													$products_horizontal_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												}
												break; 
											case 'custom_products':
													$product_ids = explode(",", $value_square_product->custom_products);
													$products_horizontal_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												break; 
											//EOC
										}
									}
								}
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-horiziontal-sliding',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="product_horizontal_sliding[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_08.jpg'; ?>"/>-->
										
											<?php if (!empty($valueComponent->component_heading)) { ?>
                                        <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
												<h4 class="comp_heading"><?php _e('Horizontal Products', 'woocommerce-mobile-app-builder'); ?></h4>
											<?php } ?>
										    <div class="slidingBannersScroll">
											<div class="slidingProducts">
												<?php
												if(isset($products_horizontal_data) && !empty($products_horizontal_data)){ 
													foreach ($products_horizontal_data as $product_data){
												?>
												<div class="productSlideList">
													<img src="<?php echo $product_data['src']; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php echo $product_data['name']; ?></h5>
															<h6 class="productPrice"><?php echo $product_data['price']; ?></h6>
														</div>
													</div>
												</div>
												<?php 
													}
												} else {
												?>											
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_1.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_2.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_3.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
											<?php } ?>
											</div>
										</div>
                                    </div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Products-Grid') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_08.jpg';
								$products_grid_data = array();
								$products_grid_sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC",$valueComponent->id_component);
								$products_grid_details = $wpdb->get_results($products_grid_sql);                                          
								foreach($products_grid_details as $key_square_product => $value_square_product){                               
									if(isset($value_square_product->product_type) && !empty($value_square_product->product_type)){
										//Get product based on product type
										switch ($value_square_product->product_type){
											case 'new_products':
												//Pass General settings , number of products
												$products_grid_data = $wmab_home_page->getRecentProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'best_seller':
												$products_grid_data = $wmab_home_page->bestSellerProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break;
											case 'special_products':                                             
											   $products_grid_data = $wmab_home_page->specialProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);                                            
												break;
											case 'featured_products':
												$products_grid_data = $wmab_home_page->featuresProduct($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode);
												break; 
											//BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
											case 'category_products':
												if(isset($value_square_product->category_products) && !empty($value_square_product->category_products)){
													$product_ids = explode(",", $value_square_product->category_products);
													$products_grid_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												}
												break; 
											case 'custom_products':
													$product_ids = explode(",", $value_square_product->custom_products);
													$products_grid_data = $wmab_home_page->getCustomProductsDetails($settings,$value_square_product->number_of_products,$value_square_product->image_content_mode,$product_ids);
												break; 
											//EOC
										}
									}
								}
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>
                                    <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-grid',<?php echo $valueComponent->id_component_type; ?>, this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-gear"></i></span>
                                    <input class="productTabContent" type="hidden" name="product_grid[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_09.jpg'; ?>"/>-->
										<div class="productGrid">	
											<?php if (!empty($valueComponent->component_heading)) { ?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
												<h4 class="comp_heading"><?php _e('Products Grid', 'woocommerce-mobile-app-builder'); ?></h4>
											<?php } ?>
											
											<div class="productGridRow">
												<?php
												if(isset($products_grid_data) && !empty($products_grid_data)){ 
													foreach ($products_grid_data as $product_data){
												?>
												<div class="productGridList">
													<img src="<?php echo $product_data['src']; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php echo $product_data['name']; ?></h5>
															<h6 class="productPrice"><?php echo $product_data['price']; ?></h6>
														</div>
													</div>
												</div>
												<?php 
													}
												} else {
												?>												
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_1.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_2.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productGridList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<?php } ?>
											</div>
										</div>
                                    </div>
                                </li>
							<?php
							} else if (trim($valueComponent->component_name) == 'Products-Last Accessed') {
								$image_url = plugins_url('/', __FILE__) . 'images/layout/mobile_09.jpg';
								//Pass General settings , number of products
								$products_recent_data = $wmab_home_page->getRecentProduct($settings, 3, 'scaleAspectFill');
							?>
                                <li class="slide">
                                    <span class="slideTitle"><?php echo trim($component_title); ?></span>
                                    <span class="trash" onclick="trashFunction(this)" attr-component-id="<?php echo $valueComponent->id_component; ?>"><i class="fa fa-trash"></i></span>                
                                    <input class="productTabContent edit-form-settings" type="hidden" name="product_last_accessed[]" value="<?php echo $valueComponent->id_component_type; ?>" attr-component-id="<?php echo $valueComponent->id_component; ?>"/>
                                    <div class="banner_preview layout_div" >
                                        <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_10.jpg'; ?> " />-->
										<div class="slidingBannersScroll">
											<?php if (!empty($valueComponent->component_heading)) { ?>
                                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueComponent->component_heading); ?></h4>
											<?php } else { ?>
												<h4 class="comp_heading"><?php _e('Recent Products', 'woocommerce-mobile-app-builder'); ?></h4>
											<?php } ?>
											
											<div class="slidingProducts">
												<?php
												if(isset($products_recent_data) && !empty($products_recent_data)){ 
													foreach ($products_recent_data as $product_data){
												?>
												<div class="productSlideList">
													<img src="<?php echo $product_data['src']; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php echo $product_data['name']; ?></h5>
															<h6 class="productPrice"><?php echo $product_data['price']; ?></h6>
														</div>
													</div>
												</div>
												<?php 
													}
												} else {
												?>											
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_1.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_2.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<div class="productSlideList">
													<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_3.jpg'; ?>">
													<div class="productContent">
														<div class="productInfo">
															<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
															<h6 class="productPrice">$100</h6>
														</div>
													</div>
												</div>
												<?php } ?>
											</div>
										</div>
                                    </div>
                                </li>
							<?php 
							}       
						}
					}
					?>
                </ul>
            </div>			
        </div>
        <div class="col-lg-4 col-md-5 col-sm-12">
            <div class="front_preview" >
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
                    <div class="iframe_html">
						<li class="slide">
							<div class="banner_preview layout_div" >
								<img src="<?php echo plugins_url('/', __FILE__); ?>images/home_pic.gif" alt="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" title="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" />
							</div>
						</li>
                    </div>
                </div>
				<div class="bottomHeader" <?php if (!empty($settings['general']['app_theme_color'])) { ?>style="background-color:<?php echo $settings['general']['app_theme_color']; ?>"<?php } ?>>
					<ul>
						<li>
							<i class="fa fa-home"></i>
							<span><?php _e('Home', 'woocommerce-mobile-app-builder'); ?></span>
						</li>
						<li>
							<i class="fa fa-cube"></i>
							<span><?php _e('Categories', 'woocommerce-mobile-app-builder'); ?></span>
						</li>
						<li>
							<i class="fa fa-search"></i>
							<span><?php _e('Search', 'woocommerce-mobile-app-builder'); ?></span>
						</li>
						<li>
							<i class="fa fa-shopping-cart"></i>
							<span><?php _e('Cart', 'woocommerce-mobile-app-builder'); ?></span>
						</li>
						<li>
							<i class="fa fa-lock"></i>
							<span><?php _e('Login', 'woocommerce-mobile-app-builder'); ?></span>
						</li>
					</ul>								
				</div>
            </div>			
        </div>
		<?php if (isset($components_details) && !empty($components_details)) { ?>
			<?php foreach ($components_details as $keyCategory => $valueCategory) { ?>
				<?php if (trim($valueCategory->component_name) == 'Top Categories') { ?>
                    <!--Dynamic HTML structure-->
                    <div class="top_category" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>                
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('top-category',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="top_category_input productTabContent" type="hidden" name="top_category[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_01.jpg'; ?>"/>-->
								<div class="topCategories">
									<ul>
										<li>
											<span class="catSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/1.jpg' ?>">
												<p><?php _e('Bag', 'woocommerce-mobile-app-builder'); ?></p>
											</span>	
										</li>
										<li>
											<span class="catSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/2.jpg' ?>">
												<p><?php _e('Shoes', 'woocommerce-mobile-app-builder'); ?></p>
											</span>	
										</li>
										<li>
											<span class="catSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/3.jpg' ?>">
												<p><?php _e('Shirt', 'woocommerce-mobile-app-builder'); ?></p>
											</span>	
										</li>
										<li>
											<span class="catSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/4.jpg' ?>">
												<p><?php _e('Watch', 'woocommerce-mobile-app-builder'); ?></p>
											</span>	
										</li>
									</ul>
								</div>
                            </div>
                        </li>
                    </div>
				<?php } ?>
                <!--BOC neeraj.kumar html for banner custom--> 
				<?php if (trim($valueCategory->component_name) == 'Banner-Custom') { ?>
                    <!--Dynamic HTML structure-->
                    <div class="banner-custom" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>                
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-custom',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="banner_custom[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg'; ?>"/>-->
								<div class="bannerSquare">
									<h4 class="comp_heading"><?php _e('Banner Custom', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="bannerSquareList">
										<span class="BSSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/banner-square_1.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Custom Banner', 'woocommerce-mobile-app-builder'); ?></h5>
										</span>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
				<?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Banner-Square') { ?>
                    <div class="banner-slide" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-sqaure',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="banner_square[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_02.jpg'; ?> "/>-->
								<div class="bannerSquare">
									<h4 class="comp_heading"><?php _e('Square Banner', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="bannerSquareList">
										<span class="BSSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/banner-square_1.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Square Design Banner', 'woocommerce-mobile-app-builder'); ?></h5>
										</span>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Banner-Horizontal Sliding') { ?>
                    <div class="Hbanner-slide" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-horizontal-sliding',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="banner_horizontal_sliding[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_03.jpg'; ?>"/>-->
								<div class="bannerHorizontalSlide">
									<h4 class="comp_heading"><?php _e('Horizontal Sliding Banner', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="slidingBannersScroll">
										<div class="slidingBanners">
											<div class="bannerHorizontalSlideList">
												<span class="BHSSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/deal_1.jpg'; ?>">
												<h5 class="elem_heading"><?php _e('Horizontal Design banner1', 'woocommerce-mobile-app-builder'); ?></h5>
												</span>
											</div>
											<div class="bannerHorizontalSlideList">
												<span class="BHSSection">
												<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/deal_2.jpg'; ?>">
												<h5 class="elem_heading"><?php _e('Horizontal Design banner2', 'woocommerce-mobile-app-builder'); ?></h5>
												</span>
											</div>
										</div>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Banner-Grid') { ?>
                    <div class="banner-grid" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-grid',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="banner_grid[]" value="<?php echo $valueCategory->id; ?>" attr-component-id="" />
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_04.jpg'; ?>"/>-->
								<div class="bannerGrid">						
									<h4 class="comp_heading"><?php _e('Grid Banner', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="bannerGridRow">
										<div class="bannerGridList">
											<span class="BGSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Banner1', 'woocommerce-mobile-app-builder'); ?></h5>
											</span>
										</div>
										<div class="bannerGridList">
											<span class="BGSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS2.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Banner2', 'woocommerce-mobile-app-builder'); ?></h5>
											</span>
										</div>
										<div class="bannerGridList">
											<span class="BGSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS3.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Banner3', 'woocommerce-mobile-app-builder'); ?></h5>
											</span>
										</div>
										<div class="bannerGridList">
											<span class="BGSection">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/BS4.jpg'; ?>">
											<h5 class="elem_heading"><?php _e('Banner4', 'woocommerce-mobile-app-builder'); ?></h5>
											</span>
										</div>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Banner-With Countdown Timer') { ?>
                    <div class="banner-countdown" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('banner-countdown-timer',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="banner_with_countdown[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_06.jpg'; ?> "/>-->
								<h4 class="comp_heading"><?php _e('Countdown Banner', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="countdownlist">
										<div class="countdownlistContent">
											<div class="countdownBackground" style="background:url('<?php echo plugins_url('/', __FILE__) . 'images/layout/flash-sale.jpg'; ?>'); background-size: 100%;">
												<!--Please add bottom: 5px; in style tag if there is no banner title-->
												<div id="days"></div>
												<div class="countDownTimer" style="background:transparent;">
													<span class="timer">23 <?php _e('Hours', 'woocommerce-mobile-app-builder'); ?></span>
													<span class="timer">21 <?php _e('Minutes', 'woocommerce-mobile-app-builder'); ?></span>
													<span class="timer">49 <?php _e('Seconds', 'woocommerce-mobile-app-builder'); ?></span>
												</div>
											</div>
											<h5 class="elem_heading"><?php _e('Countdown1', 'woocommerce-mobile-app-builder'); ?></h5>
										</div>
									</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Products-Square') { ?>
                    <div class="product-square" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-square',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="product_square[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_07.jpg'; ?>"/>-->
								<div class="productSquare">
									<h4 class="comp_heading"><?php _e('Products Square', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="productSquareList">
										<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/new-products_1.jpg'; ?>">
										<div class="productContent">
											<div class="productInfo">
												<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
												<h6 class="productPrice">$100</h6>
											</div>
											<div class="wishlistProduct">
												<i class="fa fa-heart-o"></i>
											</div>
										</div>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Products-Horizontal Sliding') { ?>
                    <div class="Hproduct-slide" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-horiziontal-sliding',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="product_horizontal_sliding[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_08.jpg'; ?>"/>-->
								<div class="slidingBannersScroll">
									<h4 class="comp_heading"><?php _e('Horizontal Products', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="slidingProducts">							
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_1.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_2.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_3.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Products-Grid') { ?>
                    <div class="product-grid" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>
                            <span class="settings margin-5 edit-form-settings" onclick="settingFunction('product-grid',<?php echo $valueCategory->id; ?>, this)" attr-component-id=""><i class="fa fa-gear"></i></span>
                            <input class="productTabContent" type="hidden" name="product_grid[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_09.jpg'; ?>"/>-->
								<div class="productGrid">	
									<h4 class="comp_heading"><?php _e('Products Grid', 'woocommerce-mobile-app-builder'); ?></h4>
									<div class="productGridRow">
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_1.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_2.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_3.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
										<div class="productGridList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/latest-product_4.jpg'; ?>" class="mCS_img_loaded">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
												<div class="wishlistProduct">
													<i class="fa fa-heart-o"></i>
												</div>
											</div>
										</div>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
                <?php } ?>
                <?php if (trim($valueCategory->component_name) == 'Products-Last Accessed') {
						//Pass General settings , number of products
						$products_recent_data = $wmab_home_page->getRecentProduct($settings, 3, 'scaleAspectFill');
				?>
                    <div class="product-lastAccess" style="display:none;">
                        <li class="slide">
                            <span class="slideTitle"><?php echo trim($valueCategory->component_name); ?></span>
                            <span class="trash" onclick="trashFunction(this)" attr-component-id=""><i class="fa fa-trash"></i></span>                
                            <input class="productTabContent edit-form-settings" type="hidden" name="product_last_accessed[]" value="<?php echo $valueCategory->id; ?>" attr-component-id=""/>
                            <div class="banner_preview layout_div" >
                                <!--<img class="banner_preview_image" src="<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile_10.jpg'; ?> " />-->
								<div class="slidingBannersScroll">
									<?php if (!empty($valueCategory->component_heading)) { ?>
                                                                    <h4 class="comp_heading"><?php echo stripslashes($valueCategory->component_heading); ?></h4>
									<?php } else { ?>
										<h4 class="comp_heading"><?php _e('Recent Products', 'woocommerce-mobile-app-builder'); ?></h4>
									<?php } ?>
									<div class="slidingProducts">
										<?php
										if(isset($products_recent_data) && !empty($products_recent_data)){ 
											foreach ($products_recent_data as $product_data){
										?>
										<div class="productSlideList">
											<img src="<?php echo $product_data['src']; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php echo $product_data['name']; ?></h5>
													<h6 class="productPrice"><?php echo $product_data['price']; ?></h6>
												</div>
											</div>
										</div>
										<?php 
											}
										} else {
										?>											
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_1.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
											</div>
										</div>
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_2.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
											</div>
										</div>
										<div class="productSlideList">
											<img src="<?php echo plugins_url('/', __FILE__) . 'images/layout/best-seller-product_3.jpg'; ?>">
											<div class="productContent">
												<div class="productInfo">
													<h5><?php _e('Hair Gel', 'woocommerce-mobile-app-builder'); ?></h5>
													<h6 class="productPrice">$100</h6>
												</div>
											</div>
										</div>
										<?php } ?>
									</div>
								</div>
                            </div>
                        </li>
                    </div>
        <?php } ?>
    <?php }
} ?>
                    <div class="modal loder-modal" style="z-index: 1500 !important;"><!-- Place at bottom of page --></div>
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
                    url('<?php echo plugins_url('/', __FILE__) . "images/loader.gif"; ?>') 
                    50% 50% 
                    no-repeat;
            }

        </style>

        <script>
		var $ = jQuery.noConflict();
		$(document).ready(function () {
			$('#top_category').click(function () {
				var top_category_html = $('.top_category').html();
				$('.slides').append(top_category_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#banner_custom').click(function () {
				var banner_custom_html = $('.banner-custom').html();
				$('.slides').append(banner_custom_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});

			$('#banner_square').click(function () {
				var banner_square_html = $('.banner-slide').html();
				$('.slides').append(banner_square_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#banner_HS').click(function () {
				var Hbanner_square_html = $('.Hbanner-slide').html();
				$('.slides').append(Hbanner_square_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#banner_grid').click(function () {
				var banner_Grid_html = $('.banner-grid').html();
				$('.slides').append(banner_Grid_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#banner_countdown').click(function () {
				var banner_countdown_html = $('.banner-countdown').html();
				$('.slides').append(banner_countdown_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#product_square').click(function () {
				var product_square_html = $('.product-square').html();
				$('.slides').append(product_square_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#product_HS').click(function () {
				var Hproduct_slide_html = $('.Hproduct-slide').html();
				$('.slides').append(Hproduct_slide_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#product_grid').click(function () {
				var product_Grid_html = $('.product-grid').html();
				$('.slides').append(product_Grid_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
			$('#product_LA').click(function () {
				var product_lastAccess_html = $('.product-lastAccess').html();
				$('.slides').append(product_lastAccess_html);
				preview_content();
				scrollToBottom();
				//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
				save_layout_component_order();
				//EOC
			});
		});

		/**
		 * BOC neeraj.kumar@velsof.com 20-Dec-2019 Module Upgrade V2
		 * @returns {null}
		 */
		function save_layout_component_order() {
//                                        $('.productTabs').find('.productTabContent').attr('name',)
			$(".loder-modal").show();
			var layout_ids = new Array();
			$('.panel-default').find('input').each(function () {
				var field_contents = $(this)[0];
				var component_array = {};
				var input_value = $(field_contents).attr('value');
				var component_id = $(field_contents).attr('attr-component-id');
				component_array['component_id'] = component_id;
				component_array['component_type'] = input_value;
				layout_ids.push(component_array);
			});
			//Send Ajax Request to saved layput component mapping                                                
			$.ajax({
				url: ajaxurl,
				type: 'post',
				data: {
					'action': 'wmab_save_layout_component_order',
					'layout_category_type': layout_ids,
					'layout_id': <?php echo $layout_id; ?>,
				},
				dataType: 'json',
				success: function (response) {
					$(".loder-modal").hide();
					if (response.response) {
						var iterate = 0;
						$('.panel-default').find('input').each(function () {
							var field_contents = $(this)[0];
							$(field_contents).attr('attr-component-id', response.component_ids[iterate]);
							iterate++;
						});
						var iterate = 0;
						$('.panel-default').find('.trash').each(function () {
							var field_contents = $(this)[0];
							$(field_contents).attr('attr-component-id', response.component_ids[iterate]);
							iterate++;
						});
						var iterate = 0;
						$('.panel-default').find('.edit-form-settings').each(function () {
							var field_contents = $(this)[0];
							$(field_contents).attr('attr-component-id', response.component_ids[iterate]);
							iterate++;
						});
					} else {

					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					$(".loder-modal").hide();
				}
			});
		}

		function trashFunction(trash) {
			var trash_id = $(trash).attr('attr-component-id');
			//Send Ajax Request to saved layput component mapping                                                
			$.ajax({
				url: ajaxurl,
				type: 'post',
				data: {
					'action': 'wmab_delete_layout_component_order',
					'component_id_delete': trash_id,
				},
				dataType: 'json',
				success: function (response) {
					if (response) {
						$(trash).parents('.slide').remove();
						preview_content();
					}
				}
			});


		}
		function preview_content() {
			$('.iframe_html').html('');
			var Display_content = $('.slides').html();
			if ($.trim(Display_content) != '') {
				$('.iframe_html').append(Display_content);
			} else {
				var default_html = '<li class="slide"><div class="banner_preview layout_div" ><img src="<?php echo plugins_url('/', __FILE__); ?>images/home_pic.gif" alt="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" title="<?php _e('Mobile App Preview', 'woocommerce-mobile-app-builder'); ?>" /></div></li>';
				$('.iframe_html').append(default_html);
			}
		}
		
		$(document).ready(function () {
			$(".slides").sortable({
				placeholder: 'slide-placeholder',
				axis: "y",
				revert: 150,
				start: function (e, ui) {

					placeholderHeight = ui.item.outerHeight();
					ui.placeholder.height(placeholderHeight + 15);
					$('<div class="slide-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);

				},
				change: function (event, ui) {

					ui.placeholder.stop().height(0).animate({
						height: ui.item.outerHeight() + 15
					}, 300);

					placeholderAnimatorHeight = parseInt($(".slide-placeholder-animator").attr("data-height"));

					$(".slide-placeholder-animator").stop().height(placeholderAnimatorHeight + 15).animate({
						height: 0
					}, 300, function () {
						$(this).remove();
						placeholderHeight = ui.item.outerHeight();
						$('<div class="slide-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);
					});
				},
				stop: function (e, ui) {
					$(".slide-placeholder-animator").remove();
					preview_content();
					//BOC neeraj.kumar@veslof.com 20-Dec-2019 Module Upgrade V2 saved details on DB with correct order
					save_layout_component_order();
					//EOC
				},
			});
		});

		<?php if (isset($component_details) && !empty($component_details)) { ?>
		preview_content();
		<?php } ?>

        </script>
        <style>
            .slide-placeholder {
                background: #DADADA;
                position: relative;
                list-style:none;
            }

            .slide-placeholder:after {
                content: " ";
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 15px;
                background-color: #FFF;
            }
            li.slide {
                list-style: none;
                background: #efefef;
                padding: 10px;
                border: 1px dashed #ccc;
                position: relative;
                margin-bottom: 5px;
                cursor: move;
            }
            .iframe_html li.slide {
                cursor: inherit;
            }
            ul.slides {
                padding: 0;
            }
            span.settings {
                position: absolute;
                right: 10px;
                cursor: pointer;
                width: 20px;
                text-align: center;
            }
            .file-uploader {
                padding: 15px 10px;
                background: #f9f9f9;
                margin-top: 10px;
                border: 1px solid #efefef;
                display:none;
            }
            .file-uploader label {
                padding-bottom: 10px;
            }
            .front_preview{
                background: url(<?php echo plugins_url('/', __FILE__) . 'images/layout/mobile.png'; ?>);
                background-repeat: no-repeat;
                background-position: top center;
                text-align: center;
                padding: 65px 0 80px;
            }
            .iframe_html {
                width: 100%;
                /*height: 478px;			
                overflow: auto;
                -ms-overflow-style: none;
                overflow: -moz-scrollbars-none;*/
                max-width: 292px;
                margin: 0 auto;
                min-height: 484px;
            }
            .layout_div{margin-bottom:0px;}
            .slides .layout_div {
                display: none;
            }
            .iframe_html .slideTitle{display:none;}
            .iframe_html .slide {
                list-style: none;
                background: transparent;
                padding: 0px;
                border: 0px dashed #ccc;
                position: relative;
                margin-bottom: 0px;
            }
            .trash {
                position: absolute;
                right: 15px;
                width: 20px;
                text-align: center;
                cursor: pointer;
            }
            .iframe_html .trash {
                display: none;
            }
            .iframe_html .settings {
                display: none;
            }
            .layout_gallery {
                max-height: 476px;
                max-width: 292px;
                margin: 0 auto;
            }
            .layout_gallery .mCSB_inside > .mCSB_container {
                margin-right: 10px;
            }
            .layout_gallery .mCSB_scrollTools {
                position: absolute;
                width: 10px;
            }
            .list-group-item .fa-plus {
                position: absolute;
                right: 10px;
                top: 13px;
            }
        </style>	
        <script>
            (function ($) {
                $(window).on("load", function () {

                    //$(".layout_gallery").mCustomScrollbar({
                    //	theme:"dark"
                    //});
                    scrollToBottom();
                });
            })(jQuery);

            function scrollToBottom() {

                var content = $(".layout_gallery"), autoScrollTimer = 200, autoScrollTimerAdjust, autoScroll;

                content.mCustomScrollbar({
                    scrollButtons: {enable: true},
                    theme: "dark",
                    callbacks: {
                        whileScrolling: function () {
                            autoScrollTimerAdjust = autoScrollTimer * this.mcs.topPct / 100;
                            privateTop = this.mcs.topPct;
                            if (privateTop >= 90) {
                                $('.goToLastMessage').hide();
                                count = 0;
                            }

                        },
                        onScroll: function () {
                            if ($(this).data("mCS").trigger === "internal") {
                                AutoScrollOff();

                            }
                        }
                    }
                });

                content.addClass("auto-scrolling-on auto-scrolling-to-bottom");
                AutoScrollOn("bottom");

                function AutoScrollOn(to, timer) {

                    if (!timer) {
                        timer = autoScrollTimer;
                    }
                    content.addClass("auto-scrolling-on").mCustomScrollbar("scrollTo", to, {scrollInertia: timer, scrollEasing: "easeInOutSmooth"});

                }
                function AutoScrollOff() {
                    clearTimeout(autoScroll);
                    content.removeClass("auto-scrolling-on").mCustomScrollbar("stop");
                }

            }
        </script>
    </div>
</div>

<!-- The Top Category Modal -->
<div id="top-category" class="modal">
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" id="top-category-span">&times;</span>
            <h4 class="modal-title">Edit Component</h4>       
        </div>
        <div class="modal-body">
            <form id="slider2_form" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="" name="top-products-form" action="admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $layout_id; ?>">              
                <input type="hidden" name="mab_layout_id" id="mab_top_category_layout_id" value="<?php $layout_id; ?>" attr-layout_id = "<?php $layout_id; ?>">
                <input type="hidden" name="mab_component_id" id="mab_top_category_component_id" class="mab_component_id" value="">
                <!--Hardcoded set total category 4 so, user allow atleast 4 category-->
                <input type="hidden" name="mab_total_category" id="mab_total_category" value="4">
                <div class="panel modal-panel" id="fieldset_0">
                    <div class="panel-heading">
                        Settings
                    </div>
                    <div class="form-wrapper">                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="">
                                    Title of this Component
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="top_category_component_title" name="component_title" class="" value="">
                                        </div>                                      
                                    </div>                                                                    
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the Activity where you have to redirect the customer after click.
                                      ">
                                    Image content Mode
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="image_content_mode" class="chosen-dropdown fixed-width-xl" id="image_content_mode1">
                                    <option value="scaleAspectFill" selected="selected">Scale aspect fill</option>

                                    <option value="scaleAspectFit">Scale aspect Fit</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 1st Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_1" class=" fixed-width-xl" id="category_id_1" onchange="setCategoryId(this)">
                                    <option value="">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_1-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_1" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_1" id="hiddensliderimage_1" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_1" type="file" name="slideruploadedfile_1" class="hide">
                                        <div class="dummyfile input-group" id="image-upload-div-1">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_1-name" type="text" name="filename_1" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_1-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_1-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_1').trigger('click');
                                        });

                                        $('#slideruploadedfile_1-name').click(function (e) {
                                            $('#slideruploadedfile_1').trigger('click');
                                        });

                                        $('#slideruploadedfile_1-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_1-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_1-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_1')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_1').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_1-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_1').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_1-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_1_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_1').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_1')[0].files.length > slideruploadedfile_1_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category1
                                      ">
                                    Heading of Category1
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_1" name="category_heading_1" class="" value="">
                                        </div>                                      
                                    </div>                                  
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 2nd Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_2" class=" fixed-width-xl" id="category_id_2" onchange="setCategoryId(this)">
                                    <option value="">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>

                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_2-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_2" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_2" id="hiddensliderimage_2" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_2" type="file" name="slideruploadedfile_2" class="hide">
                                        <div class="dummyfile input-group" id="image-upload-div-2">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_2-name" type="text" name="filename_2" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_2-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_2-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_2').trigger('click');
                                        });

                                        $('#slideruploadedfile_2-name').click(function (e) {
                                            $('#slideruploadedfile_2').trigger('click');
                                        });

                                        $('#slideruploadedfile_2-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_2-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_2-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_2')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_2').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_2-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_2').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_2-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_2_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_2').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_2')[0].files.length > slideruploadedfile_2_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category2
                                      ">
                                    Heading of Category2
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_2" name="category_heading_2" class="" value="">
                                        </div>                                      
                                    </div>                                  
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="Select the category type">
                                    Select 3rd Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_3" class=" fixed-width-xl" id="category_id_3" onchange="setCategoryId(this)">
                                    <option value="">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_3-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_3" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_3" id="hiddensliderimage_3" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_3" type="file" name="slideruploadedfile_3" class="hide">
                                        <div class="dummyfile input-group" id="image-upload-div-3">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_3-name" type="text" name="filename_3" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_3-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_3-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_3').trigger('click');
                                        });

                                        $('#slideruploadedfile_3-name').click(function (e) {
                                            $('#slideruploadedfile_3').trigger('click');
                                        });

                                        $('#slideruploadedfile_3-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_3-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_3-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_3')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_3').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_3-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_3').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_3-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_3_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_3').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_3')[0].files.length > slideruploadedfile_3_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="Enter Heading of Category3">
                                    Heading of Category3
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_3" name="category_heading_3" class="" value="">
                                        </div>                                      
                                    </div>                                  
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 4th Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_4" class=" fixed-width-xl" id="category_id_4" onchange="setCategoryId(this)">
                                    <option value="">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_4-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_4" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_4" id="hiddensliderimage_4" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_4" type="file" name="slideruploadedfile_4" class="hide">
                                        <div class="dummyfile input-group" id="image-upload-div-4">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_4-name" type="text" name="filename_4" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_4-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_4-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_4').trigger('click');
                                        });

                                        $('#slideruploadedfile_4-name').click(function (e) {
                                            $('#slideruploadedfile_4').trigger('click');
                                        });

                                        $('#slideruploadedfile_4-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_4-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_4-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_4')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_4').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_4-name').val(name.slice(0, -2));

                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_4').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_4-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_4_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_4').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_4')[0].files.length > slideruploadedfile_4_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category4
                                      ">
                                    Heading of Category4
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_4" name="category_heading_4" class="" value="">
                                        </div>                                     
                                    </div>                                                                                                      
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 5th Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_5" class=" fixed-width-xl" id="category_id_5" onchange="setCategoryId(this)">
                                    <option value="" selected="selected">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_5-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_5" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_5" id="hiddensliderimage_5" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_5" type="file" name="slideruploadedfile_5" class="hide">
                                        <div class="dummyfile input-group">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_5-name" type="text" name="filename_5" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_5-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_5-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_5').trigger('click');
                                        });

                                        $('#slideruploadedfile_5-name').click(function (e) {
                                            $('#slideruploadedfile_5').trigger('click');
                                        });

                                        $('#slideruploadedfile_5-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_5-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_5-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_5')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_5').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_5-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_5').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_5-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_5_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_5').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_5')[0].files.length > slideruploadedfile_5_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category5
                                      ">
                                    Heading of Category5
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_5" name="category_heading_5" class="" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 6th Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_6" class=" fixed-width-xl" id="category_id_6" onchange="setCategoryId(this)">
                                    <option value="" selected="selected">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_6-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_6" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_6" id="hiddensliderimage_6" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_6" type="file" name="slideruploadedfile_6" class="hide">
                                        <div class="dummyfile input-group">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_6-name" type="text" name="filename_6" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_6-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_6-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_6').trigger('click');
                                        });

                                        $('#slideruploadedfile_6-name').click(function (e) {
                                            $('#slideruploadedfile_6').trigger('click');
                                        });

                                        $('#slideruploadedfile_6-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_6-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_6-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_6')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_6').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_6-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_6').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_6-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_6_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_6').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_6')[0].files.length > slideruploadedfile_6_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category6
                                      ">
                                    Heading of Category6
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_6" name="category_heading_6" class="" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 7th Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_7" class=" fixed-width-xl" id="category_id_7" onchange="setCategoryId(this)">
                                    <option value="" selected="selected">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_7-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_7" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_7" id="hiddensliderimage_7" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_7" type="file" name="slideruploadedfile_7" class="hide">
                                        <div class="dummyfile input-group">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_7-name" type="text" name="filename_7" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_7-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file				</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_7-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_7').trigger('click');
                                        });

                                        $('#slideruploadedfile_7-name').click(function (e) {
                                            $('#slideruploadedfile_7').trigger('click');
                                        });

                                        $('#slideruploadedfile_7-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_7-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_7-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_7')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_7').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_7-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_7').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_7-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_7_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_7').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_7')[0].files.length > slideruploadedfile_7_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category7
                                      ">
                                    Heading of Category7
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_7" name="category_heading_7" class="" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select 8th Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id_8" class=" fixed-width-xl" id="category_id_8" onchange="setCategoryId(this)">
                                    <option value="" selected="selected">Select the category type</option>
<?php foreach ($product_categories as $product_category) { ?>                                  
                                        <option value="<?php echo $product_category->term_id; ?>" ><?php echo $product_category->name; ?></option>                                  
<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Image:
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="col-lg-12" id="slideruploadedfile_8-images-thumbnails">
                                        <div>
                                            <img id="sliderimage_8" class="category_image_class" src="" max-width="200px;" height="100px;">
                                            <input type="hidden" name="hiddensliderimage_8" id="hiddensliderimage_8" value="">
                                            <p style="display: none;">
                                                <a class="btn btn-default" href="">
                                                    <i class="icon-trash"></i> Delete
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <input id="slideruploadedfile_8" type="file" name="slideruploadedfile_8" class="hide">
                                        <div class="dummyfile input-group">
                                            <span class="input-group-addon width-10"><i class="dashicons dashicons-media-default"></i></span>
                                            <input id="slideruploadedfile_8-name" type="text" name="filename_8" readonly="" value="">
                                            <span class="input-group-btn">
                                                <button id="slideruploadedfile_8-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                                    <i class="icon-folder-open"></i> Add file</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">

                                    $(document).ready(function () {
                                        $('#slideruploadedfile_8-selectbutton').click(function (e) {
                                            $('#slideruploadedfile_8').trigger('click');
                                        });

                                        $('#slideruploadedfile_8-name').click(function (e) {
                                            $('#slideruploadedfile_8').trigger('click');
                                        });

                                        $('#slideruploadedfile_8-name').on('dragenter', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_8-name').on('dragover', function (e) {
                                            e.stopPropagation();
                                            e.preventDefault();
                                        });

                                        $('#slideruploadedfile_8-name').on('drop', function (e) {
                                            e.preventDefault();
                                            var files = e.originalEvent.dataTransfer.files;
                                            $('#slideruploadedfile_8')[0].files = files;
                                            $(this).val(files[0].name);
                                        });

                                        $('#slideruploadedfile_8').change(function (e) {
                                            if ($(this)[0].files !== undefined)
                                            {
                                                var files = $(this)[0].files;
                                                var name = '';

                                                $.each(files, function (index, value) {
                                                    name += value.name + ', ';
                                                });

                                                $('#slideruploadedfile_8-name').val(name.slice(0, -2));
                                                var reader = new FileReader();
                                                reader.onload = function (f) {
                                                    $('#sliderimage_8').attr('src', f.target.result);
                                                }
                                                reader.readAsDataURL($(this)[0].files[0]);
                                            } else // Internet Explorer 9 Compatibility
                                            {
                                                var name = $(this).val().split(/[\\/]/);
                                                $('#slideruploadedfile_8-name').val(name[name.length - 1]);
                                            }
                                        });

                                        if (typeof slideruploadedfile_8_max_files !== 'undefined')
                                        {
                                            $('#slideruploadedfile_8').closest('form').on('submit', function (e) {
                                                if ($('#slideruploadedfile_8')[0].files.length > slideruploadedfile_8_max_files) {
                                                    e.preventDefault();
                                                    alert('You can upload a maximum of  files');
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Enter Heading of Category8
                                      ">
                                    Heading of Category8
                                </span>
                            </label>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="category_heading_8" name="category_heading_8" class="" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.form-wrapper -->
                    <button type="submit" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitTopCategoryForms" onclick="return veloValidateTopcategoryForm(this)">Save</button>
                </div>
            </form>          
        </div>
    </div>
</div>


<!--Product Grid Category-->
<div id="product-grid" class="modal" >
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" id="product-grid-span">&times;</span>
            <h4 class="modal-title">Edit Component</h4>       
        </div>
        <div class="modal-body">
            <form id="slider2_form" action="admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $layout_id; ?>" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="" name="product-form">              
                <input type="hidden" name="mab_layout_id" id="mab_product_grid_layout_id" value="<?php $layout_id; ?>" attr-layout_id = "<?php $layout_id; ?>">
                <input type="hidden" name="mab_component_id" id="mab_product_grid_component_id" class="mab_component_id" value="">
                <div class="panel modal-panel" id="fieldset_0">
                    <div class="panel-heading">
                        Settings
                    </div>
                    <div class="form-wrapper">
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="">
                                    Title of this Component
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="product_grid_component_title" name="component_title" class="" value="">
                                        </div>                                      
                                    </div>                                                                    
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="">
                                    Heading of this Component
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <div class="form-group">
                                    <div class="translatable-field lang-1">
                                        <div class="col-lg-9">
                                            <input type="text" id="component_heading_component" name="component_heading_1" class="" value="">
                                        </div>                                      
                                    </div>                                                                    
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="s">
                                    Select Product Type
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="product_type" class="chosen-dropdown fixed-width-xl" id="product_type" onchange="showHideProductType(this)">
                                    <option value="best_seller">Best Seller Products</option>
                                    <option value="featured_products">Featured Products</option>
                                    <option value="new_products">New Products</option>
                                    <option value="special_products">Special Products</option>
                                    <option value="category_products">From a category</option>
                                    <option value="custom_products">Custom Products</option>
                                </select>
                            </div>
                        </div>
                        <!--Custom Product Added--> 
                        <div class="form-group" style="display: none;">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select product from the list  to be shown in the special product block
                                      ">
                                    Select Custom Product
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="product_list[]" class="chosen fixed-width-xl select_style" id="product_list" multiple="multiple">
                                    
                                </select>
                            </div>
                        </div>
                        <!--Select From Category-->
                        <div class="form-group" style="display: none;">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select the category type
                                      ">
                                    Select the Category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_id" class=" fixed-width-xl select_style" id="category_id" onchange="getCategoryproducts(this)">
                                    <option value="0" selected="selected">Select the category type</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="													Select product from the list  to be shown in the special product block
                                      ">
                                    Select products from category
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <select name="category_products[]" class="chosen fixed-width-xl select_style" id="category_products" multiple="multiple">
                                   
                                </select>
                            </div>
                        </div>
                        <!--End Select From Category-->
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="">
                                    Number of products
                                </span>
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="number_of_products" id="number_of_products" value="" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="">
                                    Image content Mode
                                </span>
                            </label>
                            <div class="col-lg-6">
                                <select name="image_content_mode" class="chosen-dropdown fixed-width-xl" id="image_content_mode">
                                    <option value="scaleAspectFill">Scale aspect fill</option>
                                    <option value="scaleAspectFit">Scale aspect Fit</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitProductsOptions" onclick="return veloValidateProductGrid(this)">Save</button>
                </div>
            </form>          
        </div>
    </div>
</div>

<!--Banner Model-->
<div id="banner-sqaure" class="modal" style="width: 99%;height: auto;left: 6%;">
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" id="banner-sqaure-span">&times;</span>
            <h4 class="modal-title">Edit Component</h4>       
        </div>
        <div class="modal-body">
            <form id="slider2_form" action="admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $layout_id; ?>" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="" name="product-form">              
                <input type="hidden" name="mab_layout_id" id="mab_banner_layout_id" value="<?php $layout_id; ?>" attr-layout_id = "<?php $layout_id; ?>">
                <input type="hidden" name="mab_component_id" id="mab_banner_component_id" class="mab_component_id" value="">
                <div class="panel modal-panel" id="fieldset_0" style="padding-bottom: 5%;">
                    <div class="panel-heading">
                        Settings
                        <div style="float: right;height: 36px;margin-top: -14px;">      
                            <label>Component Title</label>
                            <input type="text" name="edit_component_title" style="height: 30px;" value="" id="banner_sqaure_component_title" />
                            <button type="submit" style="margin-top: 0px;margin-left: 5px;height: 30px;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitComponentTitle">Save</button>
                        </div>
                    </div>
                    <div class="wrapper-class">
                        <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                            <thead>
                                <tr>
                                    <th><?php echo _e('Component Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Subactivity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Image Content Mode', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Image', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="banner_last_row">
                                    <td colspan="6"></td>
                                    <td>
                                        <input type="button" name="add_banner" id="add_banner" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" style="margin-top: 1%;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitBannerOptions" onclick="return veloValidateBanner(this)">Save</button>
                </div>
            </form>          
        </div>
    </div>
</div>

<!--neeraj.kumar@velsof.com Banner-Custom 7-Jan-2019-->
<!--Banner Custom Model-->
<div id="banner-custom" class="modal" style="width: 99%;height: auto;left: 6%;">
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" id="banner-custom-span">&times;</span>
            <h4 class="modal-title">Edit Component</h4>       
        </div>
        <div class="modal-body">
            <form id="slider2_form" action="admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $layout_id; ?>" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="" name="product-form">              
                <input type="hidden" name="mab_layout_id" id="mab_banner_custom_layout_id" value="<?php $layout_id; ?>" attr-layout_id = "<?php $layout_id; ?>">
                <input type="hidden" name="mab_component_id" id="mab_banner_custom_component_id" class="mab_component_id" value="">
                <div class="panel modal-panel" id="fieldset_0" style="padding-bottom: 5%;">
                    <div class="panel-heading">
                        Settings
                        <div style="float: right;height: 36px;margin-top: -14px;">      
                            <label>Component Title</label>
                            <input type="text" name="edit_component_title" style="height: 30px;" value="" id="banner_custom_component_title" />
                            <button type="submit" style="margin-top: 0px;margin-left: 5px;height: 30px;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitComponentTitle">Save</button>
                        </div>
                    </div>
                    <div class="wrapper-class">
                        <table width="100%" border="0" cellpadding="5" cellspacing="5" class="mab-table">
                            <thead>
                                <tr>
                                    <th><?php echo _e('Component Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Subactivity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Image Content Mode', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Image', 'woocommerce-mobile-app-builder'); ?></th>

                                    <th><?php echo _e('Banner Width', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Height', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Inset Top', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Inset Bottom', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Inset Left', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Inset Right', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Background Color', 'woocommerce-mobile-app-builder'); ?></th>

                                    <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="custom_banner_last_row">
                                    <td colspan="13"></td>
                                    <td>
                                        <input type="button" name="add_custom_banner" id="add_custom_banner" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" style="margin-top: 1%;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitBannerCustomOptions" onclick="return veloValidateCustomBanner(this)">Save</button>
                </div>
            </form>          
        </div>
    </div>
</div>
<!--Banner CountDown Model-->
<div id="banner-countdown-timer" class="modal" style="width: 99%;height: auto;left: 6%;">
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" id="banner-countdown-timer-span">&times;</span>
            <h4 class="modal-title">Edit Component</h4>       
        </div>
        <div class="modal-body">
            <form id="slider2_form" action="admin.php?page=mobile-app-builder&render_page=mab-home-layout-page&layout_id=<?php echo $layout_id; ?>" class="defaultForm form-horizontal kbmobileapp" method="post" enctype="multipart/form-data" novalidate="" name="product-form">              
                <input type="hidden" name="mab_layout_id" id="mab_banner_countdown_layout_id" value="<?php $layout_id; ?>" attr-layout_id = "<?php $layout_id; ?>">
                <input type="hidden" name="mab_component_id" id="mab_banner_countdown_component_id" class="mab_component_id" value="">
                <div class="panel modal-panel" id="fieldset_0" style="padding-bottom: 6%;">
                    <div class="panel-heading">
                        Settings
                        <div style="float: right;height: 36px;margin-top: -14px;">      
                            <label>Component Title</label>
                            <input type="text" name="edit_component_title" style="height: 30px;" value="" id="banner_countdown_timer_component_title" />
                            <button type="submit" style="margin-top: 0px;margin-left: 5px;height: 30px;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitComponentTitle">Save</button>
                        </div>
                    </div>
                    <div class="wrapper-class">
                        <table width="100%" border="0" cellpadding="5" cellspacing="5" class="full-width-table mab-table">
                            <thead>
                                <tr>
                                    <th><?php echo _e('Component Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Heading', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Activity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Redirect Subactivity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Image Content Mode', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Banner Image', 'woocommerce-mobile-app-builder'); ?></th>

                                    <th><?php echo _e('Countdown Time validity', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Enable background color', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Timer Background Colour', 'woocommerce-mobile-app-builder'); ?></th>
                                    <th><?php echo _e('Timer text colour', 'woocommerce-mobile-app-builder'); ?></th>

                                    <th><?php echo _e('Action', 'woocommerce-mobile-app-builder'); ?></th>                                
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="banner_countdown_last_row">                                
                                    <td colspan="10"></td>
                                    <td>
                                        <input type="button" name="add_countdown_timer" id="add_countdown_timer" value="<?php echo _e('Add New', 'woocommerce-mobile-app-builder'); ?>" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" style="margin-top: 2%;" class="btn btn-default btn btn-default pull-right kb_slider_banner_setting_btn margin-neg4" name="submitHomePageLayout"  value="submitBannerCountDownOptions" onclick="return veloValidateBannerCountdown(this)">Save</button>
                </div>
            </form>          
        </div>
    </div>
</div>


<!--BOC neeraj.kumar@velsof.com 19-Dec-2019 added color picker library-->
<input type="hidden" name="colorpicker_image_path" id="colorpicker_image_path" value="<?php echo plugins_url('/', __FILE__). '/images/'; ?>" />

<script>

    function settingFunction(modal_id, category_id = '', element) {
                            var component_id = $(element).attr('attr-component-id');
                            if (modal_id == 'top-category') {
                                $('#mab_top_category_component_id').val(component_id);
                            } else if (modal_id == 'product-grid' || modal_id == 'product-horiziontal-sliding'
                                    || modal_id == 'product-square') {
                                //suitable for all product related form
                                $('#mab_product_grid_component_id').val(component_id);
                                //show product-grid form
                                modal_id = 'product-grid';
                            } else if (modal_id == 'banner-sqaure' || modal_id == 'banner-horizontal-sliding'
                                    || modal_id == 'banner-grid') {
                                //suitable for all product related form
                                $('#mab_banner_component_id').val(component_id);
                                //Show same modal
                                modal_id = 'banner-sqaure';
                            } else if (modal_id == 'banner-countdown-timer') {
                                //suitable for all product related form
                                $('#mab_banner_countdown_component_id').val(component_id);
                            } else if (modal_id == 'banner-custom') {
                                //suitable for all product related form
                                $('#mab_banner_custom_component_id').val(component_id);
                            }
                            // Get the modal
                            var modal = $("#" + modal_id).html();
                            // Get the <span> element that closes the modal
                            //        var span = $(modal).find('.close')[0];              
                            //Call Ajax to check form will be edit or not in case of edit persistence existing details
                            if (component_id) {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'post',
                                    data: {
                                        'action': 'wmab_get_product_details',
                                        'component_id': component_id,
                                        'modal_id': modal_id,
                                    },
                                    dataType: 'json',
                                    success: function (response) {                                          
                                        if (response.response) {
                                            if (modal_id == 'product-grid') {
                                                $('#product_type').val(response.product_data['product_type']);
                                                $('#image_content_mode').val(response.product_data['image_content_mode']);
                                                $('#number_of_products').val(response.product_data['number_of_products']);
                                                $('#component_heading_component').val(response.product_data['component_heading']);
                                                $('#product_grid_component_title').val(response.product_data['component_title']);
                                                //Custom Product and Category Product changes
                                                response.product_data['product_type']
                                                if (response.product_data['product_type'] == 'category_products') {
                                                    $('#product_list').closest('.form-group').hide();
                                                    var category_id = get_link_to_options(1,$('#category_id'),response.product_data['id_category'],'category_id');            
                                                    $('#category_id').closest('.form-group').show();
                                                    $('#category_products').closest('.form-group').show();
                                                    //Get Category Products and category product hold comma separated id
                                                    get_link_to_options(3,$('#category_id'),response.product_data['category_products'],'category_products',response.product_data['id_category'],true);
                                                } else if (response.product_data['product_type'] == 'custom_products') {
                                                    //Custom Product stay comma seperated products
                                                    get_link_to_options(2,$('#product_list'),response.product_data['custom_products'],'product_list');
                                                    $('#product_list').closest('.form-group').show();
                                                    $('#category_products').closest('.form-group').hide();
                                                    $('#category_id').closest('.form-group').hide();
                                                } else {
                                                    $('#product_list').closest('.form-group').hide();
                                                    $('#category_products').closest('.form-group').hide();
                                                    $('#category_id').closest('.form-group').hide();
                                                }
                                            } else if (modal_id == 'top-category') {
                                                $('#top_category_component_title').val(response.top_product_data[0]['component_title']);
                                                //Iterate Loop upto last category                            
                                                for (var iterate = 1; iterate <= response.top_product_data.length; iterate++) {
                                                    if (iterate == 1) {
                                                        $('#image_content_mode1').val(response.top_product_data[iterate - 1]['image_content_mode']);
                                                        $('#category_id_' + iterate).val(response.top_product_data[iterate - 1]['id_category']);
                                                        $('#category_heading_' + iterate).val(response.top_product_data[iterate - 1]['category_heading']);
                                                        if (response.top_product_data[iterate - 1]['image_url'] !== '') {
                                                            $('#slideruploadedfile_' + iterate + '-name').val(response.top_product_data[iterate - 1]['image_url']);
                                                            $("#sliderimage_" + iterate).attr("src", response.top_product_data[iterate - 1]['image_url']);
                                                            $("#hiddensliderimage_" + iterate).attr("value", response.top_product_data[iterate - 1]['hidden_image_url']);
                                                        }
                                                    } else {
                                                        $('#category_id_' + iterate).val(response.top_product_data[iterate - 1]['id_category']);
                                                        $('#category_heading_' + iterate).val(response.top_product_data[iterate - 1]['category_heading']);
                                                        if (response.top_product_data[iterate - 1]['image_url'] !== '') {
                                                            $('#slideruploadedfile_' + iterate + '-name').val(response.top_product_data[iterate - 1]['image_url']);
                                                            $("#sliderimage_" + iterate).attr("src", response.top_product_data[iterate - 1]['image_url']);
                                                            $("#hiddensliderimage_" + iterate).attr("value", response.top_product_data[iterate - 1]['hidden_image_url']);
                                                        }
                                                    }
                                                }
                                            }
                                            //Banner forms persistence
                                            else if (modal_id == 'banner-sqaure') {
                                                $('.remove_row_custom').remove();
                                                for (var iterate = 0; iterate < response.banner_options.length; iterate++) {
                                                    if (iterate == 0) {
                                                        var component_heading = response.banner_options[0]['component_heading'];
                                                        $('#banner_sqaure_component_title').val(response.banner_options[0]['component_title']);
                                                    }
                                                    var banner_heading = response.banner_options[iterate]['banner_heading'];
                                                    var id_banner_type = response.banner_options[iterate]['id_banner_type'];
                                                    var product_id = response.banner_options[iterate]['product_id'];
                                                    var category_id = response.banner_options[iterate]['category_id'];
                                                    var redirect_activity = response.banner_options[iterate]['redirect_activity'];
                                                    var image_contentMode = response.banner_options[iterate]['image_contentMode'];
                                                    var image_url = response.banner_options[iterate]['image_url'];
                                                    var hidden_image_url = response.banner_options[iterate]['hidden_image_url'];
                                                    if (image_url == '') {
                                                        hidden_image_url = '';
                                                        image_url = '<?php echo plugins_url('/', __FILE__) . 'images/home_page_layout/'; ?>noimage.png';
                                                    }
                                                    var readonly_status = '';
                                                    if (iterate !== 0) {
                                                        readonly_status = 'readonly';
                                                    }
                                                    //Category
                                                    if (id_banner_type == '1') {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_custom"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" id="banner_link_type' + iterate + '" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1" selected><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to_' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td> <input type="hidden" name = "image_banner_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#banner_last_row");
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), category_id);
                                                        $('#banner_link_to_' + iterate).val(category_id);
                                                    }
                                                    //Product
                                                    else {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_custom"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" id="banner_link_type' + iterate + '" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2" selected><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to_' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#banner_last_row");
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), product_id);
                                                        $('#banner_link_to_' + iterate).val(product_id);
                                                    } 
                                                    $('#image_content_mode_' + iterate).val(image_contentMode);
                                                }
                                            }
                                            //Count Down Timer
                                            else if (modal_id == 'banner-countdown-timer') {
                                                $('.remove_row_coundown_custom').remove();
                                                var randomNumber = Math.floor((Math.random() * 999) + 1);
                                                for (var iterate = 0; iterate < response.banner_countdown_options.length; iterate++) {
                                                    if (iterate == 0) {
                                                        var component_heading = response.banner_countdown_options[0]['component_heading'];
                                                        $('#banner_countdown_timer_component_title').val(response.banner_countdown_options[0]['component_title']);
                                                    }
                                                    var banner_heading = response.banner_countdown_options[iterate]['banner_heading'];
                                                    var id_banner_type = response.banner_countdown_options[iterate]['id_banner_type'];
                                                    var product_id = response.banner_countdown_options[iterate]['product_id'];
                                                    var category_id = response.banner_countdown_options[iterate]['category_id'];
                                                    var redirect_activity = response.banner_countdown_options[iterate]['redirect_activity'];
                                                    var image_contentMode = response.banner_countdown_options[iterate]['image_contentMode'];
                                                    var image_url = response.banner_countdown_options[iterate]['image_url'];
													var hidden_image_url = response.banner_countdown_options[iterate]['hidden_image_url'];
                                                    var timer_background_status = response.banner_countdown_options[iterate]['timer_background_status'];
                                                    var timer_background_status_checked = '';
                                                    var timer_background_color = response.banner_countdown_options[iterate]['timer_background_color'];
                                                    var timer_text_color = response.banner_countdown_options[iterate]['timer_text_color'];
                                                    if (timer_background_status == '1') {
                                                        timer_background_status_checked = 'checked';
                                                    }
                                                    var readonly_status = '';
                                                    var timer_validity = response.banner_countdown_options[iterate]['timer_validity'];
                                                    var color_random_id = get_random_number();
                                                    var color_random_id2 = get_random_number();
                                                    if (iterate !== 0) {
                                                        readonly_status = 'readonly';
                                                    }
                                                    //Category
                                                    if (id_banner_type == '1') {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_coundown_custom"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this)" id="banner_link_type' + iterate + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1" selected><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to_' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td><td><div class="input-group date"><input type="text" id="datetimepicker_' + color_random_id + '" name="timer_validity[]" value ="' + timer_validity + '" class="form-control" /></div></td><td><label class="switch"><input name="background_color_status[]" type="checkbox" value="1" ' + timer_background_status_checked + ' /><span class="slider"></span></label></td><td><div class="input-group" id="background_color_div' + color_random_id + '"><input type="text" name="timer_background_color[]" id="color_' + color_random_id + '" value="' + timer_background_color + '" readonly /></div></td><td><div class="input-group" id="textcolor_color_div' + color_random_id2 + '"><input type="text" style="background:' + timer_text_color + ';color:#FFFFFF;" name="timer_text_color[]" id="color_' + color_random_id2 + '" value="' + timer_text_color + '" readonly /></div></td> <td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" id= "hidden_image_field_' + color_random_id + '" name = "image_banner_countdown_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#banner_countdown_last_row");
                                                        //get_link_to_options(id_banner_type, $('#banner_link_type' + iterate));
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), category_id);
//                                    $('#banner_link_to_'+iterate).val(category_id);
                                                    }
                                                    //Product
                                                    else {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_coundown_custom"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this)" id="banner_link_type' + iterate + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2" selected><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to_' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td> <td><div class="input-group date"><input type="text" id="datetimepicker_' + color_random_id + '" name="timer_validity[]" value ="' + timer_validity + '" class="form-control" /></div></td><td><label class="switch"><input name="background_color_status[]" type="checkbox" value="1" ' + timer_background_status_checked + ' /><span class="slider"></span></label></td><td><div class="input-group" id="background_color_div' + color_random_id + '"><input type="text" name="timer_background_color[]" id="color_' + color_random_id + '" value="' + timer_background_color + '" readonly /></div></td><td><div class="input-group" id="textcolor_color_div' + color_random_id2 + '"><input type="text" name="timer_text_color[]" id="color_' + color_random_id2 + '" value="' + timer_text_color + '" readonly /></div></td> <td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" id= "hidden_image_field_' + color_random_id + '" name = "image_banner_countdown_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#banner_countdown_last_row");
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), product_id);
                                                    }
                                                    $('#image_content_mode_' + iterate).val(image_contentMode);
                                                    $('#datetimepicker_' + color_random_id).datetimepicker();
													$('#color_' + color_random_id).wpColorPicker();
                                                    $('#color_' + color_random_id2).wpColorPicker();
                                                }
                                            }

                                            //Banner forms persistence
                                            else if (modal_id == 'banner-custom') {
                                                $('.custom_banner_custom_row').remove();
                                                $('#banner_custom_component_title').val(response.banner_options[0]['component_title']);
                                                var component_heading = response.banner_options[0]['component_heading'];
                                                for (var iterate = 0; iterate < response.banner_options.length; iterate++) {
                                                    var banner_heading = response.banner_options[iterate]['banner_heading'];
                                                    var id_banner_type = response.banner_options[iterate]['id_banner_type'];
                                                    var product_id = response.banner_options[iterate]['product_id'];
                                                    var category_id = response.banner_options[iterate]['category_id'];
                                                    var redirect_activity = response.banner_options[iterate]['redirect_activity'];
                                                    var image_contentMode = response.banner_options[iterate]['image_contentMode'];
                                                    var image_url = response.banner_options[iterate]['image_url'];
                                                    var hidden_image_url = response.banner_options[iterate]['hidden_image_url'];
                                                    //Custom Field
                                                    var banner_custom_background_color = response.banner_options[iterate]['banner_custom_background_color'];
                                                    var inset_top = response.banner_options[iterate]['inset_top'];
                                                    var inset_bottom = response.banner_options[iterate]['inset_bottom'];
                                                    var inset_left = response.banner_options[iterate]['inset_left'];
                                                    var inset_right = response.banner_options[iterate]['inset_right'];
                                                    var banner_width = response.banner_options[iterate]['banner_width'];
                                                    var banner_height = response.banner_options[iterate]['banner_height'];
                                                    var selected_3 = '';
                                                    var selected_4 = '';
                                                    var selected_5 = '';
                                                    var empty_var = '';
                                                    if (image_url == '') {
                                                        hidden_image_url = '';
                                                        image_url = 'noimage.png';
                                                    }
                                                    var readonly_status = '';
                                                    if (iterate !== 0) {
                                                        readonly_status = 'readonly';
                                                    }
                                                    var random_number = get_random_number();
                                                    //Category
                                                    if (id_banner_type == '1') {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border custom_banner_custom_row"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + '/></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this,0,0,0,true)" id="banner_link_type' + iterate + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1" selected><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option><option value="3" '+selected_3+'><?php echo _e('Login', 'woocommerce-mobile-app-builder'); ?></option> <option value="4" '+selected_4+' ><?php echo _e('Search', 'woocommerce-mobile-app-builder'); ?></option><option value="5" '+selected_5+' ><?php echo _e('Home', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td>\n\
                                                            <td><input id="banner_width_' + random_number + '" type="text" name="banner_width[]" value="' + banner_width + '" onblur="calculate_banner_width(' + random_number + ',\'' + image_url + '\')"/><p class="help-block">(In percentage)</p></td><td><input id="banner_height_' + random_number + '" type="text" name="banner_height[]" value="' + banner_height + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_top[]" value="' + inset_top + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_bottom[]" value="' + inset_bottom + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_left[]" value="' + inset_left + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_right[]" value="' + inset_right + '" /><p class="help-block">(In percentage)</p></td><td>\n\
                                                            <div class="input-group" id="banner_background_color_div' + random_number + '"><input type="text" style="background:' + banner_custom_background_color + '" name="banner_custom_background_color[]" id="color_' + random_number + '" value="' + banner_custom_background_color + '" readonly /></div></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#custom_banner_last_row");
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), category_id);
                                                        $('#banner_link_to_' + iterate).val(category_id);
                                                    }
                                                    //Product
                                                    else if(id_banner_type == '2') {
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border custom_banner_custom_row"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this,0,0,0,true)" id="banner_link_type' + iterate + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1" ><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2" selected><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option><option value="3" '+selected_3+'><?php echo _e('Login', 'woocommerce-mobile-app-builder'); ?></option> <option value="4" '+selected_4+' ><?php echo _e('Search', 'woocommerce-mobile-app-builder'); ?></option><option value="5" '+selected_5+' ><?php echo _e('Home', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to' + iterate + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td>\n\
                                                            <td><input id="banner_width_' + random_number + '" type="text" name="banner_width[]" value="' + banner_width + '" onblur="calculate_banner_width(' + random_number + ',\'' + image_url + '\')"/><p class="help-block">(In percentage)</p></td><td><input id="banner_height_' + random_number + '" type="text" name="banner_height[]" value="' + banner_height + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_top[]" value="' + inset_top + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_bottom[]" value="' + inset_bottom + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_left[]" value="' + inset_left + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_right[]" value="' + inset_right + '" /><p class="help-block">(In percentage)</p></td><td>\n\
                                                            <div class="input-group" id="banner_background_color_div' + random_number + '"><input type="text" style="background:' + banner_custom_background_color + '" name="banner_custom_background_color[]" id="color_' + random_number + '" value="' + banner_custom_background_color + '" readonly /></div></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                        $(html).insertBefore("#custom_banner_last_row");
                                                        get_link_to_options(id_banner_type, $('#banner_link_type' + iterate), product_id);
                                                        $('#banner_link_to_' + iterate).val(product_id);
                                                    }
                                                    else {                                                       
                                                        if(id_banner_type == '3'){
                                                            selected_3 = 'selected';
                                                        }else if(id_banner_type == '4'){
                                                            selected_4 = 'selected';
                                                        }
                                                        else if(id_banner_type == '5'){
                                                            selected_5 = 'selected';
                                                        }                                                        
                                                        var html = '<tr class="mab-table-odd mab-table-bottom-border custom_banner_custom_row"><td><input type="text" name="component_heading_name[]" value="' + component_heading + '" ' + readonly_status + ' /></td><td><input type="text" name="banner_heading_name[]" value="' + banner_heading + '" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this,0,0,0,true)" id="banner_link_type' + iterate + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1" ><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option><option value="3" '+selected_3+'><?php echo _e('Login', 'woocommerce-mobile-app-builder'); ?></option> <option value="4" '+selected_4+' ><?php echo _e('Search', 'woocommerce-mobile-app-builder'); ?></option><option value="5" '+selected_5+' ><?php echo _e('Home', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to' + iterate + '" style="display:none;"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode_' + iterate + '"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td class="mab-center"><img src="' + image_url + '" width="100" height="100" /></td>\n\
                                                            <td><input id="banner_width_' + random_number + '" type="text" name="banner_width[]" value="' + banner_width + '" onblur="calculate_banner_width(' + random_number + ',\'' + image_url + '\')"/><p class="help-block">(In percentage)</p></td><td><input id="banner_height_' + random_number + '" type="text" name="banner_height[]" value="' + banner_height + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_top[]" value="' + inset_top + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_bottom[]" value="' + inset_bottom + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_left[]" value="' + inset_left + '" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_right[]" value="' + inset_right + '" /><p class="help-block">(In percentage)</p></td><td>\n\
                                                            <div class="input-group" id="banner_background_color_div' + random_number + '"><input type="text" style="background:' + banner_custom_background_color + '" name="banner_custom_background_color[]" id="color_' + random_number + '" value="' + banner_custom_background_color + '" readonly /></div></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="' + hidden_image_url + '" /></tr>';
                                                                                                                        
                                                        $(html).insertBefore("#custom_banner_last_row");
                                                    }
                                                    $('#color_' + random_number).wpColorPicker();
                                                    $('#image_content_mode_' + iterate).val(image_contentMode);
                                                }
                                            }
                                        } else {
                                            if (modal_id == 'top-category') {
                                                for (var iterate = 1; iterate <= 8; iterate++) {
                                                    $('#image_content_mode1' + iterate).val("");
                                                    $('#category_id_' + iterate).val("");
                                                    $('#category_heading_' + iterate).val("");
                                                    $('#slideruploadedfile_' + iterate + '-name').val("");
                                                    $("#sliderimage_" + iterate).attr("src", "");
                                                }
                                            }
                                            if (modal_id == 'product-grid') {
                                                $('#product_type').val("best_seller");
                                                $('#image_content_mode').val("scaleAspectFill");
                                                $('#number_of_products').val("");
                                                $('#component_heading_component').val("");
                                                $('#product_grid_component_title').val("");
                                                $('#product_list').closest('.form-group').hide();
                                                $('#category_products').closest('.form-group').hide();
                                                $('#category_id').closest('.form-group').hide();
                                            }
                                            //Reset previous
                                            if (modal_id == 'banner-sqaure') {
                                                $('.remove_row_custom').remove();
                                                $('#banner_sqaure_component_title').val('');
                                            }

                                            if (modal_id == 'banner-countdown-timer') {
                                                $('.remove_row_coundown_custom').remove();
                                                $('#banner_countdown_timer_component_title').val('');
                                            }
                                            //Custom Banner
                                            if (modal_id == 'banner-custom') {
                                                $('.custom_banner_custom_row').remove();
                                                $('#banner_custom_component_title').val('');
                                            }
                                        }
                                    }
                                });
                            }

                            // When the user clicks the button, open the modal         
                            $("#" + modal_id).css('display', 'block');

                            // When the user clicks on <span> (x), close the modal        
                            $('#' + modal_id + '-span').click(function () {
                                $("#" + modal_id).css('display', 'none');
                            });
//        // When the user clicks anywhere outside of the modal, close it
//        window.onclick = function(event) {
//          if (event.target == modal) {
//            modal.style.display = "none";
//          }
//        }
                        }

//Add New Banner
    $("#add_banner").click(function () {
        var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_custom"><td><input type="text" name="component_heading_name[]" value="" /></td><td><input type="text" name="banner_heading_name[]" value="" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td><input type="file" name="banner_image[]" value="" /></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="" /></tr>';
        $(html).insertBefore("#banner_last_row");
        return $(html);
    });
//Add New Banner
        $("#add_custom_banner").click(function () {
            var random_number = get_random_number();
            var html = '<tr class="mab-table-odd mab-table-bottom-border custom_banner_custom_row"><td><input type="text" name="component_heading_name[]" value="" /></td><td><input type="text" name="banner_heading_name[]" value="" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this,0,0,0,true)" id="banner_link_type' + random_number + '"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option><option value="3"><?php echo _e('Login', 'woocommerce-mobile-app-builder'); ?></option> <option value="4"><?php echo _e('Search', 'woocommerce-mobile-app-builder'); ?></option><option value="5" ><?php echo _e('Home', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]" id="banner_link_to' + random_number + '"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td><input type="file" id="banner_images_' + random_number + '" name="banner_image[]" value="" /></td>\n\
            <td><input id="banner_width_' + random_number + '" type="text" name="banner_width[]" value="" onblur="calculate_banner_width(' + random_number + ')" /><p class="help-block">(In percentage)</p></td><td><input id="banner_height_' + random_number + '" type="text" name="banner_height[]" value="" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_top[]" value="" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_bottom[]" value="" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_left[]" value="" /><p class="help-block">(In percentage)</p></td><td><input type="text" name="inset_right[]" value="" /><p class="help-block">(In percentage)</p></td><td>\n\
            <div class="input-group" id="banner_background_color_div' + random_number + '"><input type="text" name="banner_custom_background_color[]" id="color_' + random_number + '" value="#ffffff" readonly /></div></td><td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" name = "image_banner_upload_edit[]" value ="" /></tr>';
            $(html).insertBefore("#custom_banner_last_row");
            $('#color_' + random_number).wpColorPicker();
            return $(html);
    });

//Add New Banner
        $("#add_countdown_timer").click(function () {
            var color_random_id = get_random_number();
            var color_random_id2 = get_random_number();
            var html = '<tr class="mab-table-odd mab-table-bottom-border remove_row_coundown_custom"><td><input type="text" name="component_heading_name[]" value="" /></td><td><input type="text" name="banner_heading_name[]" value="" /></td><td><select name="banner_link_type[]" onchange="get_link_to_options(this.value, this)"><option value=""><?php echo _e('Select Link Type', 'woocommerce-mobile-app-builder'); ?></option><option value="1"><?php echo _e('Category', 'woocommerce-mobile-app-builder'); ?></option><option value="2"><?php echo _e('Product', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="banner_link_to[]"><option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option></select></td><td><select name="image_content_mode[]" class="chosen-dropdown fixed-width-xl" id="image_content_mode"><option value="scaleAspectFill">Scale aspect fill</option><option value="scaleAspectFit">Scale aspect Fit</option></select></td><td><input type="file" name="banner_image[]" value="" /></td> <td><div class="input-group date"><input id="datetimepicker_' + color_random_id + '" type="text" name="timer_validity[]" value ="" class="form-control" /></div></td><td><label class="switch"><input name="background_color_status[]" type="checkbox" value="1" /><span class="slider"></span></label></td><td><div class="input-group" id="background_color_div' + color_random_id + '"><input type="text" name="timer_background_color[]" id="color_' + color_random_id + '" value="#FFFFFF" readonly /></div></td><td><div class="input-group" id="textcolor_color_div' + color_random_id2 + '"><input type="text" name="timer_text_color[]" id="color_' + color_random_id2 + '" value="#000000" readonly /></div></td> <td><input type="button" name="remove_banner[]" value="<?php echo _e('Remove', 'woocommerce-mobile-app-builder'); ?>" onclick="wmab_remove_banner(this)" /></td><input type="hidden" id= "hidden_image_field_' + color_random_id + '" name = "image_banner_upload_edit[]" value ="" /></tr>';
            $(html).insertBefore("#banner_countdown_last_row");
            $('#datetimepicker_' + color_random_id).datetimepicker();
            $('#color_' + color_random_id).wpColorPicker();
            $('#color_' + color_random_id2).wpColorPicker();
            return $(html);
        });
        function get_random_number() {
            var random_number = Math.floor((Math.random() * 99999) + 1);
            if ($('#hidden_image_field_' + random_number).id) {
                random_number = get_random_number();
            } else if ($('#color_' + random_number).id) {
                random_number = get_random_number();
            }
            return random_number;
        }
//Function to remove banner/slideshow row
        function wmab_remove_banner(ele) {
            if (ele != '') {
                $(ele).parent().parent().remove();
            }
        }
//function to send ajax request and get category or products listing based on input
        function get_link_to_options(val, ele, selected_id = '',html_set_select_id = '',category_id = '',is_not_custom_banner = false,$hide = true) {                        
                if ((val > 0 && val < 3) || (val == 3 && is_not_custom_banner)) {                    
                    $(".loder-modal").show();
                    $.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            'action': 'wmab_get_link_to_options',
                            'key': val,
                            'category_id' : category_id,
                            //BOC neeraj.kumar@velsof.com 26-dec-2019 added code to select value as pr given id
                            'selected_id': selected_id,
                        },
                        dataType: 'html',
                        success: function (html) {
                            if(html_set_select_id == ''){
                                html = '<option value=""><?php echo _e('Select Link To', 'woocommerce-mobile-app-builder'); ?></option>' + html;
                                $(ele).parent().next().children('select').html(html);
                            }else if(val != '1'){                             
//                                 html = '<option value=""><?php echo _e('Select Category', 'woocommerce-mobile-app-builder'); ?></option>' + html;
                                $('#'+html_set_select_id).html(html);                                
                            } else{
                                 html = '<option value=""><?php echo _e('Select Category', 'woocommerce-mobile-app-builder'); ?></option>' + html;
                                $('#'+html_set_select_id).html(html);                                
                            }                              
                            $(".loder-modal").hide();                            
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            $(".loder-modal").hide();
                        }
                    });   
                    
                    if(val > 0 && val < 3 && is_not_custom_banner){
                        var banner_link_type_id = ele.id;
                        if(typeof banner_link_type_id !== 'undefined'){
                            var banner_link_to_id = banner_link_type_id.replace("banner_link_type","banner_link_to");
                            $('#'+banner_link_to_id).css("display","block");
                        }                    
                    }else if(val == 3 && is_not_custom_banner && $hide){
                    //Hide Banner Link To Display None : 
                        var banner_link_type_id = ele.id;   
                        if(typeof banner_link_type_id !== 'undefined'){
                            var banner_link_to_id = banner_link_type_id.replace("banner_link_type","banner_link_to");                                
                            $('#'+banner_link_to_id).css("display" ,"none");
                        }
                    }
                } else {
                $(ele).parent().next().children().html('');
                //Hide Banner Link To Display None : 
                var banner_link_type_id = ele.id;   
                if(typeof banner_link_type_id !== 'undefined'){
                    var banner_link_to_id = banner_link_type_id.replace("banner_link_type","banner_link_to");                                
                    $('#'+banner_link_to_id).css("display" ,"none");
                }
            }
        }

    function calculate_banner_width(random_number = '', image_url = '') {
                    if (random_number != '') {
                        var custom_url_check_width_height = '';
                        if (image_url !== '') {
                            custom_url_check_width_height = $('#hidden_plugin_url').val() + image_url;
                            var img = new Image();
                            img.onload = function () {
                                var height = img.height;
                                var width = img.width;
                                var image_width_custom = ($("#banner_width_" + random_number).val() * height) / width;
                                $("#banner_height_" + random_number).val(Math.round(image_width_custom));
                                return true;
                            }
                            img.src = custom_url_check_width_height;
                        } else if ($("#banner_images_" + random_number).val() !== '') {
                            var fileUpload = $("#banner_images_" + random_number)[0];
                            var reader = new FileReader();
                            //Read the contents of Image File.
                            reader.readAsDataURL(fileUpload.files[0]);
                            reader.onload = function (e) {

                                //Initiate the JavaScript Image object.
                                var image = new Image();

                                //Set the Base64 string return from FileReader as source.
                                image.src = e.target.result;
                                //Validate the File Height and Width.
                                image.onload = function () {
                                    var height = this.height;
                                    var width = this.width;
                                    var image_width_custom = ($("#banner_width_" + random_number).val() * height) / width;
                                    $("#banner_height_" + random_number).val(Math.round(image_width_custom));
                                    return true;
                                };
                            }
                        }
                }
            }
    function showHideProductType(a) {
        if ($('#product_type').val() == 'category_products') {
            $('#product_list').closest('.form-group').hide();
            var category_id = get_link_to_options(1,$('#category_id'),'','category_id');            
            $('#category_id').closest('.form-group').show();
            $('#category_products').closest('.form-group').show();

        } else if ($('#product_type').val() == 'custom_products') {
            get_link_to_options(2,$('#product_list'),'','product_list');
            $('#product_list').closest('.form-group').show();
            $('#category_products').closest('.form-group').hide();
            $('#category_id').closest('.form-group').hide();
        } else {
            $('#product_list').closest('.form-group').hide();
            $('#category_products').closest('.form-group').hide();
            $('#category_id').closest('.form-group').hide();
            $('#category_products').html('');
        }
    }
    //Get Category Product by Category Id : 
    function getCategoryproducts(element){
        if(element.value !== 'undefined'){
            if(element.value == ''){
                //For Empty get category product with category id zero
                get_link_to_options(3,element,'','category_products',0,'',true,false);
            }else{
                get_link_to_options(3,element,'','category_products',element.value,true,false);
            }            
        }
    }
</script>
