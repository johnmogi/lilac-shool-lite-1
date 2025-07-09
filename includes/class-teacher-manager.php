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
            'role' => 'school_teacher',
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
        
        // Get users with teacher role
        return get_users($args);
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
     * @return bool True on success, false on failure
     */
    public function delete_teacher($teacher_id) {
        $teacher = $this->get_teacher($teacher_id);

        if (!$teacher) {
            return false;
        }

        do_action('school_manager_lite_before_delete_teacher', $teacher_id);

        return wp_delete_user($teacher_id);
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
            echo '<p>' . __('You have no classes assigned.', 'school-manager-lite') . '</p>';
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
        
        add_menu_page(
            __('My Classes', 'school-manager-lite'),
            __('My Classes', 'school-manager-lite'),
            'school_teacher',
            'my-classes',
            array($this, 'render_my_classes_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'my-classes',
            __('My Students', 'school-manager-lite'),
            __('My Students', 'school-manager-lite'),
            'school_teacher',
            'my-students',
            array($this, 'render_my_students_page')
        );
    }
    
    /**
     * Render my classes page
     */
    public function render_my_classes_page() {
        global $wpdb;
        
        echo '<div class="wrap">';
        echo '<h1>' . __('My Classes', 'school-manager-lite') . '</h1>';
        
        $current_user_id = get_current_user_id();
        $classes = $this->get_teacher_classes($current_user_id);
        
        if (empty($classes)) {
            echo '<p>' . __('You have no classes assigned.', 'school-manager-lite') . '</p>';
        } else {
            echo '<table class="widefat fixed" style="margin-top:1em;">';
            echo '<thead><tr>';
            echo '<th>' . __('Class Name', 'school-manager-lite') . '</th>';
            echo '<th>' . __('Description', 'school-manager-lite') . '</th>';
            echo '<th>' . __('Students', 'school-manager-lite') . '</th>';
            echo '</tr></thead>';
            
            echo '<tbody>';
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            foreach ($classes as $class) {
                $student_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}school_students WHERE class_id = %d", $class->id));
                
                echo '<tr>';
                echo '<td>' . esc_html($class->name) . '</td>';
                echo '<td>' . esc_html($class->description) . '</td>';
                echo '<td>' . intval($student_count) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '</div>';
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
