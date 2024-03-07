<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );


function enqueue_custom_form_scripts() {
    // Enqueue your script
    wp_enqueue_script('custom-form-script', get_template_directory_uri() . '/js/custom-form-script.js', array('jquery'), '1.0', true);

    // Enqueue your styles
    wp_enqueue_style('custom-form-style', get_template_directory_uri() . '/css/custom-form-style.css', array(), '1.0');
}

add_action('wp_enqueue_scripts', 'enqueue_custom_form_scripts');

function child_enqueue_styles() {
    wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

//----------------------------extra functionality adding custom fields to menu items---------------------------------
/**
 * Add custom fields to menu items.
 */
function add_menu_visibility_fields($item_id, $item, $depth, $args) {
    // Display a drop-down with options for visibility
    $visibility_options = array(
        'all'       => 'Visible for All Users',
        'loggedin'  => 'Visible for Logged In Users',
        'loggedout' => 'Visible for Logged Out Users',
    );

    $visibility = get_post_meta($item_id, '_menu_item_visibility', true);

    echo '<p class="field-visibility description description-wide">';
    echo '<label for="menu-item-visibility-' . esc_attr($item_id) . '">';
    echo 'Visibility: ';
    echo '<select id="menu-item-visibility-' . esc_attr($item_id) . '" class="widefat" name="menu-item-visibility[' . esc_attr($item_id) . ']">';
    
    foreach ($visibility_options as $key => $label) {
        $selected = ($visibility === $key) ? 'selected="selected"' : '';
        echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }

    echo '</select>';
    echo '</label>';
    echo '</p>';
}

add_filter('wp_nav_menu_item_custom_fields', 'add_menu_visibility_fields', 10, 4);

/**
 * Save the custom fields when the menu is saved.
 */
function save_menu_visibility_fields($menu_id, $menu_item_db_id, $menu_item_args) {
    if (isset($_REQUEST['menu-item-visibility'][$menu_item_db_id])) {
        $visibility = sanitize_key($_REQUEST['menu-item-visibility'][$menu_item_db_id]);
        update_post_meta($menu_item_db_id, '_menu_item_visibility', $visibility);
    }
}

add_action('wp_update_nav_menu_item', 'save_menu_visibility_fields', 10, 3);

/**
 * Exclude menu items based on visibility.
 */
function exclude_menu_items_by_visibility($items, $menu, $args) {
    foreach ($items as $key => $item) {
        $visibility = get_post_meta($item->ID, '_menu_item_visibility', true);

        if (($visibility === 'loggedin' && !is_user_logged_in()) ||
            ($visibility === 'loggedout' && is_user_logged_in())) {
            unset($items[$key]);
        }
    }

    return $items;
}

// Hook the function to the 'wp_get_nav_menu_items' filter with priority 10 and 3 arguments
add_filter('wp_get_nav_menu_items', 'exclude_menu_items_by_visibility', 10, 3);


//--------------------hiding admin bar--------------------------------------
/**
 * Hide admin bar for subscribers.
 */
function hide_admin_bar_for_subscribers() {
    if (current_user_can('subscriber')) {
        // Hide the admin bar for subscribers
        add_filter('show_admin_bar', '__return_false');
    }
}

// Hook the function to the 'wp' action
add_action('wp', 'hide_admin_bar_for_subscribers');

//----------------------redirecting subscribers to user dashboard page-------------------------
/**
 * Redirect subscribers to the user dashboard page after login.
 *
 * @param string $user_login User login name.
 * @param WP_User $user WP_User object.
 */
function redirect_subscribers_after_login($user_login, $user) {
    if (!is_wp_error($user) && !empty($user->roles) && in_array('subscriber', $user->roles)) {
        // Get the URL of the user dashboard page
        $dashboard_url = home_url('/user-dashboard'); // Replace with the actual URL of your user dashboard page

        // Redirect subscribers to the user dashboard
        wp_redirect($dashboard_url);
        exit();
    }
}

// Hook the function to the 'wp_login' action
add_action('wp_login', 'redirect_subscribers_after_login', 10, 2);

//********************************************************************** */



//******************display fields on user profiles************************** */
// Display custom fields on user profile page
function custom_user_profile_fields($user) {
    ?>
    <h3>Additional Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="name">Name</label></th>
            <td><input type="text" name="name" id="name" value="<?php echo esc_attr(get_the_author_meta('first_name', $user->ID)); ?>"></td>
        </tr>
        <tr>
            <th><label for="age">Age</label></th>
            <td><input type="number" name="age" id="age" value="<?php echo esc_attr(get_the_author_meta('age', $user->ID)); ?>"></td>
        </tr>
        <tr>
            <th><label for="contact_number">Contact Number</label></th>
            <td><input type="tel" name="contact_number" id="contact_number" value="<?php echo esc_attr(get_the_author_meta('contact_number', $user->ID)); ?>"></td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'custom_user_profile_fields');
add_action('edit_user_profile', 'custom_user_profile_fields');

// Save custom fields on profile update
function save_custom_user_profile_fields($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['name']));
        update_user_meta($user_id, 'age', intval($_POST['age']));
        update_user_meta($user_id, 'contact_number', sanitize_text_field($_POST['contact_number']));
    }
}
add_action('personal_options_update', 'save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_custom_user_profile_fields');
