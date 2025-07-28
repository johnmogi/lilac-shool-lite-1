<?php
/**
 * Import/Export functionality for School Manager Lite
 */
class School_Manager_Lite_Import_Export {
    
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Return an instance of this class.
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
        add_action('admin_init', array($this, 'handle_import_export_actions'));
        add_action('wp_ajax_export_teachers', array($this, 'ajax_export_teachers'));
        add_action('wp_ajax_nopriv_export_teachers', array($this, 'ajax_export_teachers'));
    }
    
    /**
     * AJAX handler for exporting teachers
     */
    public function ajax_export_teachers() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'export_teachers_nonce')) {
            status_header(403);
            die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            status_header(403);
            die('Unauthorized');
        }
        
        // Force download headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="teachers-export-' . date('Y-m-d') . '.csv"');
        
        // Output headers
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Username', 'Email', 'First Name', 'Last Name'));
        
        // Get teachers
        $teachers = get_users(array(
            'role__in' => array('school_teacher', 'instructor', 'teacher'),
            'number' => -1
        ));
        
        // Output data
        foreach ($teachers as $teacher) {
            fputcsv($output, array(
                $teacher->ID,
                $teacher->user_login,
                $teacher->user_email,
                $teacher->first_name,
                $teacher->last_name
            ));
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Handle import and export actions
     */
    public function handle_import_export_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle export
        if (isset($_GET['export']) && isset($_GET['page']) && $_GET['page'] === 'school-manager-import-export') {
            $type = sanitize_text_field($_GET['export']);
            $this->export_data($type);
            exit();
        }
        
        // Handle import
        if (isset($_POST['import_submit']) && isset($_FILES['import_file'])) {
            $this->import_data();
        }
    }
    
    /**
     * Export data to CSV
     */
    private function export_data($type) {
        $filename = 'school-manager-' . $type . '-' . date('Y-m-d') . '.csv';
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($type) {
            case 'students':
                $this->export_students($output);
                break;
            case 'teachers':
                $this->export_teachers($output);
                break;
            case 'classes':
                $this->export_classes($output);
                break;
            case 'promo-codes':
                $this->export_promo_codes($output);
                break;
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Export students to CSV
     */
    private function export_students($output) {
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $students = $student_manager->get_students(array('limit' => -1));
        
        // Headers (English - matching import format)
        fputcsv($output, array('ID', 'Name', 'Email', 'Class ID', 'Teacher ID', 'Course ID', 'Registration Date', 'Expiry Date', 'Status'));
        
        // Data
        foreach ($students as $student) {
            // Some rows may not have all properties set; provide sensible defaults
            $id          = '';
            $name        = isset($student->name) ? $student->name : '';
            $email       = isset($student->email) ? $student->email : '';
            $class_id    = isset($student->class_id) ? $student->class_id : '';
            // Determine Teacher ID via class
            $teacher_id = '';
            // Determine LearnDash course ID; default 898 if not found
            $course_id   = 898;
            if ($class_id) {
                $class_manager = School_Manager_Lite_Class_Manager::instance();
                $class = $class_manager->get_class($class_id);
                if ($class) {
                    if (isset($class->teacher_id) && $class->teacher_id) {
                        $teacher_id = $class->teacher_id;
                    }
                    if (isset($class->course_id) && $class->course_id) {
                        $course_id = $class->course_id;
                    }
                }
            }
            // Expiry date: 30/06 current academic year (if already passed, use next year)
            $current_year = date('Y');
            $expiry_date  = $current_year . '-06-30';
            if (strtotime($expiry_date) < time()) {
                $expiry_date = ($current_year + 1) . '-06-30';
            }
            $created_at  = isset($student->created_at) ? $student->created_at : '';
            $status      = isset($student->status) && $student->status ? $student->status : 'active';

            fputcsv($output, array(
                $id,
                $name,
                $email,
                $class_id,
                $teacher_id,
                $course_id,
                $created_at,
                $expiry_date,
                $status
            ));
        }
    }
    
    /**
     * Export teachers to CSV with class associations
     */
    private function export_teachers($output) {
        global $wpdb;
        
        // Headers (Hebrew)
        $headers = [
            'מזהה',
            'שם משתמש',
            'דוא"ל',
            'שם פרטי',
            'שם משפחה',
            'מזהה כיתה',
            'שם כיתה',
            'טלפון',
            'כניסה אחרונה',
            'סטטוס',
            'תפקידים',
            'תאריך הרשמה'
        ];
        
        fputcsv($output, $headers);
        
        // Get all users with teacher-related roles - try multiple approaches
        $teachers = get_users([
            'role__in' => ['school_teacher', 'instructor', 'teacher', 'group_leader'],
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => -1
        ]);
        
        // If no teachers found with specific roles, get all users and filter by capabilities
        if (empty($teachers)) {
            $all_users = get_users(['number' => -1]);
            $teachers = [];
            foreach ($all_users as $user) {
                if (user_can($user->ID, 'school_teacher') || 
                    user_can($user->ID, 'instructor') || 
                    user_can($user->ID, 'teacher') ||
                    in_array('school_teacher', $user->roles) ||
                    in_array('instructor', $user->roles) ||
                    in_array('teacher', $user->roles)) {
                    $teachers[] = $user;
                }
            }
        }
        
        // If still no teachers, get users from the teachers table directly
        if (empty($teachers)) {
            $teacher_ids = $wpdb->get_col("SELECT DISTINCT teacher_id FROM {$wpdb->prefix}school_classes WHERE teacher_id > 0");
            if (!empty($teacher_ids)) {
                $teachers = get_users([
                    'include' => $teacher_ids,
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ]);
            }
        }
        
        // Data
        foreach ($teachers as $teacher) {
            // Get user meta
            $first_name = get_user_meta($teacher->ID, 'first_name', true);
            $last_name = get_user_meta($teacher->ID, 'last_name', true);
            $phone = get_user_meta($teacher->ID, 'billing_phone', true) ?: get_user_meta($teacher->ID, 'phone', true);
            $last_login = get_user_meta($teacher->ID, 'last_login', true);
            $status = 'Active'; // Default to active
            $roles = implode(', ', $teacher->roles);
            $last_login_formatted = $last_login ? date('Y-m-d H:i:s', $last_login) : 'Never';
            
            // Get classes taught by this teacher
            $classes = $wpdb->get_results($wpdb->prepare(
                "SELECT c.id, c.name 
                 FROM {$wpdb->prefix}school_classes c 
                 WHERE c.teacher_id = %d 
                 ORDER BY c.name ASC",
                $teacher->ID
            ));
            
            // If teacher has no classes, add a single row
            if (empty($classes)) {
                fputcsv($output, [
                    $teacher->ID,
                    $teacher->user_login,
                    $teacher->user_email,
                    $first_name,
                    $last_name,
                    '', // Empty class ID
                    '', // Empty class name
                    $phone,
                    $last_login_formatted,
                    $status,
                    $roles,
                    $teacher->user_registered
                ]);
            } else {
                // Add a row for each class
                foreach ($classes as $class) {
                    fputcsv($output, [
                        $teacher->ID,
                        $teacher->user_login,
                        $teacher->user_email,
                        $first_name,
                        $last_name,
                        $class->id,
                        $class->name,
                        $phone,
                        $last_login_formatted,
                        $status,
                        $roles,
                        $teacher->user_registered
                    ]);
                }
            }
        }
    }
    
    /**
     * Export classes to CSV
     */
    private function export_classes($output) {
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Headers
        fputcsv($output, array('ID', 'Name', 'Description', 'Teacher ID', 'Max Students', 'Status'));
        
        // Data
        foreach ($classes as $class) {
            fputcsv($output, array(
                $class->id,
                $class->name,
                $class->description,
                $class->teacher_id,
                $class->max_students,
                $class->status
            ));
        }
    }
    
    /**
     * Export promo codes to CSV
     */
    private function export_promo_codes($output) {
        $promo_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $promo_codes = $promo_manager->get_promo_codes();
        
        // Headers
        fputcsv($output, array('Code', 'Class ID', 'Expiry Date', 'Usage Limit', 'Used Count', 'Status'));
        
        // Data
        foreach ($promo_codes as $promo) {
            fputcsv($output, array(
                $promo->code,
                $promo->class_id,
                $promo->expiry_date,
                $promo->usage_limit,
                $promo->used_count,
                $promo->status
            ));
        }
    }
    
    /**
     * Import data from CSV
     */
    private function import_data() {
        if (!isset($_FILES['import_file']['tmp_name'])) {
            return;
        }
        
        $file = $_FILES['import_file']['tmp_name'];
        $type = sanitize_text_field($_POST['import_type']);
        $handle = fopen($file, 'r');
        
        if ($handle === false) {
            return;
        }
        
        $results = array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array());
        
        switch ($type) {
            case 'students':
                $results = $this->import_students($handle);
                break;
            case 'teachers':
                $results = $this->import_teachers($handle, $headers);
                break;
            case 'classes':
                $results = $this->import_classes($handle);
                break;
            case 'promo-codes':
                $results = $this->import_promo_codes($handle);
                break;
        }
        
        // Close file handle if it's still valid
        if (is_resource($handle)) {
            fclose($handle);
        }
        
        // Store results in transient for display
        set_transient('school_import_results', $results, 300); // 5 minutes
        
        // Redirect to results page
        $redirect_url = add_query_arg(array(
            'page' => 'school-manager-import-export',
            'import_completed' => '1'
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit();
    }
    
    /**
     * Generate and download a sample CSV file
     * 
     * @param string $type Type of sample CSV to generate (students, teachers, classes, promo-codes)
     */
    public function generate_sample_csv($type) {
        $filename = 'sample-' . $type . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($type) {
            case 'students':
                // Updated for new format: ID, Name, Email, Class ID, Teacher ID, Course ID, Registration Date, Expiry Date, Status
                fputcsv($output, array('ID', 'Name', 'Email', 'Class ID', 'Teacher ID', 'Course ID', 'Registration Date', 'Expiry Date', 'Status'));
                fputcsv($output, array('', 'John Doe', 'john@example.com', '1', '10', '898', date('Y-m-d H:i:s'), date('Y-m-d', strtotime('+1 year')), 'active'));
                fputcsv($output, array('', 'Jane Smith', 'jane@example.com', '2', '11', '898', date('Y-m-d H:i:s'), date('Y-m-d', strtotime('+1 year')), 'active'));
                fputcsv($output, array('', 'יוסי כהן', 'yossi@example.com', '1', '10', '898', date('Y-m-d H:i:s'), date('Y-m-d', strtotime('+1 year')), 'active'));
                
                // Add Hebrew translations for column headers
                echo "\n\nשימו לב:\n";
                echo "ID - מזהה (השאירו ריק לתלמיד חדש)\n";
                echo "Name - שם מלא\n";
                echo "Email - כתובת דואר אלקטרוני\n";
                echo "Class ID - מזהה כיתה\n";
                echo "Teacher ID - מזהה מורה\n";
                echo "Course ID - מזהה קורס\n";
                echo "Registration Date - תאריך רישום (YYYY-MM-DD HH:MM:SS)\n";
                echo "Expiry Date - תאריך פקיעת תוקף (YYYY-MM-DD)\n";
                echo "Status - סטטוס (active או inactive)\n";
                break;
                
            case 'teachers':
                fputcsv($output, array('ID', 'Name', 'Email', 'Phone', 'Status', 'Specialty', 'Bio'));
                fputcsv($output, array('', 'David Cohen', 'david@example.com', '050-1234567', 'active', 'Math', 'Math teacher with 10 years experience'));
                fputcsv($output, array('', 'Sarah Levy', 'sarah@example.com', '052-7654321', 'active', 'English', 'English teacher with 5 years experience'));
                fputcsv($output, array('', 'רותי לוי', 'ruti@example.com', '054-1234567', 'active', 'מדעים', 'מורה למדעים עם 8 שנות ניסיון'));
                
                // Add Hebrew translations for column headers
                echo "\n\nשימו לב:\n";
                echo "ID - מזהה (השאירו ריק למורה חדש)\n";
                echo "Name - שם מלא\n";
                echo "Email - כתובת דואר אלקטרוני\n";
                echo "Phone - טלפון\n";
                echo "Status - סטטוס (active או inactive)\n";
                echo "Specialty - תחום התמחות\n";
                echo "Bio - ביוגרפיה קצרה\n";
                break;
                
            case 'classes':
                fputcsv($output, array('ID', 'Name', 'Teacher ID', 'Course ID', 'Status', 'Description'));
                fputcsv($output, array('', 'Class A', '10', '898', 'active', 'Morning class'));
                fputcsv($output, array('', 'כיתה ב', '11', '898', 'active', 'כיתת אחר הצהריים'));
                
                // Add Hebrew translations for column headers
                echo "\n\nשימו לב:\n";
                echo "ID - מזהה (השאירו ריק לכיתה חדשה)\n";
                echo "Name - שם הכיתה\n";
                echo "Teacher ID - מזהה מורה\n";
                echo "Course ID - מזהה קורס\n";
                echo "Status - סטטוס (active או inactive)\n";
                echo "Description - תיאור הכיתה\n";
                break;
                
            case 'promo-codes':
                fputcsv($output, array('ID', 'Code', 'Discount', 'Status', 'Expiry Date'));
                fputcsv($output, array('', 'WELCOME10', '10', 'active', date('Y-m-d', strtotime('+3 months'))));
                fputcsv($output, array('', 'קיץ2025', '15', 'active', date('Y-m-d', strtotime('+6 months'))));
                
                // Add Hebrew translations for column headers
                echo "\n\nשימו לב:\n";
                echo "ID - מזהה (השאירו ריק לקוד חדש)\n";
                echo "Code - קוד קופון\n";
                echo "Discount - אחוז הנחה\n";
                echo "Status - סטטוס (active או inactive)\n";
                echo "Expiry Date - תאריך תפוגה (YYYY-MM-DD)\n";
                break;
                
            default:
                wp_die(__('Invalid CSV type', 'school-manager-lite'));
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Import students from CSV
     * Expected columns: ID, Name, Email, Class ID, Teacher ID, Course ID, Registration Date, Expiry Date, Status
     */
    private function import_students($handle) {
        if (!$handle) {
            return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('File handle is invalid'));
        }

        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $class_manager   = School_Manager_Lite_Class_Manager::instance();

        $imported = 0;
        $updated  = 0;
        $errors   = array();
        $total_rows = 0;
        
        // Skip header row
        $headers = fgetcsv($handle);
        error_log('School Manager Import: CSV headers: ' . print_r($headers, true));
        
        while (($row = fgetcsv($handle)) !== false) {
            $total_rows++;
            
            if (empty(array_filter($row))) {
                error_log('School Manager Import: Skipping empty row ' . $total_rows);
                continue; // skip empty lines
            }

            // Expected format: ID, Name, Email, Class ID, Teacher ID, Course ID, Registration Date, Expiry Date, Status
            list($id, $name, $email, $class_id, $teacher_id, $course_id, $registration_date, $expiry_date, $status) = array_pad($row, 9, '');
            
            error_log('School Manager Import: Processing row ' . $total_rows . ' - Name: ' . $name . ', Email: ' . $email . ', Class ID: ' . $class_id);
            
            // Basic validation
            if (empty($name)) {
                $error_msg = sprintf(__('Row %d: Skipped - missing name', 'school-manager-lite'), $total_rows);
                $errors[] = $error_msg;
                error_log('School Manager Import: ' . $error_msg);
                continue;
            }
            
            // Ensure valid email
            if (empty($email) || !is_email($email)) {
                $error_msg = sprintf(__('Row %d: Skipped - invalid email for student: %s', 'school-manager-lite'), $total_rows, $name);
                $errors[] = $error_msg;
                error_log('School Manager Import: ' . $error_msg);
                continue;
            }
            
            // Generate username from email if needed
            $username = sanitize_user(current(explode('@', $email)), true);
            if (username_exists($username)) {
                // Add random suffix to make unique
                $username = $username . rand(100, 999);
            }
            
            // Generate random password
            $password = wp_generate_password(8, false);
            
            // Validate class ID
            if (empty($class_id)) {
                $error_msg = sprintf(__('Row %d: Skipped - missing class ID for student: %s', 'school-manager-lite'), $total_rows, $name);
                $errors[] = $error_msg;
                error_log('School Manager Import: ' . $error_msg);
                continue;
            }
            
            $class = $class_manager->get_class($class_id);
            if (!$class) {
                $error_msg = sprintf(__('Row %d: Skipped - invalid class ID %s for student: %s', 'school-manager-lite'), $total_rows, $class_id, $name);
                $errors[] = $error_msg;
                error_log('School Manager Import: ' . $error_msg);
                continue;
            }
            
            // Default status to active if not specified
            if (empty($status)) {
                $status = 'active';
            }
            
            // Check for existing user by email
            $existing_wp_user = get_user_by('email', $email);
            $existing_student = null;
            
            if ($existing_wp_user) {
                $existing_student = $student_manager->get_student_by_wp_user_id($existing_wp_user->ID);
            }
            
            if ($existing_wp_user && $existing_student) {
                // Update existing student
                wp_update_user([
                    'ID' => $existing_wp_user->ID,
                    'display_name' => $name
                ]);
                
                // Update student record
                $student_manager->update_student($existing_student->id, [
                    'name' => $name,
                    'class_id' => $class_id,
                    'status' => $status
                ]);
                
                // Update status in user meta
                update_user_meta($existing_wp_user->ID, 'school_student_status', $status);
                
                $updated++;
            } else {
                // Create new student
                $student_data = [
                    'name' => $name,
                    'user_login' => $username,
                    'user_pass' => $password,
                    'email' => $email,
                    'class_id' => $class_id,
                    'status' => $status,
                    'create_user' => true,
                    'role' => 'student_private'
                ];
                
                $create = $student_manager->create_student($student_data);
                
                if (!is_wp_error($create)) {
                    $imported++;
                    
                    // Set registration date if provided
                    if (!empty($registration_date)) {
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'school_students';
                        $wpdb->update(
                            $table_name,
                            ['created_at' => $registration_date],
                            ['id' => $create],
                            ['%s'],
                            ['%d']
                        );
                    }
                } else {
                    $errors[] = sprintf(__('Error importing student %s: %s', 'school-manager-lite'), 
                        $name, $create->get_error_message());
                }
            }
        }
        
        // Log final results
        error_log('School Manager Import: Final results - Total rows: ' . $total_rows . ', Imported: ' . $imported . ', Updated: ' . $updated . ', Errors: ' . count($errors));
        
        // Return results array for proper handling
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => 0,
            'errors' => $errors
        );
    }
    
    /**
     * Import teachers from CSV with class associations
     * Expected columns: ID, Username, Email, First Name, Last Name, Class ID, Class Name, Phone, Status
     */
    private function import_teachers($handle, $headers = null) {
        global $wpdb;
        
        error_log("Import Teachers: Starting teacher import process");
        
        // If headers not provided, read them from handle
        if (!$headers) {
            $headers = fgetcsv($handle);
        }
        
        if (!$headers) {
            error_log("Import Teachers: Error reading CSV headers");
            wp_die(__('Error reading CSV headers.', 'school-manager-lite'));
        }
        
        error_log("Import Teachers: CSV headers found: " . implode(', ', $headers));
        
        $required_columns = ['Username', 'Email', 'First Name', 'Last Name'];
        foreach ($required_columns as $column) {
            if (!in_array($column, $headers)) {
                error_log("Import Teachers: Missing required column: {$column}");
                wp_die(sprintf(__('Missing required column: %s', 'school-manager-lite'), $column));
            }
        }
        
        $column_map = array_flip($headers);
        $teachers = array();
        $imported_count = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || (count($row) == 1 && empty($row[0]))) continue;
            
            $data = array();
            foreach ($headers as $i => $header) {
                $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            
            error_log("Import Teachers: Processing row: " . print_r($data, true));
            
            // Validate required fields
            foreach ($required_columns as $column) {
                if (empty($data[$column])) {
                    error_log("Import Teachers: Skipping row due to missing {$column}");
                    continue 2; // Skip this row
                }
            }
            
            // Create or update user with CORRECT role
            $user_data = array(
                'user_login' => $data['Username'],
                'user_email' => $data['Email'],
                'first_name' => $data['First Name'],
                'last_name' => $data['Last Name'],
                'display_name' => trim($data['First Name'] . ' ' . $data['Last Name']),
                'role' => 'wdm_instructor' // FIXED: Use correct instructor role
            );
            
            $user_id = null;
            
            if (!empty($data['ID'])) {
                $user_id = intval($data['ID']);
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $user_data['ID'] = $user_id;
                    wp_update_user($user_data);
                    
                    // Ensure correct role
                    $user->remove_role('school_teacher');
                    $user->add_role('wdm_instructor');
                    
                    error_log("Import Teachers: Updated existing user {$user_id}");
                } else {
                    error_log("Import Teachers: User ID {$user_id} not found, skipping");
                    continue;
                }
            } else {
                // Check if user already exists by username or email
                $existing_user = get_user_by('login', $data['Username']);
                if (!$existing_user) {
                    $existing_user = get_user_by('email', $data['Email']);
                }
                
                if ($existing_user) {
                    $user_id = $existing_user->ID;
                    $user_data['ID'] = $user_id;
                    wp_update_user($user_data);
                    
                    // Ensure correct role
                    $existing_user->remove_role('school_teacher');
                    $existing_user->add_role('wdm_instructor');
                    
                    error_log("Import Teachers: Updated existing user {$user_id} with correct role");
                } else {
                    // Create new user
                    $user_data['user_pass'] = wp_generate_password();
                    $user_id = wp_insert_user($user_data);
                    
                    if (is_wp_error($user_id)) {
                        error_log("Import Teachers: Error creating user: " . $user_id->get_error_message());
                        continue;
                    }
                    
                    error_log("Import Teachers: Created new user {$user_id}");
                }
            }
            
            if ($user_id) {
                // Add comprehensive teacher capabilities
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $capabilities = array(
                        'read' => true,
                        'upload_files' => true,
                        'edit_posts' => true,
                        'edit_published_posts' => true,
                        'publish_posts' => true,
                        'delete_posts' => true,
                        'delete_published_posts' => true,
                        'school_teacher' => true,
                        'manage_school_students' => true,
                        'view_school_reports' => true,
                        'access_teacher_dashboard' => true,
                        'edit_sfwd-courses' => true,
                        'edit_others_sfwd-courses' => true,
                        'publish_sfwd-courses' => true,
                        'read_private_sfwd-courses' => true,
                        'delete_sfwd-courses' => true,
                        'delete_others_sfwd-courses' => true,
                        'edit_sfwd-lessons' => true,
                        'edit_others_sfwd-lessons' => true,
                        'publish_sfwd-lessons' => true,
                        'read_private_sfwd-lessons' => true,
                        'delete_sfwd-lessons' => true,
                        'delete_others_sfwd-lessons' => true,
                        'edit_sfwd-topic' => true,
                        'edit_others_sfwd-topic' => true,
                        'publish_sfwd-topic' => true,
                        'read_private_sfwd-topic' => true,
                        'delete_sfwd-topic' => true,
                        'delete_others_sfwd-topic' => true,
                        'edit_sfwd-quiz' => true,
                        'edit_others_sfwd-quiz' => true,
                        'publish_sfwd-quiz' => true,
                        'read_private_sfwd-quiz' => true,
                        'delete_sfwd-quiz' => true,
                        'delete_others_sfwd-quiz' => true,
                        'edit_sfwd-question' => true,
                        'edit_others_sfwd-question' => true,
                        'publish_sfwd-question' => true,
                        'read_private_sfwd-question' => true,
                        'delete_sfwd-question' => true,
                        'delete_others_sfwd-question' => true,
                    );
                    
                    foreach ($capabilities as $cap => $grant) {
                        $user->add_cap($cap, $grant);
                    }
                }
                
                // Update user meta
                if (!empty($data['Phone'])) {
                    update_user_meta($user_id, 'phone', $data['Phone']);
                }
                
                if (!empty($data['Status'])) {
                    update_user_meta($user_id, 'status', $data['Status']);
                }
                
                // Mark as teacher
                update_user_meta($user_id, 'is_teacher', '1');
                update_user_meta($user_id, 'teacher', '1');
                
                // Store teacher data with classes for processing
                if (!isset($teachers[$user_id])) {
                    $teachers[$user_id] = array(
                        'user_data' => $user_data,
                        'classes' => array()
                    );
                }
                
                if (!empty($data['Class ID']) && !empty($data['Class Name'])) {
                    $teachers[$user_id]['classes'][] = array(
                        'id' => $data['Class ID'],
                        'name' => $data['Class Name']
                    );
                }
                
                $imported_count++;
            }
        }
        
        // Process class associations after all users are created
        foreach ($teachers as $user_id => $teacher_data) {
            // Remove existing class associations for this teacher
            $wpdb->update(
                $wpdb->prefix . 'school_classes',
                array('teacher_id' => null),
                array('teacher_id' => $user_id)
            );
            
            // Add new class associations
            foreach ($teacher_data['classes'] as $class) {
                // Check if class exists
                $existing_class = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}school_classes WHERE id = %s",
                    $class['id']
                ));
                
                if ($existing_class) {
                    // Update class with teacher_id
                    $result = $wpdb->update(
                        $wpdb->prefix . 'school_classes',
                        array('teacher_id' => $user_id),
                        array('id' => $class['id'])
                    );
                    error_log("Import Teachers: Assigned teacher {$user_id} to existing class {$class['id']}");
                } else {
                    // Create new class if it doesn't exist
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'school_classes',
                        array(
                            'id' => $class['id'],
                            'name' => $class['name'],
                            'teacher_id' => $user_id
                        )
                    );
                    error_log("Import Teachers: Created new class {$class['id']} and assigned teacher {$user_id}");
                }
            }
        }
        
        error_log("Import Teachers: Import completed. Imported {$imported_count} teachers");
        
        wp_redirect(add_query_arg(array(
            'imported' => 'teachers',
            'count' => $imported_count
        ), admin_url('admin.php?page=school-manager-import-export')));
        exit;
    }
    private function import_classes($handle) { /* ... */ }
    private function import_promo_codes($handle) { /* ... */ }
}

// Initialize the Import/Export handler
function school_manager_lite_import_export() {
    return School_Manager_Lite_Import_Export::instance();
}

// Start the import/export handler
school_manager_lite_import_export();
