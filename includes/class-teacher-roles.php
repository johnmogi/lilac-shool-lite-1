<?php
/**
 * Teacher Role Management
 * 
 * Handles automatic assignment of teacher roles and capabilities
 * 
 * @package    School_Manager
 * @subpackage Includes
 * @since      1.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

class School_Manager_Teacher_Roles {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Hook into user registration and updates
        add_action('user_register', array($this, 'auto_assign_teacher_role'));
        add_action('profile_update', array($this, 'auto_assign_teacher_role'));
        add_action('set_user_role', array($this, 'handle_role_change'), 10, 3);
        
        // Handle imports
        add_action('school_manager_after_import_user', array($this, 'handle_imported_user'), 10, 2);
    }

    /**
     * Auto-assign teacher roles and capabilities
     * 
     * @param int $user_id The user ID
     */
    public function auto_assign_teacher_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Check if user is a teacher
        $is_teacher = get_user_meta($user_id, 'is_teacher', true);
        
        // For imports or existing users
        if (empty($is_teacher)) {
            $is_teacher = get_user_meta($user_id, 'teacher', true);
        }
        
        // If user is a teacher, assign capabilities
        if ($is_teacher || in_array('teacher', (array)$user->roles)) {
            $this->add_teacher_capabilities($user);
            
            // Log the update
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[School Manager] Assigned teacher role to user ID: %d',
                    $user_id
                ));
            }
        }
    }

    /**
     * Handle role changes from admin
     */
    public function handle_role_change($user_id, $role, $old_roles) {
        if ($role === 'teacher' || in_array('teacher', (array)$old_roles)) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $this->add_teacher_capabilities($user);
            }
        }
    }

    /**
     * Handle imported users
     */
    public function handle_imported_user($user_id, $user_data) {
        if (!empty($user_data['is_teacher']) || !empty($user_data['teacher'])) {
            update_user_meta($user_id, 'is_teacher', '1');
            $this->auto_assign_teacher_role($user_id);
        }
    }

    /**
     * Add teacher capabilities to a user
     */
    private function add_teacher_capabilities($user) {
        // Add or update capabilities
        $user->add_role('wdm_instructor');
        $user->add_cap('school_teacher');
        $user->add_cap('ld_instructor');
        $user->add_cap('manage_groups');
        $user->add_cap('manage_courses');
    }

    /**
     * Batch update existing teachers
     */
    public static function update_existing_teachers() {
        // Find users with teacher role or meta
        $teachers = get_users(array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'is_teacher',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'teacher',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'role__in' => array('teacher', 'wdm_instructor')
        ));
        
        $instance = new self();
        foreach ($teachers as $teacher) {
            $instance->add_teacher_capabilities($teacher);
        }
        
        return count($teachers);
    }
}

// Initialize the class
new School_Manager_Teacher_Roles();
