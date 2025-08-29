// In includes/tracker.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

// Initialize guest session cookie early (before headers)

add_action('init', function () {
    if (!is_user_logged_in() && !isset($_COOKIE['pre_sid'])) {
        $sid = wp_generate_password(20, false, false);
        setcookie(
            'pre_sid',
            $sid,
            time() + 60 * 60 * 24 * 30,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );
        $_COOKIE['pre_sid'] = $sid;
    }
});

//Helper: get current session id (null for logged-in users)

function pre_get_session_id()
{
    if (is_user_logged_in())
        return null;
    return isset($_COOKIE['pre_sid']) ? sanitize_text_field($_COOKIE['pre_sid']) : null;
}


// Track single product views

add_action('template_redirect', function () {
    if (!function_exists('is_product') || !is_product())
        return;

    global $post, $wpdb;
    if (!$post || $post->post_type !== 'product')
        return;

    $user_id = get_current_user_id();
    $session_id = pre_get_session_id();

    // Insert product view into DB (quantity is 1 for a view)
    $wpdb->insert(
        $wpdb->prefix . 'pre_user_activity',
        [
            'user_id' => $user_id ? intval($user_id) : null,
            'session_id' => $session_id,
            'product_id' => intval($post->ID),
            'action' => 'view',
            'quantity' => 1,
        ],
        ['%d', '%s', '%d', '%s', '%d']
    );

    // Store last viewed category for recommendations
    $terms = wp_get_post_terms($post->ID, 'product_cat');
    if (!is_wp_error($terms) && !empty($terms)) {
        $cat_id = $terms[0]->term_id;
        setcookie(
            'pre_last_cat',
            (string) $cat_id,
            time() + 60 * 60 * 24 * 7,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );
        $_COOKIE['pre_last_cat'] = (string) $cat_id;
    }
});

// Track purchases and their QUANTITY on thank-you page

add_action('woocommerce_thankyou', function ($order_id) {
    if (!$order_id || !function_exists('wc_get_order'))
        return;

    $order = wc_get_order($order_id);
    if (!$order)
        return;

    global $wpdb;
    $user_id = $order->get_user_id();
    $session_id = $user_id ? null : pre_get_session_id();

    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        $quantity = $item->get_quantity(); // <-- Get the quantity here

        if (!$pid)
            continue;

        $wpdb->insert(
            $wpdb->prefix . 'pre_user_activity',
            [
                'user_id' => $user_id ? intval($user_id) : null,
                'session_id' => $session_id,
                'product_id' => intval($pid),
                'action' => 'purchase',
                'quantity' => intval($quantity), // <-- Save the quantity
            ],
            ['%d', '%s', '%d', '%s', '%d'] // <-- Add format for quantity
        );
    }
});