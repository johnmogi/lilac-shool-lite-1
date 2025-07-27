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
     * Get classes (LearnDash groups)
     *
     * @param array $args Query arguments
     * @return array Array of class objects
     */
    public function get_classes($args = array()) {
        $defaults = array(
            'teacher_id' => '', // Filter by teacher/author (empty = all teachers)
            'orderby' => 'title',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);
        
        // Build query args for get_posts
        $query_args = array(
            'post_type'      => 'groups',
            'posts_per_page' => $args['limit'],
            'offset'         => $args['offset'],
            'post_status'    => 'publish',
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
            's'              => $args['search'],
        );
        
        // Handle teacher/author filter
        if (isset($args['teacher_id']) && $args['teacher_id'] !== '') {
            if ($args['teacher_id'] == 0) {
                // Get groups with no author assigned
                $query_args['author__in'] = array(0);
            } else {
                // Filter by specific teacher/author
                $query_args['author'] = $args['teacher_id'];
            }
        }
        // If teacher_id is empty string (default), get all groups (don't add author filter)
        
        // Get groups
        $groups = get_posts($query_args);
        $formatted_groups = array();
        
        foreach ($groups as $group) {
            $formatted_group = new stdClass();
            $formatted_group->id = $group->ID;
            $formatted_group->name = $group->post_title;
            $formatted_group->description = $group->post_content;
            $formatted_group->teacher_id = $group->post_author;
            $formatted_group->created_at = $group->post_date;
            
            // Get student count
            $group_users = function_exists('learndash_get_groups_user_ids') ? 
                learndash_get_groups_user_ids($group->ID) : array();
            $formatted_group->student_count = is_array($group_users) ? count($group_users) : 0;
            
            $formatted_groups[] = $formatted_group;
        }
        
        return $formatted_groups;
    }

    /**
     * Get class by ID (LearnDash group)
     *
     * @param int $class_id Class ID (Group ID)
     * @return object|false Class object or false if not found
     */
    public function get_class($class_id) {
        $group = get_post($class_id);
        
        if (!$group || $group->post_type !== 'groups') {
            return false;
        }
        
        // Format as class object
        $class = new stdClass();
        $class->id = $group->ID;
        $class->name = $group->post_title;
        $class->description = $group->post_content;
        $class->teacher_id = $group->post_author;
        $class->created_at = $group->post_date;
        
        // Get student count
        $group_users = function_exists('learndash_get_groups_user_ids') ? 
            learndash_get_groups_user_ids($group->ID) : array();
        $class->student_count = is_array($group_users) ? count($group_users) : 0;
        
        return $class;
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
        $valid_roles = array(
            'administrator',
            'school_teacher', 
            'wdm_instructor',
            'instructor',
            'wdm_swd_instructor',
            'swd_instructor'
        );
        $has_valid_role = false;
        
        if ($teacher) {
            foreach ($valid_roles as $role) {
                if (in_array($role, (array) $teacher->roles)) {
                    $has_valid_role = true;
                    break;
                }
            }
        }
        
        if (!$teacher || !$has_valid_role) {
            return new WP_Error('invalid_teacher', __('Invalid teacher ID or insufficient permissions. User roles: ' . implode(', ', $teacher ? $teacher->roles : array()), 'school-manager-lite'));
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
     * Update class (LearnDash group)
     *
     * @param int $class_id Class ID (Group ID)
     * @param array $data Class data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_class($class_id, $data) {
        $class = $this->get_class($class_id);
        
        if (!$class) {
            return new WP_Error('invalid_class', __('Invalid class ID', 'school-manager-lite'));
        }
        
        $update_data = array('ID' => $class_id);
        
        if (isset($data['name']) && !empty($data['name'])) {
            $update_data['post_title'] = $data['name'];
        }
        
        if (isset($data['description'])) {
            $update_data['post_content'] = $data['description'];
        }
        
        if (isset($data['teacher_id'])) {
            // Check if teacher exists and is valid
            if (!empty($data['teacher_id'])) {
                $teacher = get_user_by('id', $data['teacher_id']);
                $valid_roles = array(
                    'administrator',
                    'school_teacher', 
                    'wdm_instructor',
                    'instructor',
                    'wdm_swd_instructor',
                    'swd_instructor'
                );
                $has_valid_role = false;
                
                if ($teacher) {
                    foreach ($valid_roles as $role) {
                        if (in_array($role, (array) $teacher->roles)) {
                            $has_valid_role = true;
                            break;
                        }
                    }
                }
                
                if (!$teacher || !$has_valid_role) {
                    return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
                }
            }
            
            $update_data['post_author'] = $data['teacher_id'];
        }
        
        // If only ID is set, no data to update
        if (count($update_data) === 1) {
            return true;
        }
        
        $result = wp_update_post($update_data, true);
        
        if (is_wp_error($result)) {
            return $result;
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

/**
 * Assign teacher to class (LearnDash group)
 *
 * @param int $teacher_id Teacher ID
 * @param int $class_id Class ID (Group ID)
 * @return bool|WP_Error True on success, WP_Error on failure
 */
public function assign_teacher_to_class($teacher_id, $class_id) {
    // Validate teacher exists
    $teacher = get_user_by('ID', $teacher_id);
    if (!$teacher) {
        return new WP_Error('invalid_teacher', __('Invalid teacher ID.', 'school-manager-lite'));
    }

    // Validate class exists
    $class = $this->get_class($class_id);
    if (!$class) {
        return new WP_Error('invalid_class', __('Invalid class ID.', 'school-manager-lite'));
    }

    // Update the class with the new teacher
    $result = $this->update_class($class_id, array('teacher_id' => $teacher_id));
    
    if (is_wp_error($result)) {
        return $result;
    }

    // Set teacher as group leader in LearnDash
    if (function_exists('ld_update_leader_group_access')) {
        ld_update_leader_group_access($teacher_id, $class_id);
    }

    return true;
}
}

// Initialize the Class Manager
function School_Manager_Lite_Class_Manager() {
    return School_Manager_Lite_Class_Manager::instance();
}
