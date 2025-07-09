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

        // Shortcode to allow student to freeze / pause their enrollment
        add_shortcode('school_freeze_access', array($this, 'freeze_access_shortcode'));
        
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
                    'code_required' => __('אנא הזן את קוד הקופון.', 'school-manager-lite'),
                    'processing' => __('מטעין...', 'school-manager-lite'),
                    'error' => __('אירעה שגיאה. נסה שוב.', 'school-manager-lite'),
                    'invalid_code' => __('קוד לא תקין. נא לבדוק ולנסות שוב.', 'school-manager-lite'),
                    'code_already_used' => __('קוד זה כבר נוצל.', 'school-manager-lite'),
                    'success' => __('הצלחה! הקוד הופעל בהצלחה.', 'school-manager-lite'),
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
        
        // Form HTML with inline styles as fallback
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>" style="max-width: 600px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <?php if (!empty($atts['title'])) : ?>
            <h3 class="school-promo-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <?php echo $message; ?>
            
            <form id="school-promo-form" class="school-promo-form" method="post">
                <div class="school-form-group" style="margin-bottom: 15px;">
                    <label for="promo_code" style="display: block; margin-bottom: 5px; font-weight: 500;"><?php _e('הזן קוד קופון:', 'school-manager-lite'); ?></label>
                    <input type="text" name="promo_code" id="promo_code" class="school-form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px;" placeholder="<?php _e('קוד קופון', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group" style="margin-bottom: 15px;">
                    <label for="student_name"><?php _e('שם מלא:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_name" id="student_name" class="school-form-control" placeholder="<?php _e('שם', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group" style="margin-bottom: 15px;">
                    <label for="student_phone"><?php _e('מספר טלפון:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_phone" id="student_phone" class="school-form-control" placeholder="<?php _e('טלפון', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-group" style="margin-bottom: 15px;">
                    <label for="student_id"><?php _e('תעודת זהות:', 'school-manager-lite'); ?></label>
                    <input type="text" name="student_id" id="student_id" class="school-form-control" placeholder="<?php _e('ת.ז', 'school-manager-lite'); ?>" required>
                </div>
                
                <div class="school-form-submit" style="margin-top: 20px; text-align: center;">
                    <?php wp_nonce_field('school_redeem_promo_code', 'school_promo_nonce'); ?>
                    <input type="hidden" name="action" value="school_redeem_promo_code">
                    <button type="submit" class="school-button" id="school-redeem-button" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s;"><?php echo esc_html($atts['button_text']); ?></button>
                    <span class="school-loading" style="display:none; margin-left: 10px; font-style: italic; color: #666;"><?php _e('Processing...', 'school-manager-lite'); ?></span>
                </div>
                
                <div class="school-message-container" style="margin-top: 15px;"></div>
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
        $nonce_value = isset($_POST['school_promo_nonce']) ? $_POST['school_promo_nonce'] : ( isset($_POST['nonce']) ? $_POST['nonce'] : '' );
        if ( empty( $nonce_value ) || ( ! wp_verify_nonce( $nonce_value, 'school_redeem_promo_code' ) && ! wp_verify_nonce( $nonce_value, 'school-promo-code-redemption' ) ) ) {
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

    /**
     * Shortcode handler to freeze / pause a student's LearnDash course access.
     * Usage: [school_freeze_access]
     */
    public function freeze_access_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return __( 'You need to be logged in to freeze your course access.', 'school-manager-lite' );
        }
        $wp_user_id      = get_current_user_id();
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_row     = $student_manager->get_student_by_wp_user_id( $wp_user_id );
        if ( ! $student_row ) {
            return __( 'You are not recognised as a student in the system.', 'school-manager-lite' );
        }
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class         = $class_manager->get_class( $student_row->class_id );
        $course_id     = 898; // default fallback course ID
        if ( $class && isset( $class->course_id ) && $class->course_id ) {
            $course_id = (int) $class->course_id;
        }
        // Remove LearnDash course access
        if ( function_exists( 'ld_update_course_access' ) ) {
            ld_update_course_access( $wp_user_id, $course_id, /* remove */ true );
        }
        // Mark user as inactive
        update_user_meta( $wp_user_id, 'school_student_status', 'inactive' );
        return __( 'Your course access has been paused successfully.', 'school-manager-lite' );
    }
}

// Initialize shortcodes
function School_Manager_Lite_Shortcodes() {
    return School_Manager_Lite_Shortcodes::instance();
}
