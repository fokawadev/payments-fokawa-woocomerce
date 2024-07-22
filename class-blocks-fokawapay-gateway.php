<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class FokawaPay_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'fokawapay_gateway';

    public function initialize() {
        $this->settings = get_option('woocommerce_fokawapay_gateway_settings', []);
        $this->gateway = new FokawaPay_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        
        $v = rand(1,9999);
        wp_register_script(
            'fokawapay_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js?v='.$v,
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

            wp_enqueue_style(
                'fokawapay_gateway_style',
                plugin_dir_url(__FILE__) . 'style.css?v='.$v,
                array(),
                '1.0.0'
            );
            
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('fokawapay_gateway-blocks-integration');
        }
         
        if( isset(WC()->cart->total) > 0){
              session_start();
        // echo'before:'. $_SESSION['coin'];
            $json_crypto = $this->get_accepted_crypto_from_payments();
            // Localize the script with the cart total
            wp_localize_script('fokawapay_gateway-blocks-integration', 'wc_cart_params', array(
                'crypto'        => $json_crypto,   
                'cart_total'    => WC()->cart->total,
                'currency'      => get_woocommerce_currency(),
                'ajax_url'      => admin_url('admin-ajax.php'),
                'cart'          => json_encode( WC()->cart ),
                'selected_coin' => (isset($_SESSION['coin'])?$_SESSION['coin']:"")
            ));
        }
        
    

        return ['fokawapay_gateway-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'dir'   => plugin_dir_url(__FILE__)."/icons/" 
        ];
    }
    
    
    function get_accepted_crypto_from_payments(){ 
            // URL of the API endpoint
            $url = "https://payments.fokawa.com/apiv2/coinlist/";
            
            // Initialize a cURL session
            $curl = curl_init();
            
            // Set the options for the cURL session
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,           // URL to fetch
                CURLOPT_RETURNTRANSFER => true, // Return the transfer as a string of the return value
                CURLOPT_TIMEOUT => 30,          // Maximum number of seconds to allow cURL functions to execute
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json" // Set the content type to JSON
                ],
            ]);
            
            // Execute the cURL session and store the response
            $response = curl_exec($curl);
            
            // Check for errors in the cURL session
            if (curl_errno($curl)) {
                echo 'cURL Error: ' . curl_error($curl);
                die( $curl );
            } else {
                // Decode the JSON response
                $decoded_response = json_decode($response, true);
                 return  $decoded_response;
            }
            
            // Close the cURL session
            curl_close($curl); 

    }
    
} 











