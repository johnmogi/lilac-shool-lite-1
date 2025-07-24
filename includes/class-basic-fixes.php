<?php
/**
 * Basic Fixes for School Manager
 * 
 * Step-by-step fixes to get the system working
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_Basic_Fixes {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add admin menu for diagnostics
        add_action('admin_menu', array($this, 'add_diagnostic_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_fix_student_visibility', array($this, 'ajax_fix_student_visibility'));
        add_action('wp_ajax_create_basic_group_connection', array($this, 'ajax_create_basic_group_connection'));
        add_action('wp_ajax_test_teacher_group_connection', array($this, 'ajax_test_teacher_group_connection'));
    }
    
    /**
     * Add diagnostic menu
     * Menu item removed as it's non-functional
     */
    public function add_diagnostic_menu() {
        // Menu item removed - functionality may be available in other admin pages
    }
    
    /**
     * Diagnostic page
     */
    public function diagnostic_page() {
        ?>
        <div class="wrap">
            <h1>School Manager - System Diagnostics & Fixes</h1>
            
            <div class="notice notice-info">
                <p><strong>Step-by-step approach to fix your system:</strong></p>
                <ol>
                    <li>Fix student visibility in student list</li>
                    <li>Create basic teacher-group connections</li>
                    <li>Test LearnDash group functionality</li>
                    <li>Verify instructor dashboard access</li>
                </ol>
            </div>
            
            <div class="card">
                <h2>Step 1: Database Status</h2>
                <?php $this->show_database_status(); ?>
            </div>
            
            <div class="card">
                <h2>Step 2: Student Visibility Fix</h2>
                <p>If students imported via CSV are not visible in the student list, this will fix the database relationships.</p>
                <button id="fix-student-visibility" class="button button-primary">Fix Student Visibility</button>
                <div id="student-fix-results"></div>
            </div>
            
            <div class="card">
                <h2>Step 3: Basic Group Connections</h2>
                <p>Create basic LearnDash groups for classes and connect teachers.</p>
                <button id="create-basic-connections" class="button button-primary">Create Basic Connections</button>
                <div id="connection-results"></div>
            </div>
            
            <div class="card">
                <h2>Step 4: Test Connections</h2>
                <p>Test if teachers can access their groups and students.</p>
                <button id="test-connections" class="button button-secondary">Test Connections</button>
                <div id="test-results"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            $('#fix-student-visibility').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Fixing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fix_student_visibility',
                        nonce: '<?php echo wp_create_nonce('school_manager_fixes'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#student-fix-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#student-fix-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#student-fix-results').html('<div class="notice notice-error"><p>Fix failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Fix Student Visibility');
                    }
                });
            });
            
            $('#create-basic-connections').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Creating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_basic_group_connection',
                        nonce: '<?php echo wp_create_nonce('school_manager_fixes'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#connection-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#connection-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#connection-results').html('<div class="notice notice-error"><p>Connection creation failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Create Basic Connections');
                    }
                });
            });
            
            $('#test-connections').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_teacher_group_connection',
                        nonce: '<?php echo wp_create_nonce('school_manager_fixes'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#test-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#test-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#test-results').html('<div class="notice notice-error"><p>Test failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connections');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Show database status
     */
    public function show_database_status() {
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        $students_table = $wpdb->prefix . 'school_student_classes';
        
        echo '<table class="wp-list-table widefat">';
        echo '<tr><th>Item</th><th>Status</th><th>Count</th></tr>';
        
        // Classes table
        $classes_exists = $wpdb->get_var("SHOW TABLES LIKE '$classes_table'") == $classes_table;
        $classes_count = $classes_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $classes_table") : 0;
        echo '<tr><td>Classes Table</td><td>' . ($classes_exists ? '✓' : '✗') . '</td><td>' . $classes_count . '</td></tr>';
        
        // Classes with teachers
        $classes_with_teachers = $classes_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0") : 0;
        echo '<tr><td>Classes with Teachers</td><td>' . ($classes_with_teachers > 0 ? '✓' : '✗') . '</td><td>' . $classes_with_teachers . '</td></tr>';
        
        // Students table
        $students_exists = $wpdb->get_var("SHOW TABLES LIKE '$students_table'") == $students_table;
        $student_relationships = $students_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $students_table") : 0;
        echo '<tr><td>Student-Class Relationships</td><td>' . ($students_exists ? '✓' : '✗') . '</td><td>' . $student_relationships . '</td></tr>';
        
        // LearnDash
        $learndash_active = function_exists('learndash_get_groups') || class_exists('SFWD_LMS');
        $groups_count = $learndash_active ? wp_count_posts('groups')->publish : 0;
        echo '<tr><td>LearnDash Active</td><td>' . ($learndash_active ? '✓' : '✗') . '</td><td>' . $groups_count . ' groups</td></tr>';
        
        // User roles
        $instructor_roles = array('instructor', 'Instructor', 'wdm_instructor', 'swd_instructor', 'school_teacher');
        $total_instructors = 0;
        foreach ($instructor_roles as $role) {
            $users = get_users(array('role' => $role));
            $count = count($users);
            $total_instructors += $count;
            if ($count > 0) {
                echo '<tr><td>' . ucfirst($role) . ' Users</td><td>✓</td><td>' . $count . '</td></tr>';
            }
        }
        
        echo '<tr><td><strong>Total Instructors</strong></td><td>' . ($total_instructors > 0 ? '✓' : '✗') . '</td><td><strong>' . $total_instructors . '</strong></td></tr>';
        
        echo '</table>';
    }
    
    /**
     * Fix student visibility
     */
    public function ajax_fix_student_visibility() {
        if (!wp_verify_nonce($_POST['nonce'], 'school_manager_fixes')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        
        // Check for orphaned students (users with student role but no class relationships)
        $student_users = get_users(array('role' => 'student'));
        $students_table = $wpdb->prefix . 'school_student_classes';
        
        $orphaned_students = array();
        $fixed_count = 0;
        
        foreach ($student_users as $student) {
            $has_class = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $students_table WHERE student_id = %d",
                $student->ID
            ));
            
            if (!$has_class) {
                $orphaned_students[] = $student->display_name . ' (ID: ' . $student->ID . ')';
            }
        }
        
        $message = sprintf(
            'Student visibility check completed. Found %d students, %d orphaned students without class relationships.',
            count($student_users),
            count($orphaned_students)
        );
        
        if (!empty($orphaned_students)) {
            $message .= '<br><strong>Orphaned students:</strong><br>' . implode('<br>', $orphaned_students);
            $message .= '<br><em>These students need to be assigned to classes to be visible in the student list.</em>';
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Create basic group connections
     */
    public function ajax_create_basic_group_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'school_manager_fixes')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        if (!function_exists('learndash_get_groups')) {
            wp_send_json_error(array('message' => 'LearnDash is not active'));
        }
        
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        $classes = $wpdb->get_results(
            "SELECT id, name, description, teacher_id, group_id 
             FROM $classes_table 
             WHERE teacher_id IS NOT NULL AND teacher_id > 0"
        );
        
        if (empty($classes)) {
            wp_send_json_error(array('message' => 'No classes with teachers found'));
        }
        
        $results = array();
        $success_count = 0;
        
        foreach ($classes as $class) {
            $result = array('class' => $class->name);
            
            // Check if group already exists
            if ($class->group_id) {
                $existing_group = get_post($class->group_id);
                if ($existing_group && $existing_group->post_type === 'groups') {
                    $result['status'] = 'Group already exists (ID: ' . $class->group_id . ')';
                    $success_count++;
                    $results[] = $result;
                    continue;
                }
            }
            
            // Create group
            $group_data = array(
                'post_title' => 'Class: ' . $class->name,
                'post_content' => $class->description ?: 'LearnDash group for class: ' . $class->name,
                'post_status' => 'publish',
                'post_type' => 'groups'
            );
            
            $group_id = wp_insert_post($group_data);
            
            if (!is_wp_error($group_id) && $group_id > 0) {
                // Update class with group ID
                $wpdb->update(
                    $classes_table,
                    array('group_id' => $group_id),
                    array('id' => $class->id),
                    array('%d'),
                    array('%d')
                );
                
                // Assign teacher as leader
                if (function_exists('ld_update_leader_group_access')) {
                    ld_update_leader_group_access($class->teacher_id, $group_id, true);
                }
                
                $result['status'] = 'Group created successfully (ID: ' . $group_id . ')';
                $success_count++;
            } else {
                $result['status'] = 'Failed to create group';
            }
            
            $results[] = $result;
        }
        
        $message = sprintf(
            'Group creation completed: %d out of %d classes processed successfully.',
            $success_count,
            count($classes)
        );
        
        $message .= '<br><strong>Details:</strong><br>';
        foreach ($results as $result) {
            $message .= $result['class'] . ': ' . $result['status'] . '<br>';
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Test teacher group connections
     */
    public function ajax_test_teacher_group_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'school_manager_fixes')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        if (!function_exists('learndash_get_groups')) {
            wp_send_json_error(array('message' => 'LearnDash is not active'));
        }
        
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        $classes = $wpdb->get_results(
            "SELECT id, name, teacher_id, group_id 
             FROM $classes_table 
             WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
        );
        
        if (empty($classes)) {
            wp_send_json_error(array('message' => 'No classes with teachers and groups found'));
        }
        
        $results = array();
        $working_connections = 0;
        
        foreach ($classes as $class) {
            $teacher = get_user_by('id', $class->teacher_id);
            $group = get_post($class->group_id);
            
            $result = array(
                'class' => $class->name,
                'teacher' => $teacher ? $teacher->display_name : 'Unknown',
                'group_exists' => $group && $group->post_type === 'groups' ? 'Yes' : 'No'
            );
            
            if ($group && function_exists('learndash_get_groups_administrator_leaders')) {
                $leaders = learndash_get_groups_administrator_leaders($class->group_id);
                $is_leader = in_array($class->teacher_id, $leaders);
                $result['is_leader'] = $is_leader ? 'Yes' : 'No';
                
                if ($is_leader) {
                    $working_connections++;
                }
            } else {
                $result['is_leader'] = 'Cannot check';
            }
            
            $results[] = $result;
        }
        
        $message = sprintf(
            'Connection test completed: %d out of %d connections are working properly.',
            $working_connections,
            count($classes)
        );
        
        $message .= '<br><br><table border="1" style="border-collapse: collapse; width: 100%;">';
        $message .= '<tr><th>Class</th><th>Teacher</th><th>Group Exists</th><th>Is Leader</th></tr>';
        
        foreach ($results as $result) {
            $message .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $result['class'],
                $result['teacher'],
                $result['group_exists'],
                $result['is_leader']
            );
        }
        
        $message .= '</table>';
        
        wp_send_json_success(array('message' => $message));
    }
}

// Initialize
School_Manager_Lite_Basic_Fixes::instance();
