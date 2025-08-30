// In admin/settings.php
<?php
if (!defined('ABSPATH'))
    exit;

// 1. Add the admin menu page
add_action('admin_menu', function () {
    add_menu_page('Recommendations', 'Recommendations', 'manage_options', 'pre_recommendations', 'pre_render_settings_page', 'dashicons-chart-line', 25);
});

// Function to render the main settings page
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