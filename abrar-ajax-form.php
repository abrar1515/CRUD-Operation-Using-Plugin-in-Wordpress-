<?php
/*
Plugin Name: Abrar Ajax Form
Description: A simple contact form plugin with AJAX functionality.
Version: 1.0
Author: Abrar
*/

register_activation_hook(__FILE__, 'create_plugin_database_table');

function create_plugin_database_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'abrar_form_entries';

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            phone varchar(55) NOT NULL,
            email varchar(55) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Enqueue Bootstrap CSS and JS for both frontend and backend
function enqueue_bootstrap() {
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap');
add_action('admin_enqueue_scripts', 'enqueue_bootstrap');

// Enqueue our own JS file
function enqueue_custom_script() {
    wp_enqueue_script('abrar-ajax-form', plugin_dir_url(__FILE__) . 'js/abrar-ajax-form.js', array('jquery'), '1.0', true);
    wp_localize_script('abrar-ajax-form', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script', PHP_INT_MAX);
add_action('admin_enqueue_scripts', 'enqueue_custom_script', PHP_INT_MAX);

// Shortcode for the form
function abrar_contact_form() {
    ob_start();
    ?>
    <form id="abrar-contact-form">
        <input type="text" name="name" class="form-control" placeholder="Name" required>
        <input type="tel" name="phone" class="form-control mt-3" placeholder="Phone" required>
        <input type="email" name="email" class="form-control mt-3" placeholder="Email" required>
        <input type="submit" class="btn btn-primary mt-3" value="Submit">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('abrar_contact_form', 'abrar_contact_form');

// AJAX action for the form submission
function handle_contact_form() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abrar_form_entries';
    $wpdb->insert($table_name, array(
        'name' => $_POST['name'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email']
    ));
  $data = array( 'message' => 'inserted' );
  echo json_encode( $data );

    wp_die(); 
}
add_action('wp_ajax_contact_form', 'handle_contact_form');
add_action('wp_ajax_nopriv_contact_form', 'handle_contact_form');

// AJAX action for the delete button
function delete_entry() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abrar_form_entries';
    $deleted = $wpdb->delete($table_name, array('id' => $_POST['id']));
    if ($deleted) {
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false));
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_delete_entry', 'delete_entry');

// AJAX action for the edit button
function edit_entry() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abrar_form_entries';
    $updated = $wpdb->update($table_name, array('name' => $_POST['name'], 'phone' => $_POST['phone'], 'email' => $_POST['email']), array('id' => $_POST['id']));
    if ($updated) {
        echo json_encode(array('success' => true, 'id' => $_POST['id'], 'name' => $_POST['name'], 'phone' => $_POST['phone'], 'email' => $_POST['email']));
    } else {
        echo json_encode(array('success' => false));
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_edit_entry', 'edit_entry');

// Add a menu item in the WordPress admin area
function add_admin_menu() {
    add_menu_page('Abrar Form Entries', 'Abrar Form Entries', 'manage_options', 'abrar-form-entries', 'display_admin_page', 'dashicons-feedback', 6);
}
add_action('admin_menu', 'add_admin_menu');

// Display the admin page
function display_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abrar_form_entries';
    $entries = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="container">';
    echo '<h1>Abrar Form Entries</h1>';
    echo '<table class="table">';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach($entries as $entry) {
        echo '<tr>';
        echo '<td>' . $entry->id . '</td>';
        echo '<td>' . $entry->name . '</td>';
        echo '<td>' . $entry->phone . '</td>';
        echo '<td>' . $entry->email . '</td>';
        echo '<td><button class="btn btn-primary edit-button" data-id="' . $entry->id . '" data-toggle="modal" data-target="#editModal">Edit</button> <button class="btn btn-danger delete-button" data-id="' . $entry->id . '">Delete</button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Edit Modal
    echo '<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">';
    echo '<div class="modal-dialog" role="document">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title" id="editModalLabel">Edit Entry</h5>';
    echo '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
    echo '<span aria-hidden="true">&times;</span>';
    echo '</button>';
    echo '</div>';
    echo '<div class="modal-body">';
    echo '<form id="edit-form" class="form-group">';
    echo '<input type="hidden" name="id" id="edit-id">';
    echo '<input type="text" name="name" id="edit-name" class="form-control" placeholder="Name" required>';
    echo '<input type="tel" name="phone" id="edit-phone" class="form-control" placeholder="Phone" required>';
    echo '<input type="email" name="email" id="edit-email" class="form-control" placeholder="Email" required>';
    echo '</form>';
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
    echo '<button type="button" class="btn btn-primary" id="edit-save">Save changes</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

?>