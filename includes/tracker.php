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