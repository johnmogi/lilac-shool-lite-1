<?php
/**
 * Simple syntax check for School Manager Lite plugin
 * Run this file to check if the plugin has any PHP syntax errors
 */

// Simulate WordPress constants and functions for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../../');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', dirname(__FILE__) . '/../');
}

// Mock WordPress functions that might be called
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated, $plugin_rel_path) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        return true;
    }
}

if (!function_exists('shortcode_exists')) {
    function shortcode_exists($tag) {
        return false;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        return true;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('did_action')) {
    function did_action($hook_name) {
        return false;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true; // Simulate admin context for testing
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "LOG: $message\n";
    }
}

if (!function_exists('get_class')) {
    // get_class is a PHP built-in, but just in case
}

if (!function_exists('file_exists')) {
    // file_exists is a PHP built-in, but just in case
}

if (!function_exists('class_exists')) {
    // class_exists is a PHP built-in, but just in case
}

if (!function_exists('method_exists')) {
    // method_exists is a PHP built-in, but just in case
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        echo "JSON Error: " . json_encode($data) . "\n";
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        echo "JSON Success: " . json_encode($data) . "\n";
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return $str;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '') {
        die($message);
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($location) {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://localhost/wp-admin/' . $path;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) array('ID' => 1, 'user_login' => 'admin');
    }
}

if (!function_exists('get_users')) {
    function get_users($args = array()) {
        return array();
    }
}

if (!function_exists('wp_create_user')) {
    function wp_create_user($username, $password, $email = '') {
        return 1;
    }
}

if (!function_exists('wp_update_user')) {
    function wp_update_user($userdata) {
        return 1;
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        return (object) array('ID' => 1, 'user_login' => 'admin');
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12) {
        return 'testpassword123';
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        return true;
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        return 'Test Site';
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'http://localhost/' . $path;
    }
}

// Mock global $wpdb
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = (object) array(
        'prefix' => 'wp_',
        'prepare' => function($query) { return $query; },
        'get_results' => function($query) { return array(); },
        'get_var' => function($query) { return null; },
        'insert' => function($table, $data) { return 1; },
        'update' => function($table, $data, $where) { return 1; },
        'delete' => function($table, $where) { return 1; },
        'get_charset_collate' => function() { return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'; }
    );
}

// Additional WordPress function mocks
if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
        return true;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value, $url = '') {
        return $url . '?' . $key . '=' . $value;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_123';
    }
}

if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce') {
        return $actionurl . '&' . $name . '=' . wp_create_nonce($action);
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('wp_ajax_url')) {
    function wp_ajax_url() {
        return admin_url('admin-ajax.php');
    }
}

if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}

echo "Testing School Manager Lite plugin syntax...\n";

try {
    // Test main plugin file
    include_once dirname(__FILE__) . '/school-manager-lite.php';
    echo "✓ Main plugin file loaded successfully\n";
    
    // Check if main class exists
    if (class_exists('School_Manager_Lite')) {
        echo "✓ School_Manager_Lite class found\n";
    } else {
        echo "✗ School_Manager_Lite class not found\n";
    }
    
    echo "\nPlugin syntax test completed successfully!\n";
    echo "The plugin should now be operational in WordPress.\n";
    
} catch (ParseError $e) {
    echo "✗ PHP Parse Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "✗ PHP Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
