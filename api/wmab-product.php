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
 * class - WmabProduct
 * 
 * This class contains constructor and other methods which are actually related to Product Page actions
 * @author Knowband
 * @version v1.1
 * @Date 29-Jun-2018
 */
class WmabProduct {
    
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
			if ( null === WC()->Tax ) {
				WC()->Tax = new WC_Tax(); //For Payment Gateway Object
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
     * Function to handle appGetProductDetails API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetProductDetails
     * @param int $product_id This parameter holds Product ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_product_details($product_id, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetProductDetails');
		
        //Get Product details
        $product = wc_get_product( $product_id ); //Replaced deprecated function get_product with wc_get_product on 04-Oct-2019
        //Get product reviews
        $reviews_data = array();
        $args = array ('post_type' => 'product', 'post_id' => $product_id, 'status' => 'approve');
        $reviews = get_comments( $args );
        
        $total_reviews = 0; 
        $reviews_rating_sum = 0;
        foreach ($reviews as $review){
            $total_reviews ++;
            $meta_reviews = get_comment_meta( $review->comment_ID, 'rating', TRUE );
            $reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
        }
        if($total_reviews != 0){
            $reviews_average = $reviews_rating_sum / $total_reviews;
        }else{
            $reviews_average = 0;
        }
        
        $is_product_new = 0;
        if (strtotime($product->get_date_created()) >= strtotime($this->wmab_plugin_settings['general']['product_new_date'])) {
            $is_product_new = 1;
        }

        $product_combinations = array();
        $option_value_list_v = array();
        $quantity_by_variation = 0;
        //BOC neeraj.kumar@velsof.com Added Checked to matched product attribute with variation or not
        $variation_ids_array = array();
        $product_type = $product->get_type(); //Get the product type correctly instead of getting it directly through $product->product_type on 04-Oct-2019
        if($product_type == 'variable') { //For Variable Product
            $available_variations = $product->get_available_variations();            
            if (isset($available_variations) && !empty($available_variations)) {
                foreach ($available_variations as $available_variation) {
                    if ($available_variation['variation_is_active']) {
                        $variation_id = $available_variation['variation_id']; // Getting the variable id of just the 1st product.
                        $variable_product = new WC_Product_Variation( $variation_id );
                        $regular_price = $variable_product->regular_price;
                        $sale_price = $variable_product->sale_price;
                        
                        $variation_attributes = $variable_product->get_variation_attributes();
                        
                        //Variation Attributes
                        $variation_attribute_id = array();
                        
                        if (!empty($variation_attributes)) {
                           
                            foreach ($variation_attributes as $key => $value) {
                                $option_value_id = get_term_by( 'slug', strtolower($value), str_replace("attribute_", "", $key) );  
                               
                                if (!empty($option_value_id->term_id)) {
                                    $variation_attribute_id[] = $option_value_id->term_id;
//                                    $option_value_list_v[str_replace("attribute_", "", $key)][] = array(
//                                        'id' => $option_value_id->term_id,
//                                        'value' => $value
//                                    );
                                    //Push variation_attribute id into custom array :neeraj.kumar@velsof.com
                                    array_push($variation_ids_array, $option_value_id->term_id);
                                } else {
                                    $variation_attribute_id[] = $value;    
                                    //Push variation_attribute id into custom array :neeraj.kumar@velsof.com
                                    array_push($variation_ids_array, $value);
                                }
                            }                          
                        }
                        
                        $variation_attribute = '';
                        
                        if (isset($available_variation['attributes']) && !empty($variation_attribute_id)) {
                            foreach ($available_variation['attributes'] as $key => $value) {
                                $variation_attribute = $value;
                            }   
                        }                        
                        
                        //if (isset($variation_attribute_id) && !empty($variation_attribute_id)) {
                            $product_combinations[] = array(
                                "id_product_attribute" => $available_variation['variation_id'],
                                "quantity" => $variable_product->get_stock_quantity(),
                                "price" => !empty($sale_price) ? html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $sale_price)) ) ) ) : html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $regular_price)) ) ) ),
                                "minimal_quantity" => $available_variation['min_qty'],
                                "combination_code" => (string) implode("_", $variation_attribute_id)
                            );
                        //}
                        
                        //To get highest quantity from variations
                        if ($quantity_by_variation < $available_variation['max_qty']) {
                            $quantity_by_variation = $available_variation['max_qty'];   
                        }
                    }
                }
            }
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
        
        //Product Image
        $product_image = get_the_post_thumbnail_url( $product_id, 'full' );

        //Product Info
        $product_info = array(
            array(
                'name' => __('SKU', 'woocommerce-mobile-app-builder'),
                'value' => $product->get_sku()
            )
        );

        //Product Options/Attributes
        $product_options = $product->get_attributes();
        $attributes = array();
        
        $set_product_combination = false;
        if (!isset($product_combinations) || count($product_combinations) == 0) {
            $product_combinations = array();
            $set_product_combination = true;
        }
        
		if($product_type == 'variable') { //For Variable Product
			if (isset($product_options) && !empty($product_options)) {
				foreach ($product_options as $product_option) {
					$options = (array)$product_option;
					foreach ($options as $option) {
                                            
						$option_value_list = array();
						
						for ($i = 0; $i < count($option['options']); $i++) {
							$option_value = get_term_by( 'term_taxonomy_id', $option['options'][$i] );
							//Extra checked added id are present in variation array
                                                        
                                                        if(in_array($option['options'][$i], $variation_ids_array)){
                                                            $option_value_list[] = array(
                                                                'id' => $option['options'][$i],
//                                                                'value' => !empty($option_value->name) ? $option_value->name : $option['options'][$i]
                                                                'value' => !empty($option_value->name) && is_int($option['options'][$i]) ? $option_value->name : $option['options'][$i]
                                                            );
                                                        }
						}
                                                //BOC neeraj.kumar@velsof.com only add option when option_value_list have some value
                                                if(isset($option_value_list) && !empty($option_value_list)){
                                                    $attributes[] = array(
                                                            'id' => $option['id'],
                                                            'title' => ucwords(str_replace('pa_', '', $option['name'])),
                                                            'is_color_option' => 0,
                                                            'items' => $option_value_list
                                                    );
                                                }
					}
				}
			}
		}
		
		//BOC - Changes added by Harsh on 04-Sep-2019 to get all product images (added in product gallery)
		$product_images = array();
		$product_images[]['src'] = $product_image;
		$attachment_ids = $product->get_gallery_image_ids(); //Replaced the deprecated function get_gallery_attachment_ids with get_gallery_image_ids on 04-Oct-2019
		if (isset($attachment_ids) && !empty($attachment_ids)) {
			foreach( $attachment_ids as $attachment_id ) {
				$product_images[]['src'] = wp_get_attachment_url( $attachment_id );
			}
		}
		//EOC
        
		//Changes added to pass default quantity if stock is managing globally instead of product based by Harsh on 04-Sep-2019
		$default_quantity = 0;
		if ($product->get_stock_status() === 'instock') {
			$default_quantity = 1000;//set to 1000 so that quantity stepper can work 
		}
		
        

        $product_ids = $product->get_upsell_ids( 'edit' );
       
        
        $related_products = getRelatedProducts($product_ids);
        
        $this->wmab_response['product'] = array(
            'id_product' => $product_id,
            'name' => $product->get_name(),
            'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
            'show_price' => !empty($this->wmab_plugin_settings['general']['show_price']) ? '1' : '0',
            'new_products' => $is_product_new,
            'on_sale_products' => $discount_percentage ? '1' : '0',
            'quantity' => ($product->get_stock_quantity() != null) ? (string) $product->get_stock_quantity() : ((string) !empty($quantity_by_variation) ? $quantity_by_variation : $default_quantity ), //Sending quantity 1 in case stock is managing globally instead of at product level - added by Harsh on 04-Sep-2019
            'minimal_quantity' => '0',
            'allow_out_of_stock' => '0',
            'discount_percentage' => $discount_percentage,
            'price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $regular_price)) ) ) ),
            'discount_price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $sale_price)) ) ) ),
            'is_in_wishlist' => 'false',
            'product_url' => get_permalink( $product_id ),
            'images' => $product_images,
            'combinations' => $product_combinations,
            'options' => $attributes,
            'description' => $product->get_description(),
            'product_info' => $product_info,
            'accessories' => array('has_accessories' => '0', 'accessories_items' => array()),
            'customization_fields' => array('is_customizable' => '0', 'customizable_items' => array()),
            'pack_products' => array('is_pack' => '0', 'pack_items' => array()),
            'has_file_customization' => '0',
            'customization_message' => '',
            'seller_info' => array(),
            'product_youtube_url' => '',
            'product_attachments_array' => array(),
            'display_read_reviews' => '1',
            'display_write_reviews' => '1',
            'number_of_reviews' => $total_reviews,
            'averagecomments' => number_format($reviews_average,2),
            'related_products' => $related_products,
        );
        
        
        if(isset($this->wmab_plugin_settings['general']['display_short_desc']) && $this->wmab_plugin_settings['general']['display_short_desc']){
            $this->wmab_response['product']['short_description'] = $product->get_short_description();
        } 
        
        
        //Log Request
        log_knowband_app_request("appGetProductDetails", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
            
    }
    
        
    /**
     * Function to handle appGetProductReviews API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetProductReviews
     * @param int $product_id This parameter holds Product ID
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_product_reviews($product_id, $version){
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetProductDetails');
		
         $this->wmab_response['status'] = "success";
        
        //Get product reviews
        $this->wmab_response['reviews'] = array();
        $args = array ('post_type' => 'product', 'post_id' => $product_id, 'status' => 'approve');
        $reviews = get_comments( $args );
        $total_reviews = 0; 
        $reviews_rating_sum = 0;
        $this->wmab_response['reviews']['comments'] = array();
        foreach ($reviews as $review){
            $total_reviews ++;
            $meta_reviews = get_comment_meta( $review->comment_ID, 'rating', TRUE );
            $reviews_rating_sum += $meta_reviews;
            $this->wmab_response['reviews']['comments'][] = array(
                'id_product_comment' => $review->comment_ID,
                'customer_name' => $review->comment_author,
                'date_add' => date('Y-m-d H:i:s',  strtotime($review->comment_date)),
                'content' => $review->comment_content,
                'grade' => !empty($meta_reviews) ? $meta_reviews : 0,
            );
           
        }
        $this->wmab_response['reviews']["number_of_reviews"] = $total_reviews;
        $this->wmab_response["reviews"]["averagecomments"] = number_format($reviews_rating_sum/$total_reviews,2);
        
        //Log Request
        log_knowband_app_request("appGetProductReviews", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
}
?>
