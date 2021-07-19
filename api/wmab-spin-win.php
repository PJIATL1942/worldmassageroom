<?php

/**
 * Plugin Name: Knowband Mobile App Builder
 * Plugin URI: http://woocommerce.com/products/woocommerce-extension/
 * Description: Mobile App Builder
 * Version: 1.0.0
 * Author: Knowband
 * Author URI: https://www.knowband.com/
 * Developer: Neeraj Kumar
 * Developer URI: https://www.knowband.com/
 * Text Domain: woocommerce-mobile-app-builder
 * Domain Path: /languages
 *
 * WC requires at least: 3.3.3
 * WC tested up to: 3.3.3
 *
 * Copyright: Â© 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH'))
    exit;  // Exit if access directly

/**
 * class - WmabSpinWin
 * 
 * This class contains constructor and other methods which are actually related to Spin Win Page actions
 * @author Knowband
 * @version v1.2
 * @Date 28-Jan-2020
 */

class WmabSpinWin {

    /**
     *
     * @var string A private variable of class which holds WooCommerce plugin settings
     * @author Knowband
     */
    private $wmab_plugin_settings = array();

    /**
     * 
     * @var string A private variable of class which holds API response 
     * @author Knowband
     */
    private $wmab_response = array();
    
    /**
     *  @var string A private variable of class which hold API response
     */
    private $wmab_spin_win_settings = array();
    /**
     * Class Constructor
     * @global object $wpdb
     * @author Knowband
     */
    public function __construct($request = '') {
        global $wpdb;
        $this->wmab_response['install_module'] = ''; //Set default blank value to send as response in each request
        //Get Mobile App Builder settings from database
        $wmab_settings = get_option('wmab_settings');
        if (isset($wmab_settings) && !empty($wmab_settings)) {
            $this->wmab_plugin_settings = unserialize($wmab_settings);
        }
        //Get Spin & Win Settings       
        if (isset($this->wmab_plugin_settings['general']['spin_win_status']) && $this->wmab_plugin_settings['general']['spin_win_status']) {
            $wmab_spin_win_options = get_option('wsaw_settings');            
            if (isset($wmab_spin_win_options) && !empty($wmab_spin_win_options)) {
                
                $this->wmab_spin_win_settings = unserialize($wmab_spin_win_options);
            }
        }

        //Suspend execution if plugin is not installed or disabled and send output
        if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
            $this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'woocommerce-mobile-app-builder');
            //Log Request
            log_knowband_app_request($request, json_encode($this->wmab_response));
            echo json_encode($this->wmab_response);
            die;
        }
        
        $this->wmab_wc_version = get_woocommerce_version_number();
        if ($this->wmab_wc_version >= '3.6.0') {
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            if (null === WC()->session) {
                $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
                WC()->session = new $session_class(); //For Session Object
                WC()->session->init();
            }
            if (null === WC()->customer) {
                WC()->customer = new WC_Customer(get_current_user_id(), true); //For Customer Object
            }
            if (null === WC()->Tax) {
                WC()->Tax = new WC_Tax(); //For Payment Gateway Object
            }
            //Include Front End Libraries/Classes
            WC()->frontend_includes();
        }        
    }

    /**
     * Function to verify API Version 
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    private function verify_api($version, $request = '') {
        $verified = false;
        if (isset($version) && !empty($version)) {
            if ($version == WMAB_API_VERSION) {
                $verified = true;
            }
        }
        if (!$verified) {
            $this->wmab_response['install_module'] = __('Warning: Invalid API Version !!', 'woocommerce-mobile-app-builder');
            //Log Request
            log_knowband_app_request($request, json_encode($this->wmab_response));
            echo json_encode($this->wmab_response);
            die;
        }
    }
    
    /**
     * Function to handle app_get_spin_win API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.2/appGetSpinWin    
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_spin_win($version) {         
           
        //Log Request
        log_knowband_app_request("appGetSpinWin", json_encode($this->wmab_response));
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $settings = $this->wmab_spin_win_settings;
        
        //Check if plugin enabled
        if (isset($this->wmab_spin_win_settings['general']['enabled']) && !empty($this->wmab_spin_win_settings['general']['enabled'])) {
            if (!class_exists('MailChimp')) {
                   include_once(plugin_dir_path(__FILE__) . 'lib/drewm/mailchimp-api/src/MailChimp.php');
                }
                $show_wheel_on_page = true;
                //Check if plugin enabled
                if (isset($settings['general']['enabled']) && !empty($settings['general']['enabled'])) {
                    //Default Values
                    $mobile_only = true;
                    $every_visit_flag = true;
                    $hide_after = '';
                    $show_on_page = true;
                    $show_wheel = true;
                    $time_display = '';
                    $scroll_display = '';
                    $exit_display = false;
                    $new_visit_flag = true;
                    $return_visit_flag = true;
                    $all_visitor = true;
                    $display_interval_flag = true;
                    $mobile_class = '';
                    //Detect device

                    //Check pages to display
                    $current_page = '';
                    if (is_front_page()) {
                        $current_page = 'home';
                    } else if (is_category() || is_product_category()) {
                        $current_page = 'category';
                    } else if (is_product()) {
                        $current_page = 'product';
                    }
                    
                    if (isset($settings['display']['display_position']) && $settings['display']['display_position'] == '2') {
                        $show_pages = $settings['display']['selected_pages'];

                        if (in_array($current_page, $show_pages)) {
                            $show_on_page = true;
                        } else {
                            $show_on_page = false;
                        }
                    } else if (isset($settings['display']['display_position']) && $settings['display']['display_position'] == '3') {
                        $not_show_pages = $settings['display']['selected_pages'];
                        if (in_array($current_page, $not_show_pages)) {
                            $show_on_page = false;
                        } else {
                            $show_on_page = true;
                        }
                    }

                    //Coupon Display Options
                    if (isset($settings['email_settings']['coupon_display_options']) && $settings['email_settings']['coupon_display_options'] == '1') {
                        $coupon_display_option = 1;
                    } else if (isset($settings['email_settings']['coupon_display_options']) && $settings['email_settings']['coupon_display_options'] == '2') {
                        $coupon_display_option = 2;
                    } else {
                        $coupon_display_option = '';
                    }

//                    $current_client_time = strtotime(date('Y-m-d H:i:s'));
//                    if (($settings['display']["set_time_interval"] == 0) || ($settings['display']["set_time_interval"] == 1 && $current_client_time >= strtotime($settings['display']["start_datetime"]) && $current_client_time <= strtotime($settings['display']["end_datetime"]))) {
//                        if ($show_wheel == true && (!isset($_COOKIE['visit_cookie']) || $every_visit_flag == false) && $new_visit_flag == true && $return_visit_flag == true && $all_visitor == true && $show_on_page == true && $mobile_only == false) {
//                            if (isset($settings['general']['display_interval']) && $settings['general']['display_interval'] != 0) {
//                                $days = $settings['general']['display_interval'];
//                                if (!isset($_COOKIE['display_interval_wheel'])) {
//                                    //Do Nothing
//                                } else {
//                                    $display_interval_flag = false;
//                                    $show_wheel_on_page = false;
//                                }
//                            }
//                            
//                            if ($display_interval_flag) {
//                                if (isset($settings['display']['when_to_display']) && $settings['display']['when_to_display'] == 'when_exit') {
//                                    wp_enqueue_script( 'ouibounce', plugins_url('/', __FILE__) . 'views/js/spin-win/ouibounce.js' );
//                                }
//                            } else {
//                                $show_wheel_on_page = false;
//                            }
//                        } else {
//                            $show_wheel_on_page = false;
//                        }
//                    } else {
//                        $show_wheel_on_page = false;
//                    }
                }
        }
        
		header('Content-Type: text/html');
                //include js and css
                wp_enqueue_style( 'spin_wheel', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/css/spin_wheel.css' );
                
                wp_enqueue_script('jquery');
                wp_enqueue_script( 'spin_wheel', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/js/spin-win/spin_wheel.js?test=2' ); 
                wp_enqueue_script( 'velsof_wheel', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/js/spin-win/velsof_wheel.js' ); 
                wp_enqueue_script( 'velovalidation', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/js/spin-win/velovalidation.js' ); 
                wp_enqueue_script( 'tooltipster', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/js/spin-win/tooltipster.js' ); 
                wp_enqueue_script( 'jquery.fireworks', str_replace("api/", "", plugins_url('/', __FILE__)) . 'views/js/spin-win/jquery.fireworks.js' ); 
                
		echo wc_get_template_html( '../../'.str_replace('/api', '', plugin_basename(dirname(__FILE__))).'/views/spin-win-page.php', array(
                                'settings' => $this->wmab_spin_win_settings ,
                                'show_wheel_on_page' => $show_wheel_on_page,
                                'mobile_only' =>  $mobile_only,
                                'every_visit_flag' =>  $every_visit_flag,
                                'hide_after' =>  $hide_after,
                                'show_on_page' =>  $show_on_page,
                                'show_wheel' =>  $show_wheel,
                                'time_display' =>  $time_display,
                                'scroll_display' =>  $scroll_display,
                                'exit_display' =>  $exit_display,
                                'new_visit_flag' =>  $new_visit_flag,
                                'return_visit_flag' =>  $return_visit_flag,
                                'all_visitor' =>  $all_visitor,
                                'display_interval_flag' =>  $display_interval_flag,
                                'mobile_class' => $mobile_class,
                                'site_url' => get_site_url()
                          ));
        die;
       
    }
    
}
