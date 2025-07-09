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
     * Get student users
     * 
     * @param array $args Query arguments
     * @return array Array of WordPress user objects
     */
    public function get_student_users($args = array()) {
        $defaults = array(
            'search' => '',
            'orderby' => 'display_name',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);
        
        // WordPress user query arguments
        $user_query_args = array(
            'role' => 'student_private',
            'orderby' => $args['orderby'],
            'order' => $args['order']
        );
        
        // Add search if specified
        if (!empty($args['search'])) {
            $user_query_args['search'] = '*' . $args['search'] . '*';
        }
        
        // Get users
        $user_query = new WP_User_Query($user_query_args);
        return $user_query->get_results();
    }
}

// Initialize the Student Manager
function School_Manager_Lite_Student_Manager() {
    return School_Manager_Lite_Student_Manager::instance();
}
