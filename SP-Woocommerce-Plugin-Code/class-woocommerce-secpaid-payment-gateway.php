<?php
require_once('/opt/bitnami/wordpress/wp-load.php');
add_action('plugins_loaded', 'init_wc_secpaid_payment_gateway', 11);
function init_wc_secpaid_payment_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return; // WooCommerce is not active.
    }
    
    if (!class_exists('WC_SecPaid_Payment_Gateway')) {
        class WC_SecPaid_Payment_Gateway extends WC_Payment_Gateway {
            // Declare all properties to avoid PHP 8.2 deprecation warnings
            private $order_status;
            private $text_box_required;
            private $hide_text_box;
            private $environment;
            private $api_key;
            private $additional_text;
            
            // Constants for callback and webhook handling
            const CALLBACK_SLUG = 'secpaid-callback';
            const WEBHOOK_SLUG = 'secpaid-webhooks';
            
            public function __construct() {
                $this->id           = 'secpaid_payment';
                $this->method_title = __('SecPaid | Secure Payments', 'woocommerce-secpaid-payment-gateway');
                $this->title        = __('SecPaid | Secure Payments', 'woocommerce-secpaid-payment-gateway');
                $this->has_fields   = true;
                $this->init_form_fields();
                $this->init_settings();
                
                // Load settings from admin
                $this->enabled           = $this->get_option('enabled');
                $this->title             = $this->get_option('title');
                $this->description       = $this->get_option('description');
                $this->hide_text_box     = $this->get_option('hide_text_box');
                $this->text_box_required = $this->get_option('text_box_required');
                $this->order_status      = $this->get_option('order_status');
                $this->environment       = $this->get_option('environment', 'development');
                $this->api_key           = $this->get_option('api_key');
                $this->additional_text   = $this->get_option('additional_text');
                
                // Register endpoints for SecPaid callback and webhook.
                add_action('woocommerce_api_' . self::CALLBACK_SLUG, array($this, 'handle_callback'));
                add_action('woocommerce_api_' . self::WEBHOOK_SLUG, array($this, 'handle_webhook'));
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                
                // Fix for the Save button issue
                add_action('admin_head', array($this, 'fix_save_button'));
            }
            

            
            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce-secpaid-payment-gateway'),
                        'type' => 'checkbox',
                        'label' => __('Enable SecPaid | Secure Payments', 'woocommerce-secpaid-payment-gateway'),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __('Method Title', 'woocommerce-secpaid-payment-gateway'),
                        'type' => 'text',
                        'description' => __('This controls the title shown to customers.', 'woocommerce-secpaid-payment-gateway'),
                        'default' => __('SecPaid Secure Payment', 'woocommerce-secpaid-payment-gateway'),
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('Customer Message', 'woocommerce-secpaid-payment-gateway'),
                        'type' => 'textarea',
                        'css' => 'width:500px;',
                        'default' => 'Pay securely with SecPaid | Secure Payments',
                        'description' => __('The message which will appear on the checkout page.', 'woocommerce-secpaid-payment-gateway'),
                    ),
                    'api_key' => array(
                        'title' => 'SecPaid API Key',
                        'type' => 'text',
                        'desc_tip' => 'Enter your SecPaid API key from your account dashboard.',
                    ),
                    'environment' => array(
                        'title' => __('Environment', 'woocommerce-secpaid-payment-gateway'),
                        'type' => 'select',
                        'description' => __('Choose between Development and Production environments', 'woocommerce-secpaid-payment-gateway'),
                        'default' => 'development',
                        'options' => array(
                            'development' => __('Development', 'woocommerce-secpaid-payment-gateway'),
                            'production' => __('Production', 'woocommerce-secpaid-payment-gateway')
                        ),
                        'desc_tip' => true,
                    ),
                    'additional_text' => array(
                        'title' => __('Additional Information', 'woocommerce-secpaid-payment-gateway'),
                        'type' => 'textarea',
                        'css' => 'width:500px;',
                        'default' => 'Thank you for choosing SecPaid | Secure Payments',
                        'description' => __('Additional information to display on the checkout page.', 'woocommerce-secpaid-payment-gateway'),
                    ),
                );
            }
            
            public function admin_options() {
                $logo_url = plugin_dir_url(__FILE__) . 'resources/secpaid-logo.png';
                ?>
                <div class="secpaid-admin-header">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="SecPaid Logo" class="secpaid-logo">
                    <h2><?php _e('SecPaid Payment Settings', 'woocommerce-secpaid-payment-gateway'); ?></h2>
                </div>
                
                <div id="secpaid-settings-container">
                    <div id="secpaid-settings-main">
                        <div class="secpaid-settings-section">
                            <h3><?php _e('Gateway Configuration', 'woocommerce-secpaid-payment-gateway'); ?></h3>
                            <p class="secpaid-description"><?php _e('Configure your SecPaid payment gateway settings below to start accepting payments.', 'woocommerce-secpaid-payment-gateway'); ?></p>
                            <table class="form-table">
                                <?php $this->generate_settings_html(); ?>
                            </table>
                        </div>
                    </div>
                    
                    <div id="secpaid-settings-sidebar">
                        <div class="secpaid-sidebar-box">
                            <h3><?php _e('SecPaid - Secure Payment Solutions', 'woocommerce-secpaid-payment-gateway'); ?></h3>
                            <div class="secpaid-features">
                                <ul>
                                    <li><span class="dashicons dashicons-yes"></span> Payment Service Provider</li>
                                    <li><span class="dashicons dashicons-yes"></span> Split-Payments</li>
                                    <li><span class="dashicons dashicons-yes"></span> WebShop Integration</li>
                                    <li><span class="dashicons dashicons-yes"></span> Many Payment options</li>
                                    <li><span class="dashicons dashicons-yes"></span> Advanced API Requests</li>
                                    <li><span class="dashicons dashicons-yes"></span> Easy Payment Notification</li>
                                    <li><span class="dashicons dashicons-yes"></span> Tax Reports</li>
                                    <li><span class="dashicons dashicons-yes"></span> Refunds & Dispute Management</li>
                                    <li><span class="dashicons dashicons-yes"></span> Priority Support</li>
                                </ul>
                            </div>
                            <div class="secpaid-actions">
                                <a href="https://docs.secpaid.com" class="button button-primary" target="_blank"><?php _e('Documentation', 'woocommerce-secpaid-payment-gateway'); ?></a>
                                <a href="https://secpaid.com/support" class="button" target="_blank"><?php _e('Get Support', 'woocommerce-secpaid-payment-gateway'); ?></a>
                            </div>
                        </div>
                        
                        <div class="secpaid-sidebar-box">
                            <h3><?php _e('Need Help?', 'woocommerce-secpaid-payment-gateway'); ?></h3>
                            <p><?php _e('If you need assistance setting up your payment gateway, please contact our support team.', 'woocommerce-secpaid-payment-gateway'); ?></p>
                            <a href="mailto:support@secpaid.com" class="button"><?php _e('Contact Support', 'woocommerce-secpaid-payment-gateway'); ?></a>
                        </div>
                    </div>
                </div>
                
                <div id="secpaid-settings-footer">
                    <!-- The save button will be moved here via JavaScript -->
                </div>
                <?php
            }
            
            public function fix_save_button() {
                if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' &&
                    isset($_GET['tab']) && $_GET['tab'] === 'checkout' &&
                    isset($_GET['section']) && $_GET['section'] === $this->id) {
                    ?>
                    <style>
                        /* Main container styling */
                        #secpaid-settings-container {
                            display: flex;
                            gap: 20px;
                            margin-top: 20px;
                        }
                        
                        #secpaid-settings-main {
                            flex: 1;
                            min-width: 0;
                        }
                        
                        #secpaid-settings-sidebar {
                            width: 300px;
                            flex-shrink: 0;
                        }
                        
                        /* Header styling */
                        .secpaid-admin-header {
                            display: flex;
                            align-items: center;
                            margin-bottom: 20px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid #e2e4e7;
                        }
                        
                        .secpaid-logo {
                            max-height: 50px;
                            margin-right: 15px;
                        }
                        
                        .secpaid-admin-header h2 {
                            margin: 0;
                            color: #23282d;
                            font-size: 1.5em;
                        }
                        
                        /* Section styling */
                        .secpaid-settings-section {
                            background: #fff;
                            border: 1px solid #e2e4e7;
                            border-radius: 4px;
                            padding: 20px;
                            margin-bottom: 20px;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                        }
                        
                        .secpaid-settings-section h3 {
                            margin-top: 0;
                            padding-bottom: 10px;
                            border-bottom: 1px solid #f0f0f1;
                            color: #23282d;
                        }
                        
                        .secpaid-description {
                            color: #646970;
                            font-size: 13px;
                            margin-bottom: 20px;
                        }
                        
                        /* Sidebar styling */
                        .secpaid-sidebar-box {
                            background: #fff;
                            border: 1px solid #e2e4e7;
                            border-radius: 4px;
                            padding: 20px;
                            margin-bottom: 20px;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                        }
                        
                        .secpaid-sidebar-box h3 {
                            margin-top: 0;
                            padding-bottom: 10px;
                            border-bottom: 1px solid #f0f0f1;
                            color: #23282d;
                        }
                        
                        .secpaid-features ul {
                            margin: 0;
                            padding: 0;
                            list-style: none;
                        }
                        
                        .secpaid-features li {
                            padding: 8px 0;
                            border-bottom: 1px solid #f0f0f1;
                            display: flex;
                            align-items: center;
                        }
                        
                        .secpaid-features li:last-child {
                            border-bottom: none;
                        }
                        
                        .secpaid-features .dashicons {
                            color: #2271b1;
                            margin-right: 8px;
                        }
                        
                        .secpaid-actions {
                            margin-top: 15px;
                            display: flex;
                            gap: 10px;
                        }
                        
                        /* Footer styling */
                        #secpaid-settings-footer {
                            margin-top: 20px;
                            padding-top: 15px;
                            border-top: 1px solid #e2e4e7;
                        }
                        
                        /* Form styling */
                        .form-table th {
                            width: 200px;
                            padding: 15px 10px 15px 0;
                        }
                        
                        .form-table td {
                            padding: 15px 10px;
                        }
                        
                        /* Button styling */
                        .woocommerce-save-button {
                            background: #2271b1 !important;
                            border-color: #2271b1 !important;
                            color: #fff !important;
                            font-weight: 500 !important;
                            padding: 0 15px !important;
                            height: 35px !important;
                            line-height: 35px !important;
                            text-shadow: none !important;
                            box-shadow: none !important;
                        }
                        
                        .woocommerce-save-button:hover {
                            background: #135e96 !important;
                            border-color: #135e96 !important;
                        }
                        
                        .woocommerce-save-button.disabled {
                            opacity: 1 !important;
                            pointer-events: auto !important;
                        }
                        
                        /* Hide the default submit button */
                        p.submit {
                            display: none;
                        }
                    </style>
                    <script>
                        jQuery(document).ready(function($) {
                            // Remove disabled class from save button
                            $(".woocommerce-save-button.disabled").removeClass("disabled");
                            
                            // Move the save button to our custom footer
                            var saveButton = $(".woocommerce-save-button");
                            saveButton.detach();
                            $("#secpaid-settings-footer").append('<div class="submit"></div>');
                            $("#secpaid-settings-footer .submit").append(saveButton);
                            
                            // Hide any other submit buttons that might be present
                            $("#mainform > p.submit").hide();
                        });
                    </script>
                    <?php
                }
            }
            public function validate_fields() {
                if ($this->text_box_required === 'yes' && $this->hide_text_box !== 'yes') {
                    $textbox_value = isset($_POST['secpaid_payment-admin-note']) ? sanitize_textarea_field($_POST['secpaid_payment-admin-note']) : '';
                    if (empty($textbox_value)) {
                        error_log('[SecPaid Validate] Text field is required but empty.');
                        wc_add_notice(__('Please provide payment information', 'woocommerce-secpaid-payment-gateway'), 'error');
                        return false;
                    }
                }
                error_log('[SecPaid Validate] Fields validated successfully.');
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
                    
                    <!-- Display additional payment method logos using local resources -->
                    <div style="margin-top: 10px;">
                        <?php
                        $plugin_dir_url = plugin_dir_url(__FILE__);
                        $payment_logos = [
                            'PayPal' => $plugin_dir_url . 'resources/Paypal.png',
                            'Visa' => $plugin_dir_url . 'resources/Visa.jpg',
                            'MasterCard' => $plugin_dir_url . 'resources/Mastercard.png',
                            'Apple Pay' => $plugin_dir_url . 'resources/ApplePay.png',
                            'Google Pay' => $plugin_dir_url . 'resources/Gpay.png'
                        ];
                        
                        foreach ($payment_logos as $name => $url) {
                            if (file_exists(plugin_dir_path(__FILE__) . 'resources/' . basename($url))) {
                                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($name) . ' Logo" style="max-width: 50px; margin-right: 5px;" />';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            
            public function process_payment($order_id) {
                error_log('[SecPaid Payment] Starting payment processing for Order ID: ' . $order_id);
                $order = wc_get_order($order_id);
                // Update order to 'pending' when payment is initiated.
                $order->update_status('pending', __('Awaiting payment via SecPaid', 'woocommerce-secpaid-payment-gateway'));
                // Generate payment link
                $payment_link = $this->create_secpaid_link($order);
                if (is_wp_error($payment_link)) {
                    $error_message = $payment_link->get_error_message();
                    error_log('[SecPaid Payment] Payment link creation failed: ' . $error_message);
                    wc_add_notice($error_message, 'error');
                    return array(
                        'result' => 'failure',
                        'redirect' => wc_get_checkout_url()
                    );
                }
                // Save payment link and redirect
                $order->update_meta_data('_secpaid_payment_url', $payment_link);
                $order->save();
                // Empty cart
                WC()->cart->empty_cart();
                // Redirect to payment link
                return array(
                    'result' => 'success',
                    'redirect' => $payment_link
                );
            }
            
            // Get the API endpoint based on the environment setting
            private function get_api_endpoint() {
                $environment = $this->get_option('environment', 'development');
                if ($environment === 'production') {
                    return 'https://app.secpaid.com/api/v2/createLink';
                } else {
                    return 'https://app.dev.secpaid.com/api/v2/createLink';
                }
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
                $endpoint = $this->get_api_endpoint(); // Use the environment-based endpoint
                
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
                            'response_body' => $response_body,
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
                        __('Payment link creation failed: ', 'woocommerce-secpaid-payment-gateway') . $e->getMessage());
                }
            }
            
            public function handle_callback() {
                $logger = wc_get_logger();
                // Log the full request URI and parameters
                error_log('SecPaid callback received: ' . $_SERVER['REQUEST_URI']);
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
                    'status' => ['pending', 'on-hold', 'processing', 'failed']
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
                
                // Check if a webhook has already processed this order
                $webhook_processed = $order->get_meta('_secpaid_webhook_processed');
                
                if ($webhook_processed === 'yes') {
                    // Webhook has already processed this order - do not change status
                    error_log('Webhook has already processed this order. Callback will not change status.');
                    $order->add_order_note(__('SecPaid callback received but status not changed as webhook has already processed this order.', 'woocommerce-secpaid-payment-gateway'));
                    $order->update_meta_data('_secpaid_callback_received', 'yes');
                    $order->update_meta_data('_secpaid_callback_time', current_time('mysql'));
                    $order->update_meta_data('_secpaid_callback_status', $status);
                    $order->save();
                } else {
                    // No webhook yet, process callback normally
                    switch ($status) {
                        case 'success':
                            $order->update_status('on-hold', __('Payment confirmed via SecPaid callback', 'woocommerce-secpaid-payment-gateway'));
                            $order->update_meta_data('_secpaid_callback_received', 'yes');
                            $order->update_meta_data('_secpaid_callback_time', current_time('mysql'));
                            $order->update_meta_data('_secpaid_callback_status', $status);
                            $order->save();
                            break;
                            
                        case 'cancel':
                            $order->update_status('cancelled', __('Payment cancelled via SecPaid callback', 'woocommerce-secpaid-payment-gateway'));
                            $order->update_meta_data('_secpaid_callback_received', 'yes');
                            $order->update_meta_data('_secpaid_callback_time', current_time('mysql'));
                            $order->update_meta_data('_secpaid_callback_status', $status);
                            $order->save();
                            break;
                            
                        default:
                            error_log('Unknown callback status received: ' . $status);
                            wp_die('Invalid callback status', 'SecPaid Payment Error', ['response' => 400]);
                    }
                }
                
                // Always redirect the customer appropriately
                $redirect_url = ($status === 'success') ? $order->get_checkout_order_received_url() : $order->get_checkout_payment_url();
                
                error_log('Callback processing completed for Order ID=' . $order->get_id());
                wp_redirect($redirect_url);
                exit;
            }

            public function handle_webhook() {
                // Log request details
                error_log('SecPaid webhook received: ' . $_SERVER['REQUEST_URI']);
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
                    ]);
                    
                    if (empty($orders)) {
                        error_log('No order found for pay_id: ' . $pay_id);
                        wp_die('Invalid payment reference', 'SecPaid Webhook Error', ['response' => 400]);
                    }
                    
                    $order = $orders[0];
                    $current_status = $order->get_status();
                    error_log('Found matching order: Order ID=' . $order->get_id() . ', Status=' . $current_status);
                    
                    // Check if callback was received
                    $callback_received = $order->get_meta('_secpaid_callback_received');
                    $callback_status = $order->get_meta('_secpaid_callback_status');
                    
                    if ($callback_received === 'yes') {
                        error_log('Callback was previously received with status: ' . $callback_status);
                    }
                    
                    // Process webhook based on status - ALWAYS override callback
                    switch (strtolower($status)) {
                        case 'success':
                            // Always set to processing for success webhooks
                            if ($current_status !== 'processing') {
                                $order->update_status('processing', __('Payment confirmed via SecPaid webhook (overriding previous status)', 'woocommerce-secpaid-payment-gateway'));
                                error_log('Updated order status from ' . $current_status . ' to processing: Order ID=' . $order->get_id());
                            } else {
                                error_log('Order already in processing status: Order ID=' . $order->get_id());
                            }
                            break;
                            
                        case 'cancel':
                            // Always set to failed for cancel webhooks
                            if ($current_status !== 'failed') {
                                $order->update_status('failed', __('Payment cancelled via SecPaid webhook (overriding previous status)', 'woocommerce-secpaid-payment-gateway'));
                                error_log('Updated order status from ' . $current_status . ' to failed: Order ID=' . $order->get_id());
                            } else {
                                error_log('Order already in failed status: Order ID=' . $order->get_id());
                            }
                            break;
                            
                        default:
                            error_log('Unknown webhook status received: ' . $status);
                            wp_die('Invalid webhook status', 'SecPaid Webhook Error', ['response' => 400]);
                    }
                    
                    // Record webhook processing
                    $order->update_meta_data('_secpaid_webhook_processed', 'yes');
                    $order->update_meta_data('_secpaid_webhook_time', current_time('mysql'));
                    $order->update_meta_data('_secpaid_webhook_status', $status);
                    $order->save();
                    
                    error_log('Webhook processing completed for Order ID=' . $order->get_id());
                    wp_die('Webhook processed successfully', 'SecPaid Webhook', ['response' => 200]);
                } catch (Exception $e) {
                    error_log('Webhook processing error: ' . $e->getMessage());
                    wp_die('Webhook processing error', 'SecPaid Webhook Error', ['response' => 500]);
                }
            }
                                    
            private function handle_payment_completed($order, $data) {
                error_log('[SecPaid Webhook] Handling payment completion for Order ID: ' . $order->get_id());
                $transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : '';
                $order->update_status($this->order_status, 'SecPaid Webhook: Payment marked as complete. Transaction ID: ' . $transaction_id);
                $order->add_order_note(sprintf(
                    __('Payment confirmed via SecPaid Webhook. Transaction ID: %s', 'woocommerce-secpaid-payment-gateway'),
                    $transaction_id
                ));
                error_log('[SecPaid Webhook] New Order status: ' . $order->get_status());
            }
            
            private function handle_payment_failed($order, $data) {
                error_log('[SecPaid Webhook] Handling payment failure for Order ID: ' . $order->get_id());
                $failure_msg = isset($data['failure_message']) ? $data['failure_message'] : __('Unknown reason', 'woocommerce-secpaid-payment-gateway');
                $order->update_status('failed', 'SecPaid Webhook: Payment failed. Reason: ' . $failure_msg);
                $order->add_order_note(sprintf(
                    __('Payment failed via SecPaid Webhook. Reason: %s', 'woocommerce-secpaid-payment-gateway'),
                    $failure_msg
                ));
                error_log('[SecPaid Webhook] New Order status: ' . $order->get_status());
            }
            
            // Validate API key field
            public function validate_api_key_field($key, $value) {
                if (empty($value)) {
                    WC_Admin_Settings::add_error(__('API Key is required for SecPaid payments to work correctly.', 'woocommerce-secpaid-payment-gateway'));
                    return '';
                }
                
                return $value;
            }
        } // End class WC_SecPaid_Payment_Gateway.
    }
    
    // Add the gateway to WooCommerce.
    if (!function_exists('add_secpaid_payment_gateway')) {
        function add_secpaid_payment_gateway($gateways) {
            $gateways[] = 'WC_SecPaid_Payment_Gateway';
            return $gateways;
        }
        add_filter('woocommerce_payment_gateways', 'add_secpaid_payment_gateway');
    }
    
    // Flush rewrite rules on activation.
    register_activation_hook(__FILE__, function() {
        flush_rewrite_rules();
    });
    
    class SecPaid_Checkout_Handler {
        public function __construct() {
            add_action('template_redirect', [$this, 'redirect_to_order_checkout']);
        }
        
        public function redirect_to_order_checkout() {
            error_log('SecPaid checkout redirect hook fired');
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
                    'created_via' => 'secpaid_checkout'
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
                
                // Empty cart
                WC()->cart->empty_cart();
                error_log('Order created: ' . $order->get_id());
                
                // Redirect
                wp_redirect($order->get_checkout_payment_url());
                exit;
            }
        }
    }
    
    new SecPaid_Checkout_Handler();
}
?>