<?php
/**
 * Admin class
 *
 * Handles all admin functionality for the plugin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Admin {
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
        // Set up admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Handle admin actions
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_download_sample_csv', array($this, 'handle_download_sample_csv'));
        add_action('wp_ajax_quick_edit_student', array($this, 'handle_quick_edit_student'));
    }

    /**
     * Include required admin files
     */
    private function includes() {
        // Include teacher dashboard class
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-teacher-dashboard.php';
        
        // Initialize teacher dashboard
        School_Manager_Lite_Teacher_Dashboard::instance();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('School Manager', 'school-manager-lite'),
            __('School Manager', 'school-manager-lite'),
            'manage_options',
            'school-manager',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'school-manager',
            __('Dashboard', 'school-manager-lite'),
            __('Dashboard', 'school-manager-lite'),
            'manage_options',
            'school-manager',
            array($this, 'render_dashboard_page')
        );
        
        // Teachers
        add_submenu_page(
            'school-manager',
            __('Teachers', 'school-manager-lite'),
            __('Teachers', 'school-manager-lite'),
            'manage_options',
            'school-manager-teachers',
            array($this, 'render_teachers_page')
        );
        
        // Classes
        add_submenu_page(
            'school-manager',
            __('Classes', 'school-manager-lite'),
            __('Classes', 'school-manager-lite'),
            'manage_options',
            'school-manager-classes',
            array($this, 'render_classes_page')
        );
        
        // Students
        add_submenu_page(
            'school-manager',
            __('Students', 'school-manager-lite'),
            __('Students', 'school-manager-lite'),
            'manage_options',
            'school-manager-students',
            array($this, 'render_students_page')
        );
        
        // Promo Codes
        add_submenu_page(
            'school-manager',
            __('Promo Codes', 'school-manager-lite'),
            __('Promo Codes', 'school-manager-lite'),
            'manage_options',
            'school-manager-promo-codes',
            array($this, 'render_promo_codes_page')
        );
        
        // Import/Export
        add_submenu_page(
            'school-manager',
            __('Import/Export', 'school-manager-lite'),
            __('Import/Export', 'school-manager-lite'),
            'manage_options',
            'school-manager-import-export',
            array($this, 'render_import_export_page')
        );
        
        // Teacher's dashboard - accessible by teachers and admins
        add_menu_page(
            __('Teacher Dashboard', 'school-manager-lite'),
            __('Teacher Dashboard', 'school-manager-lite'),
            'access_school_content', // Use more general capability for teachers
            'school-teacher-dashboard',
            array('School_Manager_Lite_Teacher_Dashboard', 'render_dashboard_page'),
            'dashicons-groups',
            31
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'school-manager') === false) {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'school-manager-admin',
            SCHOOL_MANAGER_LITE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SCHOOL_MANAGER_LITE_VERSION
        );
        
        // Admin scripts
        wp_enqueue_script(
            'school-manager-admin',
            SCHOOL_MANAGER_LITE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SCHOOL_MANAGER_LITE_VERSION,
            true
        );
        
        // Localize script with AJAX URL and translations
        wp_localize_script(
            'school-manager-admin',
            'schoolManagerAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('school_manager_admin_nonce'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'school-manager-lite'),
                'importing' => __('Importing...', 'school-manager-lite'),
                'exporting' => __('Exporting...', 'school-manager-lite'),
            )
        );
        
        // Add thickbox for file uploads
        add_thickbox();
    }

    /**
     * Render the main admin page
     */
    public function render_dashboard_page() {
        // Get teacher manager instance to pass to the template
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Get class manager instance to pass to the template
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Get student manager instance to pass to the template
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $students = $student_manager->get_students();
        
        // Get promo code manager instance to pass to the template
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $promo_codes = $promo_code_manager->get_promo_codes();
        
        // Template path
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-dashboard.php';
    }

    /**
     * Render the teachers page
     */
    public function render_teachers_page() {
        // Get teacher manager instance
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Template path
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-teachers.php';
    }

    /**
     * Render the classes page
     */
    public function render_classes_page() {
        // Get class manager instance
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Template path
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-classes.php';
    }

    /**
     * Render the students page
     */
    public function render_students_page() {
        // Get student manager instance
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $students = $student_manager->get_students();
        
        // Template path
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-students.php';
    }

    /**
     * Render the promo codes page
     */
    public function render_promo_codes_page() {
        // Get promo code manager instance
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $promo_codes = $promo_code_manager->get_promo_codes();
        
        // Template path
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-promo-codes.php';
    }

    /**
     * Render the import/export page
     */
    public function render_import_export_page() {
        // Make sure Import/Export class is loaded
        if (!class_exists('School_Manager_Lite_Import_Export')) {
            require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-import-export.php';
        }
        
        // Initialize Import/Export handler
        $import_export = School_Manager_Lite_Import_Export::instance();
        
        // Include template
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-import-export.php';
    }
    
    /**
     * Handle quick edit AJAX requests for students
     */
    public function handle_quick_edit_student() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school_manager_quick_edit_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'school-manager-lite')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'school-manager-lite')));
        }
        
        // Get parameters
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
        
        if (empty($student_id)) {
            wp_send_json_error(array('message' => __('Invalid student ID', 'school-manager-lite')));
        }
        
        // Get student manager
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        
        // Update student
        $result = $student_manager->update_student($student_id, array(
            'class_id' => $class_id,
            'status' => $status
        ));
        
        // Handle result
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => __('Student updated successfully', 'school-manager-lite')));
        }
    }

    /**
     * Handle AJAX request to download sample CSV
     */
    public function handle_download_sample_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'school-manager-lite'));
        }
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $allowed_types = array('students', 'teachers', 'classes', 'promo-codes');
        
        if (!in_array($type, $allowed_types)) {
            wp_die(__('Invalid file type requested.', 'school-manager-lite'));
        }
        
        // Make sure Import/Export class is loaded
        if (!class_exists('School_Manager_Lite_Import_Export')) {
            require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-import-export.php';
        }
        
        // Generate the sample CSV
        $import_export = School_Manager_Lite_Import_Export::instance();
        $import_export->generate_sample_csv($type);
        
        // Safety exit - should not reach here
        wp_die();
    }

    /**
     * Handle admin actions like edit, view, delete for teachers, classes and students
     */
    public function handle_admin_actions() {
        // Handle student actions
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-students' && isset($_GET['action'])) {
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            
            // Handle student deletion
            if ($_GET['action'] === 'delete' && isset($_GET['student_id']) && check_admin_referer('delete_student')) {
                $student_id = intval($_GET['student_id']);
                $student_manager->delete_student($student_id, true);
                
                // Redirect back to students list
                wp_redirect(admin_url('admin.php?page=school-manager-students&deleted=1'));
                exit;
            }
        }
        
        // Handle import/export actions
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-import-export') {
            $import_export = School_Manager_Lite_Import_Export::instance();
        }
    }

    /**
     * AJAX handler for assigning a class to a student
     */
    public function ajax_assign_class_to_student() {
        // Check permissions
        if (!current_user_can('manage_school_students')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'school-manager-lite')));
        }
        
        // Verify nonce
        if (!isset($_POST['school_manager_assign_class_nonce']) || 
            !wp_verify_nonce($_POST['school_manager_assign_class_nonce'], 'school_manager_assign_class')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'school-manager-lite')));
        }
        
        // Get and validate parameters
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        
        if (!$student_id || !$class_id) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'school-manager-lite')));
        }
        
        // Perform the assignment
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Verify the student and class exist
        $student = get_user_by('id', $student_id);
        $class = $class_manager->get_class($class_id);
        
        if (!$student || !$class) {
            wp_send_json_error(array('message' => __('Invalid student or class.', 'school-manager-lite')));
        }
        
        // Assign student to class
        $result = $student_manager->assign_student_to_class($student_id, $class_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Student "%s" successfully assigned to class "%s".', 'school-manager-lite'),
                    $student->display_name,
                    $class->name
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to assign student to class.', 'school-manager-lite')));
        }
    }
    
    /**
     * AJAX handler for assigning a promo code to a student
     */
    public function ajax_assign_promo_to_student() {
        // Check permissions
        if (!current_user_can('manage_school_promo_codes')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'school-manager-lite')));
        }
        
        // Verify nonce
        if (!isset($_POST['school_manager_assign_promo_nonce']) || 
            !wp_verify_nonce($_POST['school_manager_assign_promo_nonce'], 'school_manager_assign_promo')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'school-manager-lite')));
        }
        
        // Get and validate parameters
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : 0;
        
        if (!$student_id || !$promo_id) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'school-manager-lite')));
        }
        
        // Perform the assignment
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        
        // Verify the student and promo code exist
        $student = get_user_by('id', $student_id);
        $promo_code = $promo_code_manager->get_promo_code($promo_id);
        
        if (!$student || !$promo_code) {
            wp_send_json_error(array('message' => __('Invalid student or promo code.', 'school-manager-lite')));
        }
        
        // Assign promo code to student
        $result = $promo_code_manager->assign_promo_code_to_student($promo_id, $student_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Promo code "%s" successfully assigned to student "%s".', 'school-manager-lite'),
                    $promo_code->code,
                    $student->display_name
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to assign promo code to student.', 'school-manager-lite')));
        }
    }
    
    /**
     * AJAX handler for Quick Edit student
     */
    public function ajax_quick_edit_student() {
        // Check permissions
        if (!current_user_can('manage_school_students')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'school-manager-lite')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school_manager_quick_edit_student')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'school-manager-lite')));
        }
        
        // Get and validate parameters
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
        
        if (!$student_id) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'school-manager-lite')));
        }
        
        // Get managers
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Verify the student exists
        $user = get_user_by('id', $student_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('Invalid student.', 'school-manager-lite')));
        }
        
        // Get current student data from our custom table, and create it if it doesn't exist
        $student = $student_manager->get_student_by_user_id($student_id, 0, true);
        if (!$student) {
            // Create a new student record in the custom table if possible
            $wp_user = get_user_by('id', $student_id);
            if ($wp_user) {
                // Create basic student record
                $new_student_data = array(
                    'wp_user_id' => $student_id,
                    'name' => $wp_user->display_name,
                    'email' => $wp_user->user_email,
                    'status' => 'active',
                );
                
                $student_id_in_table = $student_manager->create_student($new_student_data);
                if (!is_wp_error($student_id_in_table)) {
                    $student = $student_manager->get_student($student_id_in_table);
                    error_log("Created missing student record for WP user ID: {$student_id}");
                }
            }
            
            // If still no student record, return error
            if (!$student) {
                wp_send_json_error(array('message' => __('Student not found in school system and could not be created.', 'school-manager-lite')));
            }
        }
        
        $result = true;
        $messages = array();
        
        // Update student status if needed
        if ($status !== $student->status) {
            // Update user status in the database
            $update_result = $student_manager->update_student($student->id, array('status' => $status));
            
            if ($update_result) {
                $messages[] = __('Student status updated.', 'school-manager-lite');
            } else {
                $result = false;
                $messages[] = __('Failed to update student status.', 'school-manager-lite');
            }
        }
        
        // Handle class assignment if a class was selected
        if ($class_id > 0) {
            // Check if class exists
            $class = $class_manager->get_class($class_id);
            if (!$class) {
                wp_send_json_error(array('message' => __('Invalid class selected.', 'school-manager-lite')));
                return;
            }
            
            // Get current student classes
            $current_classes = $student_manager->get_student_classes($student_id);
            $current_class_ids = array_map(function($c) { return $c->id; }, $current_classes);
            
            // Only assign if not already assigned
            if (!in_array($class_id, $current_class_ids)) {
                $assign_result = $student_manager->assign_student_to_class($student_id, $class_id);
                
                if ($assign_result) {
                    $messages[] = sprintf(
                        __('Student assigned to class "%s".', 'school-manager-lite'),
                        $class->name
                    );
                } else {
                    $result = false;
                    $messages[] = __('Failed to assign student to class.', 'school-manager-lite');
                }
            }
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => implode(' ', $messages) ?: __('Student updated successfully.', 'school-manager-lite')
            ));
        } else {
            wp_send_json_error(array('message' => implode(' ', $messages) ?: __('Failed to update student.', 'school-manager-lite')));
        }
    }
}
