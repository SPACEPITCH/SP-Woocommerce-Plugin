<?php
/**
 * Plugin Name: WooCommerce Custom Payment Gateway
 * Plugin URI: https://secpaid.com
 * Description: SecPaid Payment Gateway for WooCommerce
 * Version: 1.0
 * Author: WPRuby, Ala Eddin Eltai
 * Author URI: https://secpaid.com, https://wpruby.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Other_Payment_Gateway extends WC_Payment_Gateway {

    private $order_status;
    private $text_box_required;
    private $hide_text_box;

    // Constants for callback and webhook handling
    const CALLBACK_SLUG    = 'secpaid-callback';
    const WEBHOOK_NAMESPACE = 'secpaid/v1';
    const WEBHOOK_ROUTE    = '/webhook';

    public function __construct() {
        $this->id                 = 'other_payment';
        $this->method_title       = __( 'Custom Payment', 'woocommerce-other-payment-gateway' );
        $this->title              = __( 'Custom Payment', 'woocommerce-other-payment-gateway' );
        $this->has_fields         = true;

        $this->init_form_fields();
        $this->init_settings();

        // Load settings
        $this->enabled            = $this->get_option( 'enabled' );
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->hide_text_box      = $this->get_option( 'hide_text_box' );
        $this->text_box_required  = $this->get_option( 'text_box_required' );
        $this->order_status       = $this->get_option( 'order_status' );

        // Add hooks for callback and webhook handling
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'init', [ $this, 'add_endpoint' ] );
        add_action( 'parse_request', [ $this, 'handle_callback' ] );
        add_action( 'rest_api_init', [ $this, 'register_webhook_route' ] );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'woocommerce-other-payment-gateway' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Custom Payment', 'woocommerce-other-payment-gateway' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __( 'Method Title', 'woocommerce-other-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This controls the title', 'woocommerce-other-payment-gateway' ),
                'default'     => __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Customer Message', 'woocommerce-other-payment-gateway' ),
                'type'        => 'textarea',
                'css'         => 'width:500px;',
                'default'     => 'None of the other payment options are suitable for you? Please drop us a note about your favorable payment option and we will contact you as soon as possible.',
                'description' => __( 'The message which you want to appear in the checkout page.', 'woocommerce-other-payment-gateway' ),
            ),
            'text_box_required' => array(
                'title'   => __( 'Make the text field required', 'woocommerce-other-payment-gateway' ),
                'type'    => 'checkbox',
                'label'   => __( 'Make the text field required', 'woocommerce-other-payment-gateway' ),
                'default' => 'no'
            ),
            'hide_text_box' => array(
                'title'       => __( 'Hide The Payment Field', 'woocommerce-other-payment-gateway' ),
                'type'        => 'checkbox',
                'label'       => __( 'Hide', 'woocommerce-other-payment-gateway' ),
                'default'     => 'no',
                'description' => __( 'If you do not need to show the text box for customers at all, enable this option.', 'woocommerce-other-payment-gateway' ),
            ),
            'order_status' => array(
                'title'       => __( 'Order Status After The Checkout', 'woocommerce-other-payment-gateway' ),
                'type'        => 'select',
                'options'     => wc_get_order_statuses(),
                'default'     => 'wc-completed',
                'description' => __( 'The default order status when this gateway is used for payment.', 'woocommerce-other-payment-gateway' ),
            ),
            'api_key' => array(
                'title'    => 'SecPaid API Key',
                'type'     => 'text',
                'desc_tip' => 'Enter your SecPaid API key obtained from the dashboard.',
            ),
            'api_endpoint' => array(
                'title'    => 'API Endpoint',
                'type'     => 'text',
                'default'  => 'https://app.dev.secpaid.com/api/v2/createLink',
                'desc_tip' => 'SecPaid API endpoint for generating payment links.',
            ),
            'api_secret' => array(
                'title'    => 'API Secret Key',
                'type'     => 'password',
                'desc_tip' => 'Used for webhook signature verification.',
            )
        );
    }

    public function admin_options() {
        ?>
        <h3><?php _e( 'Custom Payment Settings', 'woocommerce-other-payment-gateway' ); ?></h3>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <table class="form-table">
                        <?php $this->generate_settings_html(); ?>
                    </table>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h3 class="hndle"><span><i class="dashicons dashicons-update"></i>&nbsp;&nbsp;Upgrade to Pro</span></h3>
                        <hr>
                        <div class="inside">
                            <div class="support-widget">
                                <ul>
                                    <li>» Full Form Builder</li>
                                    <li>» Create Unlimited Custom Gateways</li>
                                    <li>» Custom Gateway Icon</li>
                                    <li>» Order Status After Checkout</li>
                                    <li>» Custom API Requests</li>
                                    <li>» Payment Information in Order's Email</li>
                                    <li>» Debugging Mode</li>
                                    <li>» Auto Hassle-Free Updates</li>
                                    <li>» High Priority Customer Support</li>
                                </ul>
                                <a href="https://wpruby.com/plugin/woocommerce-custom-payment-gateway-pro/?utm_source=custom-payment-lite&utm_medium=widget&utm_campaign=freetopro" class="button wpruby_button" target="_blank">
                                    <span class="dashicons dashicons-star-filled"></span> Upgrade Now
                                </a>
                            </div>
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
                wc_add_notice( __( 'Please provide payment information', 'woocommerce-other-payment-gateway' ), 'error' );
                return false;
            }
        }
        return true;
    }

    public function payment_fields() {
        ?>
        <fieldset>
            <p class="form-row form-row-wide">
                <?php if ( $this->hide_text_box !== 'yes' ) : ?>
                    <label for="<?php echo esc_attr( $this->id ); ?>-admin-note">
                        <?php echo wp_kses_post( $this->description ); ?>
                        <?php if ( $this->text_box_required === 'yes' ) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    <textarea id="<?php echo esc_attr( $this->id ); ?>-admin-note"
                        class="input-text"
                        name="<?php echo esc_attr( $this->id ); ?>-admin-note"
                        <?php echo ( $this->text_box_required === 'yes' ) ? 'required' : ''; ?>></textarea>
                <?php endif; ?>
            </p>
            <div class="clear"></div>
        </fieldset>
        <?php
    }

    public function process_payment( $order_id ) {
        // Log the start of payment processing
        error_log( 'Starting payment processing for order ID: ' . $order_id );

        $order = wc_get_order( $order_id );

        // Store custom note if provided
        if ( $this->hide_text_box !== 'yes' && isset( $_POST['other_payment-admin-note'] ) ) {
            $note = sanitize_textarea_field( $_POST['other_payment-admin-note'] );
            error_log( 'Customer note added: ' . $note );
            $order->add_order_note( 'Customer Note: ' . $note );
            $order->update_meta_data( '_custom_payment_note', $note );
        }

        // Create payment link
        error_log( 'Creating SecPaid payment link for order ID: ' . $order_id );
        $payment_link = $this->create_secpaid_link( $order );

        if ( is_wp_error( $payment_link ) ) {
            $error_message = $payment_link->get_error_message();
            error_log( 'Payment link creation failed: ' . $error_message );
            wc_add_notice( $error_message, 'error' );
            return array(
                'result'   => 'failure',
                'redirect' => wc_get_checkout_url() // Redirect back to checkout on failure
            );
        }

        // Update order status and meta
        error_log( 'Payment link created successfully: ' . $payment_link );
        $order->update_status( $this->order_status, __( 'Awaiting SecPaid payment', 'woocommerce-other-payment-gateway' ) );
        $order->update_meta_data( '_secpaid_payment_url', $payment_link );
        $order->save();

        // Empty cart
        error_log( 'Emptying cart after successful payment processing' );
        WC()->cart->empty_cart();

        // Log successful payment processing
        error_log( 'Payment processing completed successfully for order ID: ' . $order_id );

        return array(
            'result'   => 'success',
            'redirect' => $payment_link
        );
    }

    private function create_secpaid_link( $order ) {
        error_log( '[SecPaid] === STARTING PAYMENT LINK CREATION ===' );
        error_log( '[SecPaid] Order ID: ' . $order->get_id() );

        $api_key  = $this->get_option( 'api_key' );
        $endpoint = $this->get_option( 'api_endpoint' );

        // Generate a unique pay_id for tracking
        $pay_id = 'secpaid_' . time() . '_' . $order->get_id();
        $order->update_meta_data( '_secpaid_pay_id', $pay_id );
        $order->save();

        error_log( '[SecPaid] Generated pay_id: ' . $pay_id );

        // Request parameters
        $request_params = array(
            'amount'         => number_format( $order->get_total(), 2, '.', '' ),
            'recipient_note' => 'Order #' . $order->get_id(),
            'pay_id'         => $pay_id, // Include pay_id in the request
        );

        // Headers
        $headers = array(
            'token'      => $api_key,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept'     => '*/*',
            'Origin'     => site_url(),
            'Referer'    => $order->get_checkout_order_received_url(),
        );

        // Request args
        $args = array(
            'headers'            => $headers,
            'body'               => $request_params,
            'timeout'            => 45,
            'sslverify'          => false, // Temporary SSL bypass
            'reject_unsafe_urls' => false,
        );

        // Log request details
        error_log( '[SecPaid] Request Details: ' . print_r( array(
            'endpoint' => $endpoint,
            'headers'  => $headers,
            'body'     => $request_params,
        ), true ) );

        // Send request to SecPaid API
        $response = wp_remote_post( $endpoint, $args );

        // Handle response
        $status_code      = wp_remote_retrieve_response_code( $response );
        $response_body    = wp_remote_retrieve_body( $response );
        $response_headers = wp_remote_retrieve_headers( $response );

        error_log( '[SecPaid] === RESPONSE DETAILS ===' );
        error_log( "Status Code: $status_code" );
        error_log( 'Headers: ' . print_r( $response_headers, true ) );
        error_log( 'Body: ' . $response_body );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            error_log( '[SecPaid] WP_Error: ' . $error_message );
            return new WP_Error( 'secpaid_error', __( 'Payment gateway connection failed: ', 'woocommerce-other-payment-gateway' ) . $error_message );
        }

        if ( $status_code !== 200 ) {
            error_log( '[SecPaid] API Request Failed' );
            error_log( '[SecPaid] Server IP: ' . $_SERVER['SERVER_ADDR'] );
            error_log( '[SecPaid] Request ID: ' . ( $response_headers['x-kong-request-id'] ?? 'N/A' ) );
            return new WP_Error( 'secpaid_error', sprintf(
                __( 'Payment gateway error (HTTP %d). Please contact support with request ID: %s', 'woocommerce-other-payment-gateway' ),
                $status_code,
                $response_headers['x-kong-request-id'] ?? 'N/A'
            ) );
        }

        $body = json_decode( $response_body, true );

        if ( ! isset( $body['data']['pay_link'] ) ) {
            error_log( '[SecPaid] Invalid API Response Structure' );
            error_log( '[SecPaid] Full Response: ' . print_r( $body, true ) );
            return new WP_Error( 'secpaid_error', __( 'Invalid payment gateway response format', 'woocommerce-other-payment-gateway' ) );
        }

        // Success - return payment URL
        $payment_url = esc_url_raw( $body['data']['pay_link'] );
        error_log( '[SecPaid] Successfully generated payment URL: ' . $payment_url );

        return $payment_url;
    }

    // ================== CALLBACK HANDLING ==================
    public function add_query_vars( $vars ) {
        $vars[] = 'secpaid_callback';
        return $vars;
    }

    public function add_endpoint() {
        add_rewrite_endpoint( self::CALLBACK_SLUG, EP_ROOT );
    }

    public function handle_callback() {
        global $wp;

        if ( isset( $wp->query_vars['secpaid_callback'] ) ) {
            error_log( '[SecPaid] === CALLBACK RECEIVED ===' );

            // Get callback parameters
            $pay_id = isset( $_GET['pay_id'] ) ? sanitize_text_field( $_GET['pay_id'] ) : '';
            $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

            error_log( '[SecPaid] Callback Parameters: ' . print_r( array(
                'pay_id' => $pay_id,
                'status' => $status,
            ), true ) );

            // Find order by pay_id
            $orders = wc_get_orders( array(
                'meta_key'   => '_secpaid_pay_id',
                'meta_value' => $pay_id,
                'limit'      => 1
            ) );

            if ( empty( $orders ) ) {
                error_log( '[SecPaid] No order found for pay_id: ' . $pay_id );
                wp_die( 'Invalid payment reference', 'SecPaid Payment Error', array( 'response' => 400 ) );
            }

            $order = $orders[0];
            error_log( '[SecPaid] Processing callback for order: ' . $order->get_id() );

            // Determine redirect URL based on status
            switch ( $status ) {
                case 'success':
                    $order->add_order_note( __( 'Payment completed via SecPaid callback', 'woocommerce-other-payment-gateway' ) );
                    $order->payment_complete();
                    $redirect_url = $order->get_checkout_order_received_url();
                    break;
                case 'cancel':
                    $order->update_status( 'failed', __( 'Payment cancelled by customer', 'woocommerce-other-payment-gateway' ) );
                    // Here you can create a custom failure page or simply redirect to the checkout page.
                    $redirect_url = wc_get_page_permalink( 'checkout' );
                    break;
                default:
                    error_log( '[SecPaid] Unknown callback status: ' . $status );
                    wp_die( 'Invalid callback status', 'SecPaid Payment Error', array( 'response' => 400 ) );
            }

            wp_redirect( $redirect_url );
            exit;
        }
    }

    // ================== WEBHOOK HANDLING ==================
    public function register_webhook_route() {
        register_rest_route( self::WEBHOOK_NAMESPACE, self::WEBHOOK_ROUTE, array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true'
        ) );
    }

    public function handle_webhook( WP_REST_Request $request ) {
        error_log( '[SecPaid] === WEBHOOK RECEIVED ===' );

        $payload   = $request->get_body();
        $signature = $request->get_header( 'x-secpaid-signature' );

        error_log( '[SecPaid] Webhook Payload: ' . $payload );
        error_log( '[SecPaid] Webhook Signature: ' . $signature );

        // Verify webhook signature
        if ( ! $this->verify_webhook( $payload, $signature ) ) {
            error_log( '[SecPaid] Webhook signature verification failed' );
            return new WP_REST_Response( [ 'error' => 'Invalid signature' ], 401 );
        }

        $data = json_decode( $payload, true );

        // Validate response code
        if ( ! isset( $data['ResponseCode'] ) || $data['ResponseCode'] !== 1 ) {
            error_log( '[SecPaid] Invalid ResponseCode in webhook' );
            return new WP_REST_Response( [ 'error' => 'Invalid ResponseCode' ], 400 );
        }

        // Find order by pay_id
        $pay_id = isset( $data['data']['pay_id'] ) ? $data['data']['pay_id'] : '';
        $orders = wc_get_orders( array(
            'meta_key'   => '_secpaid_pay_id',
            'meta_value' => $pay_id,
            'limit'      => 1
        ) );

        if ( empty( $orders ) ) {
            error_log( '[SecPaid] No order found for pay_id: ' . $pay_id );
            return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
        }

        $order = $orders[0];
        error_log( '[SecPaid] Processing webhook for order: ' . $order->get_id() );

        // Handle event type from webhook data
        $status = isset( $data['data']['status'] ) ? $data['data']['status'] : '';
        switch ( $status ) {
            case 'success':
                $this->handle_payment_completed( $order, $data );
                break;
            case 'cancel':
                $this->handle_payment_failed( $order, $data );
                break;
            default:
                error_log( '[SecPaid] Unhandled webhook status: ' . $status );
                return new WP_REST_Response( [ 'error' => 'Unhandled status' ], 400 );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    private function verify_webhook( $payload, $signature ) {
        $secret     = $this->get_option( 'api_secret' );
        $calculated = hash_hmac( 'sha256', $payload, $secret );
        return hash_equals( $calculated, $signature );
    }

    private function handle_payment_completed( $order, $data ) {
        error_log( '[SecPaid] Handling payment completed for order: ' . $order->get_id() );
        if ( ! $order->is_paid() ) {
            $order->payment_complete( $data['transaction_id'] );
            $order->add_order_note( sprintf(
                __( 'Payment completed via SecPaid Webhook. Transaction ID: %s', 'woocommerce-other-payment-gateway' ),
                $data['transaction_id']
            ) );
            $order->update_status( $this->get_option( 'order_status' ) );
        }
    }

    private function handle_payment_failed( $order, $data ) {
        error_log( '[SecPaid] Handling payment failed for order: ' . $order->get_id() );
        $order->update_status( 'failed', sprintf(
            __( 'Payment failed via SecPaid. Reason: %s', 'woocommerce-other-payment-gateway' ),
            isset( $data['failure_message'] ) ? $data['failure_message'] : __( 'Unknown reason', 'woocommerce-other-payment-gateway' )
        ) );
    }
}

// Register the gateway
add_filter( 'woocommerce_payment_gateways', 'add_secpaid_gateway' );
function add_secpaid_gateway( $methods ) {
    $methods[] = 'WC_Other_Payment_Gateway';
    return $methods;
}