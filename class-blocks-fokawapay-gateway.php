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
        
            // Localize the script with the cart total
            wp_localize_script('fokawapay_gateway-blocks-integration', 'wc_cart_params', array(
                'cart_total'    => WC()->cart->total,
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
} 











