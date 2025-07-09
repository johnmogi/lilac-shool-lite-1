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
    }
    
    /**
     * Handle import/export actions
     */
    public function handle_import_export_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle export
        if (isset($_GET['export']) && isset($_GET['page']) && $_GET['page'] === 'school-manager-import-export') {
            $type = sanitize_text_field($_GET['export']);
            $this->export_data($type);
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
        
        // Headers
        fputcsv($output, array('ID', 'Name', 'Email', 'Class ID', 'Registration Date', 'Status'));
        
        // Data
        foreach ($students as $student) {
            fputcsv($output, array(
                $student->id,
                $student->name,
                $student->email,
                $student->class_id,
                $student->created_at,
                $student->status
            ));
        }
    }
    
    /**
     * Export teachers to CSV
     */
    private function export_teachers($output) {
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Headers
        fputcsv($output, array('ID', 'Username', 'Email', 'First Name', 'Last Name', 'Status'));
        
        // Data
        foreach ($teachers as $teacher) {
            // $teacher is a WP_User object
            $first_name = get_user_meta($teacher->ID, 'first_name', true);
            $last_name  = get_user_meta($teacher->ID, 'last_name', true);
            
            // Determine status based on role presence (customize as needed)
            $status = in_array('school_teacher', (array) $teacher->roles) ? 'active' : 'inactive';
            
            fputcsv($output, array(
                $teacher->ID,
                $teacher->user_login,
                $teacher->user_email,
                $first_name,
                $last_name,
                $status
            ));
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
        
        $header = fgetcsv($handle);
        
        switch ($type) {
            case 'students':
                $this->import_students($handle);
                break;
            case 'teachers':
                $this->import_teachers($handle);
                break;
            case 'classes':
                $this->import_classes($handle);
                break;
            case 'promo-codes':
                $this->import_promo_codes($handle);
                break;
        }
        
        fclose($handle);
        
        // Redirect back with success message
        wp_redirect(add_query_arg('imported', '1', $_SERVER['HTTP_REFERER']));
        exit();
    }
    
    /**
     * Generate and download a sample CSV file
     * 
     * @param string $type Type of sample CSV to generate (students, teachers, classes, promo-codes)
     */
    public function generate_sample_csv($type) {
        $filename = 'sample-' . $type . '.csv';
        $sample_data = array();
        
        switch ($type) {
            case 'students':
                $sample_data = array(
                    array('ID', 'Name', 'Username', 'Password', 'Email', 'Class ID', 'Status'),
                    array('', 'John Doe', '5551234567', 'S12345', 'john@example.com', '1', 'active'),
                    array('', 'Jane Smith', '5559876543', 'S67890', 'jane@example.com', '1', 'active')
                );
                break;
            case 'teachers':
                $sample_data = array(
                    array('ID', 'Username', 'Email', 'First Name', 'Last Name', 'Status'),
                    array('', 'teacher1', 'teacher1@example.com', 'John', 'Doe', 'active'),
                    array('', 'teacher2', 'teacher2@example.com', 'Jane', 'Smith', 'active')
                );
                break;
            case 'classes':
                $sample_data = array(
                    array('ID', 'Name', 'Description', 'Teacher ID', 'Max Students', 'Status'),
                    array('', 'Math 101', 'Introduction to Mathematics', '1', '30', 'active'),
                    array('', 'Science 101', 'Introduction to Science', '2', '25', 'active')
                );
                break;
            case 'promo-codes':
                $sample_data = array(
                    array('Code', 'Class ID', 'Expiry Date', 'Usage Limit', 'Used Count', 'Status'),
                    array('MATH2023', '1', date('Y-m-d', strtotime('+1 year')), '1', '0', 'active'),
                    array('SCI2023', '2', date('Y-m-d', strtotime('+1 year')), '1', '0', 'active')
                );
                break;
            default:
                wp_die(__('Invalid CSV type', 'school-manager-lite'));
        }
        
        // Generate CSV content
        $output = fopen('php://temp', 'w');
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Output headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit();
    }
    
    // Import methods for each type would be implemented here
    private function import_students($handle) { /* ... */ }
    /**
     * Import teachers from CSV
     * Expected columns: ID, Username, Email, First Name, Last Name, Status
     */
    private function import_teachers($handle) {
        if (!$handle) {
            return;
        }

        $imported = 0;
        $updated   = 0;
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to variables
            list($id, $username, $email, $first_name, $last_name, $status) = array_pad($row, 6, '');

            // Basic validation – require at least username (login) and names
            if (empty($username) || empty($first_name) || empty($last_name)) {
                // Skip invalid row
                continue;
            }

            // Ensure we have an email – generate placeholder if not provided
            if (empty($email)) {
                $email = sanitize_user($username, true) . '@example.com';
            }

            // Check if user exists by username or email
            $user = get_user_by('login', $username);
            if (!$user) {
                $user = get_user_by('email', $email);
            }

            $userdata = [
                'user_login'   => $username,
                'user_email'   => $email,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
                'role'         => 'school_teacher',
            ];

            if ($user) {
                // Update existing user
                $userdata['ID'] = $user->ID;
                wp_update_user($userdata);
                $updated++;
            } else {
                // Create new user with random password
                $userdata['user_pass'] = wp_generate_password(12, true, true);
                $new_id = wp_insert_user($userdata);
                if (!is_wp_error($new_id)) {
                    $imported++;
                }
            }
        }

        fclose($handle);

        // Redirect with query args to show notice
        $redirect_url = add_query_arg(array(
            'page'      => 'school-manager-import-export',
            'imported'  => 1,
            'added'     => $imported,
            'updated'   => $updated,
        ), admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit();
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
