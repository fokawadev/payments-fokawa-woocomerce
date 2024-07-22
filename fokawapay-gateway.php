<?php
/*
Plugin Name: FokawaPay
Description: A custom payment gateway for WooCommerce block.
Version: 1.3.4
Author: -------
Author URI: --------
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: fokawapay_gateway
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action('plugins_loaded', 'woocommerce_fokawapay_gateway_init', 0);

function woocommerce_fokawapay_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) return;

    include(plugin_dir_path(__FILE__) . 'class-fokawapay-gateway.php');

    function add_fokawapay_gateway($methods) {
        $methods[] = 'FokawaPay_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_fokawapay_gateway');
}

// Declare compatibility with WooCommerce Checkout Blocks
function declare_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Register the custom payment gateway for WooCommerce Checkout Blocks
add_action('woocommerce_blocks_loaded', 'register_fokawapay_gateway_block');

function register_fokawapay_gateway_block() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) return;

    require_once plugin_dir_path(__FILE__) . 'class-blocks-fokawapay-gateway.php';

    add_action('woocommerce_blocks_payment_method_type_registration', function( $payment_method_registry ) {
        $payment_method_registry->register(new FokawaPay_Gateway_Blocks);
    });
}

// File path: functions.php or a custom plugin file

// Hook into WordPress to handle the AJAX request
add_action('wp_ajax_update_order_custom_field', 'update_order_custom_field');
add_action('wp_ajax_nopriv_update_order_custom_field', 'update_order_custom_field');

function update_order_custom_field() {
    session_start();
        // echo'before:'. $_SESSION['coin'];
    $_SESSION['coin']          = $_POST['coin'];
    $_SESSION['phpamount']     = $_POST['phpamount'];
    
   

    //  var_dump( $options );
        // echo'current:'. $_SESSION['coin'];
     exit;
    // // Check for the required parameters
    // if (!isset($_POST['order_id']) || !isset($_POST['custom_text'])) {
    //     wp_send_json_error(array('message' => 'Missing parameters.'));
    //     return;
    // }

    // // Sanitize the input data
    // $order_id = intval($_POST['order_id']);
    // $custom_text = sanitize_text_field($_POST['custom_text']);

    // // Update the custom field (assuming it's a post meta field)
    // $update_result = update_post_meta($order_id, '_custom_field_key', $custom_text);

    // // Check if the update was successful
    // if ($update_result) {
    //     wp_send_json_success(array('message' => 'Custom field updated successfully.'));
    // } else {
    //     wp_send_json_error(array('message' => 'Failed to update custom field.'));
    // }
}
 

// add_filter( 'woocommerce_gateway_description', 'fokawapay_gateway_description', 20, 2  );

    
    
    
     
    
// Step 3: Display Custom Fields on Order Edit Page
add_action('woocommerce_admin_order_data_after_order_details', 'display_custom_order_details_fields');
function display_custom_order_details_fields($order){
    
    // echo  $order->get_payment_method_title();
    $coin           = get_post_meta($order->get_id(), '_paycoinsymbol', true);
    $coin_amount    = get_post_meta($order->get_id(), '_payAmount', true);
    $payment_id     = get_post_meta($order->get_id(), '_ordernumber', true);
     
    // echo 'coin:'.$coin;
     ?>
     
     <div class="order_data_column" style="width:100%">
						<h3>
							Fokawa Payment Details							
							
						</h3> 
						    <br>
						    <label>Payment ID:</label> <br><b><?php echo $payment_id;?></b><br>
						     
						    <label>Amount: </label> <br><b> <?php echo $coin_amount .' '.$coin;?></b><br>
						</div>
						<?php
    // $custom_field_1 = get_post_meta($order->get_id(), '_custom_field_1', true);
    // $custom_field_2 = get_post_meta($order->get_id(), '_custom_field_2', true);

    // // Step 4: Retrieve and Display Detailed Order Information
    // $payment_method = $order->get_payment_method_title();
    // $payment_date = date_i18n('F j, Y @ h:i A', strtotime($order->get_date_paid()));
    // $customer_ip = $order->get_customer_ip_address();

    // echo '<div class="custom-order-details">';
    // echo '<h4>' . __('Order #' . $order->get_id() . ' details') . '</h4>';

    // echo '<p>' . __('Payment via ') . $payment_method . '. ' . __('Paid on ') . $payment_date . '. ' . __('Customer IP: ') . $customer_ip . '</p>';

    // echo '<p><strong>' . __('Custom Field 1') . ':</strong> ' . $custom_field_1 . '</p>';
    // echo '<p><strong>' . __('Custom Field 2') . ':</strong> ' . $custom_field_2 . '</p>';

    // echo '</div>';
}
    
    
    
    
    
    
    

 
