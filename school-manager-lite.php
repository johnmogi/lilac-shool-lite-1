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

// Register emergency shortcode in case class loading fails
function school_manager_lite_emergency_shortcode($atts) {
    return '<div class="school-manager-redemption-form">' .
           '<h3>' . __('Redeem Promo Code', 'school-manager-lite') . '</h3>' .
           '<form method="post" class="school-redemption-form">' .
           '<p><label for="promo_code">' . __('Enter your promo code:', 'school-manager-lite') . '</label></p>' .
           '<p><input type="text" name="promo_code" id="promo_code" required /></p>' .
           '<p><button type="submit" class="school-button">' . __('Redeem', 'school-manager-lite') . '</button></p>' .
           '</form></div>';
}

// Register emergency shortcode early
add_shortcode('school_manager_redeem', 'school_manager_lite_emergency_shortcode');
add_shortcode('school_promo_code_redemption', 'school_manager_lite_emergency_shortcode');

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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Initialize database updates on plugins_loaded with higher priority
        add_action('plugins_loaded', array($this, 'init_database'), 5);
    }
    
    /**
     * Initialize database and run updates if needed
     */
    public function init_database() {
        // Initialize database
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-database.php';
        $database = new School_Manager_Lite_Database();
        
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
                'includes/class-import-export.php'
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
                    'includes/admin/class-teacher-dashboard.php',
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
        }
        
        // Log plugin loading status
        error_log('School Manager Lite: Plugin includes loaded');
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        require_once SCHOOL_MANAGER_LITE_PATH . 'includes/class-database.php';
        $database = new School_Manager_Lite_Database();
        $database->create_tables();
        
        // Create custom roles if they don't exist
        $this->create_custom_roles();
        
        // Set default options
        update_option('school_manager_lite_version', SCHOOL_MANAGER_LITE_VERSION);
        
        // Ensure database is up to date
        if (method_exists($database, 'maybe_update_database')) {
            $database->maybe_update_database();
        }
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
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

// Start the plugin
School_Manager_Lite();
