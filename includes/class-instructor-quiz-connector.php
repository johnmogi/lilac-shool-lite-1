<?php
/**
 * Instructor Quiz Connector
 * 
 * Connects wdm_instructor users to LearnDash groups and enables quiz creation
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_Instructor_Quiz_Connector {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_fix_instructor_leader_assignment', array($this, 'ajax_fix_instructor_leader_assignment'));
        add_action('wp_ajax_create_student_table', array($this, 'ajax_create_student_table'));
        add_action('wp_ajax_test_instructor_quiz_access', array($this, 'ajax_test_instructor_quiz_access'));
        
        // Hook to ensure instructors can create quizzes
        add_action('init', array($this, 'ensure_instructor_quiz_capabilities'));
    }
    
    /**
     * Add admin menu
     * Removed broken menu item - functionality may be available elsewhere
     */
    public function add_admin_menu() {
        // Menu item removed - functionality may be available in other admin pages
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Instructor Quiz Setup</h1>
            
            <div class="notice notice-info">
                <p><strong>Goal:</strong> Connect wdm_instructor users to LearnDash groups so they can create quizzes for their students.</p>
            </div>
            
            <div class="card">
                <h2>Step 1: Fix Instructor Leader Assignment</h2>
                <p>The test showed "Failed to assign teacher as leader". This will fix the LearnDash group leader assignment.</p>
                <button id="fix-leader-assignment" class="button button-primary">Fix Leader Assignment</button>
                <div id="leader-results"></div>
            </div>
            
            <div class="card">
                <h2>Step 2: Create Student-Classes Table</h2>
                <p>Missing student table prevents student enrollment. This will create the required table structure.</p>
                <button id="create-student-table" class="button button-primary">Create Student Table</button>
                <div id="table-results"></div>
            </div>
            
            <div class="card">
                <h2>Step 3: Test Instructor Quiz Access</h2>
                <p>Verify that wdm_instructor users can access their groups and create quizzes.</p>
                <button id="test-quiz-access" class="button button-secondary">Test Quiz Access</button>
                <div id="quiz-results"></div>
            </div>
            
            <div class="card">
                <h2>Current Status</h2>
                <?php $this->show_current_status(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            $('#fix-leader-assignment').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Fixing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fix_instructor_leader_assignment',
                        nonce: '<?php echo wp_create_nonce('instructor_quiz_setup'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#leader-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#leader-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#leader-results').html('<div class="notice notice-error"><p>Fix failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Fix Leader Assignment');
                    }
                });
            });
            
            $('#create-student-table').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Creating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_student_table',
                        nonce: '<?php echo wp_create_nonce('instructor_quiz_setup'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#table-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#table-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#table-results').html('<div class="notice notice-error"><p>Table creation failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Create Student Table');
                    }
                });
            });
            
            $('#test-quiz-access').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_instructor_quiz_access',
                        nonce: '<?php echo wp_create_nonce('instructor_quiz_setup'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#quiz-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#quiz-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#quiz-results').html('<div class="notice notice-error"><p>Test failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Quiz Access');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Show current status
     */
    public function show_current_status() {
        global $wpdb;
        
        // Get classes with teachers
        $classes_table = $wpdb->prefix . 'school_classes';
        $classes = $wpdb->get_results(
            "SELECT id, name, teacher_id, group_id FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0"
        );
        
        echo '<table class="wp-list-table widefat">';
        echo '<tr><th>Class</th><th>Teacher</th><th>Role</th><th>Group</th><th>Is Leader</th><th>Can Create Quiz</th></tr>';
        
        foreach ($classes as $class) {
            $teacher = get_user_by('id', $class->teacher_id);
            $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
            $teacher_roles = $teacher ? implode(', ', $teacher->roles) : 'None';
            
            $group_exists = false;
            $is_leader = false;
            $can_create_quiz = false;
            
            if ($class->group_id) {
                $group = get_post($class->group_id);
                $group_exists = $group && $group->post_type === 'groups';
                
                if ($group_exists && function_exists('learndash_get_groups_administrator_leaders')) {
                    $leaders = learndash_get_groups_administrator_leaders($class->group_id);
                    $is_leader = in_array($class->teacher_id, $leaders);
                }
                
                // Check if teacher can create quizzes
                if ($teacher && $is_leader) {
                    $can_create_quiz = user_can($teacher, 'edit_sfwd-quiz') || in_array('wdm_instructor', $teacher->roles);
                }
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($class->name) . '</td>';
            echo '<td>' . esc_html($teacher_name) . '</td>';
            echo '<td>' . esc_html($teacher_roles) . '</td>';
            echo '<td>' . ($group_exists ? "✓ Group #{$class->group_id}" : '✗ No group') . '</td>';
            echo '<td>' . ($is_leader ? '✓ Yes' : '✗ No') . '</td>';
            echo '<td>' . ($can_create_quiz ? '✓ Yes' : '✗ No') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Fix instructor leader assignment
     */
    public function ajax_fix_instructor_leader_assignment() {
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_quiz_setup')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        $classes = $wpdb->get_results(
            "SELECT id, name, teacher_id, group_id FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
        );
        
        if (empty($classes)) {
            wp_send_json_error(array('message' => 'No classes with teachers and groups found'));
        }
        
        $results = array();
        $success_count = 0;
        
        foreach ($classes as $class) {
            $teacher = get_user_by('id', $class->teacher_id);
            if (!$teacher) continue;
            
            $result = array('class' => $class->name, 'teacher' => $teacher->display_name);
            
            // Method 1: Try ld_update_leader_group_access
            if (function_exists('ld_update_leader_group_access')) {
                $leader_result = ld_update_leader_group_access($class->teacher_id, $class->group_id, true);
                if ($leader_result !== false) {
                    $result['method'] = 'ld_update_leader_group_access';
                    $result['status'] = 'Success';
                    $success_count++;
                    $results[] = $result;
                    continue;
                }
            }
            
            // Method 2: Try direct meta update
            $current_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
            if (!is_array($current_leaders)) {
                $current_leaders = array();
            }
            
            if (!in_array($class->teacher_id, $current_leaders)) {
                $current_leaders[] = $class->teacher_id;
                $meta_result = update_post_meta($class->group_id, 'learndash_group_leaders', $current_leaders);
                
                if ($meta_result) {
                    $result['method'] = 'direct_meta_update';
                    $result['status'] = 'Success';
                    $success_count++;
                } else {
                    $result['method'] = 'direct_meta_update';
                    $result['status'] = 'Failed';
                }
            } else {
                $result['method'] = 'already_leader';
                $result['status'] = 'Already assigned';
                $success_count++;
            }
            
            $results[] = $result;
        }
        
        $message = sprintf(
            'Leader assignment completed: %d out of %d assignments successful.',
            $success_count,
            count($classes)
        );
        
        $message .= '<br><br><strong>Details:</strong><br>';
        foreach ($results as $result) {
            $message .= sprintf(
                '%s (%s): %s via %s<br>',
                $result['class'],
                $result['teacher'],
                $result['status'],
                $result['method']
            );
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Create student table
     */
    public function ajax_create_student_table() {
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_quiz_setup')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_student_classes';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if ($table_exists) {
            wp_send_json_success(array('message' => 'Student-Classes table already exists.'));
            return;
        }
        
        // Create table
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            student_id int(11) NOT NULL,
            class_id int(11) NOT NULL,
            enrolled_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY class_id (class_id),
            UNIQUE KEY student_class (student_id, class_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Verify table was created
        $table_created = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if ($table_created) {
            wp_send_json_success(array('message' => 'Student-Classes table created successfully. You can now assign students to classes.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to create Student-Classes table.'));
        }
    }
    
    /**
     * Test instructor quiz access
     */
    public function ajax_test_instructor_quiz_access() {
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_quiz_setup')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Get wdm_instructor users
        $instructors = get_users(array('role' => 'wdm_instructor'));
        
        if (empty($instructors)) {
            wp_send_json_error(array('message' => 'No wdm_instructor users found'));
        }
        
        $results = array();
        $working_instructors = 0;
        
        foreach ($instructors as $instructor) {
            $result = array(
                'instructor' => $instructor->display_name,
                'id' => $instructor->ID
            );
            
            // Check if instructor is assigned to any classes
            global $wpdb;
            $classes_table = $wpdb->prefix . 'school_classes';
            $assigned_classes = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, group_id FROM $classes_table WHERE teacher_id = %d",
                $instructor->ID
            ));
            
            $result['assigned_classes'] = count($assigned_classes);
            
            if (empty($assigned_classes)) {
                $result['status'] = 'No classes assigned';
                $results[] = $result;
                continue;
            }
            
            // Check group leader status
            $is_leader_count = 0;
            foreach ($assigned_classes as $class) {
                if ($class->group_id && function_exists('learndash_get_groups_administrator_leaders')) {
                    $leaders = learndash_get_groups_administrator_leaders($class->group_id);
                    if (in_array($instructor->ID, $leaders)) {
                        $is_leader_count++;
                    }
                }
            }
            
            $result['group_leader_count'] = $is_leader_count;
            
            // Check quiz creation capability
            $can_create_quiz = user_can($instructor, 'edit_sfwd-quiz') || user_can($instructor, 'edit_posts');
            $result['can_create_quiz'] = $can_create_quiz ? 'Yes' : 'No';
            
            if ($is_leader_count > 0 && $can_create_quiz) {
                $result['status'] = 'Ready for quiz creation';
                $working_instructors++;
            } else {
                $result['status'] = 'Needs setup';
            }
            
            $results[] = $result;
            
            // Limit to first 10 for display
            if (count($results) >= 10) break;
        }
        
        $message = sprintf(
            'Quiz access test completed: %d out of %d instructors are ready for quiz creation.',
            $working_instructors,
            count($results)
        );
        
        $message .= '<br><br><table border="1" style="border-collapse: collapse; width: 100%;">';
        $message .= '<tr><th>Instructor</th><th>Classes</th><th>Group Leader</th><th>Can Create Quiz</th><th>Status</th></tr>';
        
        foreach ($results as $result) {
            $message .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%d</td><td>%s</td><td>%s</td></tr>',
                $result['instructor'],
                $result['assigned_classes'],
                $result['group_leader_count'],
                $result['can_create_quiz'],
                $result['status']
            );
        }
        
        $message .= '</table>';
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Ensure instructors have quiz capabilities
     */
    public function ensure_instructor_quiz_capabilities() {
        $role = get_role('wdm_instructor');
        if ($role) {
            // Add quiz creation capabilities
            $role->add_cap('edit_sfwd-quiz');
            $role->add_cap('edit_sfwd-quizzes');
            $role->add_cap('edit_others_sfwd-quizzes');
            $role->add_cap('publish_sfwd-quizzes');
            $role->add_cap('read_sfwd-quiz');
            $role->add_cap('delete_sfwd-quiz');
            $role->add_cap('delete_sfwd-quizzes');
        }
    }
}

// Initialize
School_Manager_Lite_Instructor_Quiz_Connector::instance();
