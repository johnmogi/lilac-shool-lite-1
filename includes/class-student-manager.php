<?php
/**
 * Student Manager
 *
 * Handles all operations related to students
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Student_Manager {
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
    }

    /**
     * Initialize.
     */
    public function init() {
        // Nothing to initialize yet
    }
    
    /**
     * Delete a student
     * 
     * @param int $student_id The student ID to delete
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_student($student_id) {
        global $wpdb;
        
        // Get the student data before deletion
        $student = $this->get_student($student_id);
        
        if (!$student) {
            return new WP_Error('invalid_student', __('Invalid student ID', 'school-manager-lite'));
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete from the students table
            $table_name = $wpdb->prefix . 'school_students';
            $result = $wpdb->delete(
                $table_name,
                array('id' => $student_id),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to delete student from the database.', 'school-manager-lite'));
            }
            
            // If there's a WordPress user associated, delete that as well
            if (!empty($student->wp_user_id)) {
                // First, remove any LearnDash course access
                if (function_exists('ld_update_course_access')) {
                    $student_classes = $this->get_student_classes($student->wp_user_id);
                    foreach ($student_classes as $class) {
                        $ld_course_id = 898; // default course ID fallback
                        if (isset($class->course_id) && $class->course_id) {
                            $ld_course_id = $class->course_id;
                        }
                        ld_update_course_access($student->wp_user_id, $ld_course_id, $remove=true);
                    }
                }
                
                // Then delete the user
                if (!wp_delete_user($student->wp_user_id)) {
                    throw new Exception(__('Failed to delete associated WordPress user.', 'school-manager-lite'));
                }
            }
            
            // Delete student's class relationships
            $student_class_table = $wpdb->prefix . 'school_student_class';
            $wpdb->delete(
                $student_class_table,
                array('student_id' => $student_id),
                array('%d')
            );
            
            // Delete student's teacher relationships
            $teacher_student_table = $wpdb->prefix . 'school_teacher_students';
            $wpdb->delete(
                $teacher_student_table,
                array('student_id' => $student_id),
                array('%d')
            );
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            return new WP_Error('delete_failed', $e->getMessage());
        }
    }

    /**
     * Get students from custom table
     */
    public function get_students($args = array()) {
        global $wpdb;
        
        $this->ensure_student_role_exists();
        
        $defaults = array(
            'class_id' => 0,
            'orderby' => 'name', 
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
            'search' => '',
            'count_total' => false,
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'school_students';
        
        // Base query
        if ($args['count_total']) {
            $select = "SELECT COUNT(*)";
        } else {
            $select = "SELECT *";
        }
        
        $query = "{$select} FROM {$table_name} WHERE 1=1";
        $query_args = array();
        
        // Class filter
        if (!empty($args['class_id'])) {
            $query .= " AND class_id = %d";
            $query_args[] = $args['class_id'];
        }
        
        // Search
        if (!empty($args['search'])) {
            $query .= " AND (name LIKE %s OR email LIKE %s)";
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $like;
            $query_args[] = $like;
        }
        
        // For count query, we're done here
        if ($args['count_total']) {
            if (!empty($query_args)) {
                $query = $wpdb->prepare($query, $query_args);
            }
            return $wpdb->get_var($query);
        }
        
        // Ordering
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if ($orderby) {
            $query .= " ORDER BY {$orderby}";
        }
        
        // Limit
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }
        
        // Prepare and execute the query
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get student by ID
     */
    public function get_student($student_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_students';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $student_id
        ));
    }
    
    /**
     * Update student
     */
    public function update_student($student_id, $data) {
        global $wpdb;
        
        $student = $this->get_student($student_id);
        
        if (!$student) {
            return new WP_Error('invalid_student', __('Invalid student ID', 'school-manager-lite'));
        }
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['name']) && !empty($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $update_format[] = '%s';
        }
        
        if (isset($data['email'])) {
            if (!is_email($data['email'])) {
                return new WP_Error('invalid_email', __('Invalid email address', 'school-manager-lite'));
            }
            $update_data['email'] = sanitize_email($data['email']);
            $update_format[] = '%s';
        }
        
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
            $update_format[] = '%s';
        }
        
        if (isset($data['class_id']) && !empty($data['class_id'])) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class = $class_manager->get_class($data['class_id']);
            
            if (!$class) {
                return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
            }
            
            if ($student->class_id != $data['class_id'] && !empty($student->wp_user_id)) {
                $table_name = $wpdb->prefix . 'school_students';
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE wp_user_id = %d AND class_id = %d AND id != %d",
                    $student->wp_user_id,
                    $data['class_id'],
                    $student_id
                ));
                
                if ($existing) {
                    return new WP_Error('student_exists', __('This user is already a student in the target class', 'school-manager-lite'));
                }
            }
            
            $update_data['class_id'] = intval($data['class_id']);
            $update_format[] = '%d';
        }
        
        if (empty($update_data)) {
            return true; // No changes to update
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $table_name = $wpdb->prefix . 'school_students';
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $student_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Could not update student', 'school-manager-lite'));
        }
        
        // Update WordPress user if needed
        if (!empty($student->wp_user_id) && (!empty($update_data['email']) || !empty($update_data['name']))) {
            $user_data = array('ID' => $student->wp_user_id);
            
            if (!empty($update_data['email'])) {
                $user_data['user_email'] = $update_data['email'];
            }
            
            if (!empty($update_data['name'])) {
                $user_data['display_name'] = $update_data['name'];
                $user_data['first_name'] = $update_data['name'];
            }
            
            wp_update_user($user_data);
        }
        
        do_action('school_manager_lite_after_update_student', $student_id, $data);
        
        return true;
    }
    
    /**
     * Create a student and optionally the associated WP user.
     * This is a simplified version restored to satisfy promo-code redemption.
     *
     * @param array $data
     *        Required keys:
     *          - name           : Student full name
     *          - class_id       : Class ID to enroll to
     *          - user_pass      : Student ID (password)
     *          - user_login     : Username (phone number)
     *        Optional keys:
     *          - create_user    : bool, default true – create WP user too
     *          - role           : WP role for the user (defaults student_private)
     *          - email          : email address (optional)
     *          - status         : active / inactive, defaults active
     * @return int|WP_Error Student row ID on success or WP_Error on failure
     */
    public function create_student( $data ) {
        global $wpdb;

        // Defaults
        $defaults = array(
            'name'        => '',
            'class_id'    => 0,
            'user_pass'   => '',
            'user_login'  => '',
            'email'       => '',
            'create_user' => true,
            'role'        => 'student_private',
            'status'      => 'active',
        );
        $data = wp_parse_args( $data, $defaults );

        // Basic validation
        if ( empty( $data['name'] ) ) {
            return new WP_Error( 'missing_name', __( 'Student name is required', 'school-manager-lite' ) );
        }
        if ( empty( $data['class_id'] ) ) {
            return new WP_Error( 'missing_class', __( 'Class ID is required', 'school-manager-lite' ) );
        }
        if ( empty( $data['user_login'] ) ) {
            return new WP_Error( 'missing_username', __( 'Username (phone) is required', 'school-manager-lite' ) );
        }
        if ( empty( $data['user_pass'] ) ) {
            return new WP_Error( 'missing_password', __( 'Student ID (password) is required', 'school-manager-lite' ) );
        }

        // Duplicate checks for WP user meta (student ID) and username
        $existing_meta = get_users( array(
            'meta_key'   => '_school_student_id',
            'meta_value' => $data['user_pass'],
            'number'     => 1,
            'fields'     => 'ID',
        ) );
        if ( ! empty( $existing_meta ) ) {
            return new WP_Error( 'duplicate_student_id', __( 'A student with this ID already exists', 'school-manager-lite' ) );
        }
        if ( username_exists( $data['user_login'] ) ) {
            return new WP_Error( 'duplicate_username', __( 'This phone number is already registered', 'school-manager-lite' ) );
        }

        $wp_user_id = 0;
        if ( $data['create_user'] ) {
            // Ensure student role exists
            $this->ensure_student_role_exists();

            $user_data = array(
                'user_login'   => $data['user_login'],
                'user_pass'    => $data['user_pass'],
                'display_name' => $data['name'],
                'role'         => $data['role'],
            );
            if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
                $user_data['user_email'] = $data['email'];
            }

            $wp_user_id = wp_insert_user( $user_data );
            if ( is_wp_error( $wp_user_id ) ) {
                return $wp_user_id; // Propagate error
            }

            // Save student ID meta on the WP user
            update_user_meta( $wp_user_id, '_school_student_id', $data['user_pass'] );
            // Mark student as active by default
            update_user_meta( $wp_user_id, 'school_student_status', 'active' );
        }

        // Insert into custom students table
        $table_name = $wpdb->prefix . 'school_students';
        $insert_data = array(
            'wp_user_id' => intval( $wp_user_id ),
            'class_id'   => intval( $data['class_id'] ),
            'name'       => sanitize_text_field( $data['name'] ),
            'email'      => sanitize_email( $data['email'] ),
            'created_at' => current_time( 'mysql' ),
        );

        $result = $wpdb->insert( $table_name, $insert_data, array( '%d', '%d', '%s', '%s', '%s' ) );
        if ( false === $result ) {
            // Rollback: remove WP user if we created one
            if ( $wp_user_id ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user( $wp_user_id );
            }
            return new WP_Error( 'db_error', __( 'Could not insert student', 'school-manager-lite' ) );
        }

        return intval( $wpdb->insert_id );
    }

    // Other methods will be added here...
    
    /**
     * Ensure student role exists and has proper capabilities
     */
    public function ensure_student_role_exists() {
        if (!get_role('student_private')) {
            add_role(
                'student_private',
                __('Student', 'school-manager-lite'),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                )
            );
        }
    }
    
    /**
     * Get student classes
     * 
     * @param int $student_id Student ID
     * @return array Array of class objects
     */
    public function get_student_classes($student_id) {
        global $wpdb;
        
        $student = $this->get_student_by_wp_user_id($student_id);
        
        if (!$student) {
            return array();
        }
        
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($student->class_id);
        
        return $class ? array($class) : array();
    }
    
    /**
     * Get student by WordPress user ID
     * 
     * @param int $wp_user_id WordPress user ID
     * @return object|null Student object or null if not found
     */
    public function get_student_by_wp_user_id($wp_user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_students';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }
    
    /**
     * Add student to class
     * 
     * @param int $wp_user_id WordPress user ID of the student
     * @param int $class_id Class ID to assign
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function add_student_to_class($wp_user_id, $class_id) {
        global $wpdb;
        
        // Validate student exists
        $student = $this->get_student_by_wp_user_id($wp_user_id);
        if (!$student) {
            return new WP_Error('invalid_student', __('Invalid student', 'school-manager-lite'));
        }
        
        // Validate class exists
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($class_id);
        if (!$class) {
            return new WP_Error('invalid_class', __('Invalid class', 'school-manager-lite'));
        }
        
        // Update student's class
        $update_data = array(
            'class_id' => intval($class_id),
            'updated_at' => current_time('mysql')
        );
        
        $table_name = $wpdb->prefix . 'school_students';
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $student->id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Could not update student class', 'school-manager-lite'));
        }
        
        // If LearnDash integration enabled, update course enrollment based on class
        if (function_exists('ld_update_course_access')) {
            // Get course ID from class
            $course_id = 898; // default course ID
            if (isset($class->course_id) && !empty($class->course_id)) {
                $course_id = $class->course_id;
            }
            
            // Check if student is active
            $is_active = get_user_meta($wp_user_id, 'school_student_status', true);
            
            if ($is_active == 'active') {
                // Enroll in course
                ld_update_course_access($wp_user_id, $course_id, false);
            }
        }
        
        do_action('school_manager_lite_after_student_class_assignment', $wp_user_id, $class_id);
        
        return true;
    }

    /**
     * Get student users
     * 
     * @param array $args Query arguments
     * @return array Array of WordPress user objects
     */
    public function get_student_users($args = array()) {
        global $wpdb;

        $defaults = array(
            'search'  => '',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        );
        $args = wp_parse_args($args, $defaults);

        // First fetch the list of WP user IDs that have a matching entry in the custom students table
        $student_table = $wpdb->prefix . 'school_students';
        $wp_user_ids   = $wpdb->get_col("SELECT wp_user_id FROM {$student_table}");

        // If there are no custom student records, bail early with empty set.
        if (empty($wp_user_ids)) {
            return array();
        }

        // Build WP_User_Query arguments – limit the query to only those IDs we found.
        $user_query_args = array(
            'include'   => $wp_user_ids,
            'orderby'   => $args['orderby'],
            'order'     => $args['order'],
            'role__in'  => array('student_private', 'student_school'),
            'fields'    => 'all',
        );

        // Add search pattern if provided
        if (!empty($args['search'])) {
            $user_query_args['search']         = '*' . $args['search'] . '*';
            $user_query_args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }

        $user_query = new WP_User_Query($user_query_args);
        return $user_query->get_results();
    }
}

// Initialize the Student Manager
function School_Manager_Lite_Student_Manager() {
    return School_Manager_Lite_Student_Manager::instance();
}
