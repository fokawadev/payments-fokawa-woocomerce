<?php

class FokawaPay_Gateway extends WC_Payment_Gateway {
  
    public function __construct() {
        $this->id                 = 'fokawapay_gateway';
        $this->method_title       = __('FokawaPay', 'fokawapay_gateway');
        $this->method_description = __('Accept payments through FokawaPay', 'fokawapay_gateway');
        $this->has_fields         = true;

        $this->init_form_fields();
        $this->init_settings(); 

        $this->title            = $this->get_option('title');
        $this->description      = $this->get_option('description');
        $this->api_key          = $this->get_option( 'api_key' );
        $this->api_secret       = $this->get_option( 'api_secret' );
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        // add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_checkout_field'));
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_select2_scripts'));
        // add_action('woocommerce_admin_order_data_after_billing_address',array($this,  'add_custom_order_details_fields'));
        
        // Register the callback REST API route
        add_action('rest_api_init', function () {
            register_rest_route('fokawapay/v1', '/callback', array(
                'methods' => 'POST',
                'callback' =>  array($this, 'fokawa_pay_callback_handler') ,
                'permission_callback' => '__return_true',
            ));
        });

    }
    
    // public function enqueue_select2_scripts() { 
    //     wp_enqueue_script('custom-js', plugin_dir_url(__FILE__) . 'fokawa.js?v='.rand(1,20202), array('jquery' ), null, true);
    // }
    
    
     
    // Callback function to handle the REST API request
    function fokawa_pay_callback_handler() {
        
         
        
        $options = get_option('woocommerce_fokawapay_gateway_settings');
        if (!$options || !isset($options['api_key']) || !isset($options['api_secret'])) {
            return new WP_Error('settings_not_found', 'API key and secret not found in settings', array('status' => 500));
        }
    
        
        $input  = file_get_contents('php://input');
        $data   = json_decode($input, true); 
    
        $SECRET_KEY     = $options['api_secret'];
        $merchant_id    = $options['api_key']; 
    
         
        
        if( $this->generate_signature_to_merchant($merchant_id, json_encode( $unsigned_payload ), $SECRET_KEY) ){
            echo "Authenticated";
    
            $order_id       = intval( $data['order_id'] );
            $status         = sanitize_text_field($data['orderStatus']);
            $order          = wc_get_order($order_id);
    
            if (!$order) {
                return new WP_Error('invalid_order', 'Order not found', array('status' => 404));
            }
    
            if ($status === 'PAID') {
                // var_dump( $data );
                $order->update_status('completed', __('Payment completed via FokawaPay', 'fokawa-pay'));
                update_post_meta($order_id, '_paycoinsymbol', sanitize_text_field($data['coin']));
                update_post_meta($order_id, '_ordernumber', sanitize_text_field($data['orderNum']));
                update_post_meta($order_id, '_payAmount', sanitize_text_field($data['payAmount']));
                
                // echo ">>>>>>>>> ". get_post_meta($order->get_id(), '_paycoinsymbol', true) ."<<<<<<<<";
 
            } else {
                $order->update_status('failed', __('Payment failed via FokawaPay', 'fokawa-pay'));
            }
    
            return new WP_REST_Response(array('success' => true), 200);
        }else{
            return new WP_REST_Response(array('success' => true), 400);
        }
    }
    
    
    
    function generate_signature_to_merchant($merchant_id, $json_payload, $secret) {
        $message = $merchant_id . $json_payload . $secret;
        return hash_hmac('sha256', $message, $secret);
    }



    // function add_custom_payment_field($checkout) {
    //     echo '<div id="custom_payment_field"><h2>' . __('Custom Payment Field') . '</h2>';
    //     woocommerce_form_field('custom_payment_field', array(
    //         'type'          => 'text',
    //         'class'         => array('custom-payment-field-class form-row-wide'),
    //         'label'         => __('Enter additional information'),
    //         'placeholder'   => __('Additional Information'),
    //     ), $checkout->get_value('custom_payment_field'));
    //     echo '</div>';
    // }
 

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'fokawapay_gateway'),
                'type'        => 'checkbox',
                'label'       => __('Enable FokawaPay Payment Gateway', 'fokawapay_gateway'),
                'default'     => 'yes',
            ),
            'title' => array(
                'title'       => __('Title', 'fokawapay_gateway'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'fokawapay_gateway'),
                'default'     => __('FokawaPay', 'fokawapay_gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'fokawapay_gateway'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'fokawapay_gateway'),
                'default'     => __('Pay with FokawaPay using cryptocurrency.', 'fokawapay_gateway'),
            ),
            'api_key' => array(
                'title'       => __( 'API Key', 'fokawa-pay' ),
                'type'        => 'text',
                'description' => __( 'Enter your API key for the payment gateway.', 'fokawa-pay' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_secret' => array(
                'title'       => __( 'API Secret', 'fokawa-pay' ),
                'type'        => 'password',
                'description' => __( 'Enter your API secret for the payment gateway.', 'fokawa-pay' ),
                'default'     => '',
                'desc_tip'    => true,
            ), 
        );
    }
    
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        } 
        echo '  
 
    <style>
        .select2-results__option img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .select2-selection__rendered img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            vertical-align: middle;
        }
		 
    </style> 
	';
	 
        	
        // 	$crypto = [
        //                 ['icon' => 'icon.png', 'text' => 'Fokawa (FKWT)', 'value' => 'FKWT1799'],
        //                 ['icon' => 'usdt.png', 'text' => 'Tether (USDT)', 'value' => 'USDT'],
        //                 ['icon' => 'bnb.png', 'text' => 'Binance Coin (BNB)', 'value' => 'BNB'],
        //                 ['icon' => 'ethereum.png', 'text' => 'Ethereum (ETH)', 'value' => 'ETH'],
        //                 ['icon' => 'bitcoin.png', 'text' => 'Bitcoin (BTC)', 'value' => 'BTC'],
        //                 ['icon' => 'solana.png', 'text' => 'Solana (SOL)', 'value' => 'SOL'],
        //                 ['icon' => 'xrp.png', 'text' => 'Ripple (XRP)', 'value' => 'XRP'],
        //                 ['icon' => 'tron.png', 'text' => 'Tron (TRX)', 'value' => 'TRX'],
        //                 // ['icon' => 'ton.png', 'text' => 'The Open Network (TON)', 'value' => 'TON']
        //             ];
        
        	$crypto = $this->get_accepted_crypto_from_payments();
        // 	var_dump($crypto);
// {
//     "id": "bitcoin",
//     "symbol": "btc",
//     "name": "Bitcoin",
//     "image": "https://assets.coingecko.com/coins/images/1/large/bitcoin.png"
//   }
        	$html = "";
        	$option = "";
        	foreach ($crypto as $key => $value) { 
        		$option .='<option value="'. $value['id'] .'" data-image="' . $value['image']  . '">'.$value['name']  . ' ('.strtoupper($value['symbol']).')</option>';
        	}
	
	echo '
        		    <input type="hidden" id="cart_total" value="'.WC()->cart->total.'"/>
        			
        			<fieldset class="checkout-summary">
                        <div class="coupon-section">
                            <label for="coin">Please select a cryptocurrency.</label>
                            <select id="coin" name="coin" class="form-control" style="width:100%; padding:6px" >
                                 '.$option.' 
                            </select>
                        </div>
                        <div class="subtotal-section">
                            <p>Amount</p>
                            <span class="amount" id="php_amount">'.WC()->cart->total.' '.get_woocommerce_currency().'</span>
                        </div>
                        <div class="shipping-section">
                            <p>Selected coin</p>
                            <span class="amount">
                                <img id="crypto_icon" src="" alt="Crypto icon">
                                <p id="crypto_name"></p>
                            </span>
                            <p class="shipping-details"></p>
                            <p class="shipping-location"></p>
                        </div>
                        <div class="total-section">
                            <p>To send </p>
                            <span class="amount" id="crypto_amount">---</span>
                        </div>
                    </fieldset>
                    '; 
                    ?>
                    
                    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />-->
                    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
                    <!--<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>-->
 <script>
  jQuery(document).ready(function($){
     
     const icondir ='<?php echo plugin_dir_url(__FILE__)."/icons/";?>';
     
     const currency = '<?php echo get_woocommerce_currency();?>';
      const crypto = JSON.parse('<?php echo json_encode($crypto);?>');
    //   console.log( crypto)
     
    const cart_total    = '<?php echo WC()->cart->total;?>';
    const handleSelectChange = (event) => { 
        const selectedValue = $(event.target).val();
        if (!selectedValue) return;  // Ensure the event is triggered only by coin selection
    
        // console.log(selectedValue )
    
        localStorage.setItem('selectedCrypto', selectedValue);
        const selectedCrypto = crypto.find(item => item.id === selectedValue);
        // console.log( selectedCrypto.image );
        if (selectedCrypto) {
            $('#crypto_icon').hide();
            $('#crypto_name').text("processing...");
            $('#crypto_amount').text("processing...");
            
            $.ajax({
                url: `https://payments.fokawa.com/apiv2/rate/?amount=${cart_total}&currency=${currency}&coin=${selectedCrypto.id}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // console.log( data )
                    const j = data;
                    const c =  selectedCrypto.symbol.toUpperCase();
                    const scientificNumber = parseFloat(j.conversion);
                    
                    const decimalNumber = scientificNumber.toFixed(selectedCrypto.precision);
                    
                    if (data && j.conversion) {
                        $('#crypto_icon').show();
                        $('#crypto_icon').attr('src',  selectedCrypto.image);
                        $('#crypto_amount').text(`${decimalNumber} ${c}`);
                        $('#crypto_name').text(selectedCrypto.name);
 
                    }
                },
                error: function(error) {
                    console.error('Error fetching rate:', error);
                }
            });
        }
    };

        // $('#coin').select2({
        //     minimumResultsForSearch: -1,
        //     templateResult: formatOption,
        //     templateSelection: formatOption
        // });
        
        // $('#coin').hide()

        function formatOption(option) {
            if (!option.id) {
                return option.text;
            }
            var imageUrl = $(option.element).data('image');
            var $option = $(
                '<span><img src="' + imageUrl + '" class="img-flag" /> ' + option.text + '</span>'
            );
            return $option;
        }
    // Attaching the event listener with jQuery
    $('#coin').on('change', handleSelectChange).trigger('change');
});


 </script>
        <?php
    }

    public function validate_fields() {
        session_start();
        
        if( (isset( $_POST['coin']) and $_POST['coin']!="") or (isset($_SESSION['coin']) and $_SESSION['coin']!="")){
         return true;
        }
         wc_add_notice(__('Please select a coin.' .json_encode( $_POST ), 'fokawapay_gateway'), 'error');
            return false;
        
    }

    // public function save_custom_checkout_field($order_id) {
    //     if (!empty($_POST[$this->id . '-custom-text'])) {
    //         update_post_meta($order_id, '_fokawapay_custom_text', sanitize_text_field($_POST[$this->id . '-custom-text']));
    //     }
    // }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id); 
        
        //  wc_add_notice(__('Please select a cryptocurrency.' . json_encode($order), 'fokawa-pay'), 'error');
        $res = $this->get_payment_link( $order, $order_id );
        
        if($res['success']){
            return array(
                'result'   => 'success',
                'redirect' => $res['payment_url']
            );
        }else{
             wc_add_notice(__( $res['message'], 'fokawa-pay'), 'error');
            return array(
                'result'   => 'failure',
                'redirect' => ''
            );
        }
        
            exit;
           // Get the selected cryptocurrency from the checkout data
        if (isset($_POST['coin'])) {
            $selected_crypto = sanitize_text_field($_POST['coin']);
        } else {
            wc_add_notice(__('Please select a cryptocurrency.', 'fokawa-pay'), 'error');
            return array(
                'result'   => 'failure',
                'redirect' => ''
            );
        }
    
        // Use the selected cryptocurrency in your payment processing logic
        wc_add_notice(__('Selected Cryptocurrency: ' . $selected_crypto, 'fokawa-pay'), 'success');
    

        //  wc_add_notice( __( 'Please select a cryptocurrency.'.json_encode( $_POST ), 'fokawa-pay' ), 'error' );
         
         
        // Implement your payment processing logic here
            
        // $order->payment_complete();
        // $order->reduce_order_stock();

        // wc_empty_cart();

        // return array(
        //     'result' => 'success',
        //     'redirect' => $this->get_return_url($order),
        // );
    }
    
    
    
function get_payment_link( $order, $order_id ){ 
    session_start();
    // Configuration
    
    
    $options = get_option('woocommerce_fokawapay_gateway_settings');
     
    $SECRET_KEY     = $options['api_secret'];
    $merchant_id    = $options['api_key'];  
    $api_url        = 'https://payments.fokawa.com/apiv2/payment/';
    
    // Function to generate the signature
    function generate_signature($merchant_id, $json_payload, $secret) {
        $message = $merchant_id . $json_payload . $secret;
        return hash_hmac('sha256', $message, $secret);
    }
     
    // Prepare the data
    if( isset( $_POST['coin'] ) and $_POST['coin'] !=""){
        $coin           = $_POST['coin'];
        $phpamount      = WC()->cart->total;
    }else{
        $coin           = $_SESSION['coin'];
        $phpamount      = WC()->cart->total;//$_SESSION['phpamount'];
    }
    
    $currency_code = get_woocommerce_currency();
    
    $callback_url = home_url('/wp-json/fokawapay/v1/callback');
    $order_details_url = $order->get_view_order_url();
    $buyer_email = $order->get_billing_email();
    $data = [
                'order_id'          => $order_id,// "FKWSTORE".rand(10000,90000),
                'amount'            => $phpamount, 
                'currency_code'     => $currency_code,
                'merchant_id'       => $merchant_id,
                'return_url'        => $order_details_url,//$url.'/payments/paid.php',
                'notification_url'  => $callback_url,
                'payCoinSymbol'     => $coin,
                'date_created'      => date('Y-m-d H:i:s'),
                'email'             => $buyer_email
    ];
    
    // Create a JSON payload string without the signature
    $json_payload = json_encode($data, JSON_UNESCAPED_SLASHES);
    
    // Generate the signature
    $signature = generate_signature($data['merchant_id'], $json_payload, $SECRET_KEY);
    
    // Add the signature to the data
    $data['signature'] = $signature;
    
    // Encode the data with the signature
    $json_data = json_encode($data, JSON_UNESCAPED_SLASHES);
    
    // Initialize cURL
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    
    // Execute the request
    $response = curl_exec($ch);
    $response_data = json_decode($response, true);
    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Parse and print the response
        //var_dump($response);
        
       ///echo 'Response: ' . print_r($response_data, true);
        if( isset($response_data['payment_url'])){ 
            return ['success'=>true, 'payment_url' => $response_data['payment_url'] ];
            // header('location: '.$response_data['payment_url']);
            exit;
        }
    }
    
    
    return ['success'=>false, 'message' => $response_data['message']];
    //; "https://payments.fokawa.com/backoffice/cart/?error=".$response;
    // Close the cURL session
    curl_close($ch);
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



















