<?php
/*
Template Name: User Dashboard
*/

if (function_exists('get_header')) {
    get_header();
}
?>
<!-- Include Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?php

// Check if the user is logged in
if (is_user_logged_in()) {
    global $wpdb;

    // Get user information
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;


    // Get the user's profile image URL
    $profile_image_url = get_user_meta($user_id, 'profile_image', true);

    // Set the path to the placeholder image in the child theme directory
    $placeholder_image_path = get_stylesheet_directory_uri() . '/images/placeholder-image.jpg';
    
    // Handle date filtering
    $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '';

    // Initialize $start_date and $end_date
    $start_date = $end_date = '';

// Parse the date range into start and end dates
if (!empty($date_range)) {
    $dates = explode(' to ', $date_range);

    // Make sure we have both start and end dates
    if (count($dates) === 2) {
        $start_date = date('Y-m-d', strtotime(trim($dates[0])));
        $end_date = date('Y-m-d', strtotime(trim($dates[1])));
    }
}

// Retrieve user-specific data based on the date filter
$where_clause = '';

if (!empty($start_date) && !empty($end_date)) {
    $where_clause = $wpdb->prepare(
        " AND submission_date BETWEEN %s AND %s",
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    );
}

$data = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}custom_form_data WHERE user_id = %d%s",
        $user_id,
        $where_clause
    ),
    ARRAY_A
);

// Count the number of forms submitted by the user based on the date filter
$form_count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}custom_form_data WHERE user_id = %d%s",
        $user_id,
        $where_clause
    )
);




    // Handle image replacement
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
        // Process the uploaded image
        $uploaded_image = $_FILES['profile_image'];

        // Check if the upload is successful
        if ($uploaded_image['error'] === 0) {
            // Upload the image to the media library
            $attachment_id = media_handle_upload('profile_image', 0);

            // Update the user meta with the attachment ID
            update_user_meta($user_id, 'profile_image', wp_get_attachment_url($attachment_id));

            // Get the updated profile image URL
            $profile_image_url = get_user_meta($user_id, 'profile_image', true);
        }
    }


    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $old_password = $_POST['old-password'];
        $new_password = $_POST['new-password'];
        $confirm_password = $_POST['confirm-new-password'];

        // Check if the old password matches the current password
        if (wp_check_password($old_password, $current_user->user_pass, $user_id)) {
            // Check if the new password and confirm password match
            if ($new_password === $confirm_password) {
                // Update the user's password
                wp_set_password($new_password, $user_id);
                echo '<p>Password changed successfully!</p>';
            } else {
                echo '<p>New password and confirm password do not match.</p>';
            }
        } else {
            echo '<p>Old password is incorrect.</p>';
        }
    }
    ?>

    <style>
        /* Add your custom styles here */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }

        .dashboard-container {
            display: flex;
            width: 80vw;
            height: 100vh;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            width: 30%;
            background-color: #f5f5f5;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-image {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
        }

        .user-info p {
            margin: 10px 0;
        }
        #page .site-content {
            flex-grow: 1;
            margin: 0 auto;
        }

        .user-info {
            text-align: center;
        }
        .logout-button {
            margin-top: auto;
        }

        .main-content {
            width: 60%;
            padding: 30px;
            box-sizing: border-box;
            background-color: #fff;
            border-left: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: left;
        }

        h2 {
            margin-bottom: 20px;
        }
    </style>

    <div class="dashboard-container">
        <div class="sidebar">
            <img class="profile-image" src="<?php echo esc_url($profile_image_url ? $profile_image_url : $placeholder_image_path); ?>" alt="Profile Image">
            <div class="user-info">
                <p><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></p>
                <p><?php echo esc_html($current_user->user_email); ?></p>
            </div>
            <div>
                <button id="user-dashboard-btn" type="button">Dashboard</button>
                <button id="change-password-btn" type="button">Change Password</button>
                <form method="post" action="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-button">
                    <button type="submit">Logout</button>
                </form>
            </div>
        </div>
        <div class="main-content">
            <div id="password-change-form" style="display: none;">
                <form method="post" action="">
                    <label for="old-password">Old Password:</label>
                    <input type="password" name="old-password" required> <br>

                    <label for="new-password">New Password:</label>
                    <input type="password" name="new-password" required> <br>

                    <label for="confirm-new-password">Confirm New Password:</label>
                    <input type="password" name="confirm-new-password" required> <br>

                    <button type="submit" name="change_password">Submit</button>
                </form>
            </div>
            <div id="user-dashboard-form" style="display: block;">
                <form method="post" action="" id="dateFilterForm">
                    <label for="dateRange">Select Date Range:</label>
                    <input type="text" id="dateRange" name="date_range" class="flatpickr" placeholder="Select date range">

                    <button type="submit">Apply Filter</button>
                </form>

                <h2>User Information</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['name']); ?></td>
                                <td><?php echo esc_html($row['email']); ?></td>
                                <td><?php echo esc_html($row['phone_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h4>Number of Forms Submitted: <?php echo esc_html($form_count); ?></h4>
            </div>
        </div>
    </div>

<?php
} else {
    // Display a message or redirect to the login page for non-logged-in users
    echo '<p>You must be logged in to view this content.</p>';
}

get_footer();
?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dateRangeInput = document.getElementById('dateRange');
        var userDashboardBtn = document.getElementById('user-dashboard-btn');
        var userDashboardForm = document.getElementById('user-dashboard-form');
        var passwordChangeForm = document.getElementById('password-change-form'); // Ensure this element exists

        flatpickr(dateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
        });

        // Handle the date filter form submission
        document.querySelector('#dateFilterForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var dateRangeValue = dateRangeInput.value;

            // Checking if the date range has a valid value (two dates selected)
            if (dateRangeValue.split(' to ').length === 2) {
                // Submit the form data without reloading the page
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'date_range=' + encodeURIComponent(dateRangeValue),
                })
                    .then(response => response.text())
                    .then(data => {
                        // Replace part of the page with the new filtered data.
                        // Assuming the response 'data' is the HTML content to be displayed
                        userDashboardForm.innerHTML = data;
                        // Re-initialize flatpickr on the new form elements if needed.
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                alert('Please select a valid date range!');
            }
        });

        userDashboardBtn.addEventListener('click', function () {
            userDashboardForm.style.display = 'block';
            passwordChangeForm.style.display = 'none';
        });
    });
</script>
