<?php
/**
 * Shortcodes Class
 *
 * Handles all shortcodes for the plugin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Shortcodes {
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
        // Register shortcodes
        $this->register_shortcodes();
        
        // Add AJAX handlers for frontend actions
        add_action('wp_ajax_school_redeem_promo_code', array($this, 'ajax_redeem_promo_code'));
        add_action('wp_ajax_nopriv_school_redeem_promo_code', array($this, 'ajax_redeem_promo_code'));
        
        // Register frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
    }
    
    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        // Register both the primary shortcode and its alias
        add_shortcode('school_promo_code_redemption', array($this, 'promo_code_redemption_shortcode'));
        // Ensure the school_manager_redeem shortcode is registered as an alias
        add_shortcode('school_manager_redeem', array($this, 'promo_code_redemption_shortcode'));
        
        // Log successful shortcode registration
        error_log('School Manager Lite: Class-based shortcodes registered successfully');
    }

    /**
     * Register frontend scripts and styles
     */
    public function register_frontend_scripts() {
        wp_register_style(
            'school-manager-lite-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
            array(),
            SCHOOL_MANAGER_LITE_VERSION
        );
        
        wp_register_script(
            'school-manager-lite-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/frontend.js',
            array('jquery'),
            SCHOOL_MANAGER_LITE_VERSION,
            true
        );
    }

    /**
     * Promo code redemption shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function promo_code_redemption_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'title' => __('', 'school-manager-lite'),
                'button_text' => __('הפעל מנוי', 'school-manager-lite'),
                'redirect' => '',
                'class' => 'school-promo-redemption',
            ),
            $atts,
            'school_promo_code_redemption'
        );
        
        // Enqueue required scripts and styles
        wp_enqueue_style('school-manager-lite-frontend');
        wp_enqueue_script('school-manager-lite-frontend');
        
        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'school-manager-lite-frontend',
            'school_manager_lite',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('school-promo-code-redemption'),
                'redirect_url' => !empty($atts['redirect']) ? esc_url($atts['redirect']) : '',
                'i18n' => array(
                    'code_required' => __('Please enter your promo code.', 'school-manager-lite'),
                    'processing' => __('Processing...', 'school-manager-lite'),
                    'error' => __('An error occurred. Please try again.', 'school-manager-lite'),
                    'invalid_code' => __('Invalid promo code. Please check and try again.', 'school-manager-lite'),
                    'code_already_used' => __('This promo code has already been redeemed.', 'school-manager-lite'),
                    'success' => __('Success! Your promo code has been redeemed.', 'school-manager-lite'),
                )
            )
        );
        
        // Check for error/success messages
        $message = '';
        if (isset($_GET['school_code_error'])) {
            $error_code = sanitize_text_field($_GET['school_code_error']);
            switch ($error_code) {
                case 'empty':
                    $message = '<p class="school-error">' . __('Please enter your promo code.', 'school-manager-lite') . '</p>';
                    break;
                case 'invalid':
                    $message = '<p class="school-error">' . __('Invalid promo code. Please check and try again.', 'school-manager-lite') . '</p>';
                    break;
                case 'used':
                    $message = '<p class="school-error">' . __('This promo code has already been redeemed.', 'school-manager-lite') . '</p>';
                    break;
                default:
                    $message = '<p class="school-error">' . __('An error occurred. Please try again.', 'school-manager-lite') . '</p>';
            }
        } elseif (isset($_GET['school_code_success'])) {
            $message = '<p class="school-success">' . __('Success! Your promo code has been redeemed.', 'school-manager-lite') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Form HTML
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>">
            <h3 class="school-promo-title"><?php echo esc_html($atts['title']); ?></h3>
            
            <?php echo $message; ?>
            
            <form id="school-promo-form" class="school-promo-form" method="post">
                <div class="school-form-group">
                    <label for="promo_code"><?php _e('Enter your promo code:', 'school-manager-lite'); ?></label>
                    <input type="text" name="promo_code" id="promo_code" class="school-form-control" placeholder="<?php _e('Promo Code', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group">
                    <label for="student_name"><?php _e('Your Name:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_name" id="student_name" class="school-form-control" placeholder="<?php _e('Full Name', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group">
                    <label for="student_phone"><?php _e('Your Phone Number:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_phone" id="student_phone" class="school-form-control" placeholder="<?php _e('Phone Number (Username)', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group">
                    <label for="student_id"><?php _e('Your ID:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_id" id="student_id" class="school-form-control" placeholder="<?php _e('ID Number (Password)', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-submit">
                    <?php wp_nonce_field('school_redeem_promo_code', 'school_promo_nonce'); ?>
                    <input type="hidden" name="action" value="school_redeem_promo_code">
                    <button type="submit" class="school-button" id="school-redeem-button"><?php echo esc_html($atts['button_text']); ?></button>
                    <span class="school-loading" style="display:none;"><?php _e('Processing...', 'school-manager-lite'); ?></span>
                </div>
                
                <div class="school-message-container"></div>
            </form>
        </div>
        <?php
        
        // Return buffered content
        return ob_get_clean();
    }

    /**
     * AJAX handler for promo code redemption
     */
    public function ajax_redeem_promo_code() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school-promo-code-redemption')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'school-manager-lite')));
        }
        
        // Get form data
        $promo_code = isset($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : '';
        $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
        $student_phone = isset($_POST['student_phone']) ? sanitize_text_field($_POST['student_phone']) : ''; // Phone number used as username
        $student_id = isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : ''; // ID used as password
        
        // Validate required fields
        if (empty($promo_code) || empty($student_name) || empty($student_phone) || empty($student_id)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'school-manager-lite')));
        }
        
        // Get promo code manager
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        
        // Try to redeem the promo code
        $result = $promo_code_manager->redeem_promo_code($promo_code, null, array(
            'student_name' => $student_name,
            'username' => $student_phone,  // Phone number used as username
            'password' => $student_id,     // ID used as password
            'user_login' => $student_phone // Explicitly set user_login to phone number
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Success! Your promo code has been redeemed.', 'school-manager-lite'),
                'student_id' => $result
            ));
        }
    }
}

// Initialize shortcodes
function School_Manager_Lite_Shortcodes() {
    return School_Manager_Lite_Shortcodes::instance();
}
