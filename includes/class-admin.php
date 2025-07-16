<?php
/**
 * Admin functionality for School Manager Lite
 */
class School_Manager_Lite_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_admin_init']);
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Add main menu page
        add_menu_page(
            __('School Manager Lite', 'school-manager-lite'),
            __('School Manager Lite', 'school-manager-lite'),
            'manage_options',
            'school-manager-lite',
            [$this, 'render_main_page'],
            'dashicons-admin-users',
            50
        );

        // Add Import/Export submenu page
        add_submenu_page(
            'school-manager-lite',
            __('Import/Export', 'school-manager-lite'),
            __('Import/Export', 'school-manager-lite'),
            'manage_options',
            'school-manager-import-export',
            [$this, 'render_import_export_page']
        );
    }

    /**
     * Handle admin initialization
     */
    public function handle_admin_init() {
        // Register settings if needed
        register_setting('school-manager-lite', 'school_manager_lite_settings');
    }

    /**
     * Render main admin page
     */
    public function render_main_page() {
        require_once SCHOOL_MANAGER_LITE_PATH . 'templates/admin/admin-main.php';
    }

    /**
     * Render import/export page
     */
    public function render_import_export_page() {
        require_once SCHOOL_MANAGER_LITE_PATH . 'templates/admin/admin-import-export.php';
    }
}

// Initialize the admin class
function school_manager_lite_admin() {
    return new School_Manager_Lite_Admin();
}
add_action('plugins_loaded', 'school_manager_lite_admin');
