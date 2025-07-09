<?php
/**
 * Class Manager
 *
 * Handles all operations related to classes
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Class_Manager {
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
     * Get classes
     *
     * @param array $args Query arguments
     * @return array Array of class objects
     */
    public function get_classes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'teacher_id' => 0, // Filter by teacher
            'orderby' => 'name', 
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'school_classes';
        
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $query_args = array();
        
        if (!empty($args['teacher_id'])) {
            $query .= " AND teacher_id = %d";
            $query_args[] = $args['teacher_id'];
        }
        
        // Order
        $allowed_order_fields = array('name', 'id', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_order_fields) ? $args['orderby'] : 'name';
        $order = $args['order'] === 'DESC' ? 'DESC' : 'ASC';
        
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
     * Get class by ID
     *
     * @param int $class_id Class ID
     * @return object|false Class object or false if not found
     */
    public function get_class($class_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_classes';
        
        $class = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $class_id
        ));
        
        return $class ? $class : false;
    }

    /**
     * Create class
     *
     * @param array $data Class data
     * @return int|WP_Error Class ID or WP_Error on failure
     */
    public function create_class($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'teacher_id' => 0,
        );

        $data = wp_parse_args($data, $defaults);

        // Required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Class name is required', 'school-manager-lite'));
        }

        if (empty($data['teacher_id'])) {
            return new WP_Error('missing_teacher', __('Teacher is required', 'school-manager-lite'));
        }

        // Check if teacher exists and is valid
        $teacher = get_user_by('id', $data['teacher_id']);
        if (!$teacher || !in_array('school_teacher', (array) $teacher->roles)) {
            return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
        }

        $table_name = $wpdb->prefix . 'school_classes';
        
        // Insert class
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $data['name'],
                'description' => $data['description'],
                'teacher_id' => $data['teacher_id'],
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%s')
        );
        
        if (!$result) {
            return new WP_Error('db_error', __('Could not create class', 'school-manager-lite'));
        }
        
        $class_id = $wpdb->insert_id;
        
        do_action('school_manager_lite_after_create_class', $class_id, $data);
        
        return $class_id;
    }

    /**
     * Update class
     *
     * @param int $class_id Class ID
     * @param array $data Class data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_class($class_id, $data) {
        global $wpdb;
        
        $class = $this->get_class($class_id);
        
        if (!$class) {
            return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
        }
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['name']) && !empty($data['name'])) {
            $update_data['name'] = $data['name'];
            $update_format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = $data['description'];
            $update_format[] = '%s';
        }
        
        if (isset($data['teacher_id']) && !empty($data['teacher_id'])) {
            // Check if teacher exists and is valid
            $teacher = get_user_by('id', $data['teacher_id']);
            if (!$teacher || !in_array('school_teacher', (array) $teacher->roles)) {
                return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
            }
            
            $update_data['teacher_id'] = $data['teacher_id'];
            $update_format[] = '%d';
        }
        
        // If no data to update
        if (empty($update_data)) {
            return true;
        }
        
        $table_name = $wpdb->prefix . 'school_classes';
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $class_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Could not update class', 'school-manager-lite'));
        }
        
        do_action('school_manager_lite_after_update_class', $class_id, $data);
        
        return true;
    }

    /**
     * Delete class
     *
     * @param int $class_id Class ID
     * @return bool True on success, false on failure
     */
    public function delete_class($class_id) {
        global $wpdb;
        
        $class = $this->get_class($class_id);
        
        if (!$class) {
            return false;
        }
        
        do_action('school_manager_lite_before_delete_class', $class_id);
        
        // Delete class
        $table_name = $wpdb->prefix . 'school_classes';
        $result = $wpdb->delete(
            $table_name,
            array('id' => $class_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get class students
     *
     * @param int $class_id Class ID
     * @return array Array of student objects
     */
    public function get_class_students($class_id) {
        global $wpdb;
        
        $class = $this->get_class($class_id);
        
        if (!$class) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'school_students';
        
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE class_id = %d ORDER BY name ASC",
            $class_id
        ));
        
        return is_array($students) ? $students : array();
    }

    /**
     * Count students in class
     *
     * @param int $class_id Class ID
     * @return int Number of students
     */
    public function count_class_students($class_id) {
        global $wpdb;
        
        $class = $this->get_class($class_id);
        
        if (!$class) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'school_students';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE class_id = %d",
            $class_id
        ));
        
        return (int) $count;
    }
}

// Initialize the Class Manager
function School_Manager_Lite_Class_Manager() {
    return School_Manager_Lite_Class_Manager::instance();
}
