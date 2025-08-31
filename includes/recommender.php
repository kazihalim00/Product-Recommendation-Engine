// In includes/recommender.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

function pre_get_recommended_products($force_strategy = null)
{
    global $wpdb, $post;
    $table_name = $wpdb->prefix . 'pre_user_activity';

    $strategy = $force_strategy ?: get_option('pre_recommendation_strategy', 'viewed_together');
    $limit = (int) get_option('pre_recommendation_limit', 4);
    $product_ids = [];
    $current_product_id = is_product() && $post ? $post->ID : 0;


    $exclude_ids = $current_product_id ? [$current_product_id] : [];

    // --- STRATEGY 1: "Users who viewed this also viewed..." ---
    if ($strategy === 'viewed_together' && $current_product_id) {
        $subquery = $wpdb->prepare(
            "SELECT DISTINCT session_id FROM {$table_name} WHERE product_id = %d AND action = 'view'",
            $current_product_id
        );
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$table_name} 
             WHERE action = 'view' AND product_id != %d AND session_id IN ({$subquery})
             GROUP BY product_id ORDER BY COUNT(product_id) DESC LIMIT %d",
            $current_product_id,
            $limit
        ));
    }

    // --- STRATEGY 2: "Most Purchased" (Based on total quantity sold) ---
    if (empty($product_ids) && $strategy === 'most_purchased') {
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$table_name}
             WHERE action = 'purchase'
             GROUP BY product_id ORDER BY SUM(quantity) DESC LIMIT %d", // <-- Using SUM(quantity)
            $limit
        ));
    }

    // --- STRATEGY 3: "Trending Products" ---
    if (empty($product_ids) && $strategy === 'trending') {
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$table_name}
             WHERE action = 'view' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY product_id ORDER BY COUNT(id) DESC LIMIT %d",
            $limit
        ));
    }

    // Return logic will be added later
    return [];
}


