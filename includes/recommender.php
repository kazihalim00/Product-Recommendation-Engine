<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1️⃣ Get recommended products based on selected strategy
 */
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

    // --- FALLBACK STRATEGY: "Last Viewed Category" ---
    if (empty($product_ids)) {
        $cat_id = isset($_COOKIE['pre_last_cat']) ? intval($_COOKIE['pre_last_cat']) : 0;
        if ($cat_id) {
            $args = [
                'post_type' => 'product',
                'posts_per_page' => $limit,
                'post_status' => 'publish',
                'orderby' => 'rand',
                'post__not_in' => $exclude_ids,
                'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cat_id]],
            ];
            return (new WP_Query($args))->posts;
        }
    }

    if (!empty($product_ids)) {
        $args = [
            'post_type' => 'product',
            'post__in' => $product_ids,
            'posts_per_page' => $limit,
            'orderby' => 'post__in',
        ];
        return (new WP_Query($args))->posts;
    }

    return [];
}

/**
 * 2️⃣ Shortcode to display recommendations
 */
add_shortcode('pre_recommended', function ($atts) {
    $atts = shortcode_atts(['strategy' => null], $atts);
    $products = pre_get_recommended_products($atts['strategy']);

    if (!$products)
        return '';

    $output = '<div class="pre-recommended-products-content">';
    $output .= '<h2>You Might Also Like</h2>';
    $output .= '<div class="pre-products-grid">';
    foreach ($products as $p) {
        $product = wc_get_product($p->ID);
        if (!$product)
            continue;

        $output .= '<div class="pre-product">';
        $output .= '<a href="' . get_permalink($p->ID) . '">';
        $output .= $product->get_image('woocommerce_thumbnail');
        $output .= '<h3>' . $product->get_name() . '</h3>';
        $output .= '<span class="price">' . $product->get_price_html() . '</span>';
        $output .= '</a>';
        $output .= '</div>';
    }
    $output .= '</div>';
    $output .= '</div>';
    return $output;
});

/**
 * 3️⃣ Display recommendations after single product content
 */
add_action('woocommerce_after_single_product_summary', function () {
    echo do_shortcode('[pre_recommended]');
}, 15);

/**
 * 4️⃣ Display recommendations on the shop page via a modal
 */
add_action('woocommerce_after_shop_loop', function () {
    $strategy = get_option('pre_recommendation_strategy');
    $shop_strategy = ($strategy === 'viewed_together') ? 'trending' : $strategy;

    echo '<div class="pre-modal-trigger-wrapper"><button id="pre-show-modal" class="button">✨ Show Recommendations</button></div>';
    echo '<div id="pre-modal-content-wrapper" style="display:none;">' . do_shortcode('[pre_recommended strategy="' . $shop_strategy . '"]') . '</div>';
}, 15);