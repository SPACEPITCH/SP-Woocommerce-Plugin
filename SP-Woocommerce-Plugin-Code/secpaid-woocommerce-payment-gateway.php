<?php
/**
 * Plugin Name: SecPaid WooCommerce Payment Gateway
 * Description: Secure payment processing with SecPaid for WooCommerce
 * Version: 2.0
 * Author: SecPaid Team
 * Text Domain: woocommerce-secpaid-payment-gateway
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(secpaid_is_woocommerce_active()){
    add_filter('woocommerce_payment_gateways', 'add_secpaid_payment_gateway');
    function add_secpaid_payment_gateway($gateways){
        $gateways[] = 'WC_SecPaid_Payment_Gateway';
        return $gateways;
    }
    
    add_action('plugins_loaded', 'init_secpaid_payment_gateway');
    function init_secpaid_payment_gateway(){
        // Get the plugin directory path
        $plugin_dir = plugin_dir_path(__FILE__);
        
        // Check if the original file exists (for backward compatibility)
        if (file_exists($plugin_dir . 'class-woocommerce-other-payment-gateway.php')) {
            require $plugin_dir . 'class-woocommerce-other-payment-gateway.php';
        }
        // Check if our renamed file exists
        elseif (file_exists($plugin_dir . 'class-woocommerce-secpaid-payment-gateway.php')) {
            require $plugin_dir . 'class-woocommerce-secpaid-payment-gateway.php';
        }
        // If neither file exists, output an admin notice
        else {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>SecPaid Payment Gateway: Required gateway class file is missing. Please reinstall the plugin.</p></div>';
            });
        }
    }
    
    add_action('plugins_loaded', 'secpaid_payment_load_plugin_textdomain');
    function secpaid_payment_load_plugin_textdomain() {
        load_plugin_textdomain('woocommerce-secpaid-payment-gateway', FALSE, basename(dirname(__FILE__)) . '/languages/');
    }
}

/**
 * Check if WooCommerce is active
 * 
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

// Declare compatibility with WooCommerce custom order tables
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Conditionally load blocks integration if the directory and file exist
add_action('woocommerce_blocks_loaded', function() {
    $blocks_file = plugin_dir_path(__FILE__) . 'blocks/class-secpaid-payment-block.php';
    
    // Only try to load the blocks integration if the file exists
    if (file_exists($blocks_file)) {
        require_once $blocks_file;
        
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(PaymentMethodRegistry $payment_method_registry) {
                if (class_exists('SecPaid_Payment_Block')) {
                    $payment_method_registry->register(new SecPaid_Payment_Block());
                }
            }
        );
    } else {
        // Log that the blocks file is missing but continue without it
        error_log('SecPaid Payment Gateway: Blocks integration file not found at ' . $blocks_file);
    }
});

// Declare compatibility with cart checkout blocks
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Add plugin settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'secpaid_add_plugin_page_settings_link');
function secpaid_add_plugin_page_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=secpaid_payment') . '">' . __('Settings', 'woocommerce-secpaid-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Fix for the Save button issue in WooCommerce settings
add_action('admin_head', 'secpaid_fix_save_button');
function secpaid_fix_save_button() {
    if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && 
        isset($_GET['tab']) && $_GET['tab'] === 'checkout' && 
        isset($_GET['section']) && $_GET['section'] === 'secpaid_payment') {
        echo '<style>
            .woocommerce-save-button.disabled {
                opacity: 1 !important;
                pointer-events: auto !important;
            }
        </style>';
        
        echo '<script>
            jQuery(document).ready(function($) {
                $(".woocommerce-save-button.disabled").removeClass("disabled");
            });
        </script>';
    }
}