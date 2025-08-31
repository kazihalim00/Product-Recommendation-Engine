// In admin/settings.php
<?php
if (!defined('ABSPATH'))
    exit;

// 1. Add the admin menu page
add_action('admin_menu', function () {
    add_menu_page(
        'Recommendations',          // Page Title
        'Recommendations',          // Menu Title
        'manage_options',           // Capability
        'pre_recommendations',      // Menu Slug
        'pre_render_settings_page', // Function to render the page
        'dashicons-chart-line',     // Icon
        25                          // Position
    );

    add_submenu_page(
        'pre_recommendations',      // Parent Slug
        'Activity Log',             // Page Title
        'Activity Log',             // Menu Title
        'manage_options',           // Capability
        'pre_user_activity',        // Menu Slug
        'pre_render_activity_log'   // Function
    );
});

// 2. Register settings, sections, and fields
add_action('admin_init', function () {
    register_setting('pre_settings_group', 'pre_recommendation_strategy');
    register_setting('pre_settings_group', 'pre_recommendation_limit');

    add_settings_section(
        'pre_general_settings_section',
        'Recommendation Settings',
        null,
        'pre_recommendations'
    );

    add_settings_field(
        'pre_recommendation_strategy_field',
        'Recommendation Strategy',
        'pre_strategy_field_callback',
        'pre_recommendations',
        'pre_general_settings_section'
    );
    add_settings_field(
        'pre_recommendation_limit_field',
        'Number of Products',
        'pre_limit_field_callback',
        'pre_recommendations',
        'pre_general_settings_section'
    );
});

// 3. Callback functions to render the fields
function pre_strategy_field_callback()

// Function to render the main settings pages
function pre_render_settings_page()
>>>>>>> origin/feature-login
{
    $current_value = get_option('pre_recommendation_strategy', 'viewed_together');
    ?>
    <select name="pre_recommendation_strategy">
        <option value="viewed_together" <?php selected($current_value, 'viewed_together'); ?>>Collaborative Filtering
            ("Users who viewed this also viewed...")</option>
        <option value="most_purchased" <?php selected($current_value, 'most_purchased'); ?>>Most Purchased (Bestsellers
            based on tracked sales)</option>
        <option value="trending" <?php selected($current_value, 'trending'); ?>>Trending (Most viewed products in last 30
            days)</option>
        <option value="category" <?php selected($current_value, 'category'); ?>>Simple (Random products from last viewed
            category)</option>
    </select>
    <p class="description">Choose how recommendations are generated. Collaborative Filtering works best on single product
        pages.</p>
    <?php
}

function pre_limit_field_callback()
{
    $current_value = get_option('pre_recommendation_limit', 4);
    echo '<input type="number" name="pre_recommendation_limit" value="' . esc_attr($current_value) . '" min="1" max="10" />';
}

// 4. Function to render the main settings page
function pre_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Product Recommendation Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('pre_settings_group');
            do_settings_sections('pre_recommendations');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// 5. Function to render the activity log sub-page
function pre_render_activity_log()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'pre_user_activity';
    $rows = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 100");

    echo '<div class="wrap">';
    echo '<h1>User Activity Log (Last 100 Entries)</h1>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>ID</th><th>User ID</th><th>Session ID</th><th>Product ID</th><th>Action</th><th>Timestamp</th></tr></thead>';
    echo '<tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . $r->id . '</td>';
        echo '<td>' . ($r->user_id ?: 'Guest') . '</td>';
        echo '<td>' . substr($r->session_id, 0, 12) . '...</td>';
        echo '<td><a href="' . get_edit_post_link($r->product_id) . '">' . get_the_title($r->product_id) . ' (' . $r->product_id . ')</a></td>';
        echo '<td>' . esc_html($r->action) . '</td>';
        echo '<td>' . $r->created_at . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}