<?php
/*
Plugin Name: Product Recommendation Engine
Description: Tracks product views & purchases and shows simple recommendations on product and shop pages.
Version: 1.2.0
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



require_once PRE_PLUGIN_PATH . 'includes/recommender.php';
require_once PRE_PLUGIN_PATH . 'includes/tracker.php';

// Activation: create/update table
register_activation_hook(__FILE__, 'pre_install');
function pre_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'pre_user_activity';
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NULL,
        session_id VARCHAR(64) NULL,
        product_id BIGINT(20) NOT NULL,
        action VARCHAR(20) NOT NULL,
        quantity INT(11) NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY action_idx (action),
        KEY product_idx (product_id),
        KEY user_idx (user_id),
        KEY session_idx (session_id),
        KEY created_idx (created_at)
    ) {$charset_collate};";

    dbDelta($sql);

    // Set default options if they don't exist
    add_option('pre_recommendation_strategy', 'viewed_together');
    add_option('pre_recommendation_limit', 4);
}
if (file_exists(PRE_PLUGIN_PATH . 'admin/settings.php')) {
    require_once PRE_PLUGIN_PATH . 'admin/settings.php';
}
