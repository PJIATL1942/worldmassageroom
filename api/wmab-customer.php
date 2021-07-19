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
 * class - WmabCustomer
 * 
 * This class contains constructor and other methods which are actually related to customer information or cart information
 * @author Knowband
 * @version v1.1
 * @Date 29-Jun-2018
 */
class WmabCustomer {
    
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
    
    /**
     * Function to handle appAddToCart API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appAddToCart
     * @param string $cart_products This parameter holds json string of cart products
     * @param string $session_data This parameter holds cart id or session id
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_add_to_cart($cart_products, $session_data, $return = false, $version) {
        
        global $wpdb; 
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appAddToCart');
        
        /*$cart_products = json_encode(array(
            'session_id' => '',
            'request_type' => 'add',
            'user_type' => '',
            'email' => 'hagarwal+a1@velsof.com',
            'cart_products' => array(
                array(
                    'quantity' => '2',
                    'product_id' => '125',
                    'minimal_quantity' => '1',
                    'option' => array(
                        array(
                            'id' => '1',
                            'selected_value_id' => '25'
                        ),
                        array(
                            'id' => '2',
                            'selected_value_id' => '28'
                        )
                    ),
                    'id_product_attribute' => '159'
                )
            ),
            'customization_details' => array(),
            'voucher' => '',
            'coupon' => 'ac9vtzbk'
        ));*/
        
        if (isset($cart_products) && !empty($cart_products)) {
            $cart_products = json_decode(stripslashes($cart_products));
            $email = $cart_products->email;
        
            if (isset($session_data) && !empty($session_data)) {
                $cart_id = $session_data;
            } else if (isset($email) && !empty($email)) {
                $cart_id = email_exists($email);
            } else {
		if ( empty( $cart_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher      = new PasswordHash( 8, false );
			$cart_id = md5( $hasher->get_random_bytes( 32 ) );
		}
	    }

            
        $session_value_reorder = $wpdb->get_var("SELECT session_value FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".(int)$cart_id."' and reorder_direct='1'");
        if(!empty($session_value_reorder)) {
            WC()->cart->empty_cart();
            $cart_value_reorder = json_decode($session_value_reorder,true);
            $cart_content3 = $cart_value_reorder['cart'];
            foreach ($cart_content3 as $cart_products_reorder) {

                if (isset($cart_products_reorder['variation_id']) && !empty($cart_products_reorder['variation_id'])) {
                        $product_id_reorder = $cart_products_reorder['variation_id'];
                    } else {
                        $product_id_reorder = $cart_products_reorder['product_id'];
                    }
                    WC()->cart->add_to_cart($product_id_reorder, $cart_products_reorder['quantity'], $cart_products_reorder['variation_id'],$cart_products_reorder['variation'] , array());
            }
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".$cart_id."'"));
            WC()->session->__unset( 'reorder' );
        }
        
            
            if (isset($cart_id) && !empty($cart_id)) {
                //Set Cart Session
                $this->set_session($cart_id);
                
                $products_to_add = $cart_products->cart_products;

                if (isset($products_to_add) && !empty($products_to_add)) {
                    foreach ($products_to_add as $product) {                        
                        $variation_id = !empty($product->id_product_attribute) ? $product->id_product_attribute : '';
                        $attributes = $product->option;
                        $product_attributes = array();
                        if (isset($attributes) && !empty($attributes)) {
                            foreach ($attributes as $attribute) {
                                //Get Attribute Taxonomy name by ID
                                $attribute_name = wc_attribute_taxonomy_name_by_id( $attribute->id );
                                $attribute_value = get_term_by( 'term_taxonomy_id', $attribute->selected_value_id );
                                $product_attributes['attribute_'.$attribute_name] = isset($attribute_value->name) ? $attribute_value->name : '';
                            }
                        }
                        $qty = $product->minimal_quantity != '' ? $product->minimal_quantity : $product->quantity;
                        //Add Product into the cart
                        if ( WC()->cart->add_to_cart($product->product_id, $qty, $variation_id, $product_attributes, array()) ) {
                            if (isset($cart_products->coupon) && !empty($cart_products->coupon)) {
                                //Check if woocommerce allowed to redeem coupon
                                if (get_option( 'woocommerce_enable_coupons' ) === 'yes') {
                                    WC()->cart->apply_coupon($cart_products->coupon);
                                }
                            }
                            
                            if (!$return) {
                                //Success Reponse
                                $this->wmab_response['status'] = 'success';
                                $this->wmab_response['message'] = __('Product successfully added into the cart.', 'woocommerce-mobile-app-builder');
                                $this->wmab_response['session_data'] = WC()->session->get_customer_id(); //!empty($session_data) ? $session_data : $cart_id;
                                //BOC Module Upgrade V2 neeraj.kumar@velsof.com return total cart count when user click add to cart.
                                $this->wmab_response['total_cart_items'] = WC()->cart->get_cart_contents_count();
                                $this->wmab_response['total_cart_count'] = WC()->cart->get_cart_contents_count();
                                //Log Request
                                log_knowband_app_request("appAddToCart", json_encode($this->wmab_response));
        
                                echo json_encode($this->wmab_response);
                                die;
                            }
                        }
                    }
                }
            }
        }
        
        if (!$return) {
            //Failure Reponse
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Product cannot be added into the cart.', 'woocommerce-mobile-app-builder');
            $this->wmab_response['session_data'] = WC()->session->get_customer_id(); //!empty($session_data) ? $session_data : $cart_id;
        
            //Log Request
            log_knowband_app_request("appAddToCart", json_encode($this->wmab_response));
        
            echo json_encode($this->wmab_response);
            die;
        }
    }
    
    /**
     * Function to handle appGetCartDetails API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCartDetails
     * @param string $email This parameter holds customer email address
     * @param string $session_data This parameter holds cart id or session id
     * @param boolean $return This parameter holds true of false to return value
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_cart_details($email, $session_data, $return = false, $version, $reorder = false) {
        global $wpdb;

        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetCartDetails');
        
        $session_value = $wpdb->get_var("SELECT session_value FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".(int)$session_data."' and reorder_direct='1'");
        
        if(!empty($session_value)) {
            WC()->cart->empty_cart();
            $cart_value = json_decode($session_value,true);
            $cart_content3 = $cart_value['cart'];
            foreach ($cart_content3 as $cart_products) {

                if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
                        $product_id = $cart_products['variation_id'];
                    } else {
                        $product_id = $cart_products['product_id'];
                    }
                    WC()->cart->add_to_cart($cart_products['product_id'], $cart_products['quantity'], $cart_products['variation_id'],$cart_products['variation'] , array());
            }
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".$session_data."'"));
            WC()->session->__unset( 'reorder' );
        }
        
        
        
        $this->wmab_response['checkout_page'] = (object) array(
            'per_products_shipping' => (string)0
        );
        
        $this->wmab_response['products'] = array();
        $this->wmab_response['vouchers'] = array();
        //Gift Wrapping is not available in WooCommerce so it would be send as default response
        $this->wmab_response['gift_wrapping'] = (object) array(
            'available' => '',
            'applied' => '',
            'message' => '',
            'cost_text' => ''
        );
        $this->wmab_response['guest_checkout_enabled'] = (string) get_option( 'woocommerce_enable_guest_checkout' ) === 'yes' ? '0' : '0'; //Set it as disabled
        $this->wmab_response['cart'] = (object) array(
            'total_cart_items' => 0,
            'total_cart_count' => 0
        );
        $this->wmab_response['voucher_allowed'] = (string) get_option( 'woocommerce_enable_coupons' ) === 'yes' ? 1 : 0;
        $this->wmab_response['minimum_purchase_message'] = '';
        $this->wmab_response['totals'] = array();
        $this->wmab_response['voucher_html'] = '';
        //Delay Shipping is not available in WooCommerce so it would be send as default response
        $this->wmab_response['delay_shipping'] = (object) array(
            'available' => (string) 0,
            'applied' => (string) 0
        );
        $this->wmab_response['cart_id'] = '';
        $this->wmab_response['coupon_allowed'] = (string) get_option( 'woocommerce_enable_coupons' ) === 'yes' ? 1 : 0;
        
        if (isset($session_data) && !empty($session_data)) {
            $cart_id = $session_data;
        } else if (isset($email) && !empty($email)) {
            $cart_id = email_exists($email);
            $this->set_session($cart_id);
        }
        
        if (isset($cart_id) && !empty($cart_id)) {
            //Set Cart Session
            
            $cart_content = WC()->cart->get_cart_contents();
            $total_cart_quantity = 0;
            if(empty($cart_content) && isset($email) && !empty($email)) {
                
                $session_value = $wpdb->get_var("SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = '".$cart_id."'");
                $cart_value = unserialize($session_value);
                $cart_content = unserialize($cart_value['cart']);
                foreach ($cart_content as $cart_item) {
                        if (!empty($cart_item['quantity'])) {
                                $total_cart_quantity += $cart_item['quantity'];
                        }
                }
            }
            
            //Get Cart Coupons
            $coupons = WC()->cart->get_coupons();
            if (isset($coupons) && !empty($coupons)) {
                foreach ($coupons as $coupon) {
                    $coupon_data = $coupon->get_data();
                    $this->wmab_response['vouchers'][] = array(
                        'id' => (string) $coupon_data['id'],
                        'name' => $coupon_data['code'],
                        'value' => '-'.html_entity_decode( strip_tags( wc_price( $coupon_data['amount'] )))
                    );
                }
            }

            $cart_counter = 0;
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

				//Option Data for Product SKU
                $option_data[] = array(
                    'name' => __( 'SKU', 'woocommerce-mobile-app-builder' ),
                    'value' => $product->get_sku()
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
                $product_image = get_the_post_thumbnail_url( $product_id, array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) );
                
                if ($product_image[0] == null && !empty($cart_products['variation_id'])) {
                    $product_image = get_the_post_thumbnail_url( $cart_products['product_id'], array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) );
                }
                
		if ($cart_products['quantity'] != '000') { //Condition added to ignore products which are removed by updating quantity as 000 through appRemoveProduct API
		        $this->wmab_response['products'][] = array(
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

			$cart_counter++;
		}
            }
            
            if(WC()->cart->get_cart_contents_count()) {
                $this->wmab_response['cart'] = (object) array(
                    'total_cart_items' => WC()->cart->get_cart_contents_count(),
                    'total_cart_count' => WC()->cart->get_cart_contents_count()
                );
            } else {
                $this->wmab_response['cart'] = (object) array(
                    'total_cart_items' => $total_cart_quantity,
                    'total_cart_count' => (int)$total_cart_quantity
                );
            }
            
            //Cart SubTotal
            $cart_sub_total = WC()->cart->get_cart_subtotal();
            $this->wmab_response['totals'][] = array(
                'name' => __('Subtotal', 'woocommerce-mobile-app-builder'),
                'value' => html_entity_decode( strip_tags($cart_sub_total) )
            );
            
            //Cart Coupon
            foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
                $this->wmab_response['totals'][] = array(
                    'name' => sanitize_title( $code ),
                    'value' => '-' . html_entity_decode( strip_tags( wc_price(  WC()->cart->get_coupon_discount_amount( $code, WC()->cart->display_cart_ex_tax ) ) ) )
                );
            }
            
            //Cart Shipping Total
//            $cart_shipping_total = WC()->cart->get_cart_shipping_total();
//            $this->wmab_response['totals'][] = array(
//                'name' => __('Shipping', 'woocommerce-mobile-app-builder'),
//                'value' => html_entity_decode( strip_tags($cart_shipping_total) )
//            );
            
            //Fee
            foreach ( WC()->cart->get_fees() as $fee ) {
                $this->wmab_response['totals'][] = array(
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
                        $this->wmab_response['totals'][] = array(
                            'name' => esc_html( $tax->label ) . $estimated_text,
                            'value' => html_entity_decode( strip_tags(wp_kses_post( $tax->formatted_amount )) )
                        );
                    }
                } else {
                    $this->wmab_response['totals'][] = array(
                        'name' => esc_html( WC()->countries->tax_or_vat() ) . $estimated_text,
                        'value' => html_entity_decode( strip_tags( wc_price( WC()->cart->get_taxes_total() ) ) )
                    );
                }
            }
            
            $this->wmab_response['totals'][] = array(
                'name' => __('Total', 'woocommerce-mobile-app-builder'),
                'value' => html_entity_decode( strip_tags( WC()->cart->get_total() ) )
            );
            
	    if (isset($reorder) && $reorder) {
                $this->wmab_response['cart_id'] = (string) $cart_id;
	    } else {
	        $this->wmab_response['cart_id'] = $cart_id;
	    }
        }
        
        if (!$return) {
            
            //Log Request
            log_knowband_app_request("appGetCartDetails", json_encode($this->wmab_response));
        
            echo json_encode($this->wmab_response);
            die;
        } else {
            return $this->wmab_response;
        }
        
    }
    
    /**
     * Function to handle appCheckOrderStatus API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckOrderStatus
     * @param string $email This parameter holds customer email address
     * @param string $session_data This parameter holds cart id or session id
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_check_order_status($email, $session_data, $version) {
        global $wpdb;
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appCheckOrderStatus');
        
        //Default Response Parameters
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = '';
        $this->wmab_response['cart_id'] = $session_data;
        
        if (isset($session_data) && !empty($session_data)) {
            $cart_id = $session_data;
        } else if (isset($email) && !empty($email)) {
            $cart_id = email_exists($email);
        }
        
        if (isset($cart_id) && !empty($cart_id)) {
            //Set Cart Session
            $this->set_session($cart_id);
        
	    if (WC()->cart->is_empty()) {                
                $this->wmab_response['status'] = 'success';
                $this->wmab_response['message'] = __('Order created by this cart.', 'woocommerce-mobile-app-builder');
                $this->wmab_response['cart_id'] = $cart_id;
	    }
        }
        
        //Log Request
        log_knowband_app_request("appCheckOrderStatus", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appGetCustomerAddress API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCustomerAddress
     * @param string $email This parameter holds customer email
     * @param string $session_data This parameter holds cart id or session id
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_customer_address($email, $session_data, $version) {
        global $wpdb;
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetCustomerAddress');
        
        $this->wmab_response['default_address'] = '';
        $this->wmab_response['shipping_address'] = array();

        //Get Customer ID by email
        $customer_id = email_exists($email);
        
        //Get All States
        $wc_country = new WC_Countries();
        $states = $wc_country->__get('states');
        
        $session_id = $wpdb->get_var("SELECT session_key FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".(int)$customer_id."' and reorder_direct='0'");
        if(!empty($session_id)) {
            $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $session_id ) );
            $cart_value = unserialize($session_value);
            $cart_content3 = unserialize($cart_value['cart']);    
            foreach ($cart_content3 as $cart_products) {
                if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
                        $product_id = $cart_products['variation_id'];
                    } else {
                        $product_id = $cart_products['product_id'];
                    }
                    WC()->cart->add_to_cart($cart_products['product_id'], $cart_products['quantity'], $cart_products['variation_id'],$cart_products['variation'] , array());
            }
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".(int)$customer_id."'"));
        }
        
        //Get Customer Shipping Address details
        $wc_customer = new WC_Customer($customer_id);
        $customer = $wc_customer->get_shipping();  
        $customer_billing = $wc_customer->get_billing();  
        
        if (isset($customer['first_name']) && !empty($customer['first_name'])) {
            $this->wmab_response['default_address'] = '1';   //Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available
            $this->wmab_response['shipping_address'][] = array(
                'id_shipping_address' => '1',
                'firstname' => isset($customer['first_name']) ? $customer['first_name'] : '',
                'lastname' => isset($customer['last_name']) ? $customer['last_name'] : '',
                'company' => isset($customer['company']) ? $customer['company'] : '',
                'address_1' => isset($customer['address_1']) ? $customer['address_1'] : '',
                'address_2' => isset($customer['address_2']) ? $customer['address_2'] : '',
                'city' => isset($customer['city']) ? $customer['city'] : '',
                'state' => !empty($states[ $customer['country'] ][ $customer['state'] ]) ? $states[ $customer['country'] ][ $customer['state'] ] : $customer['state'],
                'country' => !empty($customer['country']) ? WC()->countries->countries[ $customer['country'] ] : '',
                'postcode' => isset($customer['postcode']) ? $customer['postcode'] : '',
                'phone' => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
            );
        }
        
        //Log Request
        log_knowband_app_request("appGetCustomerAddress", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    } 
    
    /**
     * Function to handle appGetAddressForm API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetAddressForm
     * @param string $email This parameter holds customer email
     * @param int $id_shipping_address This parameter holds shipping address id
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_address_form($email, $id_shipping_address, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetAddressForm');
        
        //Get All Countries
        $wc_country = new WC_Countries();
        $countries = $wc_country->__get('countries');
        
        $this->wmab_response['countries'] = array();
        if (isset($countries) && !empty($countries)) {
            foreach ($countries as $code => $name) {
                $this->wmab_response['countries'][] = array(
                    'id' => $code,
                    'name' => $name
                );
            }
        }
        $checkout = WC()->checkout();
        $billing_fields = $checkout->get_checkout_fields( 'billing' );
        $shipping_fields = $checkout->get_checkout_fields( 'shipping' );
        /*
         * For now $id_shipping_address is unused as WooCommerce does nnot support multiple address feature
         * Address is being fetched on the basis of customer email ID
         */
        
        //Get Customer ID by email
        $customer_id = email_exists($email);
        
        //Get Customer Shipping Address details
        $wc_customer = new WC_Customer($customer_id);
        $customer = $wc_customer->get_shipping();  
        $customer_billing = $wc_customer->get_billing();  
                
               
        //Get Shipping Address Form
        $this->wmab_response['shipping_address_items'] = array(
            array(
                'label' => __('First Name', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'first_name',
                'value' => isset($customer['first_name']) ? $customer['first_name'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Last Name', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'last_name',
                'value' => isset($customer['last_name']) ? $customer['last_name'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Company', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'company',
                'value' => isset($customer['company']) ? $customer['company'] : '',
                'required' => '0',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Address Line 1', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'address_1',
                'value' => isset($customer['address_1']) ? $customer['address_1'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Address Line 2', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'address_2',
                'value' => isset($customer['address_2']) ? $customer['address_2'] : '',
                'required' => '0',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Town/City', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'city',
                'value' => isset($customer['city']) ? $customer['city'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('State/County', 'woocommerce-mobile-app-builder'),
                'type' => 'dropdownfield',
                'name' => 'state',
                'value' => isset($customer['state']) ? $customer['state'] : '',
                'required' => isset($billing_fields['billing_state']['required']) ? $billing_fields['billing_state']['required'] : '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Postcode/Zip', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'postcode',
                'value' => isset($customer['postcode']) ? $customer['postcode'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Country', 'woocommerce-mobile-app-builder'),
                'type' => 'dropdownfield',
                'name' => 'country',
                'value' => isset($customer['country']) ? $customer['country'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
            array(
                'label' => __('Phone', 'woocommerce-mobile-app-builder'),
                'type' => 'textfield',
                'name' => 'phone',
                'value' => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
                'required' => '1',
                'validation' => '',
                'group_items' => array(),
            ),
        );

        //Get Default Country
		$default_country = get_option('woocommerce_default_country');
		$country_string = wc_format_country_state_string($default_country);
		
		$this->wmab_response['default_state_id'] = !empty($customer['state']) ? $customer['state'] : '';
        $this->wmab_response['default_country_id'] = !empty($customer['country']) ? $customer['country'] : $country_string['country'];
        
        //Log Request
        log_knowband_app_request("appGetAddressForm", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    } 
    
    /**
     * Function to handle appAddAddress API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appAddAddress
     * @param string $shipping_address This parameter holds json string of customer shipping address
     * @param string $email This parameter hold customer email
     * @param string $session_data This parameter holds cart id or session id
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_add_address($shipping_address, $email, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appAddAddress');
        
        //Get Customer ID by email
        $customer_id = email_exists($email);
        
        if (isset($customer_id) && !empty($customer_id)) {
            $shipping_address = json_decode(str_replace("\\", "", str_replace('&quot;', '"', (trim($shipping_address)))), true);

            //Updating both Billing and shipping as only one address is being allowed by WooCommerce
            //Update First Name
            update_user_meta($customer_id, 'shipping_first_name', $shipping_address['first_name']);
            update_user_meta($customer_id, 'billing_first_name', $shipping_address['first_name']);
            //Update Last Name
            update_user_meta($customer_id, 'shipping_last_name', $shipping_address['last_name']);
            update_user_meta($customer_id, 'billing_last_name', $shipping_address['last_name']);
            //Update Company
            update_user_meta($customer_id, 'shipping_company', $shipping_address['company']);
            update_user_meta($customer_id, 'billing_company', $shipping_address['company']);
            //Update Address 1
            update_user_meta($customer_id, 'shipping_address_1', $shipping_address['address_1']);
            update_user_meta($customer_id, 'billing_address_1', $shipping_address['address_1']);
            //Update Address 2
            update_user_meta($customer_id, 'shipping_address_2', $shipping_address['address_2']);
            update_user_meta($customer_id, 'billing_address_2', $shipping_address['address_2']);
            //Update City
            update_user_meta($customer_id, 'shipping_city', $shipping_address['city']);
            update_user_meta($customer_id, 'billing_city', $shipping_address['city']);
            //Update State
            update_user_meta($customer_id, 'shipping_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
            update_user_meta($customer_id, 'billing_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
            //Update PostCode
            update_user_meta($customer_id, 'shipping_postcode', $shipping_address['postcode']);
            update_user_meta($customer_id, 'billing_postcode', $shipping_address['postcode']);
            //Update Country
            update_user_meta($customer_id, 'shipping_country', $shipping_address['country']);
            update_user_meta($customer_id, 'billing_country', $shipping_address['country']);
            
            update_user_meta($customer_id, 'billing_phone', $shipping_address['phone']);

            $this->wmab_response['shipping_address_reponse'] = array(
                'status' => 'success',
                'message' => __('Shipping address has been added successfully.', 'woocommerce-mobile-app-builder')
            );

        } else {
            $this->wmab_response['shipping_address_reponse'] = array(
                'status' => 'failure',
                'message' => __('Shipping address could not be added successfully.', 'woocommerce-mobile-app-builder')
            );
        }
        
        $this->wmab_response['cart_id'] = $session_data;
        $this->wmab_response['shipping_address_count'] = 1;
        $this->wmab_response['id_shipping_address'] = 1;  //Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available

        //Log Request
        log_knowband_app_request("appAddAddress", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    } 
    
    /**
     * Function to handle appSaveProductReview API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.2/appAddReview
     * @param string $customer_review This parameter holds json string of customer review
     * @param string $email This parameter hold customer email
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_add_review($product_id, $title, $content, $customer_name, $rating, $email, $version){
                //First do the API version verification and then go ahead
        $this->verify_api($version, 'appSaveProductReview');
        
        //Get Customer ID by email
        $customer_id = email_exists($email);
        
            if($customer_id > 0) {
                $customer_approved = 1;
            } else {
                $customer_approved = 0;
            }
        
            $args = array(
                'comment_post_ID' => $product_id,
                'comment_author' => $customer_name,
                'comment_author_email' => $email,
                'comment_author_IP' => preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']), 
                'comment_date' => date("Y-m-d H:i:s",strtotime("+5 Hours 30 Minutes")),
                'comment_date_gmt' => date("Y-m-d H:i:s"),
                'comment_content' => $content,
                'comment_approved' => $customer_approved,
                'comment_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 254),
                'comment_type' => 'review',
                'user_id' => $customer_id,
                'comment_meta' => array(
                    'rating' => $rating,
                    'verified' => 0,
                ),
                );
            $comment_wp_id = wp_insert_comment($args);
            
            if($comment_wp_id){
                $this->wmab_response['response'] = array(
                    'status' => 'success',
                    'message' => __('Review has been added successfully.', 'wmab')
                );
            }else {
                $this->wmab_response['response'] = array(
                    'status' => 'failure',
                    'message' => __('Review could not be added successfully.', 'wmab')
                );
            }
                
        //Log Request
        kbpwaapp_log_knowband_app_request("appSaveProductReview", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appUpdateAddress API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateAddAddress
     * @param int $id_shipping_address This parameter holds ID of shipping address to be updated
     * @param string $shiping_address This parameter holds customer shipping address details
     * @param string $email This parameter holds customer email
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_update_address($id_shipping_address, $shipping_address, $email, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appUpdateAddress');
        
        //Get Customer ID by email
        $customer_id = email_exists($email);
        
        if (isset($customer_id) && !empty($customer_id)) {
            $shipping_address = json_decode(str_replace("\\", "", str_replace('&quot;', '"', (trim($shipping_address)))), true);

            //Update First Name
            update_user_meta($customer_id, 'shipping_first_name', $shipping_address['first_name']);
            update_user_meta($customer_id, 'billing_first_name', $shipping_address['first_name']);
            //Update Last Name
            update_user_meta($customer_id, 'shipping_last_name', $shipping_address['last_name']);
            update_user_meta($customer_id, 'billing_last_name', $shipping_address['last_name']);
            //Update Company
            update_user_meta($customer_id, 'shipping_company', $shipping_address['company']);
            update_user_meta($customer_id, 'billing_company', $shipping_address['company']);
            //Update Address 1
            update_user_meta($customer_id, 'shipping_address_1', $shipping_address['address_1']);
            update_user_meta($customer_id, 'billing_address_1', $shipping_address['address_1']);
            //Update Address 2
            update_user_meta($customer_id, 'shipping_address_2', $shipping_address['address_2']);
            update_user_meta($customer_id, 'billing_address_2', $shipping_address['address_2']);
            //Update City
            update_user_meta($customer_id, 'shipping_city', $shipping_address['city']);
            update_user_meta($customer_id, 'billing_city', $shipping_address['city']);
            //Update State
            update_user_meta($customer_id, 'shipping_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
            update_user_meta($customer_id, 'billing_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
            //Update PostCode
            update_user_meta($customer_id, 'shipping_postcode', $shipping_address['postcode']);
            update_user_meta($customer_id, 'billing_postcode', $shipping_address['postcode']);
            //Update Country
            update_user_meta($customer_id, 'shipping_country', $shipping_address['country']);
            update_user_meta($customer_id, 'billing_country', $shipping_address['country']);
            //Update Phone
            update_user_meta($customer_id, 'billing_phone', $shipping_address['phone']);

            $this->wmab_response['shipping_address_reponse'] = array(
                'status' => 'success',
                'message' => __('Shipping address has been updated successfully.', 'woocommerce-mobile-app-builder')
            );
            
        } else {
            $this->wmab_response['shipping_address_reponse'] = array(
                'status' => 'failure',
                'message' => __('Shipping address could not be updated successfully.', 'woocommerce-mobile-app-builder')
            );
        }
        
        $this->wmab_response['cart_id'] = $session_data;
        $this->wmab_response['shipping_address_count'] = 1;
        $this->wmab_response['id_shipping_address'] = 1;  //Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available
        
        //Log Request
        log_knowband_app_request("appUpdateAddress", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    } 
    
    /**
     * Function to handle appSocialLogin API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appSocialLogin
     * @param string $user_details This parameter holds customer details
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_social_login($user_details, $session_data, $version) {
        global $wpdb;

        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appSocialLogin');
        
        if (isset($user_details) && !empty($user_details)) {
            $user_details = json_decode(stripslashes($user_details));
            
            if (isset($user_details->email) && !empty($user_details->email)) {
                //Check if user already exists
                $customer = email_exists( $user_details->email );
                
                if ($customer) {
                    //Replcae Cart ID with Customer ID
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $customer, $session_data));

                    //Get Cart details from session table based on customer ID
                    $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $customer ) );
                    $cart_value = unserialize($session_value);
                    $cart_content = unserialize($cart_value['cart']);

                    wp_clear_auth_cookie();
                    wp_set_current_user($customer);
                    wp_set_auth_cookie($customer, true, false);
                    
					$this->set_session($customer);
					
                    //Login Successful
                    $this->wmab_response['login_user'] = array(
                        'status' => 'success',
                        'message' => __('User login successfully.', 'woocommerce-mobile-app-builder'),
                        'customer_id' => $customer,
                        'wishlist_count' => '0', //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                        'session_data' => $customer,   //WooCommerce saves customer ID as session key in session table to refer cart and other details
                        'cart_count' => is_array($cart_content) ? count($cart_content) : 0,
                    );
                    echo json_encode($this->wmab_response);
                    die;
                } else {
                    //Reister user with given details
                    $registration = false;
					if (get_option( 'woocommerce_registration_generate_username' ) === 'no') {
						$username = $user_details->email; 
					} else {
						$username = ''; //Set blank in case of registration through social login function
					}

					if (get_option( 'woocommerce_registration_generate_password' ) === 'no') {
						$password = rand(00000, 99999); //Random password is generating as we do not have password value through Socia Login
					} else {
						$password = '';  //Set blank in case of registration through social login function
					}
                                        
                    $validation_error = new WP_Error();
                    $validation_error = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password, $user_details->email );

                    if ( !$validation_error->get_error_code() ) {

                        $new_customer = wc_create_new_customer( sanitize_email( $user_details->email ), wc_clean( $username ), $password );

                        if ( !is_wp_error( $new_customer ) ) {

                            $registration = true;

                        }
                    }
                    
                    if ($registration) {
                        //Registration successful
                        
                        //Replcae Cart ID with Customer ID
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $new_customer, $session_data));

                        //Get Cart details from session table based on customer ID
                        $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $new_customer ) );
                        $cart_value = unserialize($session_value);
                        $cart_content = unserialize($cart_value['cart']);
                        
                        wp_clear_auth_cookie();
                        wp_set_current_user($new_customer);
                        wp_set_auth_cookie($new_customer, true, false);
                        
						$this->set_session($new_customer);
						
                        $this->wmab_response['login_user'] = array(
                            'status' => 'success',
                            'message' => __('User login successfully.', 'woocommerce-mobile-app-builder'),
                            'customer_id' => $new_customer,
                            'wishlist_count' => '0', //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                            'session_data' => $new_customer,   //WooCommerce saves customer ID as session key in session table to refer cart and other details
                            'cart_count' => is_array($cart_content) ? count($cart_content) : 0,
                        );
                        echo json_encode($this->wmab_response);
                        die;
                    }
                }
            }
        }
        
        //Login and customer details verification Failed
        $this->wmab_response['login_user'] = array(
            'status' => 'failure',
            'message' => __('User login failed.', 'woocommerce-mobile-app-builder'),
            'customer_id' => '0',
            'wishlist_count' => '0', //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
            'session_data' => '',
            'cart_count' => '0',
        );

        //Log Request
        log_knowband_app_request("appSocialLogin", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    } 
    
    /**
     * Function to handle appLogin API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLogin
     * @param string $username This parameter holds Customer username
     * @param string $password This parameter holds Customer account password
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_login($username, $password, $session_data, $version) {
        global $wpdb; 
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appLogin');
	
        $creds = array(
            'user_login'    => trim( $username ),
            'user_password' => $password,
            'remember'      => 1,   //Keep it by default 1
        );
        
        // Perform the login
        $user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );
        
        log_knowband_app_request("appLogin", json_encode($user));
        
        if (is_wp_error($user)) {
            //Login Failed
            $this->wmab_response['login_user'] = array(
                'status' => 'failure',
                'message' => __('User login failed.', 'woocommerce-mobile-app-builder'),
                'customer_id' => '0',
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => '',
                'cart_count' => 0,
            );
        } else {
            //Replcae Cart ID with Customer ID
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $user->data->ID, $session_data));

            
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '".(int)$user->data->ID."'"));
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_login_sessions (user_id,session_key) VALUES ('".(int)$user->data->ID."','".$session_data."')"));
            
            //Get Cart details from session table based on customer ID
            $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $user->data->ID ) );
            $cart_value = unserialize($session_value);
            $cart_content = unserialize($cart_value['cart']);
            
            wp_clear_auth_cookie();
            wp_set_current_user($user->data->ID);
            wp_set_auth_cookie($user->data->ID, true, false);
            
            $this->set_session($user->data->ID);
            
            //Login Successful
            $this->wmab_response['login_user'] = array(
                'status' => 'success',
                'message' => __('User login successfully.', 'woocommerce-mobile-app-builder'),
                'customer_id' => (string) $user->data->ID,
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => $user->data->ID,   //WooCommerce saves customer ID as session key in session table to refer cart and other details
                'cart_count' => is_array($cart_content) ? count($cart_content) : 0,
            );
        }
        
        //Log Request
        log_knowband_app_request("appLogin", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
	
	/**
     * Function to handle appLoginViaPhone API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLoginViaPhone
     * @param string $phone_number This parameter holds Customer Phone Number
     * @param string $country_code This parameter holds Country Code
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
	 * @param string $iso_code This parameter holds Language ISO code
     * @author Knowband
     */
    public function app_login_via_phone($phone_number, $country_code, $session_data, $version, $iso_code) {
        global $wpdb; 
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appLoginViaPhone');
		
		//Verify Phone Number for Login
        $verify_phone = false;
		//Get Customer ID from unique verification table of MAB
		$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s", $phone_number));
		if (isset($getMapping) && !empty($getMapping)) {
			$verify_phone = true;
			$customer_id = $getMapping->user_id;
			//Get User details by ID
			$customer = get_user_by('id', $customer_id);
			$customer_email = '';
			if (isset($customer->data->user_email) && !empty($customer->data->user_email)) {
				$customer_email = $customer->data->user_email;
			}
		}
		
        if (!$verify_phone) {
            //Login Failed
            $this->wmab_response['login_user'] = array(
                'status' => 'failure',
                'message' => __('User login failed.', 'woocommerce-mobile-app-builder'),
                'customer_id' => '0',
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => '',
                'cart_count' => 0,
            );
        } else {
			
            //Replcae Cart ID with Customer ID
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $customer_id, $session_data));

            //Get Cart details from session table based on customer ID
            $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $customer_id ) );
            $cart_value = unserialize($session_value);
            $cart_content = unserialize($cart_value['cart']);
            
            wp_clear_auth_cookie();
            wp_set_current_user($customer_id);
            wp_set_auth_cookie($customer_id, true, false);
            
			$this->set_session($customer_id);
			
            //Login Successful
            $this->wmab_response['login_user'] = array(
                'status' => 'success',
                'message' => __('User login successfully.', 'woocommerce-mobile-app-builder'),
                'customer_id' => (string) $customer_id,
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => $customer_id,   //WooCommerce saves customer ID as session key in session table to refer cart and other details
				'email' => $customer_email,
                'cart_count' => is_array($cart_content) ? count($cart_content) : 0
            );
        }
        
        //Log Request
        log_knowband_app_request("appLoginViaPhone", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
	
	/**
     * Function to handle appLoginViaEmail API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLoginViaEmail
     * @param string $email_id This parameter holds Customer Email ID
     * @param string $unique_id This parameter holds Unique ID for FingerPrint Matching
     * @param string $version This parameter holds API version to verify during API call
	 * @param string $iso_code This parameter holds Language ISO code
     * @author Knowband
     */
    public function app_login_via_email($email_id, $unique_id, $version, $iso_code) {
        global $wpdb; 
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appLoginViaEmail');
		
		//Verify Email ID and Unique ID for FingerPrint Login
		$verify_email = false;
		
		$customer_id = email_exists($email_id);
		
		if (isset($customer_id) && !empty($customer_id)) {
			$verification = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d AND unique_id = %s", $customer_id, $unique_id));
			if (isset($verification) && count($verification) > 0) {
				$verify_email = true;
			}
		}
		
		if (!$verify_email) {
            //Login Failed
            $this->wmab_response['login_user'] = array(
                'status' => 'failure',
                'message' => __('User login failed.', 'woocommerce-mobile-app-builder'),
                'customer_id' => '0',
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => '',
				'email' => $email_id,
                'cart_count' => 0,
            );
        } else {
			//Replcae Cart ID with Customer ID
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $customer_id, $session_data));

            //Get Cart details from session table based on customer ID
            $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $customer_id ) );
            $cart_value = unserialize($session_value);
            $cart_content = unserialize($cart_value['cart']);
            
            wp_clear_auth_cookie();
            wp_set_current_user($customer_id);
            wp_set_auth_cookie($customer_id, true, false);
            
			$this->set_session($customer_id);
			
            //Login Successful
            $this->wmab_response['login_user'] = array(
                'status' => 'success',
                'message' => __('User login successfully.', 'woocommerce-mobile-app-builder'),
                'customer_id' => (string) $customer_id,
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => $customer_id,   //WooCommerce saves customer ID as session key in session table to refer cart and other details
				'email' => $email_id,
                'cart_count' => is_array($cart_content) ? count($cart_content) : 0
            );
        }
        
        //Log Request
        log_knowband_app_request("appLoginViaEmail", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
	
	/**
     * Function to handle appMapEmailWithUUID API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appMapEmailWithUUID
     * @param string $email_id This parameter holds Customer Email ID
     * @param string $unique_id This parameter holds Unique ID for FingerPrint Matching
     * @param string $version This parameter holds API version to verify during API call
	 * @param string $iso_code This parameter holds Language ISO code
     * @author Knowband
     */
    public function app_map_email_with_uuid($email_id, $unique_id, $version, $iso_code) {
        global $wpdb; 
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appMapEmailWithUUID');
		
		if (isset($email_id) && !empty($email_id) && isset($unique_id) && !empty($unique_id)) {
			//Map Email ID with Unique ID
			$is_mapped = false;
			
			$customer_id = email_exists($email_id);		
			if (isset($customer_id) && !empty($customer_id)) {
				$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					//Update Unique ID for existing User record
					$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET unique_id = %s WHERE id = %d", $unique_id, $getMapping->id));
					$is_mapped = true;
				} else {
					//Insert Customer and Unique ID mapping
					$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, unique_id = %s, date_added = now()", $customer_id, $unique_id));
					$is_mapped = true;
				}
			}
			
			if ($is_mapped) {
				$this->wmab_response['status'] = 'success';
				$this->wmab_response['message'] = __('Account has been mapped for the fingerprint login in this device', 'woocommerce-mobile-app-builder');
			} else {
				$this->wmab_response['status'] = 'failure';
				$this->wmab_response['message'] = __('Account could not be mapped for fingerprint login. Please try again.', 'woocommerce-mobile-app-builder');
			}
		} else {
			$this->wmab_response['status'] = 'failure';
			$this->wmab_response['message'] = __('Invalid Email ID or Unique ID.', 'woocommerce-mobile-app-builder');
		}
        
        //Log Request
        log_knowband_app_request("appMapEmailWithUUID", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
	
	/**
     * Function to handle appCheckIfContactNumberExists API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckIfContactNumberExists
     * @param string $phone_number This parameter holds Customer Phone Number
     * @param string $country_code This parameter holds Country Code
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_check_if_contact_number_exists($phone_number, $country_code, $exclude_customer = false, $customer_id = '', $version) {
        global $wpdb; 
        
		//First do the API version verification and then go ahead
        $this->verify_api($version, 'appCheckIfContactNumberExists');
		
		//Verify Phone Number for Login
        $verify_phone_exists = false;
		if (!$exclude_customer) {
			$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s", $phone_number));
		} else {
			if (!empty($customer_id)) {
				$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND user_id != %d", $phone_number, $customer_id));
			}
		}
		if (isset($getMapping) && !empty($getMapping)) {
			$verify_phone_exists = true;
		}
		
		if ($exclude_customer) {
			if ($verify_phone_exists) {
				//Phone Number Exists
				$this->wmab_response['status'] = 'failure';
				$this->wmab_response['message'] = __('Mobile number exists into the database.', 'woocommerce-mobile-app-builder');
				$this->wmab_response['does_mobile_number_exists'] = true;
				$this->wmab_response['session_data'] = '';
				
				//Log Request
				log_knowband_app_request("appCheckIfContactNumberExists", json_encode($this->wmab_response));
				
				echo json_encode($this->wmab_response);
				die;
			}
		} else {
		
			if ($verify_phone_exists) {
				//Phone Number Exists
				$this->wmab_response['status'] = 'failure';
				$this->wmab_response['message'] = __('Mobile number exists into the database.', 'woocommerce-mobile-app-builder');
				$this->wmab_response['does_mobile_number_exists'] = true;
				$this->wmab_response['session_data'] = '';
			} else {
				//Phone Number not Exists
				$this->wmab_response['status'] = 'success';
				$this->wmab_response['message'] = __('Mobile number does not exist into the database.', 'woocommerce-mobile-app-builder');
				$this->wmab_response['does_mobile_number_exists'] = false;
				$this->wmab_response['session_data'] = '';
			}
			
			//Log Request
			log_knowband_app_request("appCheckIfContactNumberExists", json_encode($this->wmab_response));
			
			echo json_encode($this->wmab_response);
			die;
		}
    }
    
    /**
     * Function to handle appRegisterUser API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRegisterUser
     * @param string $signup This parameter holds JSON string of Registration Form input
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_register_user($sign_up, $session_data, $version) {
        global $wpdb;
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appRegisterUser');
        
        $registration = false;

		$sign_up = json_decode(stripslashes($sign_up));

        if (isset($sign_up->username)) {
            $username = $sign_up->username;
        } else {
            $username = '';
        }
        $email = $sign_up->email;
        $password = $sign_up->password;
		$phone_number = $sign_up->mobile_number; //Module Upgrade V2 - Added new parameter by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		$country_code = str_replace("+", "", trim($sign_up->country_code)); //Module Upgrade V2 - Added new parameter by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		if (!empty($country_code)) {
			$country_code = '+'.$country_code;
		}
		

        $username = 'no' === get_option( 'woocommerce_registration_generate_username' ) ? $username : '';
        $password = 'no' === get_option( 'woocommerce_registration_generate_password' ) ? $password : '';
        
        $validation_error = new WP_Error();
        $validation_error = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password, $email );

        if ( !$validation_error->get_error_code() ) {
            
			if (!empty($phone_number)) {
				//Check if MObile Number already exists into the database of MAB - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$verify_phone_exists = false;
				$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s", $phone_number));
				
				if (isset($getMapping) && !empty($getMapping)) {
					$verify_phone_exists = true;
				}
				
				if ($verify_phone_exists) {
					//Phone Number Exists
					$this->wmab_response['status'] = 'failure';
					$this->wmab_response['message'] = __('Mobile number exists into the database.', 'woocommerce-mobile-app-builder');
					$this->wmab_response['does_mobile_number_exists'] = true;
					$this->wmab_response['session_data'] = '';
					
					//Log Request
					log_knowband_app_request("appRegisterUser", json_encode($this->wmab_response));
					
					echo json_encode($this->wmab_response);
					die;
				}
			}
					
            $new_customer = wc_create_new_customer( sanitize_email( $email ), wc_clean( $username ), $password );
            
            if ( !is_wp_error( $new_customer ) ) {
                
                $registration = true;
                
				/*
				 * Create User ID and Phone Number mapping MAB DB Table for future references at the time of login 
				 * via Phone Number - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				 */
				if (isset($new_customer) && !empty($new_customer)) {
					$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $new_customer));
					if (isset($getMapping) && !empty($getMapping)) {
						//Update details for existing User record
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET mobile_number = %s, country_code = %s WHERE id = %d", $phone_number, $country_code, $getMapping->id));
					} else {
						//Insert Customer and Mobile Number mapping
						$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, mobile_number = %s, country_code = %s, date_added = now()", $new_customer, $phone_number, $country_code));
					}
				}
				/**
				 * EOC - Module Upgrade V2
				 */
            }
        }
        
        //Check Registration process status and send response accordingly
        if (!$registration) {
            //Registration Failed
            $this->wmab_response['signup_user'] = array(
                'status' => 'failure',
                //'message' => __('Customer registration failed.', 'woocommerce-mobile-app-builder'),
                'message' => (isset($new_customer) && !empty($new_customer)) ? $new_customer->get_error_message() : $validation_error->get_error_message(),
                'customer_id' => '0',
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => '',
                'cart_count' => 0
            );
        } else {
            //Replcae Cart ID with Customer ID
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %d WHERE session_key = %s", $new_customer, $session_data));
            
            //Get Cart details from session table based on customer ID
            $session_value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $new_customer ) );
            $cart_value = unserialize($session_value);
            $cart_content = unserialize($cart_value['cart']);
            
            //wp_set_auth_cookie($new_customer, true);
            wp_clear_auth_cookie();
            wp_set_current_user($new_customer);
            wp_set_auth_cookie($new_customer, true, false);
            
			$this->set_session($new_customer);
			
            //Registration Successful
            $this->wmab_response['signup_user'] = array(
                'status' => 'success',
                'message' => __('Customer successfully created.', 'woocommerce-mobile-app-builder'),
                'customer_id' => (string) $new_customer,
                'wishlist_count' => 0, //Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
                'session_data' => (string) $new_customer, //WooCommerce saves customer ID as session key in session table to refer cart and other details
                'cart_count' => is_array($cart_content) ? count($cart_content) : 0,
            );
        }
        
        //Log Request
        log_knowband_app_request("appRegisterUser", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appForgotPassword API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appForgotPassword
     * @param string $email This parameter holds customer email
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_forgot_password($email, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appForgotPassword');
        
        if (empty($email)) {

            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __( 'Enter a username or email address.', 'woocommerce-mobile-app-builder' );
            
            echo json_encode($this->wmab_response);
            die;
            
        } else {
            // Check on username first, as customers can use emails as usernames.
            $user_data = get_user_by('login', $email);
        }

        // If no user found, check if it login is email and lookup user based on email.
        if (!$user_data && is_email($email) && apply_filters('woocommerce_get_username_from_email', true)) {
            $user_data = get_user_by('email', $email);
        }

        $errors = new WP_Error();

        do_action('lostpassword_post', $errors);

        if ($errors->get_error_code()) {
            
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = $errors->get_error_message();
            
            echo json_encode($this->wmab_response);
            die;
            
        }

        if (!$user_data) {
            
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid username or email.', 'woocommerce-mobile-app-builder');
            
            echo json_encode($this->wmab_response);
            die;
            
        }

        if (is_multisite() && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {
            
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid username or email.', 'woocommerce-mobile-app-builder');
            
            echo json_encode($this->wmab_response);
            die;
            
        }

        // redefining user_login ensures we return the right case in the email
        $user_login = $user_data->user_login;

        do_action('retrieve_password', $user_login);

        $allow = apply_filters('allow_password_reset', true, $user_data->ID);

        if (!$allow) {

            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Password reset is not allowed for this user.', 'woocommerce-mobile-app-builder');
            
            echo json_encode($this->wmab_response);
            die;
            
        } elseif (is_wp_error($allow)) {

            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = $allow->get_error_message();
            
            echo json_encode($this->wmab_response);
            die;
            
        }

        // Get password reset key (function introduced in WordPress 4.4).
        $key = get_password_reset_key($user_data);

        // Send email notification
        WC()->mailer(); // load email classes
        do_action('woocommerce_reset_password_notification', $user_login, $key);

        $this->wmab_response['status'] = 'success';
        $this->wmab_response['message'] = 'An email with reset password link has been sent to your email address.';

        //Log Request
        log_knowband_app_request("appForgotPassword", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appGetRegions API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetRegions
     * @param string $country This parameter holds countr ISO Code
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_regions($country, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetRegions');
        
        $this->wmab_response['zipcode_required'] = 1;
        $this->wmab_response['dni_required'] = 0;
        $this->wmab_response['states'] = array();
        if (isset($country) && !empty($country)) {
            $wc_country = new WC_Countries();
            $states = $wc_country->get_states( $country );
            
            if (isset($states) && !empty($states)) {
                foreach ($states as $code => $name) {
                    $this->wmab_response['states'][] = array(
                        'country_id' => $country,
                        'state_id' => $code,
                        'name' => $name
                    );
                }
            }
        }
        
        //Log Request
        log_knowband_app_request("appGetRegions", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appApplyVoucher API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appApplyVoucher
     * @param string $voucher This parameter holds Coupon Code to be applied
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_apply_voucher($voucher, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appApplyVoucher');
        
        //Default Response
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Coupon could not be applied.', 'woocommerce-mobile-app-builder');
        
        if (isset($session_data) && !empty($session_data)) {
            $cart_id = $session_data;
            //Set Cart Session
            $this->set_session($cart_id);
            
            if (isset($voucher) && !empty($voucher)) {
                //Check if woocommerce allowed to redeem coupon
                if (get_option( 'woocommerce_enable_coupons' ) === 'yes') {
                    if ( WC()->cart->apply_coupon($voucher) ) {
                        WC()->cart->calculate_totals();
                        $this->wmab_response['status'] = 'success';
                        $this->wmab_response['message'] = __('Coupon applied successfully.', 'woocommerce-mobile-app-builder');
                    }
                }
            }
        }
        
        //Call up to get the response data of shopping cart
        $this->app_get_cart_details('', $session_data, true, $version);
        
        //Log Request
        log_knowband_app_request("appApplyVoucher", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appRemoveVoucher API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRemoveVoucher
     * @param string $voucher This parameter holds Coupon Code to be removed
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_remove_voucher($voucher, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appRemoveVoucher');
        
        //Default Response
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Coupon could not be removed.', 'woocommerce-mobile-app-builder');
        
        if (isset($session_data) && !empty($session_data)) {
            $cart_id = $session_data;
            //Set Cart Session
            $this->set_session($cart_id);
            
            if (isset($voucher) && !empty($voucher)) {
                //Check if woocommerce allowed to redeem coupon
                if (get_option( 'woocommerce_enable_coupons' ) === 'yes') {
		    $coupon_code = wc_get_coupon_code_by_id($voucher);
                    if ( WC()->cart->remove_coupon($coupon_code) ) {
                        WC()->cart->calculate_totals();
                        $this->wmab_response['status'] = 'success';
                        $this->wmab_response['message'] = __('Coupon removed successfully.', 'woocommerce-mobile-app-builder');
                    }
                }
            }
        }
        
        //Call up to get the response data of shopping cart
        $this->app_get_cart_details('', $session_data, true, $version);
        
        //Log Request
        log_knowband_app_request("appRemoveVoucher", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appRemoveProduct API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRemoveProduct
     * @param string $cart_products This parameter holds JSON string of Product to be removed
     * @param string $email This parameter holds Customer Email
     * @param string $session_data This parameter holds current cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_remove_product($cart_products, $email, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appRemoveProduct');
        
        //Default Response
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Product could not be removed.', 'woocommerce-mobile-app-builder');

        /*$cart_products = json_encode(array(
            'cart_products' => array(
                array(
                    'product_id' => '125',
                    'quantity' => '2',
                    'id_product_attribute' => '159',
                    'id_customization_field' => ''
                )
            )
        ));*/
        
        if (isset($cart_products) && !empty($cart_products)) {
            $cart_products = json_decode(stripslashes($cart_products));
            if (isset($session_data) && !empty($session_data)) {
                $cart_id = $session_data;
            } else if (isset($email) && !empty($email)) {
                $cart_id = email_exists($email);
            }
            
            //Set Cart Session
            $this->set_session($cart_id);
            
            $cart_content = WC()->cart->get_cart_contents();
            
            if (isset($cart_content) && !empty($cart_content)) {
                foreach ($cart_products->cart_products as $cart_product) {
                    foreach ($cart_content as $cart_item_id => $cart_item) {
                        if ($cart_item['product_id'] == $cart_product->product_id) {
                            if (isset($cart_product->id_product_attribute) && $cart_product->id_product_attribute == $cart_item['variation_id']) {
                                if (WC()->cart->remove_cart_item($cart_item_id)) {
				//if (WC()->cart->set_quantity($cart_item['key'], '000', true)) { //This is used to remove product from cart as actual function which removes product from cart is not working at the moment - done by Harsh
				    //WC()->cart->calculate_totals();
				    //WC()->session->set( 'cart', WC()->session->get( 'cart' ) );
                                    $this->wmab_response['status'] = 'success';
                                    $this->wmab_response['message'] = __('Product successfully removed.', 'woocommerce-mobile-app-builder');
				    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        //Call up to get the response data of shopping cart
        $this->app_get_cart_details('', $session_data, true, $version);
        
        //Log Request
        log_knowband_app_request("appRemoveProduct", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appUpdateCartQuantity API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateCartQuantity
     * @param string $cate_products This parameter holds JSON string of cart products
     * @param string $email This parameter holds customer email
     * @param string $session_data This parameter holds customer cart ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_update_cart_quantity($cart_products, $email, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appUpdateCartQuantity');
        
        //Default Response
        $this->wmab_response['status'] = 'failure';
        $this->wmab_response['message'] = __('Product could not be updated.', 'woocommerce-mobile-app-builder');

        /*$cart_products = json_encode(array(
            'cart_products' => array(
                array(
                    'product_id' => '125',
                    'quantity' => '2',
                    'id_product_attribute' => '159',
                    'id_customization_field' => ''
                )
            )
        ));*/
        
        if (isset($cart_products) && !empty($cart_products)) {
            $cart_products = json_decode(stripslashes($cart_products));
            if (isset($session_data) && !empty($session_data)) {
                $cart_id = $session_data;
            } else if (isset($email) && !empty($email)) {
                $cart_id = email_exists($email);
            }
            
            //Set Cart Session
            $this->set_session($cart_id);
            
            $cart_content = WC()->cart->get_cart_contents();
            
            if (isset($cart_content) && !empty($cart_content)) {
                foreach ($cart_products->cart_products as $cart_product) {
                    //print_r($cart_product); die;
                    foreach ($cart_content as $cart_content) {
                        if ($cart_content['product_id'] == $cart_product->product_id) {
                            if (isset($cart_product->id_product_attribute) && $cart_product->id_product_attribute == $cart_content['variation_id']) {
                                $product_to_update = wc_get_product($cart_product->product_id);
                                if (($product_to_update->managing_stock() && $product_to_update->get_stock_quantity() >= $cart_product->quantity) || (!$product_to_update->managing_stock() && $product_to_update->is_in_stock())) {
                                    if (WC()->cart->set_quantity($cart_content['key'], $cart_product->quantity, true)) {
                                        $this->wmab_response['status'] = 'success';
                                        $this->wmab_response['message'] = __('Product successfully updated.', 'woocommerce-mobile-app-builder');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        //Call up to get the response data of shopping cart
        $this->app_get_cart_details('', $session_data, true, $version);
        
        //Log Request
        log_knowband_app_request("appUpdateCartQuantity", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to handle appGetRegistrationForm API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetRegistrationForm
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_registration_form($version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetRegistrationForm');
        
        if (get_option( 'woocommerce_registration_generate_username' ) === 'no') {
            if (get_option( 'woocommerce_registration_generate_password' ) === 'no') {
                $this->wmab_response['signup_details'] = array(
                    'username' => '',
                    'password' => '',
                );
            } else {
                $this->wmab_response['signup_details'] = array(
                    'username' => ''
                );
            }
        } else {
            if (get_option( 'woocommerce_registration_generate_password' ) === 'no') {
                $this->wmab_response['signup_details'] = array(
                    'email' => '',
                    'password' => '',
                );
            } else {
                 $this->wmab_response['signup_details'] = array(
                    'email' => ''
                );
            }
        }
        
        //Add a parameter to tell mobile app that password field should display or not
        $this->wmab_response['password'] = (get_option( 'woocommerce_registration_generate_password' ) === 'no') ? 'yes' : 'no';
		$this->wmab_response['username'] = (get_option( 'woocommerce_registration_generate_username' ) === 'no') ? 'yes' : 'no';
		$this->wmab_response['email'] = 'yes';
        
        //Log Request
        log_knowband_app_request("appGetRegistrationForm", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appUpdateProfile API request - Module Upgrade V2 - changes added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateProfile
     * @param string $email This parameter holds Customer Email
     * @param string $personal_info This parameter holds Customer profile information in JSON format
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_update_profile($email, $personal_info, $version) {
        global $wpdb;
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appUpdateProfile');
        
        /*$personal_info = json_encode(array(
            'first_name' => 'Harsh',
            'last_name' => 'Agarwal',
            'password' => 'Admin@123',
            'new_password' => 'Admin@123',
            'cnfrm_password' => 'Admin@123',
			'mobile_number' => '7676767666',
			'country_code' => '+91'
        ));*/
        
        if (isset($email) && !empty($email)) {
            $customer_id = email_exists($email);
            
            if (isset($customer_id) && !empty($customer_id)) {
                $personal_info = json_decode(stripslashes($personal_info));
                //Adding '+' symbol in Country Code
                $personal_info->country_code = str_replace("+", "", trim($personal_info->country_code));
                if (!empty($personal_info->country_code)) {
                        $personal_info->country_code = '+'.$personal_info->country_code;
                }
				
                $customer = new WC_Customer($customer_id);

                //Validate customer login
                $creds = array(
                    'user_login'    => trim( $email ),
                    'user_password' => $personal_info->password,
                    'remember'      => 1,   //Keep it by default 1
                );

                // Perform the login
                $user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );
                
                if (is_wp_error($user)) {
                    $this->wmab_response['status'] = 'failure';
                    $this->wmab_response['message'] = __('Invalid email or wrong password.', 'woocommerce-mobile-app-builder');
                } else {
                    //Check if MObile Number already exists into the database of MAB - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
                    //$this->app_check_if_contact_number_exists($personal_info->mobile_number, $personal_info->country_code, true, $customer_id, $version);
					
                    //Update Customer First Name
                    if (isset($personal_info->first_name) && !empty($personal_info->first_name)) {
                        $customer->set_first_name($personal_info->first_name);
                    }

                    //Update Customer Last Name
                    if (isset($personal_info->last_name) && !empty($personal_info->last_name)) {
                        $customer->set_last_name($personal_info->last_name);
                    }
                    
                    //Update Customer Account Password
                    if (isset($personal_info->new_password) && !empty($personal_info->new_password)) {
                        $customer->set_password($personal_info->new_password);
                    }
                    
                    $customer->save();
					
                    //Update Customer Phone Number in MAB DB Tables - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
                    $getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
                    if (isset($getMapping) && !empty($getMapping)) {
                            //Update Unique ID for existing User record
                            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET mobile_number = %s, country_code = %s WHERE id = %d", $personal_info->mobile_number, $personal_info->country_code, $getMapping->id));
                    } else {
                            //Insert Customer and Unique ID mapping
                            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, mobile_number = %s, country_code = %s, date_added = now()", $customer_id, $personal_info->mobile_number, $personal_info->country_code));
                    }
                    //EOC - Module Upgrade V2
                    
                    $this->wmab_response['status'] = 'success';
                    $this->wmab_response['message'] = __('Customer profile updated successfully.', 'woocommerce-mobile-app-builder');
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
        log_knowband_app_request("appUpdateProfile", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
	
	/**
     * Function to handle appUpdatePassword API request - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdatePassword
     * @param string $phone_number This parameter holds Customer Mobile Number
     * @param string $country_code This parameter holds Customer Country Code
	 * @param string $new_password This parameter holds Customer New Password
	 * @param string $session_data This parameter holds Session Data
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_update_password($phone_number, $country_code, $new_password, $session_data, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appUpdatePassword');
        
        if (isset($phone_number) && !empty($phone_number)) {
			//Get Customer ID by Phone Number
			$customer_id = '';
			$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s", $phone_number));
			if (isset($getMapping) && !empty($getMapping)) {
				$customer_id = $getMapping->user_id; //Set Customer ID here based on Phone Number
			}
            
            if (isset($customer_id) && !empty($customer_id)) {
                
                $customer = new WC_Customer($customer_id);

                //Update Customer Account Password
				if (isset($new_password) && !empty($new_password)) {
					$customer->set_password($new_password);
				}                    
				$customer->save();
                    
				$this->wmab_response['status'] = 'success';
				$this->wmab_response['message'] = __('The password has been changed successfully.', 'woocommerce-mobile-app-builder');
            } else {
                $this->wmab_response['status'] = 'failure';
                $this->wmab_response['message'] = __('Customer does not exist.', 'woocommerce-mobile-app-builder');
            }
        } else {
            $this->wmab_response['status'] = 'failure';
            $this->wmab_response['message'] = __('Invalid Phone Number.', 'woocommerce-mobile-app-builder');
        }
        
		$this->wmab_response['session_data'] = $session_data;
		
        //Log Request
        log_knowband_app_request("appUpdatePassword", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
}
?>
