<?php
/*
Plugin Name: Product Recommendation Engine
Description: Tracks product views & purchases and shows simple recommendations on product and shop pages.
Version: 1.0.0
Author: Kazi , Mahedi , Akhi
*/

if (!defined('ABSPATH'))
    exit;

// Activation check for WooCommerce
add_action('admin_init', 'pre_check_woocommerce_active');
function pre_check_woocommerce_active()
{
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>Product Recommendation Engine:</strong> This plugin requires WooCommerce to be installed and active. Please activate WooCommerce.</p></div>';
        });
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

// Constants
define('PRE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PRE_PLUGIN_URL', plugin_dir_url(__FILE__));