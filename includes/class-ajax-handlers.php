<?php
/**
 * AJAX Handlers for School Manager Lite
 */
class School_Manager_Lite_AJAX_Handlers {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register AJAX actions for both logged-in and non-logged-in users
        add_action('wp_ajax_school_manager_register_user', array($this, 'register_user'));
        add_action('wp_ajax_nopriv_school_manager_register_user', array($this, 'register_user'));
        
        add_action('wp_ajax_school_manager_redeem_promo', array($this, 'redeem_promo_code'));
        add_action('wp_ajax_nopriv_school_manager_redeem_promo', array($this, 'redeem_promo_code'));
    }
    
    /**
     * Handle user registration via AJAX
     */
    public function register_user() {
        check_ajax_referer('school-manager-lite-registration', 'nonce');
        
        // Parse form data
        parse_str($_POST['form_data'], $form_data);
        
        // Validate nonce
        if (!isset($form_data['school_manager_nonce']) || 
            !wp_verify_nonce($form_data['school_manager_nonce'], 'school_manager_register')) {
            wp_send_json_error(array('message' => __('Security check failed. Please try again.', 'school-manager-lite')));
            return;
        }
        
        // Validate required fields
        $required_fields = array(
            'first_name' => __('First Name', 'school-manager-lite'),
            'last_name' => __('Last Name', 'school-manager-lite'),
            'email' => __('Email Address', 'school-manager-lite'),
            'password' => __('Password', 'school-manager-lite')
        );
        
        $errors = array();
        foreach ($required_fields as $field => $label) {
            if (empty($form_data[$field])) {
                $errors[] = sprintf(__('%s is required.', 'school-manager-lite'), $label);
            }
        }
        
        // Validate email
        if (!empty($form_data['email']) && !is_email($form_data['email'])) {
            $errors[] = __('Please enter a valid email address.', 'school-manager-lite');
        }
        
        // Check if email already exists
        if (email_exists($form_data['email'])) {
            $errors[] = __('An account with this email already exists. Please log in.', 'school-manager-lite');
        }
        
        // Return errors if any
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode('<br>', $errors)));
            return;
        }
        
        // Create new user
        $user_data = array(
            'user_login' => $form_data['email'],
            'user_email' => $form_data['email'],
            'user_pass' => $form_data['password'],
            'first_name' => $form_data['first_name'],
            'last_name' => $form_data['last_name'],
            'role' => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }
        
        // Handle promo code if provided
        if (!empty($form_data['promo_code'])) {
            $this->process_promo_code($user_id, $form_data['promo_code']);
        }
        
        // Log the user in
        wp_set_auth_cookie($user_id, true);
        
        // Redirect URL after registration
        $redirect_url = home_url('/dashboard/');
        
        wp_send_json_success(array(
            'message' => __('Registration successful! Redirecting...', 'school-manager-lite'),
            'redirect' => $redirect_url
        ));
    }
    
    /**
     * Handle promo code redemption
     */
    public function redeem_promo_code() {
        check_ajax_referer('school-manager-lite-registration', 'nonce');
        
        if (!isset($_POST['promo_code']) || empty($_POST['promo_code'])) {
            wp_send_json_error(array('message' => __('Please enter a promo code.', 'school-manager-lite')));
            return;
        }
        
        $promo_code = sanitize_text_field($_POST['promo_code']);
        $user_id = get_current_user_id();
        
        // If user is not logged in, store the promo code in a cookie
        if (!$user_id) {
            setcookie('school_manager_pending_promo', $promo_code, time() + 3600, '/');
            wp_send_json_success(array(
                'message' => __('Please log in or register to redeem this promo code.', 'school-manager-lite'),
                'redirect' => wp_login_url(add_query_arg('redirect_to', urlencode(home_url('/redeem/')), home_url('/wp-login.php')))
            ));
            return;
        }
        
        // Process promo code for logged-in user
        $result = $this->process_promo_code($user_id, $promo_code);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Promo code applied successfully!', 'school-manager-lite'),
            'redirect' => home_url('/dashboard/')
        ));
    }
    
    /**
     * Process promo code for a user
     */
    private function process_promo_code($user_id, $promo_code) {
        // TODO: Implement promo code validation and processing logic
        // This is a placeholder - you'll need to implement the actual promo code logic
        
        // For now, just log the attempt
        error_log(sprintf(
            'Promo code "%s" used by user ID %d',
            $promo_code,
            $user_id
        ));
        
        return true;
    }
}

// Initialize AJAX handlers
function school_manager_lite_init_ajax_handlers() {
    School_Manager_Lite_AJAX_Handlers::instance();
}
add_action('init', 'school_manager_lite_init_ajax_handlers');
