<?php
/**
 * Simple Group Connector
 * 
 * Basic, reliable connection between teachers and LearnDash groups
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_Simple_Group_Connector {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add AJAX handler
        add_action('wp_ajax_simple_sync_teachers_groups', array($this, 'ajax_sync_teachers_groups'));
    }
    
    /**
     * Check if LearnDash is active
     */
    public function is_learndash_active() {
        return function_exists('learndash_get_groups') || class_exists('SFWD_LMS');
    }
    
    /**
     * Get all classes with teachers
     */
    public function get_classes_with_teachers() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_classes';
        
        $results = $wpdb->get_results(
            "SELECT id, name, description, teacher_id, group_id 
             FROM {$table_name} 
             WHERE teacher_id IS NOT NULL AND teacher_id > 0"
        );
        
        return $results;
    }
    
    /**
     * Create LearnDash group for class
     */
    public function create_group_for_class($class) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Check if group already exists
        if (!empty($class->group_id)) {
            $existing_group = get_post($class->group_id);
            if ($existing_group && $existing_group->post_type === 'groups') {
                return $class->group_id;
            }
        }
        
        // Create new group
        $group_data = array(
            'post_title' => 'Class: ' . $class->name,
            'post_content' => $class->description ?: 'LearnDash group for class: ' . $class->name,
            'post_status' => 'publish',
            'post_type' => 'groups'
        );
        
        $group_id = wp_insert_post($group_data);
        
        if (!is_wp_error($group_id) && $group_id > 0) {
            // Update class with group ID
            global $wpdb;
            $table_name = $wpdb->prefix . 'school_classes';
            
            $wpdb->update(
                $table_name,
                array('group_id' => $group_id),
                array('id' => $class->id),
                array('%d'),
                array('%d')
            );
            
            return $group_id;
        }
        
        return false;
    }
    
    /**
     * Assign teacher as group leader
     */
    public function assign_teacher_as_leader($teacher_id, $group_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        if (!function_exists('ld_update_leader_group_access')) {
            return false;
        }
        
        // Add teacher as group leader
        $result = ld_update_leader_group_access($teacher_id, $group_id, true);
        
        return $result !== false;
    }
    
    /**
     * Get class students
     */
    public function get_class_students($class_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_student_classes';
        
        $student_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT student_id FROM {$table_name} WHERE class_id = %d",
                $class_id
            )
        );
        
        return array_map('intval', $student_ids);
    }
    
    /**
     * Enroll students in group
     */
    public function enroll_students_in_group($student_ids, $group_id) {
        if (!$this->is_learndash_active() || empty($student_ids)) {
            return false;
        }
        
        if (!function_exists('ld_update_group_access')) {
            return false;
        }
        
        $success_count = 0;
        
        foreach ($student_ids as $student_id) {
            if (ld_update_group_access($student_id, $group_id, true)) {
                $success_count++;
            }
        }
        
        return $success_count;
    }
    
    /**
     * AJAX handler for syncing teachers to groups
     */
    public function ajax_sync_teachers_groups() {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        if (!$this->is_learndash_active()) {
            wp_send_json_error(array('message' => 'LearnDash is not active'));
            return;
        }
        
        $classes = $this->get_classes_with_teachers();
        
        if (empty($classes)) {
            wp_send_json_error(array('message' => 'No classes with teachers found'));
            return;
        }
        
        $results = array();
        $success_count = 0;
        $error_count = 0;
        
        foreach ($classes as $class) {
            $class_result = array(
                'class_name' => $class->name,
                'teacher_id' => $class->teacher_id,
                'status' => 'error',
                'message' => ''
            );
            
            // Create group
            $group_id = $this->create_group_for_class($class);
            
            if ($group_id) {
                $class_result['group_id'] = $group_id;
                
                // Assign teacher as leader
                if ($this->assign_teacher_as_leader($class->teacher_id, $group_id)) {
                    $class_result['status'] = 'success';
                    $class_result['message'] = 'Teacher assigned as group leader';
                    
                    // Enroll students
                    $students = $this->get_class_students($class->id);
                    if (!empty($students)) {
                        $enrolled = $this->enroll_students_in_group($students, $group_id);
                        $class_result['students_enrolled'] = $enrolled;
                        $class_result['message'] .= sprintf(', %d students enrolled', $enrolled);
                    }
                    
                    $success_count++;
                } else {
                    $class_result['message'] = 'Failed to assign teacher as leader';
                    $error_count++;
                }
            } else {
                $class_result['message'] = 'Failed to create group';
                $error_count++;
            }
            
            $results[] = $class_result;
        }
        
        $message = sprintf(
            'Sync completed: %d successful, %d errors',
            $success_count,
            $error_count
        );
        
        wp_send_json_success(array(
            'message' => $message,
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ));
    }
}

// Initialize
School_Manager_Lite_Simple_Group_Connector::instance();
