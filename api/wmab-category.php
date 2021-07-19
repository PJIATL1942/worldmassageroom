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
 * class - WmabCategory
 * 
 * This class contains methods which handles Mobile App Builder API Calls which are related to category page actions
 * @author Knowband
 * @version v1.1
 * @Date 29-Jun-2018
 */
class WmabCategory {
    
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
                        if (null === WC()->cart) {
                            WC()->cart = new WC_Cart(); //For Cart Object
                            WC()->cart->get_cart();
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
     * Function to set session data 
     * @param string $cart_id This parameter holds current cart ID to set int Session variable
     * @author Knowband
     */
    private function set_session($cart_id) {
        
        $this->mab_session_expiring = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) );
        $this->mab_session_expiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) );
        
        $this->mab_cookie  = 'wp_woocommerce_session_' . COOKIEHASH;
        $to_hash            = $cart_id . '|' . $this->mab_session_expiration;
        $cookie_hash        = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
        $cookie_value       = $cart_id . '||' . $this->mab_session_expiration . '||' . $this->mab_session_expiring . '||' . $cookie_hash;

        wc_setcookie( $this->mab_cookie, $cookie_value, $this->mab_session_expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );

    }
    
    /**
     * Function to handle appGetCategoryDetails API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCategoryDetails
     * @param int $category_id This parameter holds Category ID
     * @param string $search_term This parameter holds Search Term
     * @param array $filter This parameter holds Filter values basis on which filter will be applied
     * @param string $sort_by This parameter holds value on basis of which sorting will be done
     * @param string $item_count This parameter holds limit value
     * @param string $page_number This parameter holds page number
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_category_details($category_id, $search_term, $filter, $sort_by, $item_count, $page_number, $version, $iso_code, $session_data, $email) {
        global $wpdb;
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetCategoryDetails');

        $price_filter = array();
        $attribute_filter = array();
        $filtered_products_list = array();
        $price_filtered_products_list = array();
        $attribute_filtered_products_list = array();
        
        if (!empty($filter)) {
            $filter = json_decode(stripslashes(trim($filter)), true);
            foreach ($filter['filter_result'] as $filter) {

                //Price Filters
		$filterName = explode("|", $filter['title']);
                if (isset($filterName[0]) && strtolower($filterName[0]) == 'prices') {
                    if (!empty($filter['items'])) {
                        foreach ($filter['items'] as $items) {
                            $price_filter[] = $items['id'];
                        }
                    }
                }

                //Attributes Filters
		$filterName = explode("|", $filter['title']);
                if (isset($filterName[0]) && !empty($filterName[0]) && strtolower($filterName[0]) != 'prices') {
                    if (!empty($filter['items'])) {
                        foreach ($filter['items'] as $items) {
                            $attribute_filter[$filter['title']][] = $items['id'];
                        }
                    }
                }
            }
        }

        $price_sql_condition = '';
        if (!empty($price_filter)) {
            $price_sql_condition .= ' ( meta_key = "_price" AND ';
            $counter = 0;
            foreach ($price_filter as $price_filter) {
                $price_range = explode("|", $price_filter);
                if ($counter > 0) {
                    $price_sql_condition .= ' OR ';
                }
                $price_sql_condition .= ' (meta_value >= ' . $price_range[0] . ' AND meta_value <= ' . $price_range[1] . ') ';
                $counter++;
            }  
            $price_sql_condition .= ' ) ';
        }

        if (!empty($price_sql_condition)) {
            $filtered_products = $wpdb->get_results("SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta WHERE 1 AND ({$price_sql_condition})");
            if (isset($filtered_products) && !empty($filtered_products)) {
                foreach ($filtered_products as $filtered_product) {
                    $post_parent = wp_get_post_parent_id( $filtered_product->post_id );
                    if (!empty($post_parent)) {
                        $price_filtered_products_list[] = $post_parent;
                    } else {
                        $price_filtered_products_list[] = $filtered_product->post_id;
                    }
                }
            }
        }

        $attribute_sql_condition = '';
        if (!empty($attribute_filter)) {
            $counter = 0;
            foreach ($attribute_filter as $attribute_key => $attribute_value) {
		if (strpos($attribute_key, "|") !== false) {
		    $attribute_key = explode("|", $attribute_key);
		}
                if ($counter > 0) {
                    $attribute_sql_condition .= ' AND ';
                }
		if (isset($attribute_key[1])) {
	               //BOC neeraj.kumar@velsof.com : 7-Feb-2020 , Product Not filter with Custom Product Attribute
                        $finding_attribute_associated_sql = "(SELECT DISTINCT wtr.object_id as post_id FROM `".$wpdb->prefix."term_taxonomy` as wtt , ".$wpdb->prefix."terms as wt , ".$wpdb->prefix."term_relationships as wtr WHERE "
                                . "wt.term_id = wtt.term_id AND wtr.term_taxonomy_id = wtt.term_taxonomy_id AND "
                                . "`taxonomy` LIKE '".$attribute_key[1]."' AND wt.slug IN ('".implode("', '", $attribute_value)."')) "; 
	                $attribute_sql_condition .= ' post_id IN '
                                . '(SELECT DISTINCT post_id FROM '.$wpdb->prefix.'postmeta WHERE meta_key = "attribute_' . strtolower($attribute_key[1]) . '" AND ( meta_value IN (\'' . implode("', '", $attribute_value) . '\') ) ) '
                                . 'OR post_id IN '.$finding_attribute_associated_sql;
		} else {
			$attribute_sql_condition .= ' post_id IN (SELECT DISTINCT post_id FROM '.$wpdb->prefix.'postmeta WHERE meta_key = "attribute_' . strtolower($attribute_key) . '" AND ( meta_value IN (\'' . implode("', '", $attribute_value) . '\') ) )';
		}
                $counter++;
            }
        }

        if (!empty($attribute_sql_condition)) {

            $filtered_products = $wpdb->get_results("SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta WHERE 1 AND ({$attribute_sql_condition})");
            if (isset($filtered_products) && !empty($filtered_products)) {
                foreach ($filtered_products as $filtered_product) {
                    $post_parent = wp_get_post_parent_id( $filtered_product->post_id );
                    if (!empty($post_parent)) {
                        $attribute_filtered_products_list[] = $post_parent;
                    } else {
                        $attribute_filtered_products_list[] = $filtered_product->post_id;
                    }
                }
            }
        }

	if (isset($attribute_filtered_products_list) && !empty($attribute_filtered_products_list) && isset($price_filtered_products_list) && !empty($price_filtered_products_list)) {
	        $filtered_products_list = array_intersect($price_filtered_products_list, $attribute_filtered_products_list);
        } else if (isset($attribute_filtered_products_list) && !empty($attribute_filtered_products_list)) {
		$filtered_products_list = $attribute_filtered_products_list;
	} else if (isset($price_filtered_products_list) && !empty($price_filtered_products_list)) {
		$filtered_products_list = $price_filtered_products_list;
	}
        
        if ( function_exists('icl_object_id') ) {
            /*
             * Changes added for WPML compatibility and set current language for getting data through WP_QUERY() based on language
             * Added by Harsh on 16-Apr-2019
             */
            global $sitepress;
            $sitelang = !empty($iso_code) ? $iso_code : get_locale();
            $lang = explode("_", $sitelang);
            $sitepress->switch_lang($lang[0]);
            //Ends
            
            $suppress_filter = false;
        } else {
            $suppress_filter = true;
        }
        
        if (isset($category_id) && !empty($category_id)) {
            //Get Products by Category ID
	    if (!empty($filter)) {
                $args = array(
		        'post_type'             => 'product',
		        'post_status'           => 'publish',
		        'ignore_sticky_posts'   => 1,
		        'offset'                => ($page_number - 1) * $item_count,
		        'posts_per_page'        => $item_count,
		        'post__in'              => $filtered_products_list,
		        'tax_query'             => array(
		            array(
		                'taxonomy'      => 'product_cat',
		                'field'         => 'term_id', //This is optional, as it defaults to 'term_id'
		                'terms'         => $category_id,
		                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		            ),
		            array(
                                'taxonomy' => 'product_visibility',
                                'field' => 'name',
                                'terms' => 'exclude-from-catalog',
                                'operator' => 'NOT IN',
                            )
		        ),
                        'suppress_filters'    => $suppress_filter
                );
	    } else {
		$args = array(
		        'post_type'             => 'product',
		        'post_status'           => 'publish',
		        'ignore_sticky_posts'   => 1,
		        'offset'                => ($page_number - 1) * $item_count,
		        'posts_per_page'        => $item_count,
		        'tax_query'             => array(
		            array(
		                'taxonomy'      => 'product_cat',
		                'field'         => 'term_id', //This is optional, as it defaults to 'term_id'
		                'terms'         => $category_id,
		                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		            ),
		            array(
                                'taxonomy' => 'product_visibility',
                                'field' => 'name',
                                'terms' => 'exclude-from-catalog',
                                'operator' => 'NOT IN'
                            )
		        ),
                        'suppress_filters'    => $suppress_filter
		);
	    }
			
		//Sorting by options changes - added by Harsh Agarwal on 21-Aug-2020
			if ($sort_by == 'low' || $sort_by == 'high') {
				$args['meta_key'] = '_price';
				$args['orderby'] = 'meta_value_num';
				$args['order'] = ($sort_by == 'low') ? 'ASC' : 'DESC';
			} else if ($sort_by == 'atoz' || $sort_by == 'ztoa') {
				$args['orderby'] = 'title';
				$args['order'] = ($sort_by == 'atoz') ? 'ASC' : 'DESC';
			}
			//End Sorting by options changes
			
            $products = new WP_Query($args);
			
        } else if (isset($search_term) && !empty($search_term)) {
            //Get Products by Search Term
	    if (!empty($filter)) {
                $args = array(
		        'post_type'             => 'product',
		        'post_status'           => 'publish',
		        'ignore_sticky_posts'   => 1,
		        'offset'                => ($page_number - 1) * $item_count,
		        'posts_per_page'        => $item_count,
			'post__in'              => $filtered_products_list,
		        's'                     => $search_term,
		        'tax_query'             => array(
		            array(
                                'taxonomy' => 'product_visibility',
                                'field' => 'name',
                                'terms' => 'exclude-from-catalog',
                                'operator' => 'NOT IN',
                            )
		        ),
                        'suppress_filters'    => $suppress_filter
                );
	    } else {
		$args = array(
		        'post_type'             => 'product',
		        'post_status'           => 'publish',
		        'ignore_sticky_posts'   => 1,
		        'offset'                => ($page_number - 1) * $item_count,
		        'posts_per_page'        => $item_count,
			's'                     => $search_term,
		        'tax_query'             => array(
		            array(
                                'taxonomy' => 'product_visibility',
                                'field' => 'name',
                                'terms' => 'exclude-from-catalog',
                                'operator' => 'NOT IN',
                            )
		        ),
                        'suppress_filters'    => $suppress_filter
                );
	    }
			
			//Sorting by options changes - added by Harsh Agarwal on 21-Aug-2020
			if ($sort_by == 'low' || $sort_by == 'high') {
				$args['meta_key'] = '_price';
				$args['orderby'] = 'meta_value_num';
				$args['order'] = ($sort_by == 'low') ? 'ASC' : 'DESC';
			} else if ($sort_by == 'atoz' || $sort_by == 'ztoa') {
				$args['orderby'] = 'title';
				$args['order'] = ($sort_by == 'atoz') ? 'ASC' : 'DESC';
			}
			//End Sorting by options changes
			
            $products = new WP_Query($args);
        }
        
        $categoryProducts = array(
            'title' => '',
            'products' => array(),
        );
        //BOC Module Upgrade V2 neeraj.kumar@velsof.com Added category name in title key
        if( $term = get_term_by( 'id', $category_id, 'product_cat' ) ){
            $categoryProducts['title'] = html_entity_decode($term->name);
        }        
        //EOC
		
		//Code added to send total_cart_quantity 
		$total_cart_quantity = 0;                        
		//set session data
		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} else if (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		}
		
		if (!empty($cart_id)) {
			$this->set_session($cart_id);
			
			$this->wmab_response['session_data'] = $cart_id;
			
			$cart_contents = WC()->cart->get_cart_contents();
			foreach ($cart_contents as $cart_item) {
				if (!empty($cart_item['quantity'])) {
					$total_cart_quantity += $cart_item['quantity'];
				}
			}
			$this->wmab_response['total_cart_items'] = $total_cart_quantity;
		} else {
			//Cart Count - Sending it as 0 as without email or cart id we can not get count here
			$this->wmab_response['total_cart_items'] = 0;
		}
		
        if (isset($products) && !empty($products)) {
            if (isset($products->posts) && !empty($products->posts)) {
                foreach ($products->posts as $productList) {
                    
                    $product = get_product( $productList->ID );
                    if ( $product->is_visible() ) {
                    $is_product_new = 0;
                    if (strtotime($product->get_date_created()) >= strtotime($this->wmab_plugin_settings['general']['product_new_date'])) {
                        $is_product_new = 1;
                    }
                    $has_attributes = '0';
                    if($product->product_type == 'variable') { //For Variable Product
                        $has_attributes = '1';
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
                    
                    //Get Product Category
                    $product_category = $product->get_category_ids();
                    $product_category_id = '';
                    $product_category_name = '';
                    if (isset($product_category[0]) && !empty($product_category[0])) {
                        $product_category_id = $product_category[0];
                        $product_category_name = get_term_by( 'id', $product_category_id, 'product_cat' );
                        $product_category_name = $product_category_name->name;
                    }
                    
                    //Check if image exists otherwise send dummy image - changes made by Saurav (25-Jun-2020)
                        $product_thumbnail = get_the_post_thumbnail_url( $productList->ID, array($this->wmab_plugin_settings['general']['product_image_width'], $this->wmab_plugin_settings['general']['product_image_height']) );
                        if (isset($product_thumbnail) && !empty($product_thumbnail)) {
                                $image_path = $product_thumbnail;
                        } else {
                                $image_path = KBPWAAPP_URL . 'views/images/home_page_layout/noimage.png'; //No-Image
                        }
                        //Ends
                    //get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
                        $cart_quantity = 0;                        
                        // Set here your product ID
                        $targeted_id = (string) $productList->ID;
            
                        if (!empty($cart_id)) {
                            $this->set_session($cart_id);
                            $cart_contents = WC()->cart->get_cart_contents();
                            foreach ($cart_contents as $cart_item) {
                                if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
                                    $cart_quantity = $cart_item['quantity'];
                                    break; // stop the loop if product is found
                                }
                            }
                        }
                    
                    $categoryProducts['products'][] = array(
                        'id' => (string) $productList->ID,
                        'name' => $product->get_name(),
                        'available_for_order' => 1,
                        'show_price' => !empty($this->wmab_plugin_settings['general']['show_price']) ? 1 : 0,
                        'new_products' => $is_product_new,
                        'on_sale_products' => $discount_percentage ? 1 : 0,
                        'category_name' => $product_category_name,
                        'ClickActivityName' => 'ProductActivity',
                        'category_id' => $product_category_id,
                        'price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $regular_price)) ) ) ),
                        'src' => $image_path,
                        'discount_price' => html_entity_decode( strip_tags( wc_price( wc_get_price_including_tax($product, array('qty' => 1, 'price' => $sale_price)) ) ) ),
                        'discount_percentage' => $discount_percentage,
                        'is_in_wishlist' => 0,
                        'has_attributes' => $has_attributes,
                        'cart_quantity' => $cart_quantity,
                    );
                    }
                }
            }
        }

        $this->wmab_response['fproducts'] = $categoryProducts;
        
        //Log Request
        log_knowband_app_request("appGetCategoryDetails", json_encode($this->wmab_response));
            
        echo json_encode($this->wmab_response);
        die;
    }
    
    /**
     * Function to handle appGetFilters API request
     * 
     * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetFilters
     * @param int $category_id This parameter holds category ID
     * @param string $search_term This parameter holds search term
     * @param string $version This parameter holds API version to verify during API call
     * @author Knowband
     */
    public function app_get_filters($category_id, $search_term, $version) {
        
        //First do the API version verification and then go ahead
        $this->verify_api($version, 'appGetFilters');
        
        $arrIndex = 0; //Array Index starts from 0
        if (isset($category_id) && !empty($category_id)) {
            //Get Price Ranges by Category ID
            $priceFilters = $this->getPriceFiltersByCategoryID($category_id);
            if (!empty($priceFilters)) {
                //Product Price Filter
                $this->wmab_response['filter_result'][$arrIndex] = array(
                    'id' => '1',
                    'name' => __('Prices', 'woocommerce-mobile-app-builder'),
                    'title' => __('Prices', 'woocommerce-mobile-app-builder'),
                    'is_color_group' => 0,
                    'choice_type' => 'multiple',
                    'items' => array()
                );

                foreach ($priceFilters as $priceFilter) {
                    $priceRange = explode("|", $priceFilter);

                    $this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
                        'id' => (string) $priceFilter,
                        'color_value' => "",
                        'name' => html_entity_decode( strip_tags(wc_price( $priceRange[0] ) . ' - ' . wc_price( $priceRange[1] ) ) )
                    );
                }
                
                $arrIndex++; //Increment Array Index by 1
            }
            
            //Get Attributes Filters by Category ID
            $attributeFilters = $this->getAttributeFiltersByCategoryID($category_id);
            if (!empty($attributeFilters)) {
                foreach ($attributeFilters as $key => $attributeFilter) {
                    //Product Attributes Filter
                    $this->wmab_response['filter_result'][$arrIndex] = array(
                        'id' => $key,
                        'name' => $attributeFilter['name'],
                        'title' => $attributeFilter['name'],
                        'is_color_group' => 0,
                        'choice_type' => 'multiple',
                        'items' => array()
                    );
                    
                    if (isset($attributeFilter['values']) && !empty($attributeFilter['values'])) {
                        foreach ($attributeFilter['values'] as $attributeValue) {
                            $this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
                                'id' => strtolower($attributeValue->slug),
                                'color_value' => "",
                                'name' => $attributeValue->name
                            );
                        }
                    }
                    $arrIndex++; //Increment Array Index by 1
                }
            }            
        } else if (isset($search_term) && !empty($search_term)) {
            //Get Price Ranges by Search Term
            $priceFilters = $this->getPriceFiltersBySearchTerm($search_term);
            if (!empty($priceFilters)) {
                //Product Price Filter
                $this->wmab_response['filter_result'][$arrIndex] = array(
                    'id' => '1',
                    'name' => __('Prices', 'woocommerce-mobile-app-builder'),
                    'title' => __('Prices', 'woocommerce-mobile-app-builder'),
                    'is_color_group' => 0,
                    'choice_type' => 'multiple',
                    'items' => array()
                );

                foreach ($priceFilters as $priceFilter) {
                    $priceRange = explode("|", $priceFilter);

                    $this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
                        'id' => $priceFilter,
                        'color_value' => "",
                        'name' => html_entity_decode( strip_tags(wc_price( $priceRange[0] ) . ' - ' . wc_price( $priceRange[1] ) ) )
                    );
                }
                
                $arrIndex++; //Increment Array Index by 1
            }
            
            //Get Attributes Filters by Search Term
            $attributeFilters = $this->getAttributeFiltersBySearchTerm($search_term);
            if (!empty($attributeFilters)) {
                foreach ($attributeFilters as $key => $attributeFilter) {
                    //Product Attributes Filter
                    $this->wmab_response['filter_result'][$arrIndex] = array(
                        'id' => (string) $key,
                        'name' => $attributeFilter['name'],
                        'title' => $attributeFilter['name'],
                        'is_color_group' => 0,
                        'choice_type' => 'multiple',
                        'items' => array()
                    );
                    
                    if (isset($attributeFilter['values']) && !empty($attributeFilter['values'])) {
                        foreach ($attributeFilter['values'] as $attributeValue) {
                            $this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
                                'id' => (string) strtolower($attributeValue->slug),
                                'color_value' => "",
                                'name' => $attributeValue->name
                            );
                        }
                    }
                    $arrIndex++; //Increment Array Index by 1
                }
            }
        }
        
        //Log Request
        log_knowband_app_request("appGetFilters", json_encode($this->wmab_response));
        
        echo json_encode($this->wmab_response);
        die;
        
    }
    
    /**
     * Function to get Price Filters by Catgeory ID
     * @param int $category_id This parameter holds Category ID
     * @author Knowband
     */
    private function getPriceFiltersByCategoryID($category_id) {
        
        global $wpdb;
        
        $priceFiltersResponse = array();
        
        if (isset($category_id) && !empty($category_id)) {
            
            $priceRange = $wpdb->get_row("SELECT min(cast(pm.meta_value as decimal(10, 2))) as min_price, max(cast(pm.meta_value as decimal(10, 2))) as max_price
                FROM {$wpdb->prefix}term_relationships as tr
                INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
                INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
                WHERE tt.taxonomy LIKE 'product_cat'
                AND t.term_id = {$category_id}
                AND pm.meta_key = '_price'"
            );
            
            $minPrice = $priceRange->min_price;
            $maxPrice = $priceRange->max_price;

            $rangeDiff = ceil($maxPrice / 4);

            if ($rangeDiff > 0) {
                $totalRanges = ceil($maxPrice / $rangeDiff);

                for ($i = 0; $i < $totalRanges; $i++) {
                    $start = $i * $rangeDiff;
                    $end = $start + $rangeDiff;
                    $priceFiltersResponse[$i] = $start . '|' . $end;
                }
            }
        }        
        
        return $priceFiltersResponse;
    }
    
    /**
     * Function to get Price Filters by Search Term
     * @param string $search_term This parameter holds search term
     * @author Knowband
     */
    private function getPriceFiltersBySearchTerm($search_term) {
        
        global $wpdb;
        
        $priceFiltersResponse = array();
        
        if (isset($search_term) && !empty($search_term)) {
            
            $priceRange = $wpdb->get_row("SELECT min(cast(pm.meta_value as decimal(10, 2))) as min_price, max(cast(pm.meta_value as decimal(10, 2))) as max_price
                FROM {$wpdb->prefix}term_relationships as tr
                INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
                INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
                INNER JOIN {$wpdb->prefix}posts as p ON p.ID = pm.post_id
                WHERE tt.taxonomy LIKE 'product_cat' AND p.post_type = 'product'
                AND p.post_title LIKE '%{$search_term}%'
                AND pm.meta_key = '_price'"
            );
            
            $minPrice = $priceRange->min_price;
            $maxPrice = $priceRange->max_price;

            $rangeDiff = ceil($maxPrice / 4);

            if ($rangeDiff > 0) {
                $totalRanges = ceil($maxPrice / $rangeDiff);

                for ($i = 0; $i < $totalRanges; $i++) {
                    $start = $i * $rangeDiff;
                    $end = $start + $rangeDiff;
                    $priceFiltersResponse[$i] = $start . '|' . $end;
                }
            }
        }        
        
        return $priceFiltersResponse;
    }
    
    /**
     * Function to get Attribute Filters by Catgeory ID
     * @param int $category_id This parameter holds Category ID
     * @author Knowband
     */
    private function getAttributeFiltersByCategoryID($category_id) {
        
        global $wpdb;
        
        $attributeFiltersResponse = array();
        
        if (isset($category_id) && !empty($category_id)) {
            $products = $wpdb->get_results("SELECT pm.meta_value as meta_value, pm.post_id as post_id
                FROM {$wpdb->prefix}term_relationships as tr
                INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
                INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
                WHERE tt.taxonomy LIKE 'product_cat'
                AND t.term_id = {$category_id}
                AND pm.meta_key = '_product_attributes'"
            );
            
            if (isset($products) && !empty($products)) {
                foreach ($products as $product) {
                    $attributes = unserialize($product->meta_value);
                    $product_id = $product->post_id;
                    
                    if (isset($attributes) && !empty($attributes)) {
                        foreach ($attributes as $attribute) {
                            //Get Attribute Name
                            $attribute_name = wc_attribute_label( $attribute['name'] );
                            
                            $attributeFiltersResponse[$attribute['name']]['name'] = $attribute_name;
			    if (!isset($attributeFiltersResponse[$attribute['name']]['values'])) {
			        $attributeFiltersResponse[$attribute['name']]['values'] = array();
			    }
                            //Get Attribute Value
                            $attribute_values = wc_get_product_terms( $product_id, $attribute['name'] );
                            if (isset($attribute_values) && !empty($attribute_values)) {
                                foreach ($attribute_values as $attribute_value) {
                                    if (is_array($attributeFiltersResponse[$attribute['name']]['values']) && !in_array($attribute_value, $attributeFiltersResponse[$attribute['name']]['values'])) {
                                        $attributeFiltersResponse[$attribute['name']]['values'][] = $attribute_value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        log_knowband_app_request("appGetFilters", json_encode($attributeFiltersResponse));
        return $attributeFiltersResponse;
    }
    
    /**
     * Function to get Attribute Filters by Search Term
     * @param string $search_term This parameter holds search term
     * @author Knowband
     */
    private function getAttributeFiltersBySearchTerm($search_term) {
        
        global $wpdb;
        
        $attributeFiltersResponse = array();
        
        if (isset($search_term) && !empty($search_term)) {
            $products = $wpdb->get_results("SELECT pm.meta_value as meta_value, pm.post_id as post_id
                FROM {$wpdb->prefix}term_relationships as tr
                INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
                INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
                INNER JOIN {$wpdb->prefix}posts as p ON p.ID = pm.post_id
                WHERE tt.taxonomy LIKE 'product_cat' AND p.post_type = 'product'
                AND p.post_title LIKE '%{$search_term}%'
                AND pm.meta_key = '_product_attributes'"
            );
            
            if (isset($products) && !empty($products)) {
                foreach ($products as $product) {
                    $attributes = unserialize($product->meta_value);
                    $product_id = $product->post_id;
                    
                    if (isset($attributes) && !empty($attributes)) {
                        foreach ($attributes as $attribute) {
                            //Get Attribute Name
                            $attribute_name = wc_attribute_label( $attribute['name'] );
                            
                            $attributeFiltersResponse[$attribute['name']]['name'] = $attribute_name;
			    if (!isset($attributeFiltersResponse[$attribute['name']]['values'])) {
			        $attributeFiltersResponse[$attribute['name']]['values'] = array();
			    }
                            //Get Attribute Value
                            $attribute_values = wc_get_product_terms( $product_id, $attribute['name'] );
                            
                            if (isset($attribute_values) && !empty($attribute_values)) {
                                foreach ($attribute_values as $attribute_value) {
                                    if (!in_array($attribute_value, $attributeFiltersResponse[$attribute['name']]['values'])) {
                                        $attributeFiltersResponse[$attribute['name']]['values'][] = $attribute_value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $attributeFiltersResponse;
    }
}
?>