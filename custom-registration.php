<?php
/**
 * Template Name: Custom Registration Page
 */

get_header();

if (isset($_POST['register'])) {
    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']); // New field
    $contact_number = sanitize_text_field($_POST['contact_number']); // New fieldd

    // Check if username or email already exists
    if (!username_exists($username) && !email_exists($email)) {
        // Create a new user
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            // Update additional user meta
            update_user_meta($user_id, 'first_name', $first_name); // Update with first name
            update_user_meta($user_id, 'contact_number', $contact_number); // New field
            echo "User registered successfully!";
        } else {
            echo "Error creating user: " . $user_id->get_error_message();
        }
    } else {
        echo "Username or email already exists.";
    }
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="registration-form">
            <h2>Register</h2>
            <form method="post">
                <label for="first_name">First Name:</label> <!-- New field -->
                <input type="text" name="first_name" id="first_name" required>

                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <label for="contact_number">Contact Number:</label>
                <input type="tel" name="contact_number" id="contact_number">

                <input type="submit" name="register" value="Register">
            </form>
        </div>
    </main>
</div>

<?php
get_footer();
?>
