<?php
/**
 * Plugin Name: SecPaid WooCommerce Payment Gateway
 * Description: Secure payment processing with SecPaid for WooCommerce
 * Version: 2.1.0
 * Author: SecPaid Team
 * Text Domain: woocommerce-secpaid-payment-gateway
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(secpaid_is_woocommerce_active()){
	add_filter('woocommerce_payment_gateways', 'add_secpaid_payment_gateway');
	function add_secpaid_payment_gateway( $gateways ){
		$gateways[] = 'WC_SecPaid_Payment_Gateway';
		return $gateways;
	}

	add_action('plugins_loaded', 'init_secpaid_payment_gateway');
	function init_secpaid_payment_gateway(){
		require 'class-woocommerce-secpaid-payment-gateway.php';
	}

	add_action( 'plugins_loaded', 'secpaid_payment_load_plugin_textdomain' );
	function secpaid_payment_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-secpaid-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * @return bool
 */
function secpaid_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());

	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}

	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action( 'woocommerce_blocks_loaded',  function () {
    require_once plugin_dir_path(__FILE__). 'blocks/class-secpaid-payment-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new SecPaid_Payment_Block );
        }
    );
});

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

// Add plugin settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'secpaid_add_plugin_page_settings_link');
function secpaid_add_plugin_page_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=secpaid_payment') . '">' . __('Settings', 'woocommerce-secpaid-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}