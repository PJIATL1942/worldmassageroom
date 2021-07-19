<?php
/**
 * Plugin Name: Knowband Mobile App Builder
 * Plugin URI: http://woocommerce.com/products/woocommerce-extension/
 * Description: Mobile App Builder
 * Version: 1.0.0
 * Author: Knowband
 * Author URI: https://www.knowband.com/
 * Developer: Harsh Agarwal
 * Developer URI: https://www.knowband.com/
 * Text Domain: woocommerce-mobile-app-builder
 * Domain Path: /languages
 *
 * WC requires at least: 3.3.3
 * WC tested up to: 3.3.3
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH'))
    exit;  // Exit if access directly

/**
 * class - WmabCheckout
 * 
 * This class contains methods which handles Mobile App Builder API Calls which are related to checkout page actions
 * @author Knowband
 * @version v1.1
 * @Date 29-Jun-2018
 */
class WmabCheckout {
    
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
     * 
     * @var string A private variable of class which holds current session expiration for Cart 
     * @author Knowband
     */
    private $wmab_session_expiration = '';
    
    /**
     * 
     * @var string A private variable of class which holds current session expiring for Cart
     * @author Knowband
     */
    private $wmab_session_expiring = '';
    
    /**
     * 
     * @var string A private variable of class which holds cookie values for Cart
     * @author Knowband
     */
    private $wmab_cookie = '';
	
	/**
	 *
	 * @var string A private variable of class which holds woocommerce version number
	 * @author Knowband
	 */
	private $wmab_wc_version = '';
    
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
        
        //Suspend execution if plugin is not installed or disabled and send output
        if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
            $this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'woocommerce-mobile-app-builder');
            //Log Request
            log_knowband_app_request($request, json_encode($this->wmab_response));
            echo json_encode($this->wmab_response);
            die;
        }
		
		//BOC - hagarwal@velsof.com - Changes added to make module compatible on WooCommerce >= 3.6 - This change initiate the required objects to call functions
		$this->wmab_wc_version = get_woocommerce_version_number();
		if ($this->wmab_wc_version >= '3.6.0') {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
			if ( null === WC()->session ) {
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				WC()->session = new $session_class(); //For Session Object
				WC()->session->init();
			}
			if ( null === WC()->customer ) {
				WC()->customer = new WC_Customer( get_current_user_id(), true ); //For Customer Object
			}
			if ( null === WC()->cart ) {
				WC()->cart = new WC_Cart(); //For Cart Object
				WC()->cart->get_cart();
			}
			if ( null === WC()->countries ) {
				WC()->countries = new WC_Countries(); //For Country Object
			}
			if ( null === WC()->shipping ) {
				WC()->shipping = new WC_Shipping(); //For Shipping Object
			}
			if ( null === WC()->payment_gateways ) {
				WC()->payment_gateways = new WC_Payment_Gateways(); //For Payment Gateway Object
			}
			//Include Front End Libraries/Classes
			WC()->frontend_includes();
		}
		//EOC
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
     * Function to verify API Version 
     * @param string $cart_id This parameter holds current cart ID to set int Session variable
     * @author Knowband
     */
    private function set_session($cart_id) {
        
        $this->wmab_session_expiring = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) );
        $this->wmab_session_expiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) );
        
        $this->wmab_cookie  = 'wp_woocommerce_session_' . COOKIEHASH;
        $to_hash            = $cart_id . '|' . $this->wmab_session_expiration;
        $cookie_hash        = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
        $cookie_value       = $cart_id . '||' . $this->wmab_session_expiration . '||' . $this->wmab_session_expiring . '||' . $cookie_hash;

        wc_setcookie( $this->wmab_cookie, $cookie_value, $this->wmab_session_expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );
        
    }
    
    public function app_send_order_notification($order_id) {
         
        include(ABSPATH . "wp-includes/pluggable.php");
         
        $current_user = wp_get_current_user();
        if($current_user->ID) {
            $user_data = get_user_by('id',$current_user->ID);
            $email = $user_data->user_email;
        }
        
        if ($order_id) {

            //Check if FCM and Cart mapping exists
            $fcm_data = $this->isFcmExist('',$email);
           
            if (isset($fcm_data) && !empty($fcm_data)) {
                //Update FCM and Order mapping into the table
                $this->mapOrderWithFCM($order_id, $fcm_data->fcm_details_id);
                $cart_id = $fcm_data->cart;
            }

            //Order Success Push Notification
            if (isset($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled'])) {
                //Get Notification Title and Message
                $notification_title = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_title'];
                $notification_message = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_msg'];

                $this->notify($notification_title, $notification_message, 'order_placed', $cart_id, $order_id, $email, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']);
            }                 
        }
       
     }
    
    /**
     * Function to handle appCheckout API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckout
     * @param string $email This parameter holds customer email
     * @param string $session_data This parameter holds current cart ID
     * @param int $id_billing_address This parameter holds Customer Billing Address ID
     * @param int $id_shipping_address This parameter holds Customer Shipping Address ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_checkout($email, $session_data, $id_billing_address, $id_shipping_address, $set_shipping_method, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appCheckout');
        
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Cart Information could not be loaded.', 'woocommerce-mobile-app-builder');
        
        if (isset($email) && !empty($email)) {
            $customer_id = email_exists($email);
            
            $wc_customer = new WC_Customer($customer_id);
        
            if (isset($customer_id) && !empty($customer_id)) {
                
                WC()->customer = new WC_Customer($customer_id);
                if (isset($session_data) && !empty($session_data)) {
                    $cart_id = $session_data;
                } else {
                    $cart_id = $customer_id;
                }

                //Get All States
                $wc_country = new WC_Countries();
                $states = $wc_country->__get('states');

                
                //Get Customer Billing Address
                $billing_address = $wc_customer->get_shipping(); 
                
                $customer_billing = $wc_customer->get_billing();  

                //replaced get_billing() with get_shipping as both are same and system allows only one
                $this->wmab_response['checkout_page']['billing_address'] = array(
                    'id_shipping_address' => isset($id_shipping_address) ? $id_shipping_address : 0,
                    'firstname' => isset($billing_address['first_name']) ? $billing_address['first_name'] : '',
                    'lastname' => isset($billing_address['last_name']) ? $billing_address['last_name'] : '',
                    'mobile_no' => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
                    'company' => isset($billing_address['company']) ? $billing_address['company'] : '',
                    'address_1' => isset($billing_address['address_1']) ? $billing_address['address_1'] : '',
                    'address_2' => isset($billing_address['address_2']) ? $billing_address['address_2'] : '',
                    'city' => isset($billing_address['city']) ? $billing_address['city'] : '',
                    'state' => !empty($states[ $billing_address['country'] ][ $billing_address['state'] ]) ? $states[ $billing_address['country'] ][ $billing_address['state'] ] : $billing_address['state'],
                    'country' => !empty($billing_address['country']) ? WC()->countries->countries[ $billing_address['country'] ] : '',
                    'postcode' => isset($billing_address['postcode']) ? $billing_address['postcode'] : '',
                    'alias' => ''
                );
                
               
                
                //Get Customer Shipping Address
                $shipping_address = $wc_customer->get_shipping();
                
                $this->wmab_response['checkout_page']['shipping_address'] = array(
                    'id_shipping_address' => isset($id_shipping_address) ? $id_shipping_address : 0,
                    'firstname' => isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '',
                    'lastname' => isset($shipping_address['last_name']) ? $shipping_address['last_name'] : '',
                    'mobile_no' => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
                    'company' => isset($shipping_address['company']) ? $shipping_address['company'] : '',
                    'address_1' => isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '',
                    'address_2' => isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '',
                    'city' => isset($shipping_address['city']) ? $shipping_address['city'] : '',
                    'state' => !empty($states[ $shipping_address['country'] ][ $shipping_address['state'] ]) ? $states[ $shipping_address['country'] ][ $shipping_address['state'] ] : $shipping_address['state'],
                    'country' => !empty($shipping_address['country']) ? WC()->countries->countries[ $shipping_address['country'] ] : '',
                    'postcode' => isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '',
                    'alias' => ''
                );
                
                //Shipping details
                $this->wmab_response['checkout_page']['per_products_shipping'] = '0';
                $this->wmab_response['checkout_page']['per_products_shipping_methods'] = array();
                $this->wmab_response['checkout_page']['default_shipping'] = '';
                $this->wmab_response['checkout_page']['shipping_available'] = '0';
                $this->wmab_response['checkout_page']['shipping_message'] = '';
                $this->wmab_response['checkout_page']['shipping_methods'] = array();
                 
                //Products
                $this->wmab_response['checkout_page']['products'] = array();
                $this->wmab_response['checkout_page']['vouchers'] = array();
                $this->wmab_response['checkout_page']['guest_checkout_enabled'] = (string) get_option( 'woocommerce_enable_guest_checkout' ) === 'yes' ? '0' : '0'; //Set it as disabled
                $this->wmab_response['checkout_page']['cart'] = (object) array(
                    'total_cart_items' => 0
                );
                $this->wmab_response['checkout_page']['voucher_allowed'] = (string) 0;
                $this->wmab_response['checkout_page']['minimum_purchase_message'] = '';
                $this->wmab_response['checkout_page']['totals'] = array();
                
                
                if(WC()->session->get('chosen_shipping_methods')!= null) {
                    foreach(WC()->session->get('chosen_shipping_methods') as $key => $value) {
                        $this->wmab_response['checkout_page']['default_shipping'] = $value;
                    }
                }
                
                $this->wmab_response['checkout_page']['voucher_html'] = '';
                
                $this->wmab_response['gift_wrapping'] = array(
                    'available' => '0',
                    'applied' => '0',
                    'message' => '',
                    'cost_text' => ''
                );
                
                if (isset($cart_id) && !empty($cart_id)) {
                    //Set Cart Session
                    $this->set_session($cart_id);
                    
                    //Set Shipping Method
                if (isset($set_shipping_method) && !empty($set_shipping_method)) {
                    WC()->session->set('chosen_shipping_methods', array( $set_shipping_method ) );
                    WC()->cart->calculate_totals();
                }  

                    $cart_content = WC()->cart->get_cart_contents();
                    //BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
                    $is_virtual_product = false;
                    $is_virual_with_non_virtual_product = true;
                    
                    foreach ($cart_content as $cart_products) {

                        $option_data = array();

                        if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
                            $product_id = $cart_products['variation_id'];
                        } else {
                            $product_id = $cart_products['product_id'];
                        }

                        //Get Product
                        $product = wc_get_product( $product_id ); //Replaced deprecated function get_product with wc_get_product on 04-Oct-2019
                            $product_type = $product->get_type(); //Get the product type correctly instead of getting it directly through $product->product_type on 04-Oct-2019
                            
                            //BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
                            if($product->is_virtual()){
                                $is_virtual_product = true;
                            } else{
                                $is_virual_with_non_virtual_product = false;
                            }
                            //EOC
                        if($product_type == 'variable') { //For Variable Product
                            $available_variations = $product->get_available_variations();
                            $variation_id = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
                            $variable_product = new WC_Product_Variation( $variation_id );
                            $regular_price = $variable_product->regular_price;
                            $sale_price = $variable_product->sale_price;

                            if ($sale_price) {
                                $discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
                            } else {
                                $discount_percentage = 0;
                            }
                        } else {
                            //For Simple Products
                            if ($product->get_sale_price()) {
                                $sale_price = $product->get_sale_price();
                                $regular_price = $product->get_regular_price();
                                $discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
                            } else {
								$sale_price = $product->get_sale_price();
                                $regular_price = $product->get_regular_price();
                                $discount_percentage = 0;
                            }
                        }

                        //Option Data for Product Quantity
                        $option_data[] = array(
                            'name' => __( 'Quantity', 'woocommerce-mobile-app-builder' ),
                            'value' => (string) $cart_products['quantity']
                        );

                        //Option Data for Product Attributes
                        if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
                            $product_attributes = $product->get_attributes();
                            $option_data[] = array(
                                'name' => __( 'Attributes', 'woocommerce-mobile-app-builder' ),
                                'value' => ucwords(implode(", ", $product_attributes))
                            );
                        }

                        //Product Image
                        //$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id, 'single-post-thumbnail' ));
                        $product_image = get_the_post_thumbnail_url( $product_id, array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) );

                        if ($product_image == null && !empty($cart_products['variation_id'])) {
                            //$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $cart_products['product_id'], 'single-post-thumbnail' ));
                            $product_image = get_the_post_thumbnail_url( $cart_products['product_id'], array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) );
                        }

                        $this->wmab_response['checkout_page']['products'][] = array(
                            'product_id' => $cart_products['product_id'],
                            'title' => strip_tags($product->get_name()),
                            'is_gift_product' => "0",
                            'id_product_attribute' => (string)$cart_products['variation_id'],
                            'id_address_delivery' => "0",
                            'stock' => $product->get_stock_status() === 'instock' ? true : false,
                            'discount_price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => $cart_products['quantity'], 'price' => $product->get_sale_price())) ) ) ),
                            'discount_percentage' => $discount_percentage,
                            'images' => $product_image, //$product_image[0],
                            'price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => $cart_products['quantity'], 'price' => $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price())) ) ) ),
                            'quantity' => (string) $cart_products['quantity'],
                            'product_items' => $option_data,
                            'customizable_items' => array()
                        );
                    }
                    
                    //BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
                    if($is_virtual_product && $is_virual_with_non_virtual_product){
                        $this->wmab_response['checkout_page']['shipping_available'] = '1';
                    }
                    
                    $this->wmab_response['checkout_page']['cart'] = (object) array(
                        'total_cart_items' => is_array($cart_content) ? count($cart_content) : 0
                    );

                    //Cart SubTotal
                    $cart_sub_total = WC()->cart->get_cart_subtotal();
                    $this->wmab_response['checkout_page']['totals'][] = array(
                        'name' => __('Subtotal', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags($cart_sub_total) )
                    );

                    //Cart Coupon
                    foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
                        $this->wmab_response['checkout_page']['totals'][] = array(
                            'name' => sanitize_title( $code ),
                            'value' => '-' . html_entity_decode( strip_tags( wc_price(  WC()->cart->get_coupon_discount_amount( $code, WC()->cart->display_cart_ex_tax ) ) ) )
                        );
                    }
                    
                    //Cart Shipping Total
                    $cart_shipping_total = WC()->cart->get_cart_shipping_total();
                    $this->wmab_response['checkout_page']['totals'][] = array(
                        'name' => __('Shipping', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags($cart_shipping_total) )
                    );

                    //Fee
                    foreach ( WC()->cart->get_fees() as $fee ) {
                        $this->wmab_response['checkout_page']['totals'][] = array(
                            'name' => esc_html( $fee->name ),
                            'value' => WC()->cart->display_prices_including_tax() ? html_entity_decode( wc_price( $fee->total + $fee->tax ) ) : html_entity_decode( wc_price( $fee->total ) )
                        );
                    }

                    //Tax
                    if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
                        $taxable_address = WC()->customer->get_taxable_address();
                        $estimated_text  = WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()
                                                ? sprintf( ' <small>' . __( '(estimated for %s)', 'woocommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] )
                                                : '';

                        if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
                            foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
                                $this->wmab_response['checkout_page']['totals'][] = array(
                                    'name' => esc_html( $tax->label ) . $estimated_text,
                                    'value' => html_entity_decode( strip_tags(wp_kses_post( $tax->formatted_amount )) )
                                );
                            }
                        } else {
                            $this->wmab_response['checkout_page']['totals'][] = array(
                                'name' => esc_html( WC()->countries->tax_or_vat() ) . $estimated_text,
                                'value' => html_entity_decode( strip_tags( wc_price( WC()->cart->get_taxes_total() ) ) )
                            );
                        }
                    }

                    $this->wmab_response['checkout_page']['totals'][] = array(
                        'name' => __('Total', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( WC()->cart->get_total() ) )
                    );

                    $this->wmab_response['total_cost'] = html_entity_decode( strip_tags( WC()->cart->get_total() ) );
                }
                
				//Get Shipping methods
                /*$package['destination'] = array(
                    'country' => !empty($shipping_address['country']) ? $shipping_address['country'] : '',
                    'state' => !empty($shipping_address['state']) ? $shipping_address['state'] : '',
                    'postcode' => isset($shipping_address['postcode']) ? $shipping_address['postcode'] : ''
                );  */
				$package = WC()->cart->get_shipping_packages(); //Get the shipping packages through default code on 04-Oct-2019
				if (isset($package[0])) {
					$package = $package[0]; //And assigned the 0 index array into a array variable on 04-Oct-2019
				}
				
                $shipping_methods = WC()->shipping->calculate_shipping_for_package($package);
                if (isset($shipping_methods) && !empty($shipping_methods)) {
                    $this->wmab_response['checkout_page']['shipping_available'] = '1';
                    foreach ( $shipping_methods['rates'] as $shipping_method ) {
                        $this->wmab_response['checkout_page']['shipping_methods'][] = array(
                            'name' => $shipping_method->__get('label'),
                            'price' => html_entity_decode( strip_tags( wc_price( $shipping_method->__get('cost') + $shipping_method->get_shipping_tax() ) ) ),
                            'delay_text' => '',
                            'code' => $shipping_method->__get('id'),
                        );
                    }
                }
                
                

		//Get Default Currency
        	$default_currency = get_woocommerce_currency();
                
                $this->wmab_response['total_cost'] = WC()->cart->get_total('');
                $this->wmab_response['currency_code'] = $default_currency;
                $this->wmab_response['currency_symbol'] = get_woocommerce_currency_symbol( $default_currency );
              
                $this->wmab_response['status'] = 'success';
                $this->wmab_response['message'] = __('Cart Information loaded successfully.', 'woocommerce-mobile-app-builder');
            } else {
                $this->wmab_response['status'] = 'failure';
                $this->wmab_response['message'] = __('Customer does not exist.', 'woocommerce-mobile-app-builder');
            }
        } else {
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid Email Address.', 'woocommerce-mobile-app-builder');
        }
        
        //Log Request
        log_knowband_app_request("appCheckout", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appGetOrderDetails API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetOrderDetails
     * @param string $email This parameter holds Customer Email
     * @param int $order_id This parameter holds Order ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_order_details($email, $order_id, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetOrderDetails');
        
        if (isset($email) && !empty($email) && isset($order_id) && !empty($order_id)) {
            $customer_id = email_exists($email);
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                $customer = new WC_Customer($customer_id);
                
                $order_info = wc_get_order($order_id);
                $order_details = $order_info->get_data();
                
                //Order History
                $order_history = array(
                    'order_id' => (int)$order_id,
                    'cart_id' => '',
                    'order_number' => (string) $order_id,
                    'status' => ucwords($order_details['status']),
                    'status_color' => '#26A69A',
                    'date_added' => date("Y-m-d H:i:s", strtotime($order_details['date_created'])),
                    'total' => html_entity_decode( strip_tags( wc_price( $order_details['total'] ) ) ),
                    'reorder_allowed' => '1',
                );
                
                //Get All States
                $wc_country = new WC_Countries();
                $states = $wc_country->__get('states');
                
                if(empty($order_details['shipping']['first_name'])) {
                    $order_details['shipping'] = $order_details['billing']; 
                }
                
                //Shipping Address
                $shipping_address = array(
                    'firstname' => $order_details['shipping']['first_name'],
                    'lastname' => $order_details['shipping']['last_name'],
                    'company' => $order_details['shipping']['company'],
                    'address_1' => $order_details['shipping']['address_1'],
                    'address_2' => $order_details['shipping']['address_2'],
                    'mobile_no' => '',
                    'city' => $order_details['shipping']['city'],
                    'postcode' => $order_details['shipping']['postcode'],
                    'state' => $order_details['shipping']['state'], //(!empty($order_details['shipping']['country']) && !empty($order_details['shipping']['state'])) ? $states[ $order_details['shipping']['country'] ][ $order_details['shipping']['state'] ] : '',
                    'country' => $order_details['shipping']['country'], //!empty($order_details['shipping']['country']) ? WC()->countries->countries[ $order_details['shipping']['country'] ] : '',
                    'alias' => '',
                );
                
                //Billing Address
                $billing_address = array(
                    'firstname' => $order_details['billing']['first_name'],
                    'lastname' => $order_details['billing']['last_name'],
                    'company' => $order_details['billing']['company'],
                    'address_1' => $order_details['billing']['address_1'],
                    'address_2' => $order_details['billing']['address_2'],
                    'mobile_no' => $order_details['billing']['phone'],
                    'city' => $order_details['billing']['city'],
                    'postcode' => $order_details['billing']['postcode'],
                    'state' => $order_details['billing']['state'], //(!empty($order_details['billing']['country']) && !empty($order_details['billing']['state'])) ? $states[ $order_details['billing']['country'] ][ $order_details['billing']['state'] ] : '',
                    'country' => $order_details['billing']['country'], //!empty($order_details['billing']['country']) ? WC()->countries->countries[ $order_details['billing']['country'] ] : '',
                    'alias' => '',
                );
                
                //Payment Method
                $payment_method = $order_details['payment_method_title'];
                
                //print_r($order_details); die;
                
                //Shipping Method
                $shipping_method = $order_info->get_shipping_method()?$order_info->get_shipping_method():'Free Shipping';
                
                
                
                //Status History
                $status_history = array(); //Kept it blank as WooCommerce does not store previous status instead it stores order notes
                
                //Order Comment
                $order_comment = $order_details['customer_note'];
                
                // The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
                $ordered_products = array();
                foreach( $order_details['line_items'] as $item_id => $item_product ) {
                    $item_product_data = $item_product->get_data(); //Get Item data                            
                    //Get the product ID
                    $product_id = $item_product->get_product_id();
                    //Get the WC_Product object
                    $product = $item_product->get_product();
                    //Product Info
                    $product_info = array(
                        array(
                            'name' => __('SKU', 'woocommerce-mobile-app-builder'),
                            'value' => $product->get_sku()
                        )
                    );

                    $ordered_products[] = array(
                        'id' => (string) $product_id,
                        'title' => strip_tags($item_product_data['name']),
                        'is_gift_product' => '0',
                        'stock' => ($product->get_stock_status() == 'instock') ? true : false,
                        'id_product_attribute' => (string) $item_product->get_variation_id(),
                        'quantity' => (string) $item_product->get_quantity(),
                        'price' => html_entity_decode( strip_tags( wc_price( $item_product->get_subtotal() ) ) ),
                        'discount_price' => '',
                        'discount_percentage' => '',
                        'total' => html_entity_decode( strip_tags( wc_price( $item_product->get_total() ) ) ),
                        'product_items' => $product_info,
                        'images' => get_the_post_thumbnail_url( $product_id, array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) ),
                        'cusomizable_items' => array()
                    );
                }

                //Vouchers
                $vouchers = array();
                
                //Order Totals
                $order_subtotal = $order_info->get_subtotal();
                $order_discount = $order_info->get_total_discount();
                $order_tax = $order_info->get_total_tax();
                $order_shipping = $order_info->get_shipping_total();
                $order_total = $order_info->get_total();
                
                $order_totals = array(
                    array(
                        'name' => __('Subtotal', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( wc_price( $order_subtotal ) ) )
                    ),
                    array(
                        'name' => __('Discount', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( wc_price( $order_discount ) ) )
                    ),
                    array(
                        'name' => __('Tax', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( wc_price( $order_tax ) ) )
                    ),
                    array(
                        'name' => __('Shipping', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( wc_price( $order_shipping ) ) )
                    ),
                    array(
                        'name' => __('Total', 'woocommerce-mobile-app-builder'),
                        'value' => html_entity_decode( strip_tags( wc_price( $order_total ) ) )
                    )
                );
                
                //API Response
                $this->wmab_response['order_details'] = array(
                    'order_history' => $order_history,
                    'shipping_address' => $shipping_address,
                    'shipping_method' => array('name' => $shipping_method),
                    'payment_method' => array('name' => $payment_method),
                    'billing_address' => $billing_address,
                    'products' => $ordered_products,
                    'status_history' => $status_history,
                    'total' => $order_totals,
                    'vouchers' => $vouchers,
                    'gift_wrapping' => array(
                        'available' => '0',
                        'applied' => '0',
                        'message' => '',
                        'cost_text' => ''
                    ),
                    'order_comment' => $order_comment
                );
                
                $this->wmab_response['status'] = 'success';
                $this->wmab_response['message'] = '';
            } else {
                $this->wmab_response['status'] = 'failure';
                $this->wmab_response['message'] = __('Customer does not exist.', 'woocommerce-mobile-app-builder');
            }
        } else {
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid Email Address or Order ID.', 'woocommerce-mobile-app-builder');
        }
        
        //Log Request
        log_knowband_app_request("appGetOrderDetails", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appGetOrders API request - Module Upgrade V2 - changes added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetOrders
     * @param string $email This parameter holds Customer Email
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_orders($email, $version) {
        global $wpdb;
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetOrders');
		
        if (isset($email) && !empty($email)) {
            $customer_id = email_exists($email);
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                $customer = new WC_Customer($customer_id);
                
				//Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$phone_number = ''; //it will hold customer's Phone Number
				$country_code = ''; //it will hold customer's Country Code
				$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					$phone_number = $getMapping->mobile_number;
					$country_code = $getMapping->country_code;
				}
				//EOC - Module Upgrade V2
				
                //Get Customer First Name
                $first_name = $customer->get_first_name();                
                //Get Customer Last Name
                $last_name = $customer->get_last_name();                
                //Personal Info Response
                $this->wmab_response['personal_info'] = array(
                    'firstname' => $first_name,
                    'lastname' => $last_name,
                    'email' => $email,
					'mobile_number' => $phone_number, //Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019 to pass Customers Phone Number
					'country_code' => $country_code, //Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019 to pass Customers Country Code
                );
                
                //Get Customer Orders and details                
		$customer_orders = wc_get_orders( array(
			'customer' => $customer_id,
			'limit'    => -1,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'ids',
		) );
                
                if (isset($customer_orders) && !empty($customer_orders)) {
                    foreach ($customer_orders as $order) {
                        $order_details = wc_get_order($order);
                        $order_details = $order_details->get_data();

                        // The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
                        $ordered_products = array();
                        foreach( $order_details['line_items'] as $item_id => $item_product ) {
                            $item_product_data = $item_product->get_data(); //Get Item data                            
                            //Get the product ID
                            $product_id = $item_product->get_product_id();
                            //Get the WC_Product object
                            $product = $item_product->get_product();
                            //Product Info
                            $product_info = array(
                                array(
                                    'name' => __('SKU', 'woocommerce-mobile-app-builder'),
                                    'value' => $product->get_sku()
                                )
                            );
                            
                            $ordered_products[] = array(
                                'id' => (string) $product_id,
                                'title' => strip_tags($item_product_data['name']),
                                'is_gift_product' => '0',
                                'stock' => ($product->get_stock_status() == 'instock') ? true : false,
                                'id_product_attribute' => (string) $item_product->get_variation_id(),
                                'quantity' => (string) $item_product->get_quantity(),
                                'price' => html_entity_decode( strip_tags( wc_price( $item_product->get_subtotal() ) ) ),
                                'discount_price' => '',
                                'discount_percentage' => '',
                                'total' => html_entity_decode( strip_tags( wc_price( $item_product->get_total() ) ) ),
                                'product_items' => $product_info,
                                'images' => get_the_post_thumbnail_url( $product_id, array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) ),
                                'cusomizable_items' => array()
                            );
                        }

                        //Get Order details
                        $this->wmab_response['order_history'][] = array(
                            'order_id' => $order,
                            'cart_id' => '',
                            'order_number' => (string) $order,
                            'status' => ucwords($order_details['status']),
                            'status_color' => '#26A69A',
                            'date_added' => date("Y-m-d H:i:s", strtotime($order_details['date_created'])),
                            'total' => html_entity_decode( strip_tags( wc_price( $order_details['total'] ) ) ),
                            'reorder_allowed' => '1',
                            'products' => $ordered_products
                        );
                    }
                } else {
                    $this->wmab_response['order_history'] = array();
                }
                $this->wmab_response['status'] = 'success';
                $this->wmab_response['message'] = '';
            } else {
                $this->wmab_response['status'] = 'failure';
                $this->wmab_response['message'] = __('Customer does not exist.', 'woocommerce-mobile-app-builder');
            }
        } else {
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid Email Address.', 'woocommerce-mobile-app-builder');
        }
        
        //Log Request
        log_knowband_app_request("appGetOrders", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appReorder API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appReorder
     * @param string $email This parameter holds Customer Email
     * @param string $session_data This parameter holds current cart ID
     * @param int $order_id This parameter holds Order ID to reorder
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_reorder($email, $session_data, $order_id, $version) {
        global $wpdb; 
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appReorder');
        
        if (isset($email) && !empty($email)) {
            $customer_id = email_exists($email);
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                if (isset($order_id) && !empty($order_id)) {
                    
                    //Include file to import wmab customer class
                    require_once( plugin_dir_path( __FILE__ ) . 'wmab-customer.php');
                    //Create class object
                    $wmab_customer = new WmabCustomer();
                    
                    $customer = new WC_Customer($customer_id);
                    
                    //Get Products of Order
                    $order_info = wc_get_order($order_id);
                    $order_details = $order_info->get_data();
                    //print_r($order_details); die;
                    
                    // The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
                    $ordered_products = array();
                    foreach( $order_details['line_items'] as $item_id => $item_product ) {
                        $item_product_data = $item_product->get_data(); //Get Item data                            
                        //Get the product ID
                        $product_id = $item_product->get_product_id();
                        //Get the WC_Product object
                        $product = get_product( $product_id );
                        //Product Info
                        $product_info = array(
                            array(
                                'name' => __('SKU', 'woocommerce-mobile-app-builder'),
                                'value' => $product->get_sku()
                            )
                        );
                        
                        //Product Options/Attributes
                        $attributes = array();
                        $variation_id = $item_product->get_variation_id();
                        if ($variation_id) {
                            $variation = get_product( $variation_id );
                            $product_options = $variation->get_attributes();
                            if (isset($product_options) && !empty($product_options)) {
                                foreach ($product_options as $option => $value) {
                                    $option_id = wc_attribute_taxonomy_id_by_name( str_replace('pa_', '', $option) );
                                    $option_value_id = get_term_by( 'slug', $value, $option );
                                    //Set Attributes into an array variable
                                    $attributes[] = array(
                                        'id' => $option_id,
                                        'selected_value_id' => $option_value_id->term_id,
                                    );
                                }
                            }
                        }                        

                        $ordered_products[] = array(
                            'quantity' => (string) $item_product->get_quantity(),
                            'product_id' => (string) $product_id,
                            'minimal_quantity' => (string) $item_product->get_quantity(),
                            'option' => $attributes,
                            'id_product_attribute' => (string) $item_product->get_variation_id()
                        );
                    }
                    
                    $cart_products = json_encode(array(
                        'session_id' => '',
                        'request_type' => 'add',
                        'user_type' => '',
                        'email' => $email,
                        'cart_products' => $ordered_products,
                        'customization_details' => array(),
                        'voucher' => '',
                        'coupon' => ''
                    ));
                    
                    WC()->session->set( 'reorder' , "1" );
                    //Call up API to add product into cart and return cart ID
                    $add_to_cart = $wmab_customer->app_add_to_cart($cart_products, $customer_id, true, $version);
                    
                    //Call up to get the response data of shopping cart
                    $this->wmab_response = $wmab_customer->app_get_cart_details('', $customer_id, true, $version, true);
                    
                    $this->wmab_response['status'] = 'success';
                    $this->wmab_response['message'] = '';
                } else {
                    $this->wmab_response['status'] = 'failure';
                    $this->wmab_response['message'] = __('Invalid Order ID.', 'woocommerce-mobile-app-builder');
                }
            } else {
                $this->wmab_response['status'] = 'failure';
                $this->wmab_response['message'] = __('Customer does not exist.', 'woocommerce-mobile-app-builder');
            }
        } else {
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid Email Address.', 'woocommerce-mobile-app-builder');
        }
        //Log Request
        log_knowband_app_request("appReorder", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appGetPaymentMethods API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetPaymentMethods
     * @param string $email This parameter holds Customer Email
     * @param string $session_data This parameter holds current Cart ID
     * @param string $id_shipping_address This parameter holds Shipping Address ID
     * @param string $order_message This parameter holds Order Message
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_payment_methods($email, $session_data, $id_shipping_address, $order_message, $version, $temp) {
        
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetPaymentMethods');
        
        //Log Request
        log_knowband_app_request("appGetPaymentMethods", json_encode($this->wmab_response));

        if (isset($email) && !empty($email)) {
            $customer_id = email_exists($email);
            
            $wc_customer = new WC_Customer($customer_id);
            $shipping_address = $wc_customer->get_shipping();  //id_shipping_address not required as only one shipping address is kept by woocommerce
                
            //Get All States
            $wc_country = new WC_Countries();
            $states = $wc_country->__get('states');
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                if (isset($session_data) && !empty($session_data)) {
                    $cart_id = $session_data;
                } else {
                    $cart_id = $customer_id;
                }
                
                //Set Cart Session
                $this->set_session($cart_id);
                
                $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                
                WC()->cart->get_cart();
                
                // Calc totals
				WC()->cart->calculate_totals();

                $checkout = WC()->checkout();
				wp_set_current_user( $customer_id );
		
		
		if($temp == 0) {
			wp_set_auth_cookie($customer_id, true, false);
            header('Content-Type: text/html');

            echo '<form method="post" action="" id="payment_form">';
            echo '<input type="hidden" name="email" value="'.$email.'">';
            echo '<input type="hidden" name="id_shipping_address" value="'.$id_shipping_address.'">';
            echo '<input type="hidden" name="order_message" value="'.$order_message.'">';
            echo '<input type="hidden" name="version" value="'.$version.'">';
            echo '<input type="hidden" name="session_data" value="'.$session_data.'">';
            echo '<input type="hidden" name="temp" value="1">';
            
            echo '</form>';
            echo '<script>';
            echo 'document.getElementById("payment_form").submit();';
            echo '</script>';
            die();
            
        }
      
header('Content-Type: text/html');
                echo '<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <style>'.$this->wmab_plugin_settings['general']['custom_css'].'</style>
  </head>
  <body>'.wc_get_template_html( '../../'.str_replace('/api', '', plugin_basename(dirname(__FILE__))).'/views/form-checkout.php', array(
			'checkout'           => $checkout,
                        'customer'           => $wc_customer->get_data(),
                        'order_message'      => $order_message,
			'email'		     => $email
		) ).'</body>
</html>';

            }
        }
    }
    
    /**
     * Function to handle appGetMobilePaymentMethods API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetMobilePaymentMethods
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_mobile_payment_methods($version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetMobilePaymentMethods');
        
        $this->wmab_response['status'] = 'success';
        $this->wmab_response['message'] = '';
        $this->wmab_response['payments'] = array();
        
        //Check if PayPal method is enabled and then send the response
        if (isset($this->wmab_plugin_settings['payment_methods']['paypal_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['paypal_enabled'])) {
            $this->wmab_response['payments'][] = array(
                'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['payment_method_name'],
                'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['payment_method_code'],
                'configuration' => array(
                    'payment_method_mode' => $this->wmab_plugin_settings['payment_methods']['payment_method_mode'] ? 'live' : 'sandbox',
                    'client_id' => $this->wmab_plugin_settings['payment_methods']['client_id'],
                    'is_default' => 'yes',
                    'other_info' => ''
                ),
            );
        }
        
        //Check if CoD method is enabled and then send the response
        if (isset($this->wmab_plugin_settings['payment_methods']['cod_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['cod_enabled'])) {
            $this->wmab_response['payments'][] = array(
                'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_name'],
                'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_code'],
                'configuration' => array(
                    'payment_method_mode' => 'live',
                    'client_id' => '',
                    'is_default' => 'no',
                    'other_info' => ''
                ),
            );
        }
        
        //Log Request
        log_knowband_app_request("appGetMobilePaymentMethods", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);        
        die;
        
    }
    
    /**
     * Function to handle appCreateOrder API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCreateOrder
     * @param string $email This parameter holds Customer Email Address
     * @param string $session_data This parameter holds current cart ID
     * @param string $payment_info This parameter holds Payment Information
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_create_order($email, $session_data, $payment_info, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appCreateOrder');
        
        //Default Response
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Order could not be created.', 'woocommerce-mobile-app-builder');
        $this->wmab_response['order_id'] = ''; 
        
        /*$payment_info = json_encode(array(
            'payment_method_name' => 'PayPal',
            'payment_method_code' => 'paypal',
            'transaction_id' => '1212121212121212',
            'status' => 'success',
            'amount' => '18.90'
        ));*/
        
        if (isset($email) && !empty($email) && isset($payment_info) && !empty($payment_info)) {
            $customer_id = email_exists($email);

            //Payment Info
            $payment_info = json_decode(stripslashes(trim($payment_info)), true);
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                $wc_customer = new WC_Customer($customer_id);
                $billing_address = $wc_customer->get_billing();  
                $shipping_address = $wc_customer->get_shipping();  
                
                //Get All States
                $wc_country = new WC_Countries();
                $states = $wc_country->__get('states');
                
                if (isset($session_data) && !empty($session_data)) {
                    $cart_id = $session_data;
                } else {
                    $cart_id = $customer_id;
                }
                
                //Set Cart Session
                $this->set_session($cart_id);

		//Check Payment and set code
		if (isset($payment_info['payment_method_code']) && $payment_info['payment_method_code'] == 'paypal') {
			$payment_info['payment_method_code'] = 'paypal'; //Default settings for WooCommerce
		}
                
                $cart = WC()->cart;
                $order_data = array(
                    'terms' => 0,
                    'createaccount' => 0,
                    'payment_method' => isset($payment_info['payment_method_code']) ? $payment_info['payment_method_code'] : '',
                    'shipping_method' => WC()->session->get('chosen_shipping_methods'),
                    'ship_to_different_address' => 1,
                    'woocommerce_checkout_update_totals' => '',
                    'billing_first_name' => isset($billing_address['first_name']) ? $billing_address['first_name'] : '',
                    'billing_last_name' => isset($billing_address['last_name']) ? $billing_address['last_name'] : '',
                    'billing_company' => isset($billing_address['company']) ? $billing_address['company'] : '',
                    'billing_country' => !empty($billing_address['country']) ? WC()->countries->countries[ $billing_address['country'] ] : '',
                    'billing_address_1' => isset($billing_address['address_1']) ? $billing_address['address_1'] : '',
                    'billing_address_2' => isset($billing_address['address_2']) ? $billing_address['address_2'] : '',
                    'billing_city' => isset($billing_address['city']) ? $billing_address['city'] : '',
                    'billing_state' => (!empty($billing_address['country']) && !empty($billing_address['state'])) ? $states[ $billing_address['country'] ][ $billing_address['state'] ] : '',
                    'billing_postcode' => isset($billing_address['postcode']) ? $billing_address['postcode'] : '',
                    'billing_phone' => isset($billing_address['phone']) ? $billing_address['phone'] : '',
                    'billing_email' => isset($email) ? $email : '',
                    'shipping_first_name' => isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '',
                    'shipping_last_name' => isset($shipping_address['last_name']) ? $shipping_address['last_name'] : '',
                    'shipping_company' => isset($shipping_address['company']) ? $shipping_address['company'] : '',
                    'shipping_country' => !empty($shipping_address['country']) ? WC()->countries->countries[ $shipping_address['country'] ] : '',
                    'shipping_address_1' => isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '',
                    'shipping_address_2' => isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '',
                    'shipping_city' => isset($shipping_address['city']) ? $shipping_address['city'] : '',
                    'shipping_state' => (!empty($shipping_address['country']) && !empty($shipping_address['state'])) ? $states[ $shipping_address['country'] ][ $shipping_address['state'] ] : '',
                    'shipping_postcode' => isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '',
                    'order_comments' => ''
                    
                );
                log_knowband_app_request("appCreateOrder", json_encode($order_data));
                //Calculate shipping and get Shippign Methods
                WC()->shipping->calculate_shipping( WC()->cart->get_shipping_packages() );
                
                $order_id = WC()->checkout()->create_order($order_data);                
                $order = wc_get_order( $order_id );                
                update_post_meta($order_id, '_customer_user', $customer_id);
                //Update Paypal Payment Status
                if (isset($payment_info['status']) && !empty($payment_info['status'])) {
                    update_post_meta( $order_id, '_paypal_status', $payment_info['status'] );
                }
                //Update Paypal Payment Transaction ID
                if (isset($payment_info['transaction_id']) && !empty($payment_info['transaction_id'])) {
                    update_post_meta( $order_id, '_transaction_id', $payment_info['transaction_id'] );
                }
                $order->calculate_totals();                
                if (isset($payment_info['transaction_id']) && !empty($payment_info['transaction_id'])) {
                    $order->payment_complete($payment_info['transaction_id']);
                }
                $cart->empty_cart();
				WC()->session->set('cart', array());
                
                if ($order_id) {
                    
                    //Check if FCM and Cart mapping exists
                    $fcm_data = $this->isFcmExist($cart_id, $email);
                    if (isset($fcm_data) && !empty($fcm_data)) {
                        //Update FCM and Order mapping into the table
                        $this->mapOrderWithFCM($order_id, $fcm_data->fcm_details_id);
                    }
                    
                    //Order Success Push Notification
                    if (isset($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled'])) {
                        //Get Notification Title and Message
                        $notification_title = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_title'];
                        $notification_message = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_msg'];
                        
                        $this->notify($notification_title, $notification_message, 'order_placed', $cart_id, $order_id, $email, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']);
                    }
                    
                    $this->wmab_response['status'] = 'success';
                    $this->wmab_response['message'] = __('Order created by this cart.', 'woocommerce-mobile-app-builder');
                    $this->wmab_response['order_id'] = (string) $order_id;                    
                }
            }
        }
        
        //Log Request
        log_knowband_app_request("appCreateOrder", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to insert/update FCM and Order mapping into the DB table
     * 
     * @param string $order_id This parameter holds Order ID
     * @param string $email This parameter holds Customer Email
     * @param string $cart_id This parameter holds current cart ID
     * @param string $fcm_id This parameter hold FCM ID of Mobile device through which Push Notification can be sent
     * @author Knowband
     */
    private function mapOrderWithFCM($order_id, $update_id) {
        global $wpdb;
        
        if (isset($update_id) && !empty($update_id) && isset($order_id) && !empty($order_id)) {
            if ($update_id) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET order_id = %d, date_upd = now() WHERE fcm_details_id = %d", $order_id, $update_id));
            }
        }
    }
    
    /**
     * Function to check if FCM and Cart mapping exist
     * @param string $cart_id This parameter holds current Cart ID
     * @param string $email This parameter holds Customer Email
     * @author Knowband
     */
    private function isFcmExist($cart_id, $email) {
        global $wpdb;
        
        $checkMapping = ''; //Default definition of variable
        
        if (!empty($email) && !empty($cart_id)) {
            $checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND last_order_status = 0 AND order_id IS NULL", $cart_id));
            //BOC neeraj.kumar@velsof.com 31-dec-2019 Resolved this issue :  trim() expects parameter 1 to be string, object given by removing Trim function
            if (empty($checkMapping)) {
                $checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND last_order_status = 0 AND order_id IS NULL", $email));
            }
        } else if (!empty($email)) {
            $checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND last_order_status = 0 AND (order_id = 0 or order_id is NULL)", $email));
        } else if (!empty($cart_id)) {
            $checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND last_order_status = 0 AND order_id = 0", $cart_id));
        }
        
        return $checkMapping;
    }
    
    /**
     * Function to check if FCM and Order mapping exist
     * @param string $order_id This parameter holds Order ID
     * @author Knowband
     */
    private function isFcmAndOrderMappingExist($order_id) {
        global $wpdb;
        
        $checkMapping = ''; //Default definition of variable
        
        if (!empty($order_id)) {
            $checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE order_id = %d", $order_id));
        }
        return $checkMapping;
    }
    
    /**
     * Function to get Abandoned Carts
     * @author Knowband
     */
    private function getAbandonedCarts($interval) {
        global $wpdb;
        
        $abandoned_carts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE order_id IS NULL AND (notification_sent_status = 0 OR notification_sent_status IS NULL) AND last_order_status = 0 AND date_upd < DATE_SUB(NOW(), INTERVAL ".$interval." HOUR)");
        
        return $abandoned_carts;
    }
    
    
    /**
     * Function to send push notification for Abandoned Cart
     */
    public function app_send_abandoned_cart_push_notification() {
        global $wpdb;
        
        //Abandoned Cart Push Notification
        if (isset($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_enabled'])) {
            
            //Get Abandoned Carts
            $abandoned_carts = $this->getAbandonedCarts($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_time_interval']);

            if ($abandoned_carts) {
                foreach ($abandoned_carts as $abandoned_cart) {
                    //Get Notification Title and Message
                    $notification_title = $this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_notification_title'];
                    $notification_message = $this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_notification_msg'];

                    if ( $this->notify($notification_title, $notification_message, 'order_abandoned', $abandoned_cart->cart, $abandoned_cart->order_id, $abandoned_cart->email_id, $abandoned_cart->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']) ) {
                        //Update Notification sent status
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET notification_sent_status = '1', date_upd = now() WHERE fcm_details_id = %d", $abandoned_cart->fcm_details_id));
                    }
                }
            }
        }
        
        die;
    }
    
    /*
     * Function to send push notification on Order Status Pending
     */
    public function order_status_update($order_id, $status, $status_id) {
        global $wpdb;
        
        //Order Success Push Notification
        if (isset($this->wmab_plugin_settings['push_notification_settings']['order_status_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_status_enabled'])) {
            
            if ($order_id) {
                //Check if FCM and Cart mapping exists
                $fcm_data = $this->isFcmAndOrderMappingExist($order_id);

                if (isset($fcm_data) && !empty($fcm_data)) {
                    //Get Notification Title and Message
                    $notification_title = str_replace("{{STATUS}}", $status, $this->wmab_plugin_settings['push_notification_settings']['order_status_notification_title']);
                    $notification_message = str_replace("{{STATUS}}", $status, $this->wmab_plugin_settings['push_notification_settings']['order_status_notification_msg']);

                    if ( $this->notify($notification_title, $notification_message, 'order_status_changed', $fcm_data->cart, $order_id, $fcm_data->email_id, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']) ) {
                        //Update Last Order Status
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET last_order_status = %s, date_upd = now() WHERE fcm_details_id = %d", $status_id, $fcm_data->fcm_details_id));
                    }
                }
            }
        }                    
    }
    
    /**
     * Function to send push notifications
     * @param string $title This parameter holds Push Notification Title
     * @param string $message This parameter holds Push Notification Message
     * @param string $push_type This parameter holds Push Notification Push Type
     * @param string $cart_id This parameter holds Cart ID
     * @param int $order_id This parameter holds Order ID
     * @param string $email This parameter holds Customer Email
     * @param string $fcm_id This parameter holds FCM ID
     * @param string $firebase_server_key This parameter holds Firebase Server Key
     */
    private function notify($title, $message, $push_type, $cart_id, $order_id, $email, $fcm_id, $firebase_server_key) {
        
        $firebase_data = array();
        $firebase_data['data']['title'] = $title;
        $firebase_data['data']['is_background'] = false;
        $firebase_data['data']['message'] = $message;
        $firebase_data['data']['image'] = '';
        $firebase_data['data']['payload'] = '';
        $firebase_data['data']['user_id'] = '';
        $firebase_data['data']['push_type'] = $push_type;
        $firebase_data['data']['cart_id'] = $cart_id;
        $firebase_data['data']['order_id'] = $order_id;
        $firebase_data['data']['email_id'] = $email;

        if ($fcm_id) {
            wmab_send_multiple($fcm_id, $firebase_data, $firebase_server_key);
        }
        
        return true;
    }
    
}
?>
