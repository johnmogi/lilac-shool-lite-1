<?php
/**
 * LearnDash Integration Class
 * 
 * Provides shared functionality for connecting teachers to LearnDash groups
 * Can be used by both School Manager Lite and Simple Teacher Dashboard plugins
 * 
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_LearnDash_Integration {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Only initialize if LearnDash is active
        if ($this->is_learndash_active()) {
            add_action('init', array($this, 'init'));
        }
    }
    
    /**
     * Initialize the integration
     */
    public function init() {
        // Add hooks for automatic group management
        add_action('school_manager_teacher_assigned_to_class', array($this, 'handle_teacher_assignment'), 10, 2);
        add_action('school_manager_student_added_to_class', array($this, 'handle_student_assignment'), 10, 2);
    }
    
    /**
     * Check if LearnDash is active
     */
    public function is_learndash_active() {
        return function_exists('learndash_get_groups') && function_exists('ld_update_leader_group_access');
    }
    
    /**
     * Create or get LearnDash group for a class
     * 
     * @param int $class_id Class ID
     * @param array $class_data Class data (name, teacher_id, course_id)
     * @return int|WP_Error Group ID or error
     */
    public function create_or_get_group_for_class($class_id, $class_data = null) {
        if (!$this->is_learndash_active()) {
            return new WP_Error('learndash_inactive', __('LearnDash is not active', 'school-manager-lite'));
        }
        
        // Get class data if not provided
        if (!$class_data) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class_data = $class_manager->get_class($class_id);
            
            if (!$class_data) {
                return new WP_Error('invalid_class', __('Class not found', 'school-manager-lite'));
            }
        }
        
        // Check if group already exists
        $existing_groups = get_posts(array(
            'post_type' => 'groups',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'school_manager_class_id',
                    'value' => $class_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_groups)) {
            return $existing_groups[0]->ID;
        }
        
        // Create new LearnDash group
        $group_name = sprintf(__('Class: %s', 'school-manager-lite'), $class_data->name);
        $group_id = wp_insert_post(array(
            'post_title' => $group_name,
            'post_type' => 'groups',
            'post_status' => 'publish',
            'post_author' => !empty($class_data->teacher_id) ? $class_data->teacher_id : get_current_user_id()
        ));
        
        if (is_wp_error($group_id)) {
            return $group_id;
        }
        
        // Link group to class
        update_post_meta($group_id, 'school_manager_class_id', $class_id);
        
        // Add course to group if class has a course
        if (!empty($class_data->course_id)) {
            learndash_set_group_enrolled_courses($group_id, array($class_data->course_id));
        }
        
        // Update class with group ID
        if (class_exists('School_Manager_Lite_Class_Manager')) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class_manager->update_class($class_id, array('group_id' => $group_id));
        }
        
        return $group_id;
    }
    
    /**
     * Assign teacher as group leader
     * 
     * @param int $teacher_id Teacher user ID
     * @param int $group_id Group ID
     * @return bool Success
     */
    public function assign_teacher_to_group($teacher_id, $group_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Verify teacher exists
        $teacher = get_user_by('id', $teacher_id);
        if (!$teacher || !$teacher->exists()) {
            return false;
        }
        
        // Assign teacher as group leader
        if (function_exists('ld_update_leader_group_access')) {
            ld_update_leader_group_access($teacher_id, $group_id, false);
            return true;
        }
        
        return false;
    }
    
    /**
     * Add student to LearnDash group
     * 
     * @param int $student_id Student user ID
     * @param int $group_id Group ID
     * @return bool Success
     */
    public function add_student_to_group($student_id, $group_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Verify student exists
        $student = get_user_by('id', $student_id);
        if (!$student || !$student->exists()) {
            return false;
        }
        
        // Add student to group
        if (function_exists('ld_update_group_access')) {
            ld_update_group_access($student_id, $group_id, false);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove student from LearnDash group
     * 
     * @param int $student_id Student user ID
     * @param int $group_id Group ID
     * @return bool Success
     */
    public function remove_student_from_group($student_id, $group_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Remove student from group
        if (function_exists('ld_update_group_access')) {
            ld_update_group_access($student_id, $group_id, true);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all students in a LearnDash group
     * 
     * @param int $group_id Group ID
     * @return array Student user IDs
     */
    public function get_group_students($group_id) {
        if (!$this->is_learndash_active()) {
            return array();
        }
        
        if (function_exists('learndash_get_groups_user_ids')) {
            return learndash_get_groups_user_ids($group_id);
        }
        
        return array();
    }
    
    /**
     * Get teacher/group leader for a group
     * 
     * @param int $group_id Group ID
     * @return int|false Teacher user ID or false
     */
    public function get_group_teacher($group_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        if (function_exists('learndash_get_groups_administrator_ids')) {
            $leaders = learndash_get_groups_administrator_ids($group_id);
            return !empty($leaders) ? $leaders[0] : false;
        }
        
        return false;
    }
    
    /**
     * Handle teacher assignment to class (hook callback)
     * 
     * @param int $teacher_id Teacher user ID
     * @param int $class_id Class ID
     */
    public function handle_teacher_assignment($teacher_id, $class_id) {
        $group_id = $this->create_or_get_group_for_class($class_id);
        
        if (!is_wp_error($group_id)) {
            $this->assign_teacher_to_group($teacher_id, $group_id);
        }
    }
    
    /**
     * Handle student assignment to class (hook callback)
     * 
     * @param int $student_id Student user ID
     * @param int $class_id Class ID
     */
    public function handle_student_assignment($student_id, $class_id) {
        // Get the group for this class
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($class_id);
        
        if ($class && !empty($class->group_id)) {
            $this->add_student_to_group($student_id, $class->group_id);
        }
    }
    
    /**
     * Sync all classes with LearnDash groups
     * Utility function for bulk operations
     * 
     * @return array Results
     */
    public function sync_all_classes() {
        if (!$this->is_learndash_active()) {
            return array('error' => __('LearnDash is not active', 'school-manager-lite'));
        }
        
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        $results = array(
            'success' => 0,
            'errors' => 0,
            'messages' => array()
        );
        
        foreach ($classes as $class) {
            $group_id = $this->create_or_get_group_for_class($class->id, $class);
            
            if (is_wp_error($group_id)) {
                $results['errors']++;
                $results['messages'][] = sprintf(
                    __('Failed to create group for class "%s": %s', 'school-manager-lite'),
                    $class->name,
                    $group_id->get_error_message()
                );
            } else {
                $results['success']++;
                $results['messages'][] = sprintf(
                    __('Created/updated group for class "%s"', 'school-manager-lite'),
                    $class->name
                );
                
                // Assign teacher if exists
                if (!empty($class->teacher_id)) {
                    $this->assign_teacher_to_group($class->teacher_id, $group_id);
                }
            }
        }
        
        return $results;
    }
}

// Initialize the integration
School_Manager_Lite_LearnDash_Integration::instance();
