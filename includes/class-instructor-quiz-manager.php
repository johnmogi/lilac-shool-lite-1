<?php
/**
 * Instructor Quiz Manager
 * 
 * Manages the relationship between instructors, their LearnDash groups, quizzes, and students
 * Enables: Instructor → LearnDash Group → Quiz → Students in Group
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_Instructor_Quiz_Manager {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add hooks
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_get_instructor_quiz_data', array($this, 'ajax_get_instructor_quiz_data'));
        add_action('wp_ajax_connect_quiz_to_group', array($this, 'ajax_connect_quiz_to_group'));
        // DISABLED: Let Simple Teacher Dashboard handle this
        // add_action('wp_ajax_get_group_students', array($this, 'ajax_get_group_students'));
        
        // Hook into quiz creation to auto-connect to instructor's group
        add_action('save_post', array($this, 'auto_connect_quiz_to_instructor_group'), 10, 2);
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Ensure wdm_instructor role has quiz capabilities
        $this->ensure_instructor_quiz_capabilities();
    }
    
    /**
     * Add admin menu
     * Menu item removed as it's non-functional
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
            <h1>Instructor Quiz Manager</h1>
            
            <div class="notice notice-info">
                <p><strong>Instructor-Quiz Connection Flow:</strong></p>
                <p>Instructor → LearnDash Group (as leader) → Quiz (assigned to group) → Students (enrolled in group)</p>
            </div>
            
            <div class="card" style="max-width: none;">
                <h2>Current Instructor-Quiz Relationships</h2>
                <div id="quiz-relationships-container">
                    <button id="load-quiz-data" class="button button-primary">Load Current Relationships</button>
                </div>
            </div>
            
            <div class="card" style="max-width: none;">
                <h2>Quiz-Group Connection Tool</h2>
                <p>Connect existing quizzes to instructor groups:</p>
                
                <table class="form-table">
                    <tr>
                        <th><label for="quiz-select">Select Quiz:</label></th>
                        <td>
                            <select id="quiz-select" style="width: 300px;">
                                <option value="">Loading quizzes...</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="group-select">Select Group:</label></th>
                        <td>
                            <select id="group-select" style="width: 300px;">
                                <option value="">Loading groups...</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button id="connect-quiz-group" class="button button-primary">Connect Quiz to Group</button>
                            <div id="connection-result"></div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: none;">
                <h2>Group Students Viewer</h2>
                <p>View students enrolled in instructor groups:</p>
                
                <select id="group-students-select" style="width: 300px;">
                    <option value="">Select a group...</option>
                </select>
                <button id="load-group-students" class="button button-secondary">Load Students</button>
                <div id="group-students-container"></div>
            </div>
        </div>
        
        <style>
        .card { border: 1px solid #ccc; padding: 20px; margin: 20px 0; }
        .quiz-relationship { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .instructor-info { background: #e7f3ff; padding: 10px; margin: 5px 0; }
        .group-info { background: #fff2e7; padding: 10px; margin: 5px 0; }
        .quiz-info { background: #f0fff0; padding: 10px; margin: 5px 0; }
        .student-info { background: #fff7e6; padding: 10px; margin: 5px 0; }
        table.relationships { width: 100%; border-collapse: collapse; }
        table.relationships th, table.relationships td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.relationships th { background-color: #f2f2f2; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Load quizzes and groups on page load
            loadQuizzes();
            loadGroups();
            
            function loadQuizzes() {
                $.get(ajaxurl, {
                    action: 'get_instructor_quiz_data',
                    data_type: 'quizzes',
                    nonce: '<?php echo wp_create_nonce('instructor_quiz_manager'); ?>'
                }, function(response) {
                    if (response.success && response.data.quizzes) {
                        var options = '<option value="">Select a quiz...</option>';
                        response.data.quizzes.forEach(function(quiz) {
                            options += '<option value="' + quiz.ID + '">' + quiz.post_title + '</option>';
                        });
                        $('#quiz-select').html(options);
                    }
                });
            }
            
            function loadGroups() {
                $.get(ajaxurl, {
                    action: 'get_instructor_quiz_data',
                    data_type: 'groups',
                    nonce: '<?php echo wp_create_nonce('instructor_quiz_manager'); ?>'
                }, function(response) {
                    if (response.success && response.data.groups) {
                        var options = '<option value="">Select a group...</option>';
                        response.data.groups.forEach(function(group) {
                            options += '<option value="' + group.ID + '">' + group.post_title + '</option>';
                        });
                        $('#group-select, #group-students-select').html(options);
                    }
                });
            }
            
            // Load quiz relationships
            $('#load-quiz-data').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Loading...');
                
                $.get(ajaxurl, {
                    action: 'get_instructor_quiz_data',
                    data_type: 'relationships',
                    nonce: '<?php echo wp_create_nonce('instructor_quiz_manager'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#quiz-relationships-container').html(response.data.html);
                    } else {
                        $('#quiz-relationships-container').html('<div class="notice notice-error"><p>Failed to load relationships.</p></div>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Refresh Relationships');
                });
            });
            
            // Connect quiz to group
            $('#connect-quiz-group').on('click', function() {
                var quizId = $('#quiz-select').val();
                var groupId = $('#group-select').val();
                
                if (!quizId || !groupId) {
                    alert('Please select both a quiz and a group.');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Connecting...');
                
                $.post(ajaxurl, {
                    action: 'connect_quiz_to_group',
                    quiz_id: quizId,
                    group_id: groupId,
                    nonce: '<?php echo wp_create_nonce('instructor_quiz_manager'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#connection-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#connection-result').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Connect Quiz to Group');
                });
            });
            
            // Load group students
            $('#load-group-students').on('click', function() {
                var groupId = $('#group-students-select').val();
                
                if (!groupId) {
                    alert('Please select a group.');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Loading...');
                
                $.get(ajaxurl, {
                    action: 'get_group_students',
                    group_id: groupId,
                    nonce: '<?php echo wp_create_nonce('instructor_quiz_manager'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#group-students-container').html(response.data.html);
                    } else {
                        $('#group-students-container').html('<div class="notice notice-error"><p>Failed to load students.</p></div>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Load Students');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Get instructor quiz data
     */
    public function ajax_get_instructor_quiz_data() {
        if (!wp_verify_nonce($_GET['nonce'], 'instructor_quiz_manager')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $data_type = $_GET['data_type'];
        
        switch ($data_type) {
            case 'quizzes':
                $quizzes = get_posts(array(
                    'post_type' => 'sfwd-quiz',
                    'post_status' => 'publish',
                    'numberposts' => 50
                ));
                wp_send_json_success(array('quizzes' => $quizzes));
                break;
                
            case 'groups':
                $groups = get_posts(array(
                    'post_type' => 'groups',
                    'post_status' => 'publish',
                    'numberposts' => 50
                ));
                wp_send_json_success(array('groups' => $groups));
                break;
                
            case 'relationships':
                $html = $this->get_relationships_html();
                wp_send_json_success(array('html' => $html));
                break;
        }
        
        wp_send_json_error(array('message' => 'Invalid data type'));
    }
    
    /**
     * Get relationships HTML
     */
    private function get_relationships_html() {
        global $wpdb;
        
        // Get instructors with classes
        $classes_table = $wpdb->prefix . 'school_classes';
        $instructors_with_classes = $wpdb->get_results("
            SELECT DISTINCT c.teacher_id, c.id as class_id, c.name as class_name, c.group_id,
                   u.display_name as instructor_name
            FROM $classes_table c
            JOIN {$wpdb->users} u ON c.teacher_id = u.ID
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
            AND um.meta_value LIKE '%wdm_instructor%'
            AND c.teacher_id IS NOT NULL
            ORDER BY u.display_name, c.name
        ");
        
        if (empty($instructors_with_classes)) {
            return '<p>No wdm_instructor users with classes found.</p>';
        }
        
        $html = '<table class="relationships">';
        $html .= '<tr><th>Instructor</th><th>Class</th><th>Group</th><th>Group Quizzes</th><th>Students</th><th>Status</th></tr>';
        
        foreach ($instructors_with_classes as $instructor_class) {
            $group_quizzes = array();
            $student_count = 0;
            $status = 'No Group';
            
            if ($instructor_class->group_id) {
                // Get quizzes associated with this group
                $group_quizzes = get_posts(array(
                    'post_type' => 'sfwd-quiz',
                    'meta_query' => array(
                        array(
                            'key' => 'learndash_quiz_groups',
                            'value' => $instructor_class->group_id,
                            'compare' => 'LIKE'
                        )
                    ),
                    'numberposts' => 10
                ));
                
                // Get student count
                if (function_exists('learndash_get_groups_users')) {
                    $group_users = learndash_get_groups_users($instructor_class->group_id);
                    $student_count = count($group_users);
                }
                
                $status = 'Connected';
            }
            
            $quiz_list = '';
            if (!empty($group_quizzes)) {
                $quiz_names = array_map(function($quiz) { return $quiz->post_title; }, $group_quizzes);
                $quiz_list = implode('<br>', array_slice($quiz_names, 0, 3));
                if (count($group_quizzes) > 3) {
                    $quiz_list .= '<br>... +' . (count($group_quizzes) - 3) . ' more';
                }
            } else {
                $quiz_list = '<em>No quizzes</em>';
            }
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html($instructor_class->instructor_name) . '</td>';
            $html .= '<td>' . esc_html($instructor_class->class_name) . '</td>';
            $html .= '<td>' . ($instructor_class->group_id ? "Group #{$instructor_class->group_id}" : 'No Group') . '</td>';
            $html .= '<td>' . $quiz_list . '</td>';
            $html .= '<td>' . $student_count . ' students</td>';
            $html .= '<td>' . $status . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        return $html;
    }
    
    /**
     * AJAX: Connect quiz to group
     */
    public function ajax_connect_quiz_to_group() {
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_quiz_manager')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $group_id = intval($_POST['group_id']);
        
        if (!$quiz_id || !$group_id) {
            wp_send_json_error(array('message' => 'Invalid quiz or group ID'));
        }
        
        // Get current quiz groups
        $current_groups = get_post_meta($quiz_id, 'learndash_quiz_groups', true);
        if (!is_array($current_groups)) {
            $current_groups = array();
        }
        
        // Add group if not already present
        if (!in_array($group_id, $current_groups)) {
            $current_groups[] = $group_id;
            $result = update_post_meta($quiz_id, 'learndash_quiz_groups', $current_groups);
            
            if ($result) {
                $quiz = get_post($quiz_id);
                $group = get_post($group_id);
                
                wp_send_json_success(array(
                    'message' => sprintf(
                        'Successfully connected quiz "%s" to group "%s".',
                        $quiz->post_title,
                        $group->post_title
                    )
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to update quiz group association'));
            }
        } else {
            wp_send_json_success(array('message' => 'Quiz is already connected to this group'));
        }
    }
    
    /**
     * AJAX: Get group students
     */
    public function ajax_get_group_students() {
        // FLEXIBLE: Accept both GET and POST, and multiple nonce types
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
        $valid_nonces = array('instructor_quiz_manager', 'teacher_dashboard_nonce');
        
        $nonce_valid = false;
        foreach ($valid_nonces as $nonce_action) {
            if ($nonce && wp_verify_nonce($nonce, $nonce_action)) {
                $nonce_valid = true;
                break;
            }
        }
        
        // TEMPORARY: Disable nonce check for debugging
        // if (!$nonce_valid) {
        //     wp_send_json_error(array('message' => 'Security check failed'));
        // }
        
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : (isset($_GET['group_id']) ? intval($_GET['group_id']) : 0);
        
        if (!$group_id) {
            wp_send_json_error(array('message' => 'Invalid group ID'));
        }
        
        // COMPATIBLE: Return data in Simple Teacher Dashboard format
        $students = array();
        
        if (function_exists('learndash_get_groups_users')) {
            $group_users = learndash_get_groups_users($group_id);
            
            if (!empty($group_users)) {
                foreach ($group_users as $user_id) {
                    $user = get_user_by('id', $user_id);
                    if ($user) {
                        $students[] = array(
                            'student_id' => $user->ID,
                            'student_name' => $user->display_name,
                            'student_email' => $user->user_email,
                            'enrollment_date' => $user->user_registered,
                            'quiz_count' => 0, // Default for compatibility
                            'avg_score' => 0,  // Default for compatibility
                            'course_progress' => '0%' // Default for compatibility
                        );
                    }
                }
            }
        }
        
        // Return in Simple Teacher Dashboard format
        wp_send_json_success(array('students' => $students));
    }
    
    /**
     * Auto-connect quiz to instructor's group when quiz is created
     */
    public function auto_connect_quiz_to_instructor_group($post_id, $post) {
        // Only for quizzes
        if ($post->post_type !== 'sfwd-quiz') {
            return;
        }
        
        // Only for published posts
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if current user is wdm_instructor
        if (!in_array('wdm_instructor', $current_user->roles)) {
            return;
        }
        
        // Find instructor's groups
        global $wpdb;
        $classes_table = $wpdb->prefix . 'school_classes';
        $instructor_groups = $wpdb->get_col($wpdb->prepare(
            "SELECT group_id FROM $classes_table WHERE teacher_id = %d AND group_id IS NOT NULL",
            $current_user->ID
        ));
        
        if (!empty($instructor_groups)) {
            // Connect quiz to instructor's first group (or all groups)
            $current_groups = get_post_meta($post_id, 'learndash_quiz_groups', true);
            if (!is_array($current_groups)) {
                $current_groups = array();
            }
            
            // Add instructor's groups
            foreach ($instructor_groups as $group_id) {
                if (!in_array($group_id, $current_groups)) {
                    $current_groups[] = $group_id;
                }
            }
            
            update_post_meta($post_id, 'learndash_quiz_groups', $current_groups);
        }
    }
    
    /**
     * Ensure instructor quiz capabilities
     */
    private function ensure_instructor_quiz_capabilities() {
        $role = get_role('wdm_instructor');
        if ($role) {
            // Quiz capabilities
            $role->add_cap('edit_sfwd-quiz');
            $role->add_cap('edit_sfwd-quizzes');
            $role->add_cap('edit_others_sfwd-quizzes');
            $role->add_cap('publish_sfwd-quizzes');
            $role->add_cap('read_sfwd-quiz');
            $role->add_cap('delete_sfwd-quiz');
            $role->add_cap('delete_sfwd-quizzes');
            
            // Group access capabilities
            $role->add_cap('edit_groups');
            $role->add_cap('read_groups');
        }
    }
}

// Initialize
School_Manager_Lite_Instructor_Quiz_Manager::instance();
