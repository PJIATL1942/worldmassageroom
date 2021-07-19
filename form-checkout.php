<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_head();

$billing_fields = $checkout->get_checkout_fields( 'billing' );
$shipping_fields = $checkout->get_checkout_fields( 'shipping' );
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    #order_review_heading {
        float: unset !important;
        width:100% !important;
    }
    #order_review{
        float: unset !important;
        width:100% !important;
    }
    body {
        padding: 20px;
    }
    #trp-floater-ls{
        display:none !important;
    }
    .demo_store {
        display:none !important;
    }
h3 {
    color: #333;
    font-size: 18px;
    font-size: 1.125rem;
    font-weight: 300;
    clear: both;
    line-height: 1.4;
    margin: 0 0 0.75em;
    padding: 1.5em 0 0;
}

table {
    border-collapse: collapse;
    margin: 0 0 1.5em;
    width: 100%;
}

th {
    padding: 0.4em;
    text-align: left;
}

tr {
    border-bottom: 1px solid #eee;
}

.woocommerce-checkout-review-order-table td {
    padding: 1em .5em;
}

ul {
    list-style: disc;
}

ul, ol {
    margin: 0 0 1.5em;
    padding: 0;
}

.wc_payment_method {
    list-style: none;
    border-bottom: 1px solid #ddd;
    padding: 10px 0px;
}

.payment_box, .about_paypal {
    display: none;
}

.payment_method_paypal img {
    display: none;
    height: auto;
    max-width: 100%;
}

.woocommerce #payment #place_order, .woocommerce-page #payment #place_order {
    float: right;
}

button, input[type="button"], input[type="submit"] {
    background-color: #222;
    border: 0;
    -webkit-border-radius: 2px;
    border-radius: 2px;
    -webkit-box-shadow: none;
    box-shadow: none;
    color: #fff;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-size: 0.875rem;
    font-weight: 800;
    line-height: 1;
    padding: 1em 2em;
    text-shadow: none;
    -webkit-transition: background 0.2s;
    transition: background 0.2s;
}

.place-order {
    text-align: center;
}

.wmab_error {
    padding: 10px 0;
    color: red;
}

.wmab_overlay {
    z-index: 1000; 
    border: none; 
    margin: 0px; 
    padding: 0px; 
    width: 100%; 
    height: 100%; 
    top: 0px; 
    left: 0px; 
    background: rgb(255, 255, 255); 
    opacity: 0.6; 
    cursor: default; 
    position: fixed;
    display: none;
}

.wmab_loader {
    height: 1em;
    width: 1em;
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    margin-left: -.5em;
    margin-top: -.5em;
    content: '';
    -webkit-animation: spin 1s ease-in-out infinite;
    animation: spin 1s ease-in-out infinite;
    background: url(<?php echo esc_url( plugins_url() .'/'.plugin_basename(dirname(__FILE__)).'/views/images/loader.svg'); ?>) center center;
    background-size: cover;
    line-height: 1;
    text-align: center;
    font-size: 2em;
    color: rgba(0,0,0,.75);
}
</style>

<div class="wmab_overlay"></div>
<div class="wmab_loader"></div>
<form name="checkout" id="wmab_checkout_form" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( site_url() . '/?wc-ajax=checkout' ); ?>" enctype="multipart/form-data">
       
    <!--Billing Details-->
    <input type="hidden" name="billing_first_name" id="billing_first_name" value="<?php echo $customer['shipping']['first_name']; ?>" />
    <input type="hidden" name="billing_last_name" id="billing_last_name" value="<?php echo $customer['shipping']['last_name']; ?>" />
    <input type="hidden" name="billing_company" id="billing_company" value="<?php echo $customer['shipping']['company']; ?>" />
    <input type="hidden" name="billing_country" id="billing_country" value="<?php echo $customer['shipping']['country']; ?>" />
    <input type="hidden" name="billing_address_1" id="billing_address_1" value="<?php echo $customer['shipping']['address_1']; ?>" />
    <input type="hidden" name="billing_address_2" id="billing_address_2" value="<?php echo $customer['shipping']['address_2']; ?>" />
    <input type="hidden" name="billing_city" id="billing_city" value="<?php echo $customer['shipping']['city']; ?>" />
    <?php if (isset($billing_fields['billing_state'])) { ?>
    <input type="hidden" name="billing_state" id="billing_state" value="<?php echo $customer['shipping']['state']; ?>" />
    <?php } ?>
    <input type="hidden" name="billing_postcode" id="billing_postcode" value="<?php echo $customer['shipping']['postcode']; ?>" />
    <input type="hidden" name="billing_phone" id="billing_phone" value="<?php echo $customer['billing']['phone']; ?>" />
    <input type="hidden" name="billing_email" id="billing_email" value="<?php echo $email; ?>" />
    
    <!--Shipping Details-->
    <input type="hidden" name="shipping_first_name" id="shipping_first_name" value="<?php echo $customer['shipping']['first_name']; ?>" />
    <input type="hidden" name="shipping_last_name" id="shipping_last_name" value="<?php echo $customer['shipping']['last_name']; ?>" />
    <input type="hidden" name="shipping_company" id="shipping_company" value="<?php echo $customer['shipping']['company']; ?>" />
    <input type="hidden" name="shipping_country" id="shipping_country" value="<?php echo $customer['shipping']['country']; ?>" />
    <input type="hidden" name="shipping_address_1" id="shipping_address_1" value="<?php echo $customer['shipping']['address_1']; ?>" />
    <input type="hidden" name="shipping_address_2" id="shipping_address_2" value="<?php echo $customer['shipping']['address_2']; ?>" />
    <input type="hidden" name="shipping_city" id="shipping_city" value="<?php echo $customer['shipping']['city']; ?>" />
    <?php if (isset($shipping_fields['billing_state'])) { ?>
    <input type="hidden" name="shipping_state" id="shipping_state" value="<?php echo $customer['shipping']['state']; ?>" />
    <?php } ?>
    <input type="hidden" name="shipping_postcode" id="shipping_postcode" value="<?php echo $customer['shipping']['postcode']; ?>" />
    
    <!--Order Comments-->
    <input type="hidden" name="order_comments" id="order_comments" value="<?php echo $order_message; ?>" />
    
	<h3 id="order_review_heading"><?php _e( 'Your order', 'woocommerce' ); ?></h3>

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<script type="text/javascript">
    jQuery("#place_order").attr("type", "button");
    jQuery("#place_order").click(function(){
	jQuery(".wmab_loader").show();
	jQuery(".wmab_overlay").show();
	jQuery(".wmab_error").remove();
        jQuery.ajax({
            type:		'POST',
            url:		'<?php echo esc_url( site_url() . '/?wc-ajax=checkout' ); ?>',
            data:		jQuery("#wmab_checkout_form").serialize(),
            success:	function( result ) {
                jQuery(".wmab_loader").hide();
		jQuery(".wmab_overlay").hide();
                if ( 'success' === result.result ) {
			if ( -1 === result.redirect.indexOf( 'https://' ) || -1 === result.redirect.indexOf( 'http://' ) ) {
				window.location = result.redirect;
			} else {
				window.location = decodeURI( result.redirect );
			}
		} else if ( 'failure' === result.result ) {
			//jQuery("#wmab_checkout_form").before('<span class="wmab_error">Your session has been expired.</span>');
			//Changes added by Harsh Agarwal on 12-Aug-2020 to display proper error message coming from WC instead of showing hard-coded message on webview of App
			if (result.messages != '') {
			jQuery("#wmab_checkout_form").before(result.messages);
			} else {
			jQuery("#wmab_checkout_form").before('<span class="wmab_error">Your session has been expired.</span>');
			}
			//End of changes to display error message on webview of App
		} else {
			jQuery("#wmab_checkout_form").before('<span class="wmab_error">Something went wrong. Please try again after some time.</span>');
		}
            }
        });
    });    
</script>
<?php wp_enqueue_script('wc-checkout'); ?>
<?php wp_footer(); ?>
