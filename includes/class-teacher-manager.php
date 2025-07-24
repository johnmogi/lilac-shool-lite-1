<?php
/**
 * Teacher Manager Class
 *
 * Handles all operations related to teachers
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Teacher_Manager {
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
        // Add dashboard widget for teachers
        add_action('wp_dashboard_setup', array($this, 'add_teacher_dashboard_widgets'));
        
        // Add menu items for teachers
        add_action('admin_menu', array($this, 'add_teacher_menu_items'));
    }

    /**
     * Get teachers
     *
     * @param array $args Query arguments
     * @return array Array of teachers (WP_User objects)
     */
    public function get_teachers($args = array()) {
        // Ensure teacher role exists
        $this->ensure_teacher_role_exists();
        
        $defaults = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => -1,
            'paged' => 1,
            'search' => '',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'count_total' => true, // For pagination
            'fields' => 'all_with_meta', // Return complete user objects
        );

        $args = wp_parse_args($args, $defaults);
        
        // Convert search into WordPress format if provided
        if (!empty($args['search'])) {
            $args['search'] = '*' . $args['search'] . '*';
        }
        
        // Define all teacher roles
        $teacher_roles = array(
            'school_teacher',
            'group_leader', 
            'instructor',
            'Instructor',
            'wdm_instructor',
            'stm_lms_instructor'
        );
        
        // If a specific role was requested, use it
        if (isset($args['role']) && in_array($args['role'], $teacher_roles)) {
            return get_users($args);
        }
        
        // Otherwise, get users with any teacher role
        unset($args['role']); // Remove role filter
        $args['role__in'] = $teacher_roles;
        
        $teachers = get_users($args);
        
        // Also check for users with group_leader meta keys (LearnDash compatibility)
        $meta_args = $args;
        unset($meta_args['role__in']);
        $meta_args['meta_query'] = array(
            array(
                'key' => 'wp_capabilities',
                'value' => 'group_leader',
                'compare' => 'LIKE'
            )
        );
        
        $meta_teachers = get_users($meta_args);
        
        // Merge and remove duplicates
        $all_teachers = array_merge($teachers, $meta_teachers);
        $unique_teachers = array();
        $seen_ids = array();
        
        foreach ($all_teachers as $teacher) {
            if (!in_array($teacher->ID, $seen_ids)) {
                $unique_teachers[] = $teacher;
                $seen_ids[] = $teacher->ID;
            }
        }
        
        // Apply class filter if specified
        if (isset($args['class_filter']) && $args['class_filter'] > 0) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $filtered_teachers = array();
            
            foreach ($unique_teachers as $teacher) {
                $teacher_classes = $class_manager->get_classes(array('teacher_id' => $teacher->ID));
                foreach ($teacher_classes as $class) {
                    if ($class->id == $args['class_filter']) {
                        $filtered_teachers[] = $teacher;
                        break; // Teacher found in this class, no need to check other classes
                    }
                }
            }
            
            return $filtered_teachers;
        }
        
        return $unique_teachers;
    }
    
    /**
     * Ensure teacher role exists and has proper capabilities
     */
    public function ensure_teacher_role_exists() {
        // Check if the role exists
        if (!get_role('school_teacher')) {
            // The role doesn't exist, so create it
            add_role(
                'school_teacher',
                __('School Teacher', 'school-manager-lite'),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => true,
                    'manage_school_classes' => true,
                    'manage_school_students' => true,
                    'access_school_content' => true,
                )
            );
        } else {
            // Ensure role has all required capabilities
            $teacher_role = get_role('school_teacher');
            $capabilities = array(
                'read' => true,
                'upload_files' => true,
                'manage_school_classes' => true,
                'manage_school_students' => true,
                'access_school_content' => true,
            );
            
            foreach ($capabilities as $cap => $grant) {
                if (!$teacher_role->has_cap($cap)) {
                    $teacher_role->add_cap($cap);
                }
            }
        }
        
        // Also add these capabilities to administrators
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_school_classes');
            $admin->add_cap('manage_school_students');
            $admin->add_cap('manage_school_promo_codes');
            $admin->add_cap('access_school_content');
        }
    }

    /**
     * Get teacher by ID
     *
     * @param int $teacher_id Teacher ID
     * @return WP_User|false Teacher object or false if not found
     */
    public function get_teacher($teacher_id) {
        $user = get_user_by('id', $teacher_id);
        
        if (!$user || !in_array('school_teacher', (array) $user->roles)) {
            return false;
        }
        
        return $user;
    }

    /**
     * Create teacher
     *
     * @param array $data Teacher data
     * @return int|WP_Error Teacher ID or WP_Error on failure
     */
    public function create_teacher($data) {
        $defaults = array(
            'role' => 'school_teacher',
            'user_pass' => wp_generate_password(12, true, true),
            'user_login' => '', // Will be set from phone number or email
            'user_email' => '',
            'first_name' => '',
            'last_name' => '',
            'display_name' => '',
            'phone' => '',
            'send_credentials' => false,
        );

        $data = wp_parse_args($data, $defaults);

        // Required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            return new WP_Error('missing_required', __('First name and last name are required', 'school-manager-lite'));
        }

        // Set user_login based on phone or email
        if (!empty($data['phone'])) {
            $data['user_login'] = $data['phone'];
        } elseif (!empty($data['user_email'])) {
            $data['user_login'] = $data['user_email'];
        } else {
            return new WP_Error('missing_login', __('Either phone number or email is required', 'school-manager-lite'));
        }

        // Set a default email if not provided
        if (empty($data['user_email'])) {
            $data['user_email'] = $data['user_login'] . '@example.com';
        }

        // Set display_name if not provided
        if (empty($data['display_name'])) {
            $data['display_name'] = $data['first_name'] . ' ' . $data['last_name'];
        }

        // Check if user already exists
        if (username_exists($data['user_login']) || email_exists($data['user_email'])) {
            return new WP_Error('user_exists', __('A user with this username or email already exists', 'school-manager-lite'));
        }

        // Create user data array
        $user_data = array(
            'user_login' => $data['user_login'],
            'user_pass' => $data['user_pass'],
            'user_email' => $data['user_email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'],
            'role' => $data['role']
        );

        // Insert user
        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Store additional user meta
        if (!empty($data['phone'])) {
            update_user_meta($user_id, 'phone_number', $data['phone']);
        }

        // Send welcome email if requested
        if ($data['send_credentials'] && !empty($data['user_email'])) {
            $this->send_teacher_credentials($user_id, $data['user_pass'], $data['user_email']);
        }

        do_action('school_manager_lite_after_create_teacher', $user_id, $data);

        return $user_id;
    }

    /**
     * Update teacher
     *
     * @param int $teacher_id Teacher ID
     * @param array $data Teacher data
     * @return int|WP_Error Teacher ID or WP_Error on failure
     */
    /**
     * Get quiz completion status for a student
     *
     * @param int $student_id Student ID
     * @param int $course_id Optional course ID to filter by
     * @return string HTML output with quiz completion status
     */
    public function get_student_quiz_status($student_id, $course_id = 0) {
        // Check if LearnDash is active
        if (!function_exists('learndash_get_course_quiz_list')) {
            return '<div class="notice notice-error"><p>' . __('LearnDash is required for quiz functionality', 'school-manager-lite') . '</p></div>';
        }

        $output = '';
        $quizzes_completed = 0;
        $total_quizzes = 0;
        $quiz_data = array();

        // Get all courses the student is enrolled in
        $enrolled_courses = array();
        
        if ($course_id) {
            // If specific course is provided, use only that
            $enrolled_courses = array($course_id);
        } else {
            // Get all enrolled courses
            if (function_exists('ld_get_mycourses')) {
                $enrolled_courses = ld_get_mycourses($student_id, array('fields' => 'ids'));
            }
        }

        if (empty($enrolled_courses)) {
            return '<div class="quiz-status"><p>' . __('No enrolled courses found.', 'school-manager-lite') . '</p></div>';
        }

        // Process each course
        foreach ($enrolled_courses as $course_id) {
            $course = get_post($course_id);
            if (!$course) continue;

            // Get all quizzes in the course
            $quizzes = learndash_get_course_quiz_list($course_id, $student_id);
            if (empty($quizzes)) continue;

            foreach ($quizzes as $quiz) {
                $quiz_id = $quiz['post']->ID;
                $quiz_title = get_the_title($quiz_id);
                $quiz_url = get_permalink($quiz_id);
                $attempts = learndash_get_quiz_attempt($student_id, $quiz_id);
                $score = 0;
                $status = 'not-started';
                $last_attempt = '';
                $pass = false;

                if (!empty($attempts)) {
                    // Get the most recent attempt
                    $attempt = end($attempts);
                    $last_attempt = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $attempt['time']);
                    
                    // Get the score
                    if (isset($attempt['score'])) {
                        $score = round($attempt['score'] * 100, 2) . '%';
                    } elseif (isset($attempt['percentage_earned'])) {
                        $score = round($attempt['percentage_earned'], 2) . '%';
                    }
                    
                    // Check if passed
                    $pass = (isset($attempt['pass']) && $attempt['pass'] == 1) || 
                            (isset($attempt['rank']) && $attempt['rank'] === 'passed');
                    
                    $status = $pass ? 'passed' : 'failed';
                    $quizzes_completed++;
                }

                $total_quizzes++;
                
                $quiz_data[] = array(
                    'course_id' => $course_id,
                    'course_title' => get_the_title($course_id),
                    'quiz_id' => $quiz_id,
                    'quiz_title' => $quiz_title,
                    'quiz_url' => $quiz_url,
                    'status' => $status,
                    'score' => $score,
                    'last_attempt' => $last_attempt,
                    'pass' => $pass
                );
            }
        }

        // Calculate completion percentage
        $completion_percentage = $total_quizzes > 0 ? round(($quizzes_completed / $total_quizzes) * 100) : 0;

        // Start building output
        ob_start();
        ?>
        <div class="school-manager-quiz-status">
            <div class="quiz-summary">
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo esc_attr($completion_percentage); ?>%;">
                        <span><?php echo esc_html($completion_percentage); ?>%</span>
                    </div>
                </div>
                <div class="quiz-stats">
                    <span class="stat"><?php 
                        echo sprintf(
                            _n('%d of %d quiz completed', '%d of %d quizzes completed', $total_quizzes, 'school-manager-lite'),
                            $quizzes_completed,
                            $total_quizzes
                        ); 
                    ?></span>
                    <span class="stat"><?php echo esc_html($completion_percentage); ?>% <?php _e('Complete', 'school-manager-lite'); ?></span>
                </div>
            </div>

            <?php if (!empty($quiz_data)) : ?>
                <div class="quiz-details">
                    <h4><?php _e('Quiz Details', 'school-manager-lite'); ?></h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Course', 'school-manager-lite'); ?></th>
                                <th><?php _e('Quiz', 'school-manager-lite'); ?></th>
                                <th><?php _e('Status', 'school-manager-lite'); ?></th>
                                <th><?php _e('Score', 'school-manager-lite'); ?></th>
                                <th><?php _e('Last Attempt', 'school-manager-lite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_data as $quiz) : 
                                $status_class = 'status-' . $quiz['status'];
                                $status_text = '';
                                
                                switch($quiz['status']) {
                                    case 'passed':
                                        $status_text = __('Passed', 'school-manager-lite');
                                        $status_icon = '✓';
                                        break;
                                    case 'failed':
                                        $status_text = __('Failed', 'school-manager-lite');
                                        $status_icon = '✗';
                                        break;
                                    default:
                                        $status_text = __('Not Started', 'school-manager-lite');
                                        $status_icon = '—';
                                }
                            ?>
                                <tr>
                                    <td><?php echo esc_html($quiz['course_title']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($quiz['quiz_url']); ?>" target="_blank">
                                            <?php echo esc_html($quiz['quiz_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="quiz-status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($status_text); ?>">
                                            <?php echo esc_html($status_icon); ?>
                                        </span>
                                        <?php echo esc_html($status_text); ?>
                                    </td>
                                    <td><?php echo $quiz['status'] !== 'not-started' ? esc_html($quiz['score']) : '—'; ?></td>
                                    <td><?php echo $quiz['last_attempt'] ? esc_html($quiz['last_attempt']) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p><?php _e('No quiz data found for this student.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
        </div>
        <style>
            .school-manager-quiz-status {
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .quiz-summary {
                background: #f8f9fa;
                border: 1px solid #e2e4e7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .progress-container {
                height: 24px;
                background: #e9ecef;
                border-radius: 12px;
                margin-bottom: 10px;
                overflow: hidden;
            }
            .progress-bar {
                height: 100%;
                background: #2271b1;
                color: white;
                text-align: center;
                line-height: 24px;
                font-size: 12px;
                font-weight: 600;
                transition: width 0.6s ease;
                min-width: 40px;
            }
            .quiz-stats {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                color: #646970;
            }
            .quiz-details h4 {
                margin: 20px 0 10px;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            .quiz-status-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                margin-right: 5px;
                font-size: 12px;
                line-height: 1;
            }
            .status-passed {
                background: #00a32a;
                color: white;
            }
            .status-failed {
                background: #d63638;
                color: white;
            }
            .status-not-started {
                background: #f0f0f1;
                color: #646970;
            }
            .wp-list-table th, .wp-list-table td {
                padding: 12px;
            }
        </style>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Update teacher
     *
     * @param int $teacher_id Teacher ID
     * @param array $data Teacher data
     * @return int|WP_Error Teacher ID or WP_Error on failure
     */
    public function update_teacher($teacher_id, $data) {
        $teacher = $this->get_teacher($teacher_id);

        if (!$teacher) {
            return new WP_Error('invalid_teacher', __('Invalid teacher ID', 'school-manager-lite'));
        }

        // Prepare user data
        $user_data = array(
            'ID' => $teacher_id
        );

        if (!empty($data['first_name'])) {
            $user_data['first_name'] = $data['first_name'];
        }

        if (!empty($data['last_name'])) {
            $user_data['last_name'] = $data['last_name'];
        }

        if (!empty($data['user_email'])) {
            $user_data['user_email'] = $data['user_email'];
        }

        if (!empty($data['display_name'])) {
            $user_data['display_name'] = $data['display_name'];
        } elseif (!empty($data['first_name']) && !empty($data['last_name'])) {
            $user_data['display_name'] = $data['first_name'] . ' ' . $data['last_name'];
        }

        // Update user
        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Update additional user meta
        if (!empty($data['phone'])) {
            update_user_meta($teacher_id, 'phone_number', $data['phone']);
        }

        do_action('school_manager_lite_after_update_teacher', $teacher_id, $data);

        return $teacher_id;
    }

    /**
     * Assign teacher role to existing user
     *
     * @param int $user_id User ID
     * @return WP_User|WP_Error User object or WP_Error on failure
     */
    public function assign_teacher_role($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid user ID', 'school-manager-lite'));
        }
        
        // Add teacher role
        $user->add_role('school_teacher');
        
        do_action('school_manager_lite_after_assign_teacher_role', $user_id);
        
        return $user;
    }
    
    /**
     * Remove teacher role from user
     *
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function remove_teacher_role($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        // Check if user has teacher role
        if (!in_array('school_teacher', (array) $user->roles)) {
            return false;
        }
        
        // Remove teacher role
        $user->remove_role('school_teacher');
        
        // Update any classes assigned to this teacher
        global $wpdb;
        $table_name = $wpdb->prefix . 'school_classes';
        
        $wpdb->update(
            $table_name,
            array('teacher_id' => 0),
            array('teacher_id' => $user_id),
            array('%d'),
            array('%d')
        );
        
        do_action('school_manager_lite_after_remove_teacher_role', $user_id);
        
        return true;
    }
    
    /**
     * Delete teacher
     *
     * @param int $teacher_id Teacher ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_teacher($teacher_id) {
        // Check if user exists
        $teacher = get_userdata($teacher_id);
        if (!$teacher) {
            return new WP_Error('teacher_not_found', __('Teacher not found.', 'school-manager-lite'));
        }

        // Check if user is actually a teacher
        $teacher_roles = array('school_teacher', 'group_leader', 'instructor', 'wdm_instructor', 'stm_lms_instructor');
        $user_roles = $teacher->roles;
        $is_teacher = false;
        
        foreach ($user_roles as $role) {
            if (in_array($role, $teacher_roles)) {
                $is_teacher = true;
                break;
            }
        }

        if (!$is_teacher) {
            return new WP_Error('not_a_teacher', __('The specified user is not a teacher.', 'school-manager-lite'));
        }

        // Run action before deletion
        do_action('school_manager_lite_before_delete_teacher', $teacher_id);

        // Remove teacher from all classes
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes(array('teacher_id' => $teacher_id));
        
        foreach ($classes as $class) {
            $class_manager->remove_teacher_from_class($teacher_id, $class->id);
        }

        // Delete the WordPress user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($teacher_id);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete teacher.', 'school-manager-lite'));
        }

        return true;
    }

    /**
     * Get student teacher
     *
     * @param int $student_id Student ID
     * @return WP_User|false Teacher object or false if not found
     */
    public function get_student_teacher($student_id) {
        global $wpdb;
        
        $student = get_user_by('id', $student_id);
        
        if (!$student || !in_array('student_private', (array) $student->roles)) {
            return false;
        }
        
        // Get the teacher assigned to this student via user meta
        $teacher_id = get_user_meta($student_id, 'school_teacher_id', true);
        
        // If no direct teacher assignment found, try to get a teacher via student's class
        if (!$teacher_id) {
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $student_classes = $student_manager->get_student_classes($student_id);
            
            if (!empty($student_classes) && isset($student_classes[0]->teacher_id) && $student_classes[0]->teacher_id > 0) {
                $teacher_id = $student_classes[0]->teacher_id;
            }
        }
        
        if (!$teacher_id) {
            return false;
        }
        
        return $this->get_teacher($teacher_id);
    }
    
    /**
     * Assign student to teacher
     *
     * @param int $student_id Student ID
     * @param int $teacher_id Teacher ID
     * @return bool True on success, false on failure
     */
    /**
     * Add dashboard widgets for teachers
     */
    public function add_teacher_dashboard_widgets() {
        // Only show for teachers
        if (!current_user_can('school_teacher') && !current_user_can('manage_options')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'school_manager_teacher_classes',
            __('My Classes', 'school-manager-lite'),
            array($this, 'display_teacher_classes_widget')
        );
        
        wp_add_dashboard_widget(
            'school_manager_teacher_students',
            __('My Students', 'school-manager-lite'),
            array($this, 'display_teacher_students_widget')
        );
    }
    
    /**
     * Display teacher classes widget
     */
    public function display_teacher_classes_widget() {
        $current_user_id = get_current_user_id();
        $classes = $this->get_teacher_classes($current_user_id);
        
        if (empty($classes)) {
            echo '<p>' . __('', 'school-manager-lite') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($classes as $class) {
            echo '<li><strong>' . esc_html($class->name) . '</strong>: ' . esc_html($class->description) . '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Display teacher students widget
     */
    public function display_teacher_students_widget() {
        $current_user_id = get_current_user_id();
        
        // Get classes for this teacher
        $classes = $this->get_teacher_classes($current_user_id);
        
        if (empty($classes)) {
            echo '<p>' . __('You have no students assigned.', 'school-manager-lite') . '</p>';
            return;
        }
        
        // Get students for each class
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $all_students = array();
        
        foreach ($classes as $class) {
            $students = $student_manager->get_students(array('class_id' => $class->id));
            if (!empty($students)) {
                foreach ($students as $student) {
                    $all_students[] = array(
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'class' => $class->name
                    );
                }
            }
        }
        
        if (empty($all_students)) {
            echo '<p>' . __('You have no students in your classes.', 'school-manager-lite') . '</p>';
            return;
        }
        
        echo '<table class="widefat fixed" style="margin-bottom:1em;">';
        echo '<thead><tr>';
        echo '<th>' . __('Name', 'school-manager-lite') . '</th>';
        echo '<th>' . __('Email', 'school-manager-lite') . '</th>';
        echo '<th>' . __('Class', 'school-manager-lite') . '</th>';
        echo '</tr></thead>';
        
        echo '<tbody>';
        foreach ($all_students as $student) {
            echo '<tr>';
            echo '<td>' . esc_html($student['name']) . '</td>';
            echo '<td>' . esc_html($student['email']) . '</td>';
            echo '<td>' . esc_html($student['class']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    /**
     * Add menu items for teachers
     */
    public function add_teacher_menu_items() {
        // Only show for teachers
        if (!current_user_can('school_teacher') && !current_user_can('manage_options')) {
            return;
        }
        
        // Remove old menu items if they exist
        remove_menu_page('class-management');
        remove_menu_page('school-classes');
        
        // Add main menu item for teachers
        $hook = add_menu_page(
            __('Teacher Dashboard', 'school-manager-lite'),
            __('Teacher Dashboard', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-dashboard',
            array($this, 'render_teacher_dashboard'),
            'dashicons-welcome-learn-more',
            30
        );
        
        // Add screen options for the teacher dashboard
        add_action("load-$hook", array($this, 'add_teacher_dashboard_screen_options'));
        
        // Add submenu items
        add_submenu_page(
            'school-teacher-dashboard',
            __('My Students', 'school-manager-lite'),
            __('My Students', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-students',
            array($this, 'render_teacher_students_page')
        );
        
        add_submenu_page(
            'school-teacher-dashboard',
            __('My Classes', 'school-manager-lite'),
            __('My Classes', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-classes',
            array($this, 'render_teacher_classes_page')
        );
        
        // Redirect old URLs to the new dashboard
        add_action('admin_init', array($this, 'redirect_old_teacher_urls'));
    }
    
    /**
     * Redirect old teacher URLs to the new dashboard
     */
    public function redirect_old_teacher_urls() {
        if (!is_admin() || !current_user_can('school_teacher')) {
            return;
        }
        
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $old_pages = array('class-management', 'school-classes');
        
        if (in_array($current_page, $old_pages)) {
            wp_redirect(admin_url('admin.php?page=school-teacher-dashboard'));
            exit;
        }
    }
    
    /**
     * Add screen options for the teacher dashboard
     */
    public function add_teacher_dashboard_screen_options() {
        $option = 'per_page';
        $args = array(
            'label' => __('Items per page', 'school-manager-lite'),
            'default' => 20,
            'option' => 'teacher_dashboard_per_page'
        );
        add_screen_option($option, $args);
    }
    
    /**
     * Render teacher dashboard
     */
    public function render_teacher_dashboard() {
        if (!current_user_can('school_teacher')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $teacher_id = get_current_user_id();
        $students = $this->get_teacher_students($teacher_id);
        $classes = $this->get_teacher_classes($teacher_id);
        ?>
        <div class="wrap">
            <h1><?php _e('Teacher Dashboard', 'school-manager-lite'); ?></h1>
            
            <div class="teacher-dashboard-content">
                <div class="teacher-stats">
                    <div class="stat-box">
                        <h3><?php _e('Total Students', 'school-manager-lite'); ?></h3>
                        <p class="stat-number"><?php echo count($students); ?></p>
                    </div>
                    
                    <div class="stat-box">
                        <h3><?php _e('Total Classes', 'school-manager-lite'); ?></h3>
                        <p class="stat-number"><?php echo count($classes); ?></p>
                    </div>
                </div>
                
                <div class="recent-students">
                    <h2><?php _e('Recent Students', 'school-manager-lite'); ?></h2>
                    <?php if (!empty($students)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Name', 'school-manager-lite'); ?></th>
                                    <th><?php _e('Email', 'school-manager-lite'); ?></th>
                                    <th><?php _e('Status', 'school-manager-lite'); ?></th>
                                    <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $student_manager = School_Manager_Lite_Student_Manager::instance();
                                $count = 0;
                                foreach ($students as $student) : 
                                    if ($count >= 5) break;
                                    $status = get_user_meta($student->ID, 'school_student_status', true);
                                    $status_label = $status === 'active' ? __('Active', 'school-manager-lite') : __('Inactive', 'school-manager-lite');
                                    $status_class = $status === 'active' ? 'status-active' : 'status-inactive';
                                    $count++;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($student->display_name); ?></td>
                                        <td><?php echo esc_html($student->user_email); ?></td>
                                        <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $student->ID)); ?>" class="button button-small">
                                                <?php _e('View Profile', 'school-manager-lite'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($students) > 5) : ?>
                            <p class="view-all">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=school-teacher-students')); ?>">
                                    <?php _e('View all students', 'school-manager-lite'); ?> &rarr;
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p><?php _e('No students assigned yet.', 'school-manager-lite'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <style>
            .teacher-dashboard-content { margin-top: 20px; }
            .teacher-stats { display: flex; gap: 20px; margin-bottom: 30px; }
            .stat-box { 
                background: #fff; 
                padding: 20px; 
                border-radius: 4px; 
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                flex: 1;
            }
            .stat-box h3 { margin: 0 0 10px 0; font-size: 14px; color: #646970; }
            .stat-number { font-size: 32px; font-weight: 600; margin: 0; line-height: 1; }
        <?php
    }
    
    /**
     * Render teacher students page
     */
    public function render_teacher_students_page() {
        if (!current_user_can('school_teacher')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $teacher_id = get_current_user_id();
        $students = $this->get_teacher_students($teacher_id);
        ?>
        <div class="wrap">
            <h1><?php _e('My Students', 'school-manager-lite'); ?></h1>
            
            <?php if (!empty($students)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'school-manager-lite'); ?></th>
                            <th><?php _e('Email', 'school-manager-lite'); ?></th>
                            <th><?php _e('Status', 'school-manager-lite'); ?></th>
                            <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $student_manager = School_Manager_Lite_Student_Manager::instance();
                        foreach ($students as $student) : 
                            $status = get_user_meta($student->ID, 'school_student_status', true);
                            $status_label = $status === 'active' ? __('Active', 'school-manager-lite') : __('Inactive', 'school-manager-lite');
                            $status_class = $status === 'active' ? 'status-active' : 'status-inactive';
                        ?>
                            <tr>
                                <td><?php echo esc_html($student->display_name); ?></td>
                                <td><?php echo esc_html($student->user_email); ?></td>
                                <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $student->ID)); ?>" class="button button-small">
                                        <?php _e('View Profile', 'school-manager-lite'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No students assigned to you yet.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render teacher classes page
     */
    public function render_teacher_classes_page() {
        if (!current_user_can('school_teacher')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $teacher_id = get_current_user_id();
        $classes = $this->get_teacher_classes($teacher_id);
        ?>
        <div class="wrap">
            <h1><?php _e('My Classes', 'school-manager-lite'); ?></h1>
            
            <?php if (!empty($classes)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Class Name', 'school-manager-lite'); ?></th>
                            <th><?php _e('Description', 'school-manager-lite'); ?></th>
                            <th><?php _e('Students', 'school-manager-lite'); ?></th>
                            <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        global $wpdb;
                        foreach ($classes as $class) : 
                            $student_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}school_teacher_students WHERE teacher_id = %d AND class_id = %d",
                                $teacher_id,
                                $class->id
                            ));
                        ?>
                            <tr>
                                <td><?php echo esc_html($class->name); ?></td>
                                <td><?php echo esc_html($class->description); ?></td>
                                <td><?php echo intval($student_count); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=school-teacher-students&class_id=' . $class->id)); ?>" class="button button-small">
                                        <?php _e('View Students', 'school-manager-lite'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No classes assigned to you yet.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render my students page
     */
    public function render_my_students_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('My Students', 'school-manager-lite') . '</h1>';
        
        $current_user_id = get_current_user_id();
        
        // Get classes for this teacher
        $classes = $this->get_teacher_classes($current_user_id);
        
        if (empty($classes)) {
            echo '<p>' . __('You have no students assigned.', 'school-manager-lite') . '</p>';
        } else {
            // Get students for each class
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $all_students = array();
            
            foreach ($classes as $class) {
                $students = $student_manager->get_students(array('class_id' => $class->id));
                if (!empty($students)) {
                    foreach ($students as $student) {
                        $all_students[] = array(
                            'id' => $student->id,
                            'name' => $student->name,
                            'email' => $student->email,
                            'class' => $class->name,
                            'status' => isset($student->status) ? $student->status : 'active'
                        );
                    }
                }
            }
            
            if (empty($all_students)) {
                echo '<p>' . __('You have no students in your classes.', 'school-manager-lite') . '</p>';
            } else {
                echo '<table class="widefat fixed" style="margin-top:1em;">';
                echo '<thead><tr>';
                echo '<th>' . __('Name', 'school-manager-lite') . '</th>';
                echo '<th>' . __('Email', 'school-manager-lite') . '</th>';
                echo '<th>' . __('Class', 'school-manager-lite') . '</th>';
                echo '<th>' . __('Status', 'school-manager-lite') . '</th>';
                echo '</tr></thead>';
                
                echo '<tbody>';
                foreach ($all_students as $student) {
                    echo '<tr>';
                    echo '<td>' . esc_html($student['name']) . '</td>';
                    echo '<td>' . esc_html($student['email']) . '</td>';
                    echo '<td>' . esc_html($student['class']) . '</td>';
                    echo '<td>' . esc_html($student['status']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        }
        
        echo '</div>';
    }

    public function assign_student_to_teacher($student_id, $teacher_id) {
        $student = get_user_by('id', $student_id);
        $teacher = $this->get_teacher($teacher_id);
        
        if (!$student || !$teacher) {
            return false;
        }
        
        // Store the assignment in user meta
        update_user_meta($student_id, 'school_teacher_id', $teacher_id);
        
        do_action('school_manager_lite_after_assign_student_to_teacher', $student_id, $teacher_id);
        
        return true;
    }
    
    /**
     * Get teacher classes
     *
     * @param int $teacher_id Teacher ID
     * @return array Array of class objects
     */
    public function get_teacher_classes($teacher_id) {
        global $wpdb;
        
        $teacher = $this->get_teacher($teacher_id);
        
        if (!$teacher) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'school_classes';
        
        $classes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE teacher_id = %d ORDER BY name ASC",
            $teacher_id
        ));
        
        return $classes;
    }

    /**
     * Send welcome email with login credentials
     *
     * @param int $user_id User ID
     * @param string $password User password
     * @param string $email User email
     * @return bool True on success, false on failure
     */
    private function send_teacher_credentials($user_id, $password, $email) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        $blog_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        $subject = sprintf(__('Your %s Teacher Account', 'school-manager-lite'), $blog_name);
        
        $message = sprintf(__('Hello %s,', 'school-manager-lite'), $user->first_name) . "\r\n\r\n";
        $message .= sprintf(__('A teacher account has been created for you on %s.', 'school-manager-lite'), $blog_name) . "\r\n\r\n";
        $message .= __('Your login details:', 'school-manager-lite') . "\r\n";
        $message .= __('Username: ', 'school-manager-lite') . $user->user_login . "\r\n";
        $message .= __('Password: ', 'school-manager-lite') . $password . "\r\n\r\n";
        $message .= __('You can log in here: ', 'school-manager-lite') . $login_url . "\r\n\r\n";
        $message .= __('For security reasons, please change your password after your first login.', 'school-manager-lite') . "\r\n\r\n";
        $message .= __('Best regards,', 'school-manager-lite') . "\r\n";
        $message .= $blog_name . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($email, $subject, $message, $headers);
    }
}

// Initialize the Teacher Manager
function School_Manager_Lite_Teacher_Manager() {
    return School_Manager_Lite_Teacher_Manager::instance();
}
