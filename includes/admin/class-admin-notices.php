<?php
/**
 * Admin Notices Class
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * School_Manager_Lite_Admin_Notices class
 * 
 * Handles admin notices and messaging throughout the plugin
 */
class School_Manager_Lite_Admin_Notices {

    /**
     * Instance variable
     *
     * @var School_Manager_Lite_Admin_Notices|null
     */
    private static $instance = null;

    /**
     * Notices collection
     * 
     * @var array
     */
    private $notices = array();

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'display_notices'));
        add_action('admin_init', array($this, 'load_notices'));
    }

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
     * Load notices from session
     */
    public function load_notices() {
        $this->notices = get_option('school_manager_lite_admin_notices', array());
        
        if (!empty($this->notices)) {
            // Clear notices after loading
            update_option('school_manager_lite_admin_notices', array());
        }
    }

    /**
     * Add a notice
     * 
     * @param string $message The message to display
     * @param string $type The notice type (success, error, warning, info)
     * @param bool $is_dismissible Whether the notice should be dismissible
     * @param string $hebrew_message Optional Hebrew translation of the message
     */
    public function add_notice($message, $type = 'info', $is_dismissible = true, $hebrew_message = '') {
        // Add notice to collection
        $this->notices[] = array(
            'message' => $message,
            'type' => $type,
            'is_dismissible' => $is_dismissible,
            'hebrew_message' => $hebrew_message,
        );
        
        // Save notices to option for retrieval after redirect
        update_option('school_manager_lite_admin_notices', $this->notices);
    }

    /**
     * Add a success notice
     * 
     * @param string $message The message to display
     * @param string $hebrew_message Optional Hebrew translation of the message
     */
    public function add_success($message, $hebrew_message = '') {
        $this->add_notice($message, 'success', true, $hebrew_message);
    }

    /**
     * Add an error notice
     * 
     * @param string $message The message to display
     * @param string $hebrew_message Optional Hebrew translation of the message
     */
    public function add_error($message, $hebrew_message = '') {
        $this->add_notice($message, 'error', true, $hebrew_message);
    }

    /**
     * Add a warning notice
     * 
     * @param string $message The message to display
     * @param string $hebrew_message Optional Hebrew translation of the message
     */
    public function add_warning($message, $hebrew_message = '') {
        $this->add_notice($message, 'warning', true, $hebrew_message);
    }

    /**
     * Display all queued notices
     */
    public function display_notices() {
        // Check if we're on a School Manager page
        global $hook_suffix;
        if (!strpos($hook_suffix, 'school-manager')) {
            return;
        }
        
        foreach ($this->notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            
            if ($notice['is_dismissible']) {
                $class .= ' is-dismissible';
            }
            
            echo '<div class="' . esc_attr($class) . '">';
            echo '<p>' . wp_kses_post($notice['message']);
            
            // Add Hebrew translation if available
            if (!empty($notice['hebrew_message'])) {
                echo ' / <span lang="he" dir="rtl">' . wp_kses_post($notice['hebrew_message']) . '</span>';
            }
            
            echo '</p>';
            echo '</div>';
        }
        
        // Clear notices after displaying
        $this->notices = array();
    }
}

// Initialize
School_Manager_Lite_Admin_Notices::instance();
