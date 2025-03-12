<?php

class WC_Other_Payment_Gateway extends WC_Payment_Gateway {

    private $order_status;
    private $text_box_required;
    private $hide_text_box;

    // Constants for callback and webhook handling
    const CALLBACK_SLUG    = 'secpaid-callback';
    const WEBHOOK_NAMESPACE = 'secpaid/v1';
    const WEBHOOK_ROUTE    = '/webhook';

    public function __construct() {
        $this->id           = 'other_payment';
        $this->method_title = __( 'Custom Payment', 'woocommerce-other-payment-gateway' );
        $this->title        = __( 'Custom Payment', 'woocommerce-other-payment-gateway' );
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

        // (Initialization log removed on purpose)

        // Register endpoints and hooks for callback and webhook handling.
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
                'description' => __( 'This controls the title shown to customers.', 'woocommerce-other-payment-gateway' ),
                'default'     => __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Customer Message', 'woocommerce-other-payment-gateway' ),
                'type'        => 'textarea',
                'css'         => 'width:500px;',
                'default'     => 'If none of our other payment options meet your needs, please leave a note...',
                'description' => __( 'The message which will appear on the checkout page.', 'woocommerce-other-payment-gateway' ),
            ),
            'text_box_required' => array(
                'title'   => __( 'Make the text field required', 'woocommerce-other-payment-gateway' ),
                'type'    => 'checkbox',
                'label'   => __( 'Require the text field', 'woocommerce-other-payment-gateway' ),
                'default' => 'no'
            ),
            'hide_text_box' => array(
                'title'       => __( 'Hide The Payment Field', 'woocommerce-other-payment-gateway' ),
                'type'        => 'checkbox',
                'label'       => __( 'Hide', 'woocommerce-other-payment-gateway' ),
                'default'     => 'no',
                'description' => __( 'Check if you do not want to display the text box.', 'woocommerce-other-payment-gateway' ),
            ),
            'order_status' => array(
                'title'       => __( 'Order Status After Checkout', 'woocommerce-other-payment-gateway' ),
                'type'        => 'select',
                'options'     => wc_get_order_statuses(),
                'default'     => 'wc-completed',
                'description' => __( 'Initial order status when payment is initiated.', 'woocommerce-other-payment-gateway' ),
            ),
            'api_key' => array(
                'title'    => 'SecPaid API Key',
                'type'     => 'text',
                'desc_tip' => 'Enter your SecPaid API key from your account dashboard.',
                'default' => 'u06AuLfBhQdQDtYGVcbGQtUNgIO1wrFN',
            ),
            'api_endpoint' => array(
                'title'    => 'API Endpoint',
                'type'     => 'text',
                'default'  => 'https://app.dev.secpaid.com/api/v2/createLink',
                'desc_tip' => 'The API endpoint for generating payment links.',
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
                <div id="postbox-container-1">
                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e( 'Check us out at SecPaid.com', 'woocommerce-other-payment-gateway' ); ?></span></h3>
                        <div class="inside">
                            <ul>
                                <li>Payment Processing</li>
                                <li>WebShop Integration</li>
                                <li>Automation</li>
                                <li>Crypto</li>
                                <li>Advanced API Requests</li>
                                <li>Bank transfers</li>
                                <li>Refund</li>
                                <li>Split your Payments</li>
                                <li>Priority Support</li>
                            </ul>
                            <a href="https://secpaid.com" class="button" target="_blank"><?php _e( 'Check us out at SecPaid.com', 'woocommerce-other-payment-gateway' ); ?></a>
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
                <?php else : ?>
                    <?php error_log( '[SecPaid Payment Fields] Skipped displaying text field due to "hide" setting.' ); ?>
                <?php endif; ?>
            </p>
            <div class="clear"></div>
        </fieldset>
        <?php
    }

    public function process_payment( $order_id ) {
        error_log( '[SecPaid Payment] Starting payment processing for Order ID: ' . $order_id );
        $order = wc_get_order( $order_id );

        error_log( '[SecPaid Payment] HTTP_USER_AGENT: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set') );

        if ( $this->hide_text_box !== 'yes' && isset( $_POST['other_payment-admin-note'] ) ) {
            $note = sanitize_textarea_field( $_POST['other_payment-admin-note'] );
            error_log( '[SecPaid Payment] Customer note received: ' . $note );
            $order->add_order_note( 'Customer Note: ' . $note );
            $order->update_meta_data( '_custom_payment_note', $note );
        }

        error_log( '[SecPaid Payment] Creating SecPaid payment link for Order ID: ' . $order_id );
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

        error_log( '[SecPaid Payment] Payment link created: ' . $payment_link );
        // Update order to an interim status.
        $order->update_status( 'on-hold', __( 'Awaiting SecPaid payment confirmation', 'woocommerce-other-payment-gateway' ) );
        $order->update_meta_data( '_secpaid_payment_url', $payment_link );
        $order->save();

        error_log( '[SecPaid Payment] Cart emptied for Order ID: ' . $order_id );
        WC()->cart->empty_cart();

        error_log( '[SecPaid Payment] Payment processing completed for Order ID: ' . $order_id );
        return array(
            'result'   => 'success',
            'redirect' => $payment_link
        );
    }

    private function create_secpaid_link( $order ) {
        error_log( '[SecPaid API] === STARTING PAYMENT LINK CREATION for Order ID: ' . $order->get_id() . ' ===' );
        $api_key  = $this->get_option( 'api_key' );
        $endpoint = $this->get_option( 'api_endpoint' );

        $pay_id = 'secpaid_' . time() . '_' . $order->get_id();
        $order->update_meta_data( '_secpaid_pay_id', $pay_id );
        $order->save();
        error_log( '[SecPaid API] Generated pay_id: ' . $pay_id );

        $request_params = array(
            'amount'         => number_format( $order->get_total(), 2, '.', '' ),
            'recipient_note' => 'Order #' . $order->get_id(),
            'pay_id'         => $pay_id,
        );

        $headers = array(
            'token'      => $api_key,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept'     => '*/*',
            'Origin'     => site_url(),
            'Referer'    => $order->get_checkout_order_received_url(),
        );

        $args = array(
            'headers'            => $headers,
            'body'               => $request_params,
            'timeout'            => 45,
            'sslverify'          => false,
            'reject_unsafe_urls' => false,
        );

        error_log( '[SecPaid API] Request Details: ' . print_r( array(
            'endpoint' => $endpoint,
            'headers'  => $headers,
            'body'     => $request_params,
        ), true ) );

        $response = wp_remote_post( $endpoint, $args );
        $status_code      = wp_remote_retrieve_response_code( $response );
        $response_body    = wp_remote_retrieve_body( $response );
        $response_headers = wp_remote_retrieve_headers( $response );

        error_log( '[SecPaid API] Response Status Code: ' . $status_code );
        error_log( '[SecPaid API] Response Headers: ' . print_r( $response_headers, true ) );
        error_log( '[SecPaid API] Response Body: ' . $response_body );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            error_log( '[SecPaid API] WP_Error: ' . $error_message );
            return new WP_Error( 'secpaid_error', __( 'Payment gateway connection failed: ', 'woocommerce-other-payment-gateway' ) . $error_message );
        }
        if ( $status_code !== 200 ) {
            error_log( '[SecPaid API] API Request Failed. Server IP: ' . $_SERVER['SERVER_ADDR'] );
            error_log( '[SecPaid API] Request ID: ' . ( $response_headers['x-kong-request-id'] ?? 'N/A' ) );
            return new WP_Error( 'secpaid_error', sprintf(
                __( 'Payment gateway error (HTTP %d). Contact support with ID: %s', 'woocommerce-other-payment-gateway' ),
                $status_code,
                $response_headers['x-kong-request-id'] ?? 'N/A'
            ) );
        }

        $body = json_decode( $response_body, true );
        if ( ! isset( $body['data']['pay_link'] ) ) {
            error_log( '[SecPaid API] Invalid API Response Structure: ' . print_r( $body, true ) );
            return new WP_Error( 'secpaid_error', __( 'Invalid response from payment gateway', 'woocommerce-other-payment-gateway' ) );
        }

        $payment_url = esc_url_raw( $body['data']['pay_link'] );
        error_log( '[SecPaid API] Successfully generated payment URL: ' . $payment_url );
        return $payment_url;
    }

    // CALLBACK HANDLING
    public function add_query_vars( $vars ) {
        $vars[] = 'secpaid_callback';
        return $vars;
    }

    public function add_endpoint() {
        add_rewrite_endpoint( self::CALLBACK_SLUG, EP_ROOT );
    }

    public function handle_callback() {
        global $wp;
        error_log( '[SecPaid Callback] Request URI: ' . $_SERVER['REQUEST_URI'] );
        error_log( '[SecPaid Callback] Client IP: ' . $_SERVER['REMOTE_ADDR'] );
        error_log( '[SecPaid Callback] User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') );
        error_log( '[SecPaid Callback] Raw GET array: ' . print_r( $_GET, true ) );

        if ( isset( $wp->query_vars['secpaid_callback'] ) ) {
            error_log( '[SecPaid Callback] === CALLBACK RECEIVED ===' );
            $pay_id  = isset( $_GET['pay_id'] ) ? sanitize_text_field( $_GET['pay_id'] ) : '';
            $user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
            $status  = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
            error_log( '[SecPaid Callback] Parsed parameters: ' . print_r( [
                'pay_id'  => $pay_id,
                'user_id' => $user_id,
                'status'  => $status,
            ], true ) );

            $orders = wc_get_orders( [
                'meta_key'   => '_secpaid_pay_id',
                'meta_value' => $pay_id,
                'limit'      => 1
            ] );

            if ( empty( $orders ) ) {
                error_log( '[SecPaid Callback] No order found for pay_id: ' . $pay_id );
                wp_die( 'Invalid payment reference', 'SecPaid Payment Error', [ 'response' => 400 ] );
            }

            $order = $orders[0];
            error_log( '[SecPaid Callback] Found Order ID: ' . $order->get_id() );
            error_log( '[SecPaid Callback] Order status before update: ' . $order->get_status() );

            switch ( $status ) {
                case 'success':
                    error_log( '[SecPaid Callback] Processing "success" event for Order ID: ' . $order->get_id() );
                    // Force update order status to the configured status.
                    $order->update_status( $this->order_status, 'SecPaid callback: Payment completed' );
                    error_log( '[SecPaid Callback] New Order status: ' . $order->get_status() );
                    $redirect_url = $order->get_checkout_order_received_url();
                    break;
                case 'cancel':
                    error_log( '[SecPaid Callback] Processing "cancel" event for Order ID: ' . $order->get_id() );
                    $order->update_status( 'failed', 'SecPaid callback: Payment cancelled by customer' );
                    error_log( '[SecPaid Callback] New Order status: ' . $order->get_status() );
                    $redirect_url = wc_get_page_permalink( 'checkout' );
                    break;
                default:
                    error_log( '[SecPaid Callback] Unknown status value: ' . $status );
                    wp_die( 'Invalid callback status', 'SecPaid Payment Error', [ 'response' => 400 ] );
                    break;
            }

            error_log( '[SecPaid Callback] Redirecting user to: ' . $redirect_url );
            wp_redirect( $redirect_url );
            exit;
        } else {
            error_log( '[SecPaid Callback] No secpaid_callback query var found.' );
        }
    }

    // WEBHOOK HANDLING
    public function register_webhook_route() {
        register_rest_route( self::WEBHOOK_NAMESPACE, self::WEBHOOK_ROUTE, [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true'
        ] );
    }

    public function handle_webhook( WP_REST_Request $request ) {
        error_log( '[SecPaid Webhook] === WEBHOOK RECEIVED ===' );
        error_log( '[SecPaid Webhook] Request URI: ' . $_SERVER['REQUEST_URI'] );
        error_log( '[SecPaid Webhook] Client IP: ' . $_SERVER['REMOTE_ADDR'] );
        error_log( '[SecPaid Webhook] User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') );

        $payload   = $request->get_body();
        $signature = $request->get_header( 'x-secpaid-signature' );
        error_log( '[SecPaid Webhook] Raw Payload: ' . $payload );
        error_log( '[SecPaid Webhook] Received Signature: ' . $signature );

        if ( ! $this->verify_webhook( $payload, $signature ) ) {
            error_log( '[SecPaid Webhook] Signature verification FAILED' );
            return new WP_REST_Response( [ 'error' => 'Invalid signature' ], 401 );
        }

        $data = json_decode( $payload, true );
        error_log( '[SecPaid Webhook] Decoded Data: ' . print_r( $data, true ) );

        if ( ! isset( $data['ResponseCode'] ) || $data['ResponseCode'] !== 1 ) {
            error_log( '[SecPaid Webhook] Invalid ResponseCode in payload.' );
            return new WP_REST_Response( [ 'error' => 'Invalid ResponseCode' ], 400 );
        }

        $pay_id = isset( $data['data']['pay_id'] ) ? $data['data']['pay_id'] : '';
        error_log( '[SecPaid Webhook] Looking up order using pay_id: ' . $pay_id );
        $orders = wc_get_orders( [
            'meta_key'   => '_secpaid_pay_id',
            'meta_value' => $pay_id,
            'limit'      => 1
        ] );

        if ( empty( $orders ) ) {
            error_log( '[SecPaid Webhook] No order found for pay_id: ' . $pay_id );
            return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
        }

        $order = $orders[0];
        error_log( '[SecPaid Webhook] Found Order ID: ' . $order->get_id() );
        error_log( '[SecPaid Webhook] Order status before update: ' . $order->get_status() );

        $status = '';
        if ( isset( $data['data']['status'] ) ) {
            $status = $data['data']['status'];
        } elseif ( isset( $data['data']['Status'] ) ) {
            $status = $data['data']['Status'];
        }
        error_log( '[SecPaid Webhook] Extracted status: ' . $status );

        switch ( $status ) {
            case 'success':
                error_log( '[SecPaid Webhook] Processing "success" event for Order ID: ' . $order->get_id() );
                $this->handle_payment_completed( $order, $data );
                break;
            case 'cancel':
                error_log( '[SecPaid Webhook] Processing "cancel" event for Order ID: ' . $order->get_id() );
                $this->handle_payment_failed( $order, $data );
                break;
            default:
                error_log( '[SecPaid Webhook] Unhandled status value: ' . $status );
                return new WP_REST_Response( [ 'error' => 'Unhandled status' ], 400 );
        }

        error_log( '[SecPaid Webhook] Updated Order ID: ' . $order->get_id() . ' to status: ' . $order->get_status() );
        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    private function verify_webhook( $payload, $signature ) {
        $secret     = $this->get_option( 'api_secret' );
        $calculated = hash_hmac( 'sha256', $payload, $secret );
        error_log( '[SecPaid Webhook] Calculated Signature: ' . $calculated );
        return hash_equals( $calculated, $signature );
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
        error_log( '[SecPaid Webhook] New Order status: ' . $order->get_status() );
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_secpaid_gateway' );
function add_secpaid_gateway( $methods ) {
    $methods[] = 'WC_Other_Payment_Gateway';
    return $methods;
}