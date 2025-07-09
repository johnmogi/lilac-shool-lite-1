<?php
/**
 * Teacher Dashboard
 *
 * Handles the teacher dashboard functionality within WordPress admin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Teacher_Dashboard {
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
        // Add actions and filters
        add_action('wp_dashboard_setup', array($this, 'add_teacher_dashboard_widgets'));
        add_action('admin_menu', array($this, 'register_teacher_submenu_pages'));
        add_action('admin_init', array($this, 'restrict_teacher_access'));
        add_filter('login_redirect', array($this, 'teacher_login_redirect'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_school_get_class_students', array($this, 'ajax_get_class_students'));
    }
    
    /**
     * Add teacher dashboard widgets to WP dashboard
     */
    public function add_teacher_dashboard_widgets() {
        // Only add for teachers
        if (!current_user_can('school_teacher')) {
            return;
        }
        
        // Remove default WordPress dashboard widgets for teachers
        $this->remove_default_dashboard_widgets();
        
        // Add our custom widgets
        wp_add_dashboard_widget(
            'school_teacher_classes_summary',
            __('My Classes', 'school-manager-lite'),
            array($this, 'render_classes_widget')
        );
        
        wp_add_dashboard_widget(
            'school_teacher_students_summary',
            __('My Students', 'school-manager-lite'),
            array($this, 'render_students_widget')
        );
    }
    
    /**
     * Remove default dashboard widgets for teachers
     */
    private function remove_default_dashboard_widgets() {
        // Only for teachers, not admins
        if (current_user_can('manage_options')) {
            return;
        }
        
        // Remove welcome panel
        remove_action('welcome_panel', 'wp_welcome_panel');
        
        global $wp_meta_boxes;
        
        // Remove WordPress news widget
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
        
        // Remove Quick Draft
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
        
        // Remove Activity widget
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
        
        // Remove Right Now widget
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
    }
    
    /**
     * Register submenu pages for teachers
     */
    public function register_teacher_submenu_pages() {
        // Add submenu to teacher dashboard
        add_submenu_page(
            'school-teacher-dashboard',
            __('My Classes', 'school-manager-lite'),
            __('My Classes', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-classes',
            array($this, 'render_classes_page')
        );
        
        add_submenu_page(
            'school-teacher-dashboard',
            __('My Students', 'school-manager-lite'),
            __('My Students', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-students',
            array($this, 'render_students_page')
        );
        
        add_submenu_page(
            'school-teacher-dashboard',
            __('Promo Codes', 'school-manager-lite'),
            __('Promo Codes', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-promo-codes',
            array($this, 'render_promo_codes_page')
        );
    }
    
    /**
     * Restrict teachers from accessing other admin pages
     */
    public function restrict_teacher_access() {
        // Only apply to teachers who are not admins
        if (!current_user_can('school_teacher') || current_user_can('manage_options')) {
            return;
        }
        
        global $pagenow;
        
        // Allow these pages
        $allowed_pages = array(
            'index.php', // Dashboard
            'profile.php', // Profile
            'admin.php', // Custom admin pages
            'admin-ajax.php', // AJAX calls
        );
        
        // If on an allowed page but admin.php needs further checking
        if ($pagenow === 'admin.php') {
            $allowed_admin_pages = array(
                'school-teacher-dashboard',
                'school-teacher-classes',
                'school-teacher-students',
                'school-teacher-promo-codes'
            );
            
            // Check if page parameter is allowed
            $page = isset($_GET['page']) ? $_GET['page'] : '';
            if (!in_array($page, $allowed_admin_pages)) {
                wp_redirect(admin_url('admin.php?page=school-teacher-dashboard'));
                exit;
            }
        }
        // If not on allowed pages, redirect to teacher dashboard
        elseif (!in_array($pagenow, $allowed_pages)) {
            wp_redirect(admin_url('admin.php?page=school-teacher-dashboard'));
            exit;
        }
    }
    
    /**
     * Redirect teachers to their dashboard after login
     */
    public function teacher_login_redirect($redirect_to, $request, $user) {
        // If user exists
        if (isset($user->roles) && is_array($user->roles)) {
            // If user is a teacher and not an admin
            if (in_array('school_teacher', $user->roles) && !in_array('administrator', $user->roles)) {
                return admin_url('admin.php?page=school-teacher-dashboard');
            }
        }
        return $redirect_to;
    }
    
    /**
     * Get current teacher ID
     */
    private function get_current_teacher_id() {
        $current_user = wp_get_current_user();
        return $current_user->ID;
    }
    
    /**
     * Render the main dashboard page
     */
    public static function render_dashboard_page() {
        $instance = self::instance();
        $teacher_id = $instance->get_current_teacher_id();
        
        // Get teacher manager
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teacher = get_user_by('id', $teacher_id);
        
        // Get class manager and student manager
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Count students
        $total_students = 0;
        $class_stats = array();
        
        foreach ($classes as $class) {
            $students = $class_manager->get_students_in_class($class->id);
            $student_count = count($students);
            $total_students += $student_count;
            
            $class_stats[] = array(
                'id' => $class->id,
                'name' => $class->name,
                'student_count' => $student_count
            );
        }
        
        // Include dashboard template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/teacher-dashboard.php';
    }
    
    /**
     * Render classes widget in dashboard
     */
    public function render_classes_widget() {
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Get total class count
        $class_count = count($classes);
        
        // Include widget template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/dashboard-widgets/classes.php';
    }
    
    /**
     * Render students widget in dashboard
     */
    public function render_students_widget() {
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Count total students
        $total_students = 0;
        $recent_students = array();
        
        foreach ($classes as $class) {
            $students = $class_manager->get_students_in_class($class->id);
            $total_students += count($students);
            
            // Get 5 most recent students
            foreach ($students as $student) {
                $student_obj = $student_manager->get_student($student->id);
                if ($student_obj) {
                    $recent_students[] = $student_obj;
                }
            }
        }
        
        // Sort by most recent
        usort($recent_students, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        // Limit to 5
        $recent_students = array_slice($recent_students, 0, 5);
        
        // Include widget template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/dashboard-widgets/students.php';
    }
    
    /**
     * Render classes page
     */
    public function render_classes_page() {
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Include template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/teacher-classes.php';
    }
    
    /**
     * Render students page
     */
    public function render_students_page() {
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Get class filter from URL
        $filter_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
        
        // Get all students for this teacher's classes
        $students = array();
        
        foreach ($classes as $class) {
            // If filtering by class, skip other classes
            if ($filter_class_id > 0 && $class->id != $filter_class_id) {
                continue;
            }
            
            $class_students = $class_manager->get_students_in_class($class->id);
            
            foreach ($class_students as $student) {
                $student_obj = $student_manager->get_student($student->id);
                if ($student_obj) {
                    $student_obj->class_name = $class->name;
                    $student_obj->class_id = $class->id;
                    $students[] = $student_obj;
                }
            }
        }
        
        // Include template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/teacher-students.php';
    }
    
    /**
     * Render promo codes page
     */
    public function render_promo_codes_page() {
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        
        // Get classes for this teacher
        $classes = $class_manager->get_classes(array(
            'teacher_id' => $teacher_id
        ));
        
        // Get class filter from URL
        $filter_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
        
        // Get promo codes for this teacher
        $promo_codes = $promo_code_manager->get_promo_codes(array(
            'teacher_id' => $teacher_id,
            'class_id' => $filter_class_id > 0 ? $filter_class_id : null
        ));
        
        // Include template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/teacher-promo-codes.php';
    }
    
    /**
     * AJAX handler for getting students in a class
     */
    public function ajax_get_class_students() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school-manager-lite-admin')) {
            wp_send_json_error(array('message' => __('Security check failed', 'school-manager-lite')));
        }
        
        // Check teacher permissions
        if (!current_user_can('school_teacher')) {
            wp_send_json_error(array('message' => __('Permission denied', 'school-manager-lite')));
        }
        
        // Get class ID
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        
        if ($class_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid class ID', 'school-manager-lite')));
        }
        
        // Verify teacher has access to this class
        $teacher_id = $this->get_current_teacher_id();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($class_id);
        
        if (!$class || $class->teacher_id != $teacher_id) {
            wp_send_json_error(array('message' => __('You do not have permission to view this class', 'school-manager-lite')));
        }
        
        // Get students
        $students = $class_manager->get_students_in_class($class_id);
        
        // Get student details
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_data = array();
        
        foreach ($students as $student) {
            $student_obj = $student_manager->get_student($student->id);
            if ($student_obj) {
                $student_data[] = array(
                    'id' => $student_obj->id,
                    'name' => $student_obj->name,
                    'email' => $student_obj->email,
                    'created_at' => $student_obj->created_at
                );
            }
        }
        
        wp_send_json_success(array(
            'students' => $student_data,
            'count' => count($student_data)
        ));
    }
}
