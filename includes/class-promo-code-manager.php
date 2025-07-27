<?php
/**
 * Promo Code Manager
 *
 * Handles all operations related to promo codes
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Promo_Code_Manager {
    /**
     * The single instance of the class.
     */
    private static $instance = null;

    /**
     * Main Instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_validate_promo_code', array($this, 'ajax_validate_promo_code'));
        add_action('wp_ajax_nopriv_validate_promo_code', array($this, 'ajax_validate_promo_code'));
    }

    /**
     * Initialize.
     */
    public function init() {
        // No longer registering shortcodes here as they are registered via register_shortcodes()
    }
    
    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        // Register promo code form shortcode
        add_shortcode('school_promo_code_form', array($this, 'promo_code_form_shortcode'));
    }

    /**
     * Get promo codes
     *
     * @param array $args Query arguments
     * @return array Array of promo code objects
     */
    /**
     * Get promo code by user ID
     *
     * @param int $user_id User ID
     * @return object|false Promo code object or false if not found
     */
    public function get_user_promo_code($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_promo_codes';
        $user_meta_table = $wpdb->usermeta;
        
        // First try to find the promo code by user ID in the promo codes table
        $query = $wpdb->prepare(
            "SELECT pc.* FROM {$table_name} pc 
            INNER JOIN {$user_meta_table} um ON um.meta_value = pc.code
            WHERE um.user_id = %d AND um.meta_key = 'school_promo_code'
            LIMIT 1",
            $user_id
        );
        
        $promo_code = $wpdb->get_row($query);
        
        return $promo_code ? $promo_code : false;
    }
    
    /**
     * Get promo codes
     *
     * @param array $args Query arguments
     * @return array Array of promo code objects
     */
    public function get_promo_codes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'class_id' => 0, // Filter by class
            'teacher_id' => 0, // Filter by teacher
            'used' => null, // Filter by usage status (true/false/null)
            'orderby' => 'created_at', 
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $query_args = array();
        
        if (!empty($args['class_id'])) {
            $query .= " AND class_id = %d";
            $query_args[] = $args['class_id'];
        }
        
        if (!empty($args['teacher_id'])) {
            $query .= " AND teacher_id = %d";
            $query_args[] = $args['teacher_id'];
        }
        
        // Filter by usage status
        if ($args['used'] === true) {
            $query .= " AND used_at IS NOT NULL";
        } else if ($args['used'] === false) {
            $query .= " AND used_at IS NULL";
        }
        
        // Order
        $allowed_order_fields = array('code', 'created_at', 'used_at');
        $orderby = in_array($args['orderby'], $allowed_order_fields) ? $args['orderby'] : 'created_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // Limit
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $query_args[] = $args['limit'];
            $query_args[] = $args['offset'];
        }
        
        // Prepare query if needed
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        // Execute query
        $results = $wpdb->get_results($query);
        
        return is_array($results) ? $results : array();
    }

    /**
     * Get promo code by ID
     *
     * @param int $code_id Promo code ID
     * @return object|false Promo code object or false if not found
     */
    public function get_promo_code($code_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        $code = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $code_id
        ));
        
        return $code ? $code : false;
    }

    /**
     * Get promo code by code
     *
     * @param string $code Promo code
     * @return object|false Promo code object or false if not found
     */
    public function get_promo_code_by_code($code) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        $code_obj = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE code = %s",
            $code
        ));
        
        return $code_obj ? $code_obj : false;
    }

    /**
     * Create promo code
     *
     * @param array $data Promo code data
     * @return int|WP_Error Promo code ID or WP_Error on failure
     */
    public function create_promo_code($data) {
        global $wpdb;
        
        $defaults = array(
            'code' => '',
            'prefix' => '',
            'class_id' => 0,
            'teacher_id' => 0,
            'expiry_date' => null, // MySQL date format or null
            'usage_limit' => 1,    // Default to single-use
            'used_count' => 0,     // Start with 0 uses
        );

        $data = wp_parse_args($data, $defaults);

        // Required fields
        if (empty($data['code'])) {
            return new WP_Error('missing_code', __('Promo code is required', 'school-manager-lite'));
        }

        if (empty($data['class_id'])) {
            return new WP_Error('missing_class', __('Class is required', 'school-manager-lite'));
        }
        
        if (empty($data['teacher_id'])) {
            return new WP_Error('missing_teacher', __('Teacher is required', 'school-manager-lite'));
        }

        // Check if class exists
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($data['class_id']);
        
        if (!$class) {
            return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
        }
        
        // Check if teacher exists
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teacher = $teacher_manager->get_teacher($data['teacher_id']);
        
        if (!$teacher) {
            return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
        }

        // Check if code already exists
        $existing = $this->get_promo_code_by_code($data['code']);
        if ($existing) {
            return new WP_Error('code_exists', __('This promo code already exists', 'school-manager-lite'));
        }

        // Insert promo code
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        $insert_data = array(
            'code' => $data['code'],
            'prefix' => $data['prefix'],
            'class_id' => $data['class_id'],
            'teacher_id' => $data['teacher_id'],
            'expiry_date' => $data['expiry_date'],
            'usage_limit' => isset($data['usage_limit']) ? (int)$data['usage_limit'] : 1,
            'used_count' => isset($data['used_count']) ? (int)$data['used_count'] : 0,
            'created_at' => current_time('mysql'),
        );
        
        $insert_format = array('%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s');
        
        $result = $wpdb->insert($table_name, $insert_data, $insert_format);
        
        if (!$result) {
            return new WP_Error('db_error', __('Could not create promo code', 'school-manager-lite'));
        }
        
        $code_id = $wpdb->insert_id;
        
        do_action('school_manager_lite_after_create_promo_code', $code_id, $data);
        
        return $code_id;
    }

    /**
     * Update promo code
     *
     * @param int $code_id Promo code ID
     * @param array $data Promo code data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_promo_code($code_id, $data) {
        global $wpdb;
        
        $code = $this->get_promo_code($code_id);
        
        if (!$code) {
            return new WP_Error('invalid_code', __('Invalid promo code ID', 'school-manager-lite'));
        }
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['code']) && !empty($data['code'])) {
            // Check if code already exists (other than this one)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}school_promo_codes WHERE code = %s AND id != %d",
                $data['code'],
                $code_id
            ));
            
            if ($existing) {
                return new WP_Error('code_exists', __('This promo code already exists', 'school-manager-lite'));
            }
            
            $update_data['code'] = $data['code'];
            $update_format[] = '%s';
        }
        
        if (isset($data['prefix'])) {
            $update_data['prefix'] = $data['prefix'];
            $update_format[] = '%s';
        }
        
        if (isset($data['class_id']) && !empty($data['class_id'])) {
            // Check if class exists
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class = $class_manager->get_class($data['class_id']);
            
            if (!$class) {
                return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
            }
            
            $update_data['class_id'] = $data['class_id'];
            $update_format[] = '%d';
        }
        
        if (isset($data['teacher_id']) && !empty($data['teacher_id'])) {
            // Check if teacher exists
            $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
            $teacher = $teacher_manager->get_teacher($data['teacher_id']);
            
            if (!$teacher) {
                return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
            }
            
            $update_data['teacher_id'] = $data['teacher_id'];
            $update_format[] = '%d';
        }
        
        if (isset($data['expiry_date'])) {
            $update_data['expiry_date'] = $data['expiry_date'];
            $update_format[] = '%s';
        }
        
        if (isset($data['student_id'])) {
            $update_data['student_id'] = $data['student_id'] ?: null;
            $update_format[] = '%d';
        }
        
        if (isset($data['used_at'])) {
            $update_data['used_at'] = $data['used_at'] ?: null;
            $update_format[] = '%s';
        }
        
        // If no data to update
        if (empty($update_data)) {
            return true;
        }
        
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $code_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Could not update promo code', 'school-manager-lite'));
        }
        
        do_action('school_manager_lite_after_update_promo_code', $code_id, $data);
        
        return true;
    }

    /**
     * Delete promo code
     *
     * @param int $code_id Promo code ID
     * @return bool True on success, false on failure
     */
    public function delete_promo_code($code_id) {
        global $wpdb;
        
        $code = $this->get_promo_code($code_id);
        
        if (!$code) {
            return false;
        }
        
        do_action('school_manager_lite_before_delete_promo_code', $code_id, $code);
        
        // Delete promo code
        $table_name = $wpdb->prefix . 'school_promo_codes';
        $result = $wpdb->delete(
            $table_name,
            array('id' => $code_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Generate multiple promo codes
     *
     * @param array $data Generation parameters
     * @return array|WP_Error Array of generated codes or WP_Error on failure
     */
    public function generate_promo_codes($data) {
        global $wpdb;
        
        $defaults = array(
            'quantity' => 1,
            'prefix' => '',
            'class_id' => 0,
            'teacher_id' => 0,
            'expiry_date' => null, // MySQL date format or null for no expiry
            'length' => 8, // Length of the random part of the code
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['class_id'])) {
            return new WP_Error('missing_class', __('Class ID is required', 'school-manager-lite'));
        }
        
        if (empty($data['teacher_id'])) {
            return new WP_Error('missing_teacher', __('Teacher ID is required', 'school-manager-lite'));
        }

        // Validate quantity
        $quantity = intval($data['quantity']);
        if ($quantity <= 0 || $quantity > 1000) {
            return new WP_Error('invalid_quantity', __('Quantity must be between 1 and 1000', 'school-manager-lite'));
        }

        // Check if class exists
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($data['class_id']);
        
        if (!$class) {
            return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
        }

        // Check if teacher exists
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teacher = $teacher_manager->get_teacher($data['teacher_id']);
        
        if (!$teacher) {
            return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
        }

        // Generate and save codes
        $generated_codes = array();
        
        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generate_unique_code($data['prefix'], $data['length']);
            
            $promo_id = $this->create_promo_code(array(
                'code' => $code,
                'prefix' => $data['prefix'],
                'class_id' => $data['class_id'],
                'teacher_id' => $data['teacher_id'],
                'expiry_date' => $this->normalize_expiry_date($data['expiry_date']),
            ));
            
            if (!is_wp_error($promo_id)) {
                $generated_codes[] = $code;
            }
        }

        if (empty($generated_codes)) {
            return new WP_Error('generation_failed', __('Failed to generate promo codes', 'school-manager-lite'));
        }

        return $generated_codes;
    }

    /**
     * Generate a unique promo code
     *
     * @param string $prefix Optional prefix
     * @param int $length Code length (excluding prefix)
     * @return string Unique code
     */
        /**
     * Normalize expiry date: if provided date is before today, move to next year same day
     *
     * @param string|null $date YYYY-MM-DD or null
     * @return string|null Normalized date or null
     */
    private function normalize_expiry_date($date) {
        if (empty($date)) {
            return null;
        }
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }
        if ($timestamp < time()) {
            // add one year
            return date('Y-m-d', strtotime('+1 year', $timestamp));
        }
        return date('Y-m-d', $timestamp);
    }

    private function generate_unique_code($prefix = '', $length = 8) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'school_promo_codes';
        
        // Characters to use in code (excluding ambiguous characters like O, 0, 1, I)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max_attempts = 10;
        $attempts = 0;
        
        do {
            // Generate code
            $code = $prefix;
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[rand(0, strlen($chars) - 1)];
            }
            
            // Check if code exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE code = %s",
                $code
            ));
            
            $attempts++;
        } while ($exists && $attempts < $max_attempts);
        
        if ($attempts >= $max_attempts) {
            // If we couldn't generate a unique code, add timestamp to make it unique
            $code = $prefix . substr(md5(microtime()), 0, $length);
        }
        
        return $code;
    }

    /**
     * Validate and use promo code
     *
     * @param string $code Promo code
     * @param int|null $student_id Optional student ID (if already exists)
     * @param array $student_data Optional student data (for creating new student)
     * @return array|WP_Error Success data or WP_Error on failure
     */
    public function redeem_promo_code($code, $student_id = null, $student_data = array()) {
        global $wpdb;
        
        // Get promo code
        $promo = $this->get_promo_code_by_code($code);
        
        if (!$promo) {
            return new WP_Error('invalid_code', __('קוד לא תקין', 'school-manager-lite'));
        }
        
        // Check if code has reached its usage limit
        if ($promo->used_count >= $promo->usage_limit) {
            return new WP_Error('code_limit_reached', __('קוד זה הגיע למגבלת השימוש', 'school-manager-lite'));
        }
        
        // Check if code is already used (for single-use codes)
        if ($promo->usage_limit == 1 && $promo->used_count > 0) {
            return new WP_Error('code_already_used', __('קוד זה כבר נוצל', 'school-manager-lite'));
        }
        
        // Check if student_id is already linked to a promo code
        if (!empty($student_data['username'])) {
            $existing_user = get_user_by('login', $student_data['username']);
            if ($existing_user) {
                // Check if this user already has a promo code
                $existing_promo = $this->get_user_promo_code($existing_user->ID);
                if ($existing_promo) {
                    return new WP_Error('user_has_promo', __('לתלמיד זה כבר יש קוד קופון', 'school-manager-lite'));
                }
            }
        }
        
        // Check if code is expired
        if (!empty($promo->expiry_date) && strtotime($promo->expiry_date) < time()) {
            return new WP_Error('code_expired', __('קוד זה פג תוקף', 'school-manager-lite'));
        }
        
        // Use the code - link it to the student if provided
        $update_data = array(
            'used_count' => $promo->used_count + 1,
            'used_at' => current_time('mysql')
        );
        
        // If this was the last use, mark it as used
        if (($promo->used_count + 1) >= $promo->usage_limit) {
            $update_data['used_at'] = current_time('mysql');
        }
        
        // If student ID is provided, link it directly
        if (!empty($student_id)) {
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $student = $student_manager->get_student($student_id);
            
            if (!$student) {
                return new WP_Error('invalid_student', __('מזהה תלמיד לא תקין', 'school-manager-lite'));
            }
            
            $update_data['student_id'] = $student_id;
        }
        // If student data is provided, create a new student
        else if (!empty($student_data)) {
            // Make sure required fields are provided
            if (empty($student_data['student_name']) || empty($student_data['username']) || empty($student_data['password'])) {
                return new WP_Error('missing_student_data', __('שם תלמיד, מספר טלפון ותעודת זהות דרושים', 'school-manager-lite'));
            }
            
            // Check if student ID (password) already exists to prevent duplicates
            // First check WordPress users meta
            $existing_user = get_users([
                'meta_key' => '_school_student_id',
                'meta_value' => $student_data['password'],
                'number' => 1,
                'count_total' => false
            ]);
            
            if (!empty($existing_user)) {
                return new WP_Error('duplicate_student_id', __('תלמיד עם תעודת זהות זו כבר קיים במערכת', 'school-manager-lite'));
            }
            
            // Additional duplicate check in custom student table is skipped because the table does not store a separate student_id column.
            // Primary uniqueness is enforced via WP user meta above.

            
            // Phone number check can be skipped - student ID is the primary unique identifier
            
            // Add class ID from promo code
            $student_data['class_id'] = $promo->class_id;
            
            // Create basic student data with phone as username and ID as password
            $new_student_data = array(
                'name' => $student_data['student_name'],
                'user_login' => $student_data['username'], // Phone number as username
                'user_pass' => $student_data['password'],  // ID as password
                'role' => 'student_private',
                'class_id' => $promo->class_id,
                'create_user' => true,  // Ensure WordPress user is created
                'status' => 'active'     // Ensure student is created as active
            );
            
            // Log attempt to create student
            error_log('School Manager Lite: Attempting to create student with username: ' . $student_data['username']);
            
            // Create student
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $student_id = $student_manager->create_student($new_student_data);
            
            if (is_wp_error($student_id)) {
                error_log('School Manager Lite: Error creating student: ' . $student_id->get_error_message());
                return $student_id; // Return the error
            }
            
            error_log('School Manager Lite: Student created with ID: ' . $student_id);
            $update_data['student_id'] = $student_id;
        }
        
        // Update promo code to mark it as used and link to the student
        $result = $this->update_promo_code($promo->id, $update_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // For single-use codes, explicitly mark it as used and ensure student is linked
        if ($promo->usage_limit == 1) {
            // Double-check to ensure the promo code is properly linked to this student
            // This ensures the student shows up in the promo code table
            global $wpdb;
            $table_name = $wpdb->prefix . 'school_promo_codes';
            $wpdb->update(
                $table_name,
                array(
                    'student_id' => $student_id,
                    'used_count' => 1,
                    'used_at' => current_time('mysql')
                ),
                array('id' => $promo->id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            // Also add promo code reference to student's user meta
            if ($student_id) {
                update_user_meta($student_id, 'school_promo_code', $promo->code);
            }
        }
        
        // Get the class details
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($promo->class_id);
        
        // Ensure we have the student manager instance
        $student_manager = School_Manager_Lite_Student_Manager::instance();

        // Auto-login and capture WP user ID
        $student_row = $student_manager->get_student( $student_id );
        $wp_user_id  = null;
        if ( $student_row && ! empty( $student_row->wp_user_id ) ) {
            $wp_user_id = (int) $student_row->wp_user_id;
            // log user in
            wp_set_current_user( $wp_user_id );
            wp_set_auth_cookie( $wp_user_id );
        }

        // Update the student's status meta to active (for both new & existing users)
        if ( $wp_user_id ) {
            update_user_meta( $wp_user_id, 'school_student_status', 'active' );
        }

        // Determine LearnDash course ID - prioritize the course_id from shortcode attributes
        $ld_course_id = 898; // Default fallback
        
        // Use course_id from shortcode attributes (highest priority)
        if ( !empty($student_data['course_id']) && intval($student_data['course_id']) > 0 ) {
            $ld_course_id = intval($student_data['course_id']);
            error_log('School Manager Lite: Using course_id from shortcode: ' . $ld_course_id);
        }
        // Otherwise use class course_id if available
        else if ( $class && isset( $class->course_id ) && $class->course_id ) {
            $ld_course_id = (int) $class->course_id;
            error_log('School Manager Lite: Using course_id from class: ' . $ld_course_id);
        } else {
            error_log('School Manager Lite: Using default course_id: ' . $ld_course_id);
        }

        // Enroll in LearnDash – must use the WP user ID
        if ( function_exists( 'ld_update_course_access' ) && $wp_user_id && $ld_course_id ) {
            // ULTRA-COMPREHENSIVE COURSE ACCESS CONTROL
            // This ensures ONLY the specified course is accessible and blocks ALL others
            
            error_log('School Manager Lite: Starting ULTRA-comprehensive course access control for user ' . $wp_user_id . ' - TARGET COURSE: ' . $ld_course_id);
            
            // Step 1: Get all courses in the system
            $all_courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'private', 'draft'),
                'fields' => 'ids'
            ));
            
            error_log('School Manager Lite: Found ' . count($all_courses) . ' total courses to process.');
            
            // Step 2: NUCLEAR OPTION - Remove ALL course access using EVERY method
            foreach ($all_courses as $course_id) {
                // Method 1: Use LearnDash's access control (remove access)
                ld_update_course_access( $wp_user_id, $course_id, /* remove */ true );
                
                // Method 2: Remove from course's user access list in post meta
                $course_access_list = get_post_meta($course_id, 'course_access_list', true);
                if (is_array($course_access_list)) {
                    $course_access_list = array_diff($course_access_list, array($wp_user_id, (string)$wp_user_id));
                    update_post_meta($course_id, 'course_access_list', $course_access_list);
                } else {
                    // Ensure it's an empty array, not containing the user
                    update_post_meta($course_id, 'course_access_list', array());
                }
                
                // Method 3: Remove ALL course-specific user meta
                delete_user_meta($wp_user_id, 'course_' . $course_id . '_access_from');
                delete_user_meta($wp_user_id, 'course_' . $course_id . '_access_until');
                delete_user_meta($wp_user_id, 'learndash_course_expired_' . $course_id);
                delete_user_meta($wp_user_id, 'course_completed_' . $course_id);
                delete_user_meta($wp_user_id, 'learndash_course_' . $course_id . '_access_from');
                delete_user_meta($wp_user_id, 'learndash_course_' . $course_id . '_access_until');
                delete_user_meta($wp_user_id, '_sfwd-course_' . $course_id . '_access_from');
                delete_user_meta($wp_user_id, '_sfwd-course_' . $course_id . '_access_until');
                
                // Method 4: Remove from any group course access
                delete_user_meta($wp_user_id, 'learndash_group_enrolled_' . $course_id);
                delete_user_meta($wp_user_id, 'learndash_group_users_' . $course_id);
            }
            
            // Step 3: NUCLEAR CLEANUP - Clear ALL LearnDash data
            delete_user_meta($wp_user_id, '_sfwd-course_progress');
            delete_user_meta($wp_user_id, 'learndash_course_info');
            delete_user_meta($wp_user_id, '_learndash_course_enrollment');
            delete_user_meta($wp_user_id, 'learndash_group_users');
            delete_user_meta($wp_user_id, 'learndash_user_groups');
            delete_user_meta($wp_user_id, 'course_access_list');
            
            // ENHANCED: Remove ALL group access using database query
            global $wpdb;
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, 'learndash_group_users_%'
            ));
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, 'learndash_group_enrolled_%'
            ));
            
            error_log('School Manager Lite: ENHANCED - Removed ALL group access via database query for user ' . $wp_user_id);
            
            // ENHANCED: Remove ALL course access metadata using database queries
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, 'course_%_access_%'
            ));
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, 'learndash_course_%'
            ));
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, '_sfwd-course_%'
            ));
            
            error_log('School Manager Lite: ENHANCED - Removed ALL course access metadata via database query for user ' . $wp_user_id);
            
            // ENHANCED: Clear LearnDash user cache to prevent cached access data
            if (function_exists('learndash_user_clear_data')) {
                learndash_user_clear_data($wp_user_id);
                error_log('School Manager Lite: ENHANCED - Cleared LearnDash user cache for user ' . $wp_user_id);
            }
            
            // ENHANCED: Verify all group access was removed
            $remaining_group_meta = $wpdb->get_results( $wpdb->prepare(
                "SELECT meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
                $wp_user_id, 'learndash_group_users_%'
            ));
            
            if (!empty($remaining_group_meta)) {
                error_log('School Manager Lite: WARNING - Some group access meta still exists: ' . print_r($remaining_group_meta, true));
            } else {
                error_log('School Manager Lite: SUCCESS - ALL group access metadata confirmed removed for user ' . $wp_user_id);
            }
            
            // Step 4: Force ALL courses to 'closed' access (prevent open enrollment)
            foreach ($all_courses as $course_id) {
                $price_type = get_post_meta($course_id, '_ld_price_type', true);
                if (empty($price_type) || $price_type === 'open' || $price_type === 'free') {
                    update_post_meta($course_id, '_ld_price_type', 'closed');
                    error_log('School Manager Lite: FORCED course ' . $course_id . ' to CLOSED access (was: ' . $price_type . ')');
                }
                
                // Also ensure course access mode is set correctly
                update_post_meta($course_id, '_ld_course_access_list', 'closed');
            }
            
            // Step 5: GRANT access ONLY to the TARGET course
            error_log('School Manager Lite: Granting access to course ' . $ld_course_id . ' for user ' . $wp_user_id);
            
            // First, ensure the user is enrolled in the course
            ld_update_course_access($wp_user_id, $ld_course_id, false);
            
            // Step 6: EXPLICITLY add user to the TARGET course's access list
            $target_course_access = get_post_meta($ld_course_id, 'course_access_list', true);
            if (!is_array($target_course_access)) {
                $target_course_access = array();
            }
            
            // Add both integer and string versions to be safe
            $user_ids_to_add = array(
                $wp_user_id,
                (string)$wp_user_id,
                strval($wp_user_id)
            );
            
            $access_updated = false;
            foreach ($user_ids_to_add as $uid) {
                if (!in_array($uid, $target_course_access, true)) {
                    $target_course_access[] = $uid;
                    $access_updated = true;
                }
            }
            
            if ($access_updated) {
                update_post_meta($ld_course_id, 'course_access_list', array_unique($target_course_access));
                error_log('School Manager Lite: Updated course access list for course ' . $ld_course_id);
            }
            
            // Step 7: Set explicit course access meta for target course
            $current_time = time();
            update_user_meta($wp_user_id, 'course_' . $ld_course_id . '_access_from', $current_time);
            update_user_meta($wp_user_id, 'learndash_course_' . $ld_course_id . '_access_from', $current_time);
            update_user_meta($wp_user_id, '_sfwd-course_' . $ld_course_id . '_access_from', $current_time);
            
            // Ensure course is in the user's course progress
            $course_progress = get_user_meta($wp_user_id, '_sfwd-course_progress', true);
            if (empty($course_progress) || !is_array($course_progress)) {
                $course_progress = array();
            }
            if (!isset($course_progress[$ld_course_id])) {
                $course_progress[$ld_course_id] = array(
                    'completed' => 0,
                    'total' => 0,
                    'lessons' => array(),
                    'topics' => array()
                );
                update_user_meta($wp_user_id, '_sfwd-course_progress', $course_progress);
                error_log('School Manager Lite: Initialized course progress for course ' . $ld_course_id);
            }
            
            // Step 8: AGGRESSIVE VERIFICATION - Double-check and remove any remaining enrollments
            $this->verify_and_enforce_single_course_access($wp_user_id, $ld_course_id);
            
            error_log('School Manager Lite: SUCCESSFULLY granted access ONLY to course ' . $ld_course_id . ' for user ' . $wp_user_id . ' - ALL OTHER COURSES BLOCKED');
            
            // ENHANCED: Final verification - check that ONLY target course access exists
            $final_course_meta = $wpdb->get_results( $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND (meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s)",
                $wp_user_id, 'course_%_access_%', 'learndash_course_%', '_sfwd-course_%'
            ));
            
            $target_course_found = false;
            $other_courses_found = array();
            
            foreach ($final_course_meta as $meta) {
                if (strpos($meta->meta_key, '_' . $ld_course_id . '_') !== false || 
                    strpos($meta->meta_key, '_' . $ld_course_id) !== false) {
                    $target_course_found = true;
                } else {
                    $other_courses_found[] = $meta->meta_key;
                }
            }
            
            if ($target_course_found && empty($other_courses_found)) {
                error_log('School Manager Lite: VERIFICATION SUCCESS - User ' . $wp_user_id . ' has access ONLY to target course ' . $ld_course_id);
            } else {
                error_log('School Manager Lite: VERIFICATION WARNING - Target found: ' . ($target_course_found ? 'YES' : 'NO') . ', Other courses: ' . print_r($other_courses_found, true));
            }
            
            // Set course access expiration to June 30th of next year
            $current_year = date('Y');
            $current_date = date('Y-m-d');
            
            // If we're past June 30th this year, set expiration to next year
            if ($current_date > $current_year . '-06-30') {
                $expiration_year = $current_year + 1;
            } else {
                $expiration_year = $current_year;
            }
            
            $expiration_date = $expiration_year . '-06-30 23:59:59';
            
            // Set course access expiration using LearnDash meta
            if (function_exists('learndash_user_course_access_from_update')) {
                // Use LearnDash's built-in expiration system
                $course_access_list = get_user_meta($wp_user_id, '_sfwd-course_progress', true);
                if (!is_array($course_access_list)) {
                    $course_access_list = array();
                }
                
                if (!isset($course_access_list[$ld_course_id])) {
                    $course_access_list[$ld_course_id] = array();
                }
                
                $course_access_list[$ld_course_id]['access_from'] = time();
                $course_access_list[$ld_course_id]['access_until'] = strtotime($expiration_date);
                
                update_user_meta($wp_user_id, '_sfwd-course_progress', $course_access_list);
            }
            
            // Also set a custom meta for our own tracking
            update_user_meta($wp_user_id, 'school_course_expiration_' . $ld_course_id, $expiration_date);
            
            $promo_access_data = array(
                'course_id' => $ld_course_id,
                'expires' => $expiration_date,
                'granted_at' => current_time('mysql')
            );
            
            update_user_meta($wp_user_id, 'school_promo_course_access', $promo_access_data);
            
            // VERIFY the meta was set correctly
            $verify_meta = get_user_meta($wp_user_id, 'school_promo_course_access', true);
            error_log('School Manager Lite: VERIFICATION - Promo access meta set for user ' . $wp_user_id . ': ' . print_r($verify_meta, true));
            
            // Also set the user role to student_private if not already set
            $user = get_user_by('ID', $wp_user_id);
            if ($user && !in_array('student_private', $user->roles)) {
                $user->set_role('student_private');
                error_log('School Manager Lite: Set user ' . $wp_user_id . ' role to student_private');
            } else {
                error_log('School Manager Lite: User ' . $wp_user_id . ' already has correct role or user not found');
            }
            
            error_log('School Manager Lite: Enrolled user ' . $wp_user_id . ' in course ' . $ld_course_id . ' with expiration ' . $expiration_date);
            
            // ENHANCED: Connect student to teacher and group from promo code
            if ($promo->teacher_id && $wp_user_id) {
                // Set teacher assignment for this student
                update_user_meta($wp_user_id, 'school_assigned_teacher', $promo->teacher_id);
                
                // Also create reverse mapping - teacher to students
                $teacher_students = get_user_meta($promo->teacher_id, 'school_teacher_students', true);
                if (!is_array($teacher_students)) {
                    $teacher_students = array();
                }
                if (!in_array($wp_user_id, $teacher_students)) {
                    $teacher_students[] = $wp_user_id;
                    update_user_meta($promo->teacher_id, 'school_teacher_students', $teacher_students);
                }
                
                // CRITICAL: Insert into school_teacher_students table for shortcodes to work
                $teacher_student_table = $wpdb->prefix . 'school_teacher_students';
                
                // Check if relationship already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$teacher_student_table} WHERE teacher_id = %d AND student_id = %d",
                    $promo->teacher_id, $student_id
                ));
                
                if (!$existing) {
                    $wpdb->insert(
                        $teacher_student_table,
                        array(
                            'teacher_id' => $promo->teacher_id,
                            'student_id' => $student_id,
                            'class_id' => $promo->class_id,
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%d', '%d', '%s')
                    );
                    
                    error_log('School Manager Lite: Inserted teacher-student relationship into database table - Teacher: ' . $promo->teacher_id . ', Student: ' . $student_id);
                }
                
                error_log('School Manager Lite: Connected student ' . $wp_user_id . ' to teacher ' . $promo->teacher_id);
            }
            
            // ENHANCED: Connect student to class/group if class has a group
            if ($class && $class->group_id && $wp_user_id) {
                // Add student to LearnDash group
                if (function_exists('ld_update_group_access')) {
                    ld_update_group_access($wp_user_id, $class->group_id, false); // false = add access
                    error_log('School Manager Lite: Added student ' . $wp_user_id . ' to LearnDash group ' . $class->group_id);
                }
                
                // Set group meta for student
                update_user_meta($wp_user_id, 'school_assigned_group', $class->group_id);
                
                // Set group users meta (LearnDash format)
                update_user_meta($wp_user_id, 'learndash_group_users_' . $class->group_id, $class->group_id);
                
                error_log('School Manager Lite: Connected student ' . $wp_user_id . ' to group ' . $class->group_id);
            }
        }
        
        do_action('school_manager_lite_after_redeem_promo_code', $promo->id, $student_id, $class);
        
        return array(
            'success' => true,
            'promo_code' => $promo,
            'student_id' => $student_id,
            'wp_user_id' => $wp_user_id,
            'class' => $class
        );
    }
    
    /**
     * AJAX handler for validating promo code
     */
    public function ajax_validate_promo_code() {
        // Check nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'validate_promo_code')) {
            wp_send_json_error(array('message' => __('Security check failed', 'school-manager-lite')));
        }
        
        // Get the code
        $code = isset($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : '';
        
        if (empty($code)) {
            wp_send_json_error(array('message' => __('Please enter a promo code', 'school-manager-lite')));
        }
        
        // Get the promo code
        $promo = $this->get_promo_code_by_code($code);
        
        if (!$promo) {
            wp_send_json_error(array('message' => __('Invalid promo code', 'school-manager-lite')));
        }
        
        // Check if code has reached its usage limit
        if ($promo->used_count >= $promo->usage_limit) {
            wp_send_json_error(array('message' => __('This promo code has reached its usage limit', 'school-manager-lite')));
        }
        
        // Check if code is expired
        if (!empty($promo->expiry_date) && strtotime($promo->expiry_date) < time()) {
            wp_send_json_error(array('message' => __('This promo code has expired', 'school-manager-lite')));
        }
        
        // Get the class details
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($promo->class_id);
        
        wp_send_json_success(array(
            'message' => __('Valid promo code', 'school-manager-lite'),
            'class_name' => $class->name,
            'expiry_date' => $promo->expiry_date
        ));
    }
    
    /**
     * Promo code form shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function promo_code_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '', // URL to redirect after successful registration
            'title' => __('Register with Promo Code', 'school-manager-lite'),
            'description' => __('Enter your promo code to register for the class.', 'school-manager-lite'),
        ), $atts, 'school_promo_code_form');
        
        // Enqueue scripts and styles
        wp_enqueue_script('jquery');
        
        // Start output buffering
        ob_start();
        
        // Get template
        include plugin_dir_path(dirname(__FILE__)) . 'templates/promo-code-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Aggressively verify and enforce single course access
     * This method runs after course enrollment to ensure no other courses are accessible
     */
    private function verify_and_enforce_single_course_access($user_id, $target_course_id) {
        error_log('School Manager Lite: AGGRESSIVE VERIFICATION - Starting single course enforcement for user ' . $user_id . ', target course: ' . $target_course_id);
        
        // Get all courses the user is currently enrolled in
        $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
        
        error_log('School Manager Lite: VERIFICATION - User currently enrolled in courses: ' . implode(', ', $enrolled_courses));
        
        // Remove enrollment from any course that's not the target
        $removed_courses = array();
        foreach ($enrolled_courses as $course_id) {
            if ($course_id != $target_course_id) {
                // Aggressively remove access
                ld_update_course_access($user_id, $course_id, true); // Remove access
                
                // Remove from course access list
                $course_access_list = get_post_meta($course_id, 'course_access_list', true);
                if (is_array($course_access_list)) {
                    $course_access_list = array_diff($course_access_list, array($user_id, (string)$user_id));
                    update_post_meta($course_id, 'course_access_list', $course_access_list);
                }
                
                // Remove all user meta for this course
                delete_user_meta($user_id, 'course_' . $course_id . '_access_from');
                delete_user_meta($user_id, 'learndash_course_' . $course_id . '_access_from');
                delete_user_meta($user_id, '_sfwd-course_' . $course_id . '_access_from');
                
                $removed_courses[] = $course_id;
                error_log('School Manager Lite: VERIFICATION - REMOVED access to unwanted course: ' . $course_id);
            }
        }
        
        // Clear LearnDash cache to ensure changes take effect immediately
        if (function_exists('learndash_user_clear_data')) {
            learndash_user_clear_data($user_id);
        }
        
        // Double-check enrollment after cleanup
        $final_enrolled_courses = learndash_user_get_enrolled_courses($user_id);
        error_log('School Manager Lite: VERIFICATION - Final enrolled courses after cleanup: ' . implode(', ', $final_enrolled_courses));
        
        // If still enrolled in unwanted courses, use nuclear option
        $unwanted_courses = array_diff($final_enrolled_courses, array($target_course_id));
        if (!empty($unwanted_courses)) {
            error_log('School Manager Lite: VERIFICATION - NUCLEAR OPTION needed for courses: ' . implode(', ', $unwanted_courses));
            
            global $wpdb;
            
            // Remove from LearnDash user activity table
            foreach ($unwanted_courses as $course_id) {
                $wpdb->delete(
                    $wpdb->prefix . 'learndash_user_activity',
                    array(
                        'user_id' => $user_id,
                        'post_id' => $course_id,
                        'activity_type' => 'course'
                    )
                );
                
                error_log('School Manager Lite: VERIFICATION - NUCLEAR - Removed activity records for course: ' . $course_id);
            }
            
            // Clear cache again
            if (function_exists('learndash_user_clear_data')) {
                learndash_user_clear_data($user_id);
            }
        }
        
        // Final verification
        $absolutely_final_courses = learndash_user_get_enrolled_courses($user_id);
        if (count($absolutely_final_courses) === 1 && in_array($target_course_id, $absolutely_final_courses)) {
            error_log('School Manager Lite: VERIFICATION - SUCCESS! User ' . $user_id . ' now has access ONLY to target course ' . $target_course_id);
        } else {
            error_log('School Manager Lite: VERIFICATION - WARNING! User ' . $user_id . ' still has unexpected course access: ' . implode(', ', $absolutely_final_courses));
        }
        
        return $removed_courses;
    }
}
