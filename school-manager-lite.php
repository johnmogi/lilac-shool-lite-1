<?php
/**
 * Plugin Name: School Manager Lite
 * Plugin URI: https://example.com/school-manager-lite
 * Description: A lightweight school management system for managing teachers, classes, students, and promo codes without LearnDash dependency.
 * Version: 1.0.0
 * Author: Custom Development Team
 * Text Domain: school-manager-lite
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// CRITICAL: Define all constants early before any includes or code execution
define('SCHOOL_MANAGER_LITE_VERSION', '1.0.0');
define('SCHOOL_MANAGER_LITE_PATH', plugin_dir_path(__FILE__));
define('SCHOOL_MANAGER_LITE_URL', plugin_dir_url(__FILE__));
define('SCHOOL_MANAGER_LITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCHOOL_MANAGER_LITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCHOOL_MANAGER_LITE_BASENAME', plugin_basename(__FILE__));

// Load translations
function school_manager_lite_load_textdomain() {
    load_plugin_textdomain('school-manager-lite', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'school_manager_lite_load_textdomain');

// Register emergency shortcode in case class loading fails
function school_manager_lite_emergency_shortcode($atts) {
    // Only load translations when needed
    if (!did_action('plugins_loaded')) {
        school_manager_lite_load_textdomain();
    }
    
    return '<div class="school-manager-redemption-form">' .
           '<h3>' . esc_html__('Redeem Promo Code', 'school-manager-lite') . '</h3>' .
           '<form method="post" class="school-redemption-form">' .
           '<p><label for="promo_code">' . esc_html__('Enter your promo code:', 'school-manager-lite') . '</label></p>' .
           '<p><input type="text" name="promo_code" id="promo_code" required /></p>' .
           '<p><button type="submit" class="school-button">' . esc_html__('Redeem', 'school-manager-lite') . '</button></p>' .
           '</form></div>';
}

// Load translations at the right time
add_action('plugins_loaded', function() {
    // Register shortcodes after translations are loaded
    add_shortcode('school_manager_redeem', 'school_manager_lite_emergency_shortcode');
    add_shortcode('school_promo_code_redemption', 'school_manager_lite_emergency_shortcode');
    
    // Initialize the plugin
    School_Manager_Lite::instance();
});



/**
 * Class School_Manager_Lite
 * 
 * Main plugin class to initialize components and hooks
 */
class School_Manager_Lite {
    /**
     * The single instance of the class.
     */
    private static $instance = null;

    /**
     * Main School_Manager_Lite Instance.
     * 
     * Ensures only one instance of School_Manager_Lite is loaded or can be loaded.
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
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Initialize database updates on plugins_loaded with higher priority
        add_action('plugins_loaded', array($this, 'init_database'), 5);
        
        // Load textdomain after database initialization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize database and run updates if needed
     */
    public function init_database() {
        // Initialize database
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-database.php';
        $database = new School_Manager_Lite_Database();
        
        // Create tables if they don't exist
        $database->create_tables();
        
        // Run database updates if needed
        if (method_exists($database, 'maybe_update_database')) {
            $database->maybe_update_database();
        }
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Use error suppression and checking to avoid fatal errors from missing files
        // Core classes with safe includes
        if (file_exists(SCHOOL_MANAGER_LITE_PATH . 'includes/class-database.php')) {
            require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-database.php';
        }
        
        // Only load other classes if database exists (to maintain dependency order)
        if (class_exists('School_Manager_Lite_Database')) {
            // Core classes with safe includes
            $core_files = array(
                'includes/class-teacher-manager.php',
                'includes/class-class-manager.php',
                'includes/class-student-manager.php',
                'includes/class-promo-code-manager.php',
                'includes/class-shortcodes.php',
                'includes/class-import-export.php',
                'includes/class-teacher-dashboard.php',
                'includes/class-learndash-integration.php',
                'includes/class-simple-group-connector.php',
                'includes/class-basic-fixes.php',
                'includes/class-instructor-quiz-connector.php',
                'includes/class-instructor-quiz-manager.php',
                'includes/class-instructor-dashboard-widget.php'
            );
            
            foreach ($core_files as $file) {
                if (file_exists(SCHOOL_MANAGER_LITE_PATH . $file)) {
                    require_once SCHOOL_MANAGER_LITE_PATH . $file;
                }
            }

            // Admin
            if (is_admin()) {
                $admin_files = array(
                    'includes/admin/class-admin.php',
                    'includes/admin/class-wizard.php',
                    'includes/admin/class-student-profile.php'
                );
                
                foreach ($admin_files as $file) {
                    if (file_exists(SCHOOL_MANAGER_LITE_PATH . $file)) {
                        require_once SCHOOL_MANAGER_LITE_PATH . $file;
                    }
                }
                
                // Initialize admin if class exists
                if (class_exists('School_Manager_Lite_Admin')) {
                    $this->admin = School_Manager_Lite_Admin::instance();
                }
                
                // Initialize student profile customization if class exists
                if (class_exists('School_Manager_Lite_Student_Profile')) {
                    School_Manager_Lite_Student_Profile::instance();
                }
            }

            // Register shortcodes
            add_action('init', array($this, 'register_shortcodes'));
            
            // Initialize shortcodes if class exists
            if (class_exists('School_Manager_Lite_Shortcodes')) {
                $this->shortcodes = School_Manager_Lite_Shortcodes::instance();
            }
            
            // Initialize additional classes
            if (class_exists('School_Manager_Lite_Basic_Fixes')) {
                School_Manager_Lite_Basic_Fixes::instance();
            }
            
            if (class_exists('School_Manager_Lite_Instructor_Quiz_Connector')) {
                School_Manager_Lite_Instructor_Quiz_Connector::instance();
            }
            
            if (class_exists('School_Manager_Lite_Instructor_Quiz_Manager')) {
                School_Manager_Lite_Instructor_Quiz_Manager::instance();
            }
            
            if (class_exists('School_Manager_Lite_Instructor_Dashboard_Widget')) {
                School_Manager_Lite_Instructor_Dashboard_Widget::instance();
            }
        }
        
        // Log plugin loading status
        error_log('School Manager Lite: Plugin includes loaded');
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Initialize database first
        $this->init_database();
        
        // Create custom roles
        $this->create_custom_roles();
        
        // Set default options
        update_option('school_manager_lite_version', SCHOOL_MANAGER_LITE_VERSION);
    }
    
    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clean up any temporary data or caches
        delete_option('school_manager_lite_version');
        flush_rewrite_rules();
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain('school-manager-lite', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        // Double-check emergency shortcodes are registered
        if (!shortcode_exists('school_manager_redeem')) {
            add_shortcode('school_manager_redeem', 'school_manager_lite_emergency_shortcode');
        }
        
        if (!shortcode_exists('school_promo_code_redemption')) {
            add_shortcode('school_promo_code_redemption', 'school_manager_lite_emergency_shortcode');
        }
        
        // Let the shortcodes class handle registration for advanced functionality
        if (isset($this->shortcodes) && is_object($this->shortcodes) && method_exists($this->shortcodes, 'register_shortcodes')) {
            $this->shortcodes->register_shortcodes();
            error_log('School Manager Lite: Advanced shortcodes registered');
        } else {
            error_log('School Manager Lite: Using emergency shortcodes only');
        }
    }
    
    /**
     * Create custom roles for teachers and students.
     */
    private function create_custom_roles() {
        // Define custom capabilities
        $custom_caps = array(
            'manage_school_classes' => true,
            'manage_school_students' => true,
            'manage_school_promo_codes' => true,
            'access_school_content' => true,
        );
        
        // Create school_teacher role if it doesn't exist
        if (!get_role('school_teacher')) {
            add_role(
                'school_teacher',
                __('School Teacher', 'school-manager-lite'),
                array(
                    'read' => true,
                    'manage_school_classes' => true,
                    'manage_school_students' => true,
                    'access_school_content' => true,
                )
            );
        }
        
        // Create student_private role if it doesn't exist
        if (!get_role('student_private')) {
            add_role(
                'student_private',
                __('Private Student', 'school-manager-lite'),
                array(
                    'read' => true,
                    'access_school_content' => true,
                )
            );
        }
        
        // Create student_school role if it doesn't exist
        if (!get_role('student_school')) {
            add_role(
                'student_school',
                __('School Student', 'school-manager-lite'),
                array(
                    'read' => true,
                    'access_school_content' => true,
                )
            );
        }
        
        // Add custom capabilities to administrator role
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($custom_caps as $cap => $grant) {
                $admin->add_cap($cap);
            }
        }
    }
}

/**
 * Main instance of School_Manager_Lite.
 * 
 * Returns the main instance of School_Manager_Lite to prevent the need to use globals.
 */
function School_Manager_Lite() {
    return School_Manager_Lite::instance();
}

// Include required files with class existence checks
if (!class_exists('School_Manager_Lite_Teacher_Student_Relationships')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-teacher-student-relationships.php';
}

// Include Teacher Roles management
if (!class_exists('School_Manager_Teacher_Roles')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-teacher-roles.php';
}

if (!class_exists('School_Manager_Lite_Teacher_Dashboard')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-teacher-dashboard.php';
}

if (!class_exists('School_Manager_Lite_Registration_Fallback')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-registration-fallback.php';
}

if (!class_exists('School_Manager_Lite_AJAX_Handlers')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-ajax-handlers.php';
}

// Include Import/Export class
if (!class_exists('School_Manager_Lite_Import_Export')) {
    require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-import-export.php';
    error_log('School Manager Lite: Import/Export class loaded');
    
    // Initialize the import/export handler
    $import_export = School_Manager_Lite_Import_Export::instance();
    error_log('School Manager Lite: Import/Export instance created: ' . get_class($import_export));
} else {
    error_log('School Manager Lite: Import/Export class already exists');
}

// Activation hook
register_activation_hook(__FILE__, 'school_manager_lite_activate');

/**
 * Plugin activation function
 */
function school_manager_lite_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'school_teacher_students';
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        teacher_id bigint(20) NOT NULL,
        student_id bigint(20) NOT NULL,
        class_id bigint(20) DEFAULT NULL,
        date_assigned datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY teacher_student_class (teacher_id, student_id, class_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add version to handle future updates
    add_option('school_manager_lite_db_version', '1.0.0');
}

// Start the plugin
School_Manager_Lite();
