<?php
require_once('/opt/bitnami/wordpress/wp-load.php');
add_action( 'plugins_loaded', 'init_wc_other_payment_gateway', 11 );
function init_wc_other_payment_gateway() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return; // WooCommerce is not active.
    }

    if ( ! class_exists( 'WC_Other_Payment_Gateway' ) ) {

        class WC_Other_Payment_Gateway extends WC_Payment_Gateway {
            private $order_status;
            private $text_box_required;
            private $hide_text_box;

            // Constants for callback and webhook handling
            const CALLBACK_SLUG = 'secpaid-callback';
            const WEBHOOK_SLUG = 'payments-webhooks';
            public function __construct() {
                $this->id           = 'other_payment';
                $this->method_title = __( 'SecPaid | Secure Payments', 'woocommerce-other-payment-gateway' );
                $this->title        = __( 'SecPaid | Secure Payments', 'woocommerce-other-payment-gateway' );
                $this->has_fields   = true;
                $this->init_form_fields();
                $this->init_settings();

                // Load settings from admin
                $this->enabled           = $this->get_option( 'enabled' );
                $this->title             = $this->get_option( 'title' );
                $this->description       = $this->get_option( 'description' );
                $this->hide_text_box     = $this->get_option( 'hide_text_box' );
                $this->text_box_required = $this->get_option( 'text_box_required' );
                $this->order_status      = $this->get_option( 'order_status' );

                // Register endpoints for SecPaid callback and webhook.
                add_action( 'woocommerce_api_' . self::CALLBACK_SLUG, array( $this, 'handle_callback' ) );
                add_action('woocommerce_api_' . self::WEBHOOK_SLUG, array($this, 'handle_webhook'));
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'woocommerce-other-payment-gateway' ),
                        'type' => 'checkbox',
                        'label' => __( 'Enable SecPaid | Secure Payments', 'woocommerce-other-payment-gateway' ),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __( 'Method Title', 'woocommerce-other-payment-gateway' ),
                        'type' => 'text',
                        'description' => __( 'This controls the title shown to customers.', 'woocommerce-other-payment-gateway' ),
                        'default' => __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __( 'Customer Message', 'woocommerce-other-payment-gateway' ),
                        'type' => 'textarea',
                        'css' => 'width:500px;',
                        'default' => 'Describe the SecPaid | Secure Payments in your Checkout',
                        'description' => __( 'The message which will appear on the checkout page.', 'woocommerce-other-payment-gateway' ),
                    ),
                    'api_key' => array(
                        'title' => 'SecPaid API Key',
                        'type' => 'text',
                        'desc_tip' => 'Enter your SecPaid API key from your account dashboard.',
                    ),
                    'api_endpoint' => array(
                        'title' => 'SecPaid.com API Endpoint',
                        'type' => 'text',
                        'default' => 'https://app.dev.secpaid.com/api/v2/createLink',
                        'desc_tip' => 'The API endpoint for generating payment links. Can be set to Dev/Test',
                    ),
                    'additional_text' => array(
                        'title' => __( 'Additional Information', 'woocommerce-other-payment-gateway' ),
                        'type' => 'textarea',
                        'css' => 'width:500px;',
                        'default' => 'Thank you for choosing SecPaid | Secure Payments',
                        'description' => __( 'Additional information to display on the checkout page.', 'woocommerce-other-payment-gateway' ),
                    ),
                );
            }
            public function admin_options() {
                ?>
                <h3><?php _e( 'SecPaid Payment Settings', 'woocommerce-other-payment-gateway' ); ?></h3>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <table class="form-table">
                                <?php $this->generate_settings_html(); ?>
                            </table>
                        </div>
                        <div id="postbox-container-1">
                            <div class="postbox">
                                <h3 class="hndle"><span><?php _e( 'Check us out at SecPaid.com', 'woocommerce-other-payment-gateway' ); ?></span></h3>
                                <div class="inside">
                                    <ul>
                                        <li>Payment Service Provider</li>
                                        <li>Split-Payments</li>
                                        <li>WebShop Integration</li>
                                        <li>Many Payment options</li>
                                        <li>Advanced API Requests</li>
                                        <li>Easy Payment Notifcation</li>
                                        <li>Tax Reports</li>
                                        <li>Refunds & Dispute Management</li>
                                        <li>Priority Support</li>
                                    </ul>
                                    <a href="https://docs.secpaid.com" class="button" target="_blank"><?php _e( 'Documentation', 'woocommerce-other-payment-gateway' ); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }

            public function validate_fields() {
                if ( $this->text_box_required === 'yes' && $this->hide_text_box !== 'yes' ) {
                    $textbox_value = isset( $_POST['other_payment-admin-note'] ) ? sanitize_textarea_field( $_POST['other_payment-admin-note'] ) : '';
                    if ( empty( $textbox_value ) ) {
                        error_log( '[SecPaid Validate] Text field is required but empty.' );
                        wc_add_notice( __( 'Please provide payment information', 'woocommerce-other-payment-gateway' ), 'error' );
                        return false;
                    }
                }
                error_log( '[SecPaid Validate] Fields validated successfully.' );
                return true;
            }

            public function payment_fields() {
                ?>
                <div style="margin-bottom: 10px;">
                    <!-- Display the main payment gateway logo -->
                    <?php
                    $logo_url = $this->get_option('logo');
                    if (!empty($logo_url)) {
                        echo '<img src="' . esc_url($logo_url) . '" alt="SecPaid Logo" style="max-width: 100px; margin-bottom: 10px;" />';
                    }
                    ?>
            
                    <!-- Display the description -->
                    <p><?php echo wp_kses_post($this->description); ?></p>
            
                    <!-- Display additional payment method logos -->
                    <div style="margin-top: 10px;">
                        <?php
                        $payment_logos = [
                            'PayPal' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg',
                            'Visa' => 'https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png',
                            'MasterCard' => 'https://upload.wikimedia.org/wikipedia/commons/b/b7/MasterCard_Logo.svg',
                            'Apple Pay' => 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fassets-global.website-files.com%2F6047a9e35e5dc54ac86ddd90%2F638a66f53496040a4a1629b2_pyY_-KcDDLTxLW16brCFaa8QlHS6i-b_gfpqFwRD3y0.png&f=1&nofb=1&ipt=8cf4531d6082fb577e98f6509b0851c86d19c668bc1647e0db1d922aa0d2aee2&ipo=images',
                            'Google Pay' => 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fi.pinimg.com%2Foriginals%2Ffc%2Fd1%2F6a%2Ffcd16a3389b7f88c4aa5539d33f50646.png&f=1&nofb=1&ipt=2e804e03d911d91da1df430b41e7715f9c146da13a5e859487d784fe8d98c2a4&ipo=images',
                            'SEPA' => 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fcdn0.iconfinder.com%2Fdata%2Ficons%2Fbusiness-and-marketing-glyph-2%2F64%2Fbusiness-and-marketing-glyph-2-16-1024.png&f=1&nofb=1&ipt=7e1783fc7a94484250e65e0d61fc7e2c19a7c6ead27e863a4f8521d0584cf195&ipo=images'
                        ];
            
                        foreach ($payment_logos as $name => $url) {
                            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($name) . ' Logo" style="max-width: 50px; margin-right: 5px;" />';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            public function process_payment( $order_id ) {
                error_log( '[SecPaid Payment] Starting payment processing for Order ID: ' . $order_id );
                $order = wc_get_order( $order_id );
                // Update order to 'pending' when payment is initiated.
                $order->update_status( 'pending', __( 'Awaiting payment via SecPaid', 'woocommerce-other-payment-gateway' ) );
                // Generate payment link
                $payment_link = $this->create_secpaid_link( $order );
                if ( is_wp_error( $payment_link ) ) {
                    $error_message = $payment_link->get_error_message();
                    error_log( '[SecPaid Payment] Payment link creation failed: ' . $error_message );
                    wc_add_notice( $error_message, 'error' );
                    return array(
                        'result'   => 'failure',
                        'redirect' => wc_get_checkout_url()
                    );
                }
                // Save payment link and redirect
                $order->update_meta_data( '_secpaid_payment_url', $payment_link );
                $order->save();
                // Empty cart
                WC()->cart->empty_cart();
                // Redirect to payment link
                return array(
                    'result'   => 'success',
                    'redirect' => $payment_link
                );
            }
            
            private function create_secpaid_link($order) {
                $logger = wc_get_logger();
                
                $logger->info(
                    'Starting payment link creation',
                    array(
                        'source' => 'secpaid_payment',
                        'order_id' => $order->get_id(),
                        'order_total' => $order->get_total()
                    )
                );
                
                $api_key = $this->get_option('api_key');
                $endpoint = $this->get_option('api_endpoint');
                
                // Prepare request parameters
                $request_params = array(
                    'amount' => number_format($order->get_total(), 2, '.', ''),
                    'recipient_note' => 'Order #' . $order->get_id()
                );
                
                $logger->debug(
                    'Payment request parameters',
                    array(
                        'source' => 'secpaid_payment',
                        'params' => $request_params
                    )
                );
                
                $headers = array(
                    'token' => $api_key,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept' => '*/*',
                    'Origin' => site_url(),
                    'Referer' => $order->get_checkout_order_received_url(),
                );
                
                $args = array(
                    'headers' => $headers,
                    'body' => $request_params,
                    'timeout' => 45,
                    'sslverify' => false,
                    'reject_unsafe_urls' => false,
                );
                
                $logger->debug(
                    'API request configuration',
                    array(
                        'source' => 'secpaid_payment',
                        'endpoint' => $endpoint,
                        'headers' => $headers,
                        'args' => $args
                    )
                );
                
                try {
                    $response = wp_remote_post($endpoint, $args);
                    $status_code = wp_remote_retrieve_response_code($response);
                    $response_body = wp_remote_retrieve_body($response);
                    $response_headers = wp_remote_retrieve_headers($response);
                    
                    $logger->info(
                        'API response received',
                        array(
                            'source' => 'secpaid_payment',
                            'status_code' => $status_code,
                            'response_body' => $response_body, // Log the full response body
                            'response_headers' => $response_headers
                        )
                    );
                    
                    if (is_wp_error($response)) {
                        throw new Exception($response->get_error_message());
                    }
                    
                    if ($status_code !== 200) {
                        throw new Exception(sprintf(
                            'API request failed with status code %d',
                            $status_code
                        ));
                    }
                    
                    $body = json_decode($response_body, true);
                    
                    // Correctly access the pay_link and pay_id from the response
                    if (!isset($body['data']['pay_link']) || !isset($body['data']['id'])) {
                        throw new Exception('Invalid API response structure');
                    }
                    
                    $payment_url = esc_url_raw($body['data']['pay_link']);
                    $pay_id = $body['data']['id']; // Use 'id' as the pay_id
                    
                    // Store the SecPaid-generated pay_id
                    $order->update_meta_data('_secpaid_pay_id', $pay_id);
                    $order->save();
                    
                    $logger->info(
                        'Payment link generated and pay_id stored',
                        array(
                            'source' => 'secpaid_payment',
                            'payment_url' => $payment_url,
                            'pay_id' => $pay_id,
                            'order_id' => $order->get_id()
                        )
                    );
                    
                    return $payment_url;
                    
                } catch (Exception $e) {
                    $logger->error(
                        'Payment link creation failed',
                        array(
                            'source' => 'secpaid_payment',
                            'error' => $e->getMessage(),
                            'backtrace' => true
                        )
                    );
                    
                    return new WP_Error('secpaid_error', 
                        __('Payment link creation failed: ', 'woocommerce-other-payment-gateway') . $e->getMessage());
                }
            }

            // CALLBACK HANDLING
            public function handle_callback() {
                $logger = wc_get_logger();
            
                // Log the full request URI and parameters
                error_log('Callback received: ' . $_SERVER['REQUEST_URI']);
                error_log('Query parameters before decoding: ' . print_r($_GET, true));
            
                // Decode the query string to fix HTML-encoded ampersands
                $query_string = html_entity_decode($_SERVER['QUERY_STRING']);
                parse_str($query_string, $query_params);
            
                // Log the decoded query parameters
                error_log('Decoded query parameters: ' . print_r($query_params, true));
            
                // Get and sanitize parameters
                $pay_id = isset($query_params['pay_id']) ? sanitize_text_field($query_params['pay_id']) : '';
                $status = isset($query_params['status']) ? sanitize_text_field($query_params['status']) : '';
            
                // Log the extracted parameters
                error_log('Extracted callback parameters: pay_id=' . $pay_id . ', status=' . $status);
            
                // Check for missing parameters
                if (empty($pay_id) || empty($status)) {
                    error_log('Missing required callback parameters: pay_id=' . $pay_id . ', status=' . $status);
                    wp_die('Missing required parameters', 'SecPaid Payment Error', ['response' => 400]);
                }
            
                // Enhanced order lookup with error handling
                $orders = wc_get_orders([
                    'meta_key' => '_secpaid_pay_id',
                    'meta_value' => $pay_id,
                    'limit' => 1,
                    'status' => ['pending', 'on-hold', 'processing',  'failed'] // Added processing status
                ]);

                if (empty($orders)) {
                    // Try alternative lookup method
                    $order_id = wc_get_order_id_by_order_key($pay_id);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && in_array($order->get_status(), ['pending', 'on-hold', 'processing', 'failed'])) {
                            $orders = [$order];
                        }
                    }
                }

                if (empty($orders)) {
                    error_log('No order found for pay_id: ' . $pay_id . '. Checked meta_key and order_key methods.');
                    wp_die('Invalid payment reference', 'SecPaid Payment Error', ['response' => 400]);
                }
            
                $order = $orders[0];
                error_log('Found matching order: Order ID=' . $order->get_id() . ', Status=' . $order->get_status());
            

                error_log('Found matching order: Order ID=' . $order->get_id() . ', Status=' . $order->get_status());

                // Process callback based on status
                switch ($status) {
                    case 'success':
                        $order->update_status('processing', __('Payment confirmed via SecPaid callback', 'woocommerce-other-payment-gateway'));
                        $redirect_url = $order->get_checkout_order_received_url();
                        break;
                    
                    case 'cancel':
                        $order->update_status('failed', __('Payment cancelled via SecPaid callback', 'woocommerce-other-payment-gateway'));
                        $redirect_url = $order->get_checkout_payment_url();
                        break;
                    
                    default:
                        error_log('Unknown callback status received: ' . $status);
                        wp_die('Invalid callback status', 'SecPaid Payment Error', ['response' => 400]);
                }
                
                error_log('Callback processing completed for Order ID=' . $order->get_id());
                wp_redirect($redirect_url);
                exit;
            }

            public function handle_webhook() {
                // Log request details
                error_log('Webhook received: ' . $_SERVER['REQUEST_URI']);
                error_log('Client IP: ' . $_SERVER['REMOTE_ADDR']);
                error_log('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
            
                // Get and log the raw payload
                $payload = file_get_contents('php://input');
                error_log('Raw webhook payload: ' . $payload);
            
                // Decode the query string to fix HTML-encoded ampersands
                parse_str($payload, $data);
                error_log('Decoded webhook data: ' . print_r($data, true));
            
                try {
                    if (!isset($data['ResponseCode']) || $data['ResponseCode'] != 1) {
                        error_log('Invalid ResponseCode in webhook payload: ' . ($data['ResponseCode'] ?? 'missing'));
                        wp_die('Invalid ResponseCode', 'SecPaid Webhook Error', ['response' => 400]);
                    }
            
                    $pay_id = isset($data['data']['pay_id']) ? sanitize_text_field($data['data']['pay_id']) : '';
                    $status = isset($data['data']['status']) ? sanitize_text_field($data['data']['status']) : '';
            
                    // Log the extracted parameters
                    error_log('Extracted webhook parameters: pay_id=' . $pay_id . ', status=' . $status);
            
                    // Check for missing parameters
                    if (empty($pay_id) || empty($status)) {
                        error_log('Missing required webhook parameters: pay_id=' . $pay_id . ', status=' . $status);
                        wp_die('Missing required parameters', 'SecPaid Webhook Error', ['response' => 400]);
                    }
            
                    // Enhanced order lookup with error handling
                    $orders = wc_get_orders([
                        'meta_key' => '_secpaid_pay_id',
                        'meta_value' => $pay_id,
                        'limit' => 1,
                        'status' => ['pending', 'on-hold', 'processing', 'completed']
                    ]);
            
                    if (empty($orders)) {
                        error_log('No order found for pay_id: ' . $pay_id);
                        wp_die('Invalid payment reference', 'SecPaid Webhook Error', ['response' => 400]);
                    }
            
                    $order = $orders[0];
                    error_log('Found matching order: Order ID=' . $order->get_id() . ', Status=' . $order->get_status());
            
                    // Update order status based on webhook status
                    switch (strtolower($status)) {
                        case 'success':
                            $order->update_status('processing', __('Payment confirmed via SecPaid webhook', 'woocommerce-other-payment-gateway'));
                            break;
                        case 'cancel':
                            $order->update_status('failed', __('Payment cancelled via SecPaid webhook', 'woocommerce-other-payment-gateway'));
                            break;
                        default:
                            error_log('Unknown webhook status received: ' . $status);
                            wp_die('Invalid webhook status', 'SecPaid Webhook Error', ['response' => 400]);
                    }
            
                    error_log('Webhook processing completed for Order ID=' . $order->get_id());
                    wp_die('Webhook processed successfully', 'SecPaid Webhook', ['response' => 200]);
            
                } catch (Exception $e) {
                    error_log('Webhook processing error: ' . $e->getMessage());
                    wp_die('Webhook processing error', 'SecPaid Webhook Error', ['response' => 500]);
                }
            }

            private function handle_payment_completed( $order, $data ) {
                error_log( '[SecPaid Webhook] Handling payment completion for Order ID: ' . $order->get_id() );
                $transaction_id = isset( $data['transaction_id'] ) ? $data['transaction_id'] : '';
                $order->update_status( $this->order_status, 'SecPaid Webhook: Payment marked as complete. Transaction ID: ' . $transaction_id );
                $order->add_order_note( sprintf(
                    __( 'Payment confirmed via SecPaid Webhook. Transaction ID: %s', 'woocommerce-other-payment-gateway' ),
                    $transaction_id
                ) );
                error_log( '[SecPaid Webhook] New Order status: ' . $order->get_status() );
            }

            private function handle_payment_failed( $order, $data ) {
                error_log( '[SecPaid Webhook] Handling payment failure for Order ID: ' . $order->get_id() );
                $failure_msg = isset( $data['failure_message'] ) ? $data['failure_message'] : __( 'Unknown reason', 'woocommerce-other-payment-gateway' );
                $order->update_status( 'failed', 'SecPaid Webhook: Payment failed. Reason: ' . $failure_msg );
                $order->add_order_note( sprintf(
                    __( 'Payment failed via SecPaid Webhook. Reason: %s', 'woocommerce-other-payment-gateway' ),
                    $failure_msg
                ) );
                error_log( '[SecPaid Webhook] New Order status: ' . $order->get_status() );
            }
        } // End class WC_Other_Payment_Gateway.
    }

    // Add the gateway to WooCommerce.
    if ( ! function_exists( 'add_other_payment_gateway' ) ) {
        function add_other_payment_gateway( $gateways ) {
            $gateways[] = 'WC_Other_Payment_Gateway';
            return $gateways;
        }
        add_filter( 'woocommerce_payment_gateways', 'add_other_payment_gateway' );
    }
    
    // Flush rewrite rules on activation.
    register_activation_hook( __FILE__, function() {
        flush_rewrite_rules();
    } );

    class Custom_Checkout_Handler {
        public function __construct() {
            add_action('template_redirect', [$this, 'redirect_to_order_checkout']);
        }
    
        public function redirect_to_order_checkout() {
            error_log('Checkout redirect hook fired');
    
            // Prevent infinite loop on order-pay page
            if (is_wc_endpoint_url('order-pay')) {
                error_log('Already on order-pay page - aborting');
                return;
            }
    
            $cart = WC()->cart;
            
            // Check cart first
            if ($cart->is_empty()) {
                error_log('Cart is empty - no action needed');
                return;
            }
    
            // Only run on main checkout page without order params
            if (is_checkout() && !isset($_GET['order'])) {
                error_log('Checkout page detected without order parameter');
    
                $order = wc_create_order([
                    'customer_id' => get_current_user_id(),
                    'status' => 'pending',
                    'created_via' => 'custom_checkout'
                ]);
    
                // Add error handling
                if (is_wp_error($order)) {
                    error_log('Order creation failed: ' . $order->get_error_message());
                    return;
                }
    
                // Add cart items
                foreach ($cart->get_cart() as $cart_item) {
                    $order->add_product($cart_item['data'], $cart_item['quantity']);
                }
    
                // Set addresses
                $order->set_address(WC()->customer->get_billing(), 'billing');
                $order->set_address(WC()->customer->get_shipping(), 'shipping');
    
                // Calculate totals
                $order->calculate_totals();
                $order->save();
    
                // ⭐️⭐️⭐️ EMPTY CART ⭐️⭐️⭐️
                WC()->cart->empty_cart();
    
                error_log('Order created: ' . $order->get_id());
    
                // Redirect
                wp_redirect($order->get_checkout_payment_url());
                exit;
            }
        }
    }
    new Custom_Checkout_Handler();
}
?>