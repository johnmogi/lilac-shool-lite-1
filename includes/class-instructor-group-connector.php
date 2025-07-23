<?php
/**
 * Instructor Group Connector
 * 
 * Handles connection between all instructor roles and LearnDash groups
 * Supports: instructor, Instructor, wdm_instructor, swd_instructor, school_teacher
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * School_Manager_Lite_Instructor_Group_Connector Class
 */
class School_Manager_Lite_Instructor_Group_Connector {
    
    /**
     * The single instance of the class.
     */
    private static $instance = null;
    
    /**
     * All supported instructor roles
     */
    private $instructor_roles = array(
        'instructor',
        'Instructor', 
        'wdm_instructor',
        'swd_instructor',
        'school_teacher'
    );
    
    /**
     * Main instance
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
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Hook into class creation/updates
        add_action('school_manager_class_created', array($this, 'handle_class_created'), 10, 2);
        add_action('school_manager_class_updated', array($this, 'handle_class_updated'), 10, 2);
        add_action('school_manager_teacher_assigned_to_class', array($this, 'handle_teacher_assigned'), 10, 2);
        
        // AJAX handlers for manual connections
        add_action('wp_ajax_connect_instructor_to_group', array($this, 'ajax_connect_instructor_to_group'));
        add_action('wp_ajax_sync_all_instructor_groups', array($this, 'ajax_sync_all_instructor_groups'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add admin menu for manual sync
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'school-manager',
            __('Instructor-Group Sync', 'school-manager-lite'),
            __('Instructor-Group Sync', 'school-manager-lite'),
            'manage_school_classes',
            'instructor-group-sync',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for manual sync
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Instructor-Group Synchronization', 'school-manager-lite'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Sync All Instructors to LearnDash Groups', 'school-manager-lite'); ?></h2>
                <p><?php _e('This will connect all instructors to their assigned classes as LearnDash group leaders.', 'school-manager-lite'); ?></p>
                <p><?php _e('Supported roles: instructor, Instructor, wdm_instructor, swd_instructor, school_teacher', 'school-manager-lite'); ?></p>
                
                <button id="sync-all-instructors" class="button button-primary">
                    <?php _e('Sync All Instructors Now', 'school-manager-lite'); ?>
                </button>
                
                <div id="sync-results" style="margin-top: 20px;"></div>
            </div>
            
            <div class="card">
                <h2><?php _e('Current Status', 'school-manager-lite'); ?></h2>
                <?php $this->display_current_status(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#sync-all-instructors').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Syncing...', 'school-manager-lite'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sync_all_instructor_groups',
                        nonce: '<?php echo wp_create_nonce('instructor_group_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#sync-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#sync-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#sync-results').html('<div class="notice notice-error"><p><?php _e('Sync failed. Please try again.', 'school-manager-lite'); ?></p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Sync All Instructors Now', 'school-manager-lite'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display current sync status
     */
    public function display_current_status() {
        $classes = $this->get_all_classes_with_instructors();
        $learndash_active = $this->is_learndash_active();
        
        if (!$learndash_active) {
            echo '<div class="notice notice-warning"><p>' . __('LearnDash is not active. Group connections cannot be made.', 'school-manager-lite') . '</p></div>';
            return;
        }
        
        if (empty($classes)) {
            echo '<p>' . __('No classes with assigned instructors found.', 'school-manager-lite') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Class', 'school-manager-lite') . '</th>';
        echo '<th>' . __('Instructor', 'school-manager-lite') . '</th>';
        echo '<th>' . __('Role', 'school-manager-lite') . '</th>';
        echo '<th>' . __('LearnDash Group', 'school-manager-lite') . '</th>';
        echo '<th>' . __('Status', 'school-manager-lite') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($classes as $class) {
            $instructor = get_user_by('id', $class->teacher_id);
            $group_id = $this->get_group_for_class($class->id);
            $is_leader = $group_id ? $this->is_instructor_group_leader($class->teacher_id, $group_id) : false;
            
            echo '<tr>';
            echo '<td>' . esc_html($class->name) . '</td>';
            echo '<td>' . ($instructor ? esc_html($instructor->display_name) : __('No instructor', 'school-manager-lite')) . '</td>';
            echo '<td>' . ($instructor ? esc_html(implode(', ', $instructor->roles)) : '-') . '</td>';
            echo '<td>' . ($group_id ? sprintf(__('Group #%d', 'school-manager-lite'), $group_id) : __('No group', 'school-manager-lite')) . '</td>';
            echo '<td>';
            if ($group_id && $is_leader) {
                echo '<span style="color: green;">✓ ' . __('Connected', 'school-manager-lite') . '</span>';
            } else if ($group_id) {
                echo '<span style="color: orange;">⚠ ' . __('Group exists, not leader', 'school-manager-lite') . '</span>';
            } else {
                echo '<span style="color: red;">✗ ' . __('Not connected', 'school-manager-lite') . '</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Check if user has any instructor role
     */
    public function is_instructor($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        foreach ($this->instructor_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get all classes with assigned instructors
     */
    public function get_all_classes_with_instructors() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_classes';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE teacher_id IS NOT NULL AND teacher_id > 0"
        );
    }
    
    /**
     * Connect instructor to LearnDash group for a class
     */
    public function connect_instructor_to_group($instructor_id, $class_id) {
        if (!$this->is_learndash_active()) {
            return new WP_Error('learndash_inactive', __('LearnDash is not active', 'school-manager-lite'));
        }
        
        if (!$this->is_instructor($instructor_id)) {
            return new WP_Error('not_instructor', __('User is not an instructor', 'school-manager-lite'));
        }
        
        // Get or create LearnDash group for class
        $group_id = $this->get_or_create_group_for_class($class_id);
        
        if (is_wp_error($group_id)) {
            return $group_id;
        }
        
        // Assign instructor as group leader
        $result = $this->assign_instructor_as_group_leader($instructor_id, $group_id);
        
        if ($result) {
            // Update class record with group ID
            $this->update_class_group_id($class_id, $group_id);
            
            // Enroll existing students in the group
            $this->enroll_class_students_in_group($class_id, $group_id);
            
            return $group_id;
        }
        
        return new WP_Error('connection_failed', __('Failed to connect instructor to group', 'school-manager-lite'));
    }
    
    /**
     * Get or create LearnDash group for class
     */
    public function get_or_create_group_for_class($class_id) {
        // Check if group already exists
        $existing_group_id = $this->get_group_for_class($class_id);
        if ($existing_group_id) {
            return $existing_group_id;
        }
        
        // Get class details
        $class = $this->get_class($class_id);
        if (!$class) {
            return new WP_Error('class_not_found', __('Class not found', 'school-manager-lite'));
        }
        
        // Create LearnDash group
        $group_data = array(
            'post_title' => sprintf(__('Class: %s', 'school-manager-lite'), $class->name),
            'post_content' => $class->description ?: sprintf(__('LearnDash group for class: %s', 'school-manager-lite'), $class->name),
            'post_status' => 'publish',
            'post_type' => 'groups'
        );
        
        $group_id = wp_insert_post($group_data);
        
        if (is_wp_error($group_id)) {
            return $group_id;
        }
        
        // Update class with group ID
        $this->update_class_group_id($class_id, $group_id);
        
        return $group_id;
    }
    
    /**
     * Assign instructor as group leader
     */
    public function assign_instructor_as_group_leader($instructor_id, $group_id) {
        if (!function_exists('ld_update_leader_group_access')) {
            return false;
        }
        
        // Add instructor as group leader
        $current_leaders = learndash_get_groups_administrator_leaders($group_id);
        if (!in_array($instructor_id, $current_leaders)) {
            $current_leaders[] = $instructor_id;
            return ld_update_leader_group_access($instructor_id, $group_id, true);
        }
        
        return true;
    }
    
    /**
     * Enroll class students in group
     */
    public function enroll_class_students_in_group($class_id, $group_id) {
        $students = $this->get_class_students($class_id);
        
        if (empty($students)) {
            return true;
        }
        
        foreach ($students as $student_id) {
            if (function_exists('ld_update_group_access')) {
                ld_update_group_access($student_id, $group_id, true);
            }
        }
        
        return true;
    }
    
    /**
     * Get class students
     */
    public function get_class_students($class_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_student_classes';
        
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT student_id FROM {$table_name} WHERE class_id = %d",
                $class_id
            )
        );
        
        return array_map('intval', $results);
    }
    
    /**
     * AJAX handler for syncing all instructors
     */
    public function ajax_sync_all_instructor_groups() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_group_sync')) {
            wp_send_json_error(array('message' => __('Security check failed', 'school-manager-lite')));
        }
        
        // Check permissions
        if (!current_user_can('manage_school_classes')) {
            wp_send_json_error(array('message' => __('Permission denied', 'school-manager-lite')));
        }
        
        $classes = $this->get_all_classes_with_instructors();
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($classes as $class) {
            if ($this->is_instructor($class->teacher_id)) {
                $result = $this->connect_instructor_to_group($class->teacher_id, $class->id);
                
                if (is_wp_error($result)) {
                    $error_count++;
                    $errors[] = sprintf(__('Class %s: %s', 'school-manager-lite'), $class->name, $result->get_error_message());
                } else {
                    $success_count++;
                }
            }
        }
        
        $message = sprintf(
            __('Sync completed: %d successful, %d errors', 'school-manager-lite'),
            $success_count,
            $error_count
        );
        
        if (!empty($errors)) {
            $message .= '<br><strong>' . __('Errors:', 'school-manager-lite') . '</strong><br>' . implode('<br>', $errors);
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'success_count' => $success_count,
            'error_count' => $error_count
        ));
    }
    
    /**
     * Handle class created
     */
    public function handle_class_created($class_id, $class_data) {
        if (!empty($class_data['teacher_id']) && $this->is_instructor($class_data['teacher_id'])) {
            $this->connect_instructor_to_group($class_data['teacher_id'], $class_id);
        }
    }
    
    /**
     * Handle class updated
     */
    public function handle_class_updated($class_id, $class_data) {
        if (!empty($class_data['teacher_id']) && $this->is_instructor($class_data['teacher_id'])) {
            $this->connect_instructor_to_group($class_data['teacher_id'], $class_id);
        }
    }
    
    /**
     * Handle teacher assigned to class
     */
    public function handle_teacher_assigned($teacher_id, $class_id) {
        if ($this->is_instructor($teacher_id)) {
            $this->connect_instructor_to_group($teacher_id, $class_id);
        }
    }
    
    /**
     * Helper methods
     */
    
    public function is_learndash_active() {
        return class_exists('SFWD_LMS') || function_exists('learndash_get_groups');
    }
    
    public function get_group_for_class($class_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'school_classes';
        return $wpdb->get_var($wpdb->prepare("SELECT group_id FROM {$table_name} WHERE id = %d", $class_id));
    }
    
    public function get_class($class_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'school_classes';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $class_id));
    }
    
    public function update_class_group_id($class_id, $group_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'school_classes';
        return $wpdb->update(
            $table_name,
            array('group_id' => $group_id),
            array('id' => $class_id),
            array('%d'),
            array('%d')
        );
    }
    
    public function is_instructor_group_leader($instructor_id, $group_id) {
        if (!function_exists('learndash_get_groups_administrator_leaders')) {
            return false;
        }
        
        $leaders = learndash_get_groups_administrator_leaders($group_id);
        return in_array($instructor_id, $leaders);
    }
}

// Initialize the connector
School_Manager_Lite_Instructor_Group_Connector::instance();
