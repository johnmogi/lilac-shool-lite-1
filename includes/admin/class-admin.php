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
     * Admin notices instance
     *
     * @var School_Manager_Lite_Admin_Notices|null
     */
    protected $notices = null;

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
        add_action('wp_ajax_add_class_to_student', array($this, 'ajax_assign_class_to_student'));
        add_action('wp_ajax_bulk_assign_teacher', array($this, 'ajax_bulk_assign_teacher'));
        add_action('wp_ajax_bulk_assign_students', array($this, 'ajax_bulk_assign_students'));
        add_action('wp_ajax_assign_promo_to_student', array($this, 'ajax_assign_promo_to_student'));
        add_action('wp_ajax_quick_edit_student', array($this, 'ajax_quick_edit_student'));
        
        // Include required files
        $this->includes();
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on school manager admin pages
        if (!strpos($hook, 'school-manager')) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'school-manager-admin',
            SCHOOL_MANAGER_LITE_URL . 'assets/css/admin.css',
            array(),
            SCHOOL_MANAGER_LITE_VERSION
        );
        
        // Enqueue enhanced admin styles for UI improvements
        wp_enqueue_style(
            'school-manager-admin-enhanced',
            SCHOOL_MANAGER_LITE_URL . 'assets/css/admin-style.css',
            array(),
            SCHOOL_MANAGER_LITE_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'school-manager-admin',
            SCHOOL_MANAGER_LITE_URL . 'assets/js/admin.js',
            array('jquery'),
            SCHOOL_MANAGER_LITE_VERSION,
            true
        );
        
        // Enqueue class list specific scripts
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-classes' && 
            (!isset($_GET['action']) || $_GET['action'] !== 'edit')) {
            wp_enqueue_script(
                'school-manager-class-teacher-assignment',
                SCHOOL_MANAGER_LITE_URL . 'assets/js/class-teacher-assignment.js',
                array('jquery'),
                SCHOOL_MANAGER_LITE_VERSION,
                true
            );
        }
        
        // Enqueue class edit specific scripts
        if (strpos($hook, 'page_school-manager-class-edit') !== false) {
            wp_enqueue_script(
                'school-manager-class-edit',
                SCHOOL_MANAGER_LITE_URL . 'assets/js/class-edit.js',
                array('jquery'),
                SCHOOL_MANAGER_LITE_VERSION,
                true
            );
            
            wp_enqueue_script(
                'school-manager-student-assignment',
                SCHOOL_MANAGER_LITE_URL . 'assets/js/student-assignment.js',
                array('jquery'),
                SCHOOL_MANAGER_LITE_VERSION,
                true
            );
        }
        
        // Localize script with AJAX URL and translations
        wp_localize_script(
            'school-manager-admin',
            'schoolManagerAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('school_manager_lite_admin_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'school-manager-lite'),
                    'confirm_bulk_assign' => __('Are you sure you want to assign this teacher to the selected classes?', 'school-manager-lite'),
                    'confirm_remove_student' => __('Are you sure you want to remove this student from the class?', 'school-manager-lite'),
                    'select_teacher_first' => __('Please select a teacher first.', 'school-manager-lite'),
                    'select_classes_first' => __('Please select at least one class.', 'school-manager-lite'),
                    'select_students_first' => __('Please select at least one student.', 'school-manager-lite'),
                    'assigning_teacher' => __('Assigning teacher...', 'school-manager-lite'),
                    'processing' => __('Processing...', 'school-manager-lite'),
                    'add_selected_students' => __('Add Selected Students', 'school-manager-lite'),
                    'server_error' => __('Server error occurred. Please try again.', 'school-manager-lite'),
                    'error' => __('An error occurred. Please try again.', 'school-manager-lite'),
                    'saving' => __('Saving...', 'school-manager-lite'),
                    'saved' => __('Saved!', 'school-manager-lite'),
                    'importing' => __('Importing...', 'school-manager-lite'),
                    'exporting' => __('Exporting...', 'school-manager-lite'),
                )
            )
        );
        
        // Add thickbox for file uploads
        add_thickbox();
    }

    /**
     * Include required admin files
     */
    public function includes() {
        // Include admin classes
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-admin-notices.php';
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-students-list-table.php';
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-teachers-list-table.php';
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-classes-list-table.php';
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-promo-codes-list-table.php';
        
        // Initialize the notices system
        $this->notices = School_Manager_Lite_Admin_Notices::instance();
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
        
        // Promo Codes list
        add_submenu_page(
            'school-manager',
            __('Promo Codes', 'school-manager-lite'),
            __('Promo Codes', 'school-manager-lite'),
            'access_school_content',
            'school-manager-promo-codes',
            array($this, 'render_promo_codes_page')
        );
        // Promo Codes Generator
        add_submenu_page(
            'school-manager',
            __('Generate Promo Codes', 'school-manager-lite'),
            __('Generate Codes', 'school-manager-lite'),
            'access_school_content',
            'school-manager-promo-generate',
            array($this, 'render_promo_generate_page')
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
    /**
     * Render promo code generator page
     */
    public function render_promo_generate_page() {
        require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/admin-promo-generate.php';
    }

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
            
            // Handle student deletion (single row)
            if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
                $student_id = intval($_GET['id']);

                // Verify nonce matches action delete_student_<ID>
                $nonce_action = 'delete_student_' . $student_id;
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
                    wp_die(__('Security check failed.', 'school-manager-lite'));
                }

                // Map WP user ID to internal student table ID if necessary
                $student_row = $student_manager->get_student_by_wp_user_id($student_id);
                if ($student_row) {
                    $result = $student_manager->delete_student($student_row->id);
                } else {
                    // Fall back to assuming ID is already student table ID
                    $result = $student_manager->delete_student($student_id);
                }
                if (is_wp_error($result)) {
                    wp_die($result->get_error_message());
                }

                // Redirect back to students list
                wp_redirect(admin_url('admin.php?page=school-manager-students&deleted=1'));
                exit;
            }
        }
        
        // Handle class actions
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-classes' && isset($_GET['action'])) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            
            // Handle class edit action
            if ($_GET['action'] === 'edit' && isset($_GET['id'])) {
                $class_id = intval($_GET['id']);
                $class = $class_manager->get_class($class_id);
                
                if (!$class) {
                    wp_die(__('Class not found.', 'school-manager-lite'));
                }
                
                // Get teachers for dropdown
                $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
                $teachers = $teacher_manager->get_teachers();
                
                // Include edit template
                require_once SCHOOL_MANAGER_LITE_PLUGIN_DIR . 'templates/admin/edit-class.php';
                exit;
            }
            
            // Handle class delete action
            if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
                $class_id = intval($_GET['id']);
                
                // Verify nonce
                $nonce_action = 'delete_class_' . $class_id;
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
                    wp_die(__('Security check failed.', 'school-manager-lite'));
                }
                
                // Delete class
                $result = $class_manager->delete_class($class_id);
                
                if (is_wp_error($result)) {
                    wp_die($result->get_error_message());
                }
                
                // Redirect back to classes list
                wp_redirect(admin_url('admin.php?page=school-manager-classes&deleted=1'));
                exit;
            }
        }
        
        // Handle import/export actions
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-import-export') {
            $import_export = School_Manager_Lite_Import_Export::instance();
        }
        
        // Process adding students to class from edit class page
        $this->process_add_students_to_class();
        
        // Process removing student from class
        $this->process_remove_student_from_class();
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
        $student = $student_manager->get_student($student_id);
        $class = $class_manager->get_class($class_id);
        
        if (!$student || !$class) {
            wp_send_json_error(array('message' => __('Student or class not found.', 'school-manager-lite')));
        }
        
        // Add student to class
        $result = $student_manager->add_student_to_class($student->wp_user_id, $class_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Student %s successfully assigned to class %s.', 'school-manager-lite'), $student->name, $class->name)
        ));
    }
    
    /**
     * Handle adding students to a class from the edit class page
     */
    public function process_add_students_to_class() {
        // Check if we're processing the form submission
        if (isset($_POST['add_students_to_class']) && isset($_POST['_wpnonce'])) {
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            
            // Verify nonce
            if (!wp_verify_nonce($_POST['_wpnonce'], 'add_students_to_class_' . $class_id)) {
                wp_die(__('Security check failed.', 'school-manager-lite'));
            }
            
            // Check permissions
            if (!current_user_can('manage_school_students')) {
                wp_die(__('You do not have permission to perform this action.', 'school-manager-lite'));
            }
            
            // Check if class exists
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class = $class_manager->get_class($class_id);
            
            if (!$class) {
                wp_die(__('Invalid class.', 'school-manager-lite'));
            }
            
            // Get selected student IDs
            $student_ids = isset($_POST['student_ids']) ? (array) $_POST['student_ids'] : array();
            $student_ids = array_map('intval', $student_ids);
            
            if (empty($student_ids)) {
                // Redirect back with error message
                $redirect_url = add_query_arg(array(
                    'page' => 'school-manager-classes',
                    'action' => 'edit',
                    'id' => $class_id,
                    'error' => 'no_students_selected'
                ), admin_url('admin.php'));
                
                wp_redirect($redirect_url);
                exit;
            }
            
            // Add students to class
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $success_count = 0;
            $error_count = 0;
            
            foreach ($student_ids as $student_id) {
                $student = $student_manager->get_student($student_id);
                
                if ($student && $student->wp_user_id) {
                    $result = $student_manager->add_student_to_class($student->wp_user_id, $class_id);
                    
                    if (!is_wp_error($result)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++;
                }
            }
            
            // Redirect back with success/error message
            $redirect_url = add_query_arg(array(
                'page' => 'school-manager-classes',
                'action' => 'edit',
                'id' => $class_id,
                'added' => $success_count,
                'errors' => $error_count
            ), admin_url('admin.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Handle removing a student from a class
     */
    public function process_remove_student_from_class() {
        // Check if we're processing a student removal
        if (isset($_GET['action']) && $_GET['action'] === 'remove_student' && isset($_GET['class_id']) && isset($_GET['student_id'])) {
            $class_id = intval($_GET['class_id']);
            $student_id = intval($_GET['student_id']);
            
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'remove_student_' . $student_id)) {
                wp_die(__('Security check failed.', 'school-manager-lite'));
            }
            
            // Check permissions
            if (!current_user_can('manage_school_students')) {
                wp_die(__('You do not have permission to perform this action.', 'school-manager-lite'));
            }
            
            // Remove student from class
            $student_manager = School_Manager_Lite_Student_Manager::instance();
            $student = $student_manager->get_student($student_id);
            
            if ($student && $student->wp_user_id) {
                global $wpdb;
                
                // Update student record
                $wpdb->update(
                    $student_manager->students_table,
                    array('class_id' => 0),
                    array('id' => $student_id)
                );
                
                // Get the course ID associated with this class
                $class_manager = School_Manager_Lite_Class_Manager::instance();
                $class = $class_manager->get_class($class_id);
                
                if ($class && !empty($class->course_id) && function_exists('ld_update_course_access')) {
                    // Remove from LearnDash course
                    ld_update_course_access($student->wp_user_id, $class->course_id, true);
                }
                
                // Fire action hook
                do_action('school_manager_student_removed_from_class', $student->wp_user_id, $class_id);
            }
            
            // Redirect back to class edit page
            $redirect_url = add_query_arg(array(
                'page' => 'school-manager-classes',
                'action' => 'edit',
                'id' => $class_id,
                'removed' => 1
            ), admin_url('admin.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * AJAX handler for bulk assigning a teacher to multiple classes
     */
    public function ajax_bulk_assign_teacher() {
        // Check permissions
        if (!current_user_can('manage_school_classes')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'school-manager-lite'),
                'hebrew' => 'אין הרשאה מתאימה'
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school_manager_lite_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'school-manager-lite'),
                'hebrew' => 'בדיקת אבטחה נכשלה'
            ));
        }
        
        // Get parameters
        $class_ids = isset($_POST['class_ids']) ? $_POST['class_ids'] : array();
        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        
        if (empty($class_ids) || !$teacher_id) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters.', 'school-manager-lite'),
                'hebrew' => 'חסרים פרמטרים נדרשים'
            ));
        }
        
        // Convert class_ids to array if it's a comma-separated string
        if (!is_array($class_ids)) {
            $class_ids = explode(',', $class_ids);
        }
        
        // Make sure class IDs are integers
        $class_ids = array_map('intval', $class_ids);
        
        // Verify teacher exists
        $teacher = get_user_by('id', $teacher_id);
        if (!$teacher || !$teacher->exists()) {
            wp_send_json_error(array(
                'message' => __('Invalid teacher.', 'school-manager-lite'),
                'hebrew' => 'מורה לא תקין'
            ));
        }
        
        // Update classes
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $success_count = 0;
        $error_count = 0;
        $updates = array();
        
        foreach ($class_ids as $class_id) {
            $class = $class_manager->get_class($class_id);
            if ($class) {
                // Update class with new teacher ID
                $result = $class_manager->update_class($class_id, array('teacher_id' => $teacher_id));
                
                if ($result) {
                    $success_count++;
                    $updates[$class_id] = $teacher->display_name;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
        
        // Create appropriate message based on results
        $message = '';
        $hebrew_message = '';
        
        if ($success_count > 0) {
            $message = sprintf(
                _n(
                    'Teacher successfully assigned to %d class.',
                    'Teacher successfully assigned to %d classes.',
                    $success_count,
                    'school-manager-lite'
                ),
                $success_count
            );
            
            $hebrew_message = sprintf(
                _n(
                    'מורה שויך בהצלחה ל-%d כיתה.',
                    'מורה שויך בהצלחה ל-%d כיתות.',
                    $success_count,
                    'school-manager-lite'
                ),
                $success_count
            );
            
            if ($error_count > 0) {
                $message .= ' ' . sprintf(
                    _n(
                        '%d class update failed.',
                        '%d class updates failed.',
                        $error_count,
                        'school-manager-lite'
                    ),
                    $error_count
                );
                
                $hebrew_message .= ' ' . sprintf(
                    _n(
                        'עדכון %d כיתה נכשל.',
                        'עדכון %d כיתות נכשל.',
                        $error_count,
                        'school-manager-lite'
                    ),
                    $error_count
                );
            }
            
            // Store the notice for display after page reload if not AJAX
            if ($this->notices) {
                $this->notices->add_success($message, $hebrew_message);
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'hebrew' => $hebrew_message,
                'updates' => $updates
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to assign teacher to classes.', 'school-manager-lite'),
                'hebrew' => 'שיוך המורה לכיתות נכשל'
            ));
        }
    }
    
    /**
     * AJAX handler for bulk assigning students to a class
     */
    public function ajax_bulk_assign_students() {
        // Check permissions
        if (!current_user_can('manage_school_students')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'school-manager-lite'),
                'hebrew' => 'אין הרשאה מתאימה'
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school_manager_lite_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'school-manager-lite'),
                'hebrew' => 'בדיקת אבטחה נכשלה'
            ));
        }
        
        // Get parameters
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : array();
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        
        if (empty($student_ids) || !$class_id) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters.', 'school-manager-lite'),
                'hebrew' => 'חסרים פרמטרים נדרשים'
            ));
        }
        
        // Make sure student IDs are integers
        if (!is_array($student_ids)) {
            $student_ids = explode(',', $student_ids);
        }
        $student_ids = array_map('intval', $student_ids);
        
        // Get necessary managers
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        
        // Verify class exists
        $class = $class_manager->get_class($class_id);
        if (!$class) {
            wp_send_json_error(array(
                'message' => __('Invalid class.', 'school-manager-lite'),
                'hebrew' => 'כיתה לא תקינה'
            ));
        }
        
        // Assign students
        $success_count = 0;
        $error_count = 0;
        $assigned_students = array();
        
        foreach ($student_ids as $student_id) {
            $student = get_user_by('id', $student_id);
            if ($student && $student->exists()) {
                // Assign student to class
                $result = $student_manager->assign_class($student_id, $class_id);
                
                if ($result) {
                    $success_count++;
                    $assigned_students[] = array(
                        'id' => $student_id,
                        'name' => $student->display_name,
                        'email' => $student->user_email
                    );
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
        
        // Create appropriate message based on results
        $message = '';
        $hebrew_message = '';
        
        if ($success_count > 0) {
            $message = sprintf(
                _n(
                    '%d student successfully added to class.',
                    '%d students successfully added to class.',
                    $success_count,
                    'school-manager-lite'
                ),
                $success_count
            );
            
            $hebrew_message = sprintf(
                _n(
                    '%d תלמיד נוסף לכיתה בהצלחה',
                    '%d תלמידים נוספו לכיתה בהצלחה',
                    $success_count,
                    'school-manager-lite'
                ),
                $success_count
            );
            
            if ($error_count > 0) {
                $message .= ' ' . sprintf(
                    _n(
                        '%d student could not be added.',
                        '%d students could not be added.',
                        $error_count,
                        'school-manager-lite'
                    ),
                    $error_count
                );
                
                $hebrew_message .= ' ' . sprintf(
                    _n(
                        '%d תלמיד לא ניתן היה להוסיף',
                        '%d תלמידים לא ניתן היה להוסיף',
                        $error_count,
                        'school-manager-lite'
                    ),
                    $error_count
                );
            }
            
            // Store the notice for display after page reload if not AJAX
            if ($this->notices) {
                $this->notices->add_success($message, $hebrew_message);
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'hebrew' => $hebrew_message,
                'assigned' => $assigned_students,
                'success_count' => $success_count,
                'error_count' => $error_count
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to assign students to class.', 'school-manager-lite'),
                'hebrew' => 'הוספת התלמידים לכיתה נכשלה'
            ));
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
