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
        
        // Add AJAX handler for teacher promo code creation
        add_action('wp_ajax_teacher_create_promo_codes', array($this, 'ajax_teacher_create_promo_codes'));
        add_action('wp_ajax_nopriv_teacher_create_promo_codes', array($this, 'ajax_teacher_create_promo_codes'));
        
        // Register frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
    }
    
    /**
     * Render student quiz status
     * 
     * Shortcode: [school_student_quiz_status student_id="" course_id=""]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_student_quiz_status($atts) {
        // Only allow logged-in users
        if (!is_user_logged_in()) {
            return '<div class="school-manager-notice">' . 
                   __('Please log in to view quiz status.', 'school-manager-lite') . 
                   '</div>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'student_id' => get_current_user_id(),
                'course_id' => 0,
            ),
            $atts,
            'school_student_quiz_status'
        );
        
        // Check if current user can view this student's data
        $current_user = wp_get_current_user();
        $student_id = intval($atts['student_id']);
        $course_id = intval($atts['course_id']);
        
        // Allow admins to view any student's data
        if (!current_user_can('manage_options') && $current_user->ID != $student_id) {
            // Check if current user is a teacher and the student is in their class
            $is_teacher = in_array('school_teacher', (array) $current_user->roles) || 
                         in_array('wdm_instructor', (array) $current_user->roles) ||
                         in_array('instructor', (array) $current_user->roles);
            
            if ($is_teacher) {
                $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
                $students = $teacher_manager->get_teacher_students($current_user->ID);
                $student_ids = wp_list_pluck($students, 'ID');
                
                if (!in_array($student_id, $student_ids)) {
                    return '<div class="school-manager-notice">' . 
                           __('You do not have permission to view this student\'s quiz status.', 'school-manager-lite') . 
                           '</div>';
                }
            } else {
                return '<div class="school-manager-notice">' . 
                       __('You do not have permission to view this quiz status.', 'school-manager-lite') . 
                       '</div>';
            }
        }
        
        // Get the quiz status
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        return $teacher_manager->get_student_quiz_status($student_id, $course_id);
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
        
        // Quiz status shortcode for students and teachers
        add_shortcode('school_student_quiz_status', array($this, 'render_student_quiz_status'));
        
        // Shortcode for teachers to create promo codes
        add_shortcode('teacher_create_promo_codes', array($this, 'teacher_create_promo_codes_shortcode'));
        
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
                'course_id' => '', // Optional specific course ID to assign
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
                'course_id' => !empty($atts['course_id']) ? intval($atts['course_id']) : 0,
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
                    $message = '<p class="school-error">' . __('אנא הזן את קוד הקופון.', 'school-manager-lite') . '</p>';
                    break;
                case 'invalid':
                    $message = '<p class="school-error">' . __('קוד לא תקין. נא לבדוק ולנסות שוב.', 'school-manager-lite') . '</p>';
                    break;
                case 'used':
                    $message = '<p class="school-error">' . __('קוד זה כבר נוצל.', 'school-manager-lite') . '</p>';
                    break;
                case 'expired':
                    $message = '<p class="school-error">' . __('קוד זה פג תוקף.', 'school-manager-lite') . '</p>';
                    break;
                case 'limit_reached':
                    $message = '<p class="school-error">' . __('קוד זה הגיע למגבלת השימוש.', 'school-manager-lite') . '</p>';
                    break;
                case 'user_has_promo':
                    $message = '<p class="school-error">' . __('לתלמיד זה כבר יש קוד קופון.', 'school-manager-lite') . '</p>';
                    break;
                case 'duplicate_student_id':
                    $message = '<p class="school-error">' . __('תלמיד עם תעודת זהות זו כבר קיים במערכת.', 'school-manager-lite') . '</p>';
                    break;
                default:
                    $message = '<p class="school-error">' . __('אירעה שגיאה. נסה שוב.', 'school-manager-lite') . '</p>';
            }
        } elseif (isset($_GET['school_code_success'])) {
            $message = '<p class="school-success">' . __('הצלחה! הקוד הופעל בהצלחה.', 'school-manager-lite') . '</p>';
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
                    <?php if (!empty($atts['course_id'])) : ?>
                    <input type="hidden" name="course_id" value="<?php echo esc_attr($atts['course_id']); ?>">
                    <?php endif; ?>
                    <button type="submit" class="school-button" id="school-redeem-button" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s;"><?php echo esc_html($atts['button_text']); ?></button>
                    <span class="school-loading" style="display:none; margin-left: 10px; font-style: italic; color: #666;"><?php _e('מטעין...', 'school-manager-lite'); ?></span>
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
            wp_send_json_error(array('message' => __('בדיקת אבטחה נכשלה. אנא רענן את העמוד ונסה שוב.', 'school-manager-lite')));
        }
        
        // Get form data
        $promo_code = isset($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : '';
        $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
        $student_phone = isset($_POST['student_phone']) ? sanitize_text_field($_POST['student_phone']) : ''; // Phone number used as username
        $student_id = isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : ''; // ID used as password
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0; // Optional specific course ID
        
        // Validate required fields
        if (empty($promo_code) || empty($student_name) || empty($student_phone) || empty($student_id)) {
            wp_send_json_error(array('message' => __('כל השדות דרושים.', 'school-manager-lite')));
        }
        
        // Get promo code manager
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        
        // Try to redeem the promo code
        $result = $promo_code_manager->redeem_promo_code($promo_code, null, array(
            'student_name' => $student_name,
            'username' => $student_phone,  // Phone number used as username
            'password' => $student_id,     // ID used as password
            'user_login' => $student_phone, // Explicitly set user_login to phone number
            'course_id' => $course_id      // Optional specific course ID
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            // Get the WordPress user ID from the result
            $wp_user_id = isset($result['wp_user_id']) ? $result['wp_user_id'] : null;
            
            // Log in the user if we have a valid user ID
            if ($wp_user_id) {
                wp_set_current_user($wp_user_id);
                wp_set_auth_cookie($wp_user_id, true);
                do_action('wp_login', wp_get_current_user()->user_login, wp_get_current_user());
                
                error_log('School Manager Lite: Logged in user ' . $wp_user_id . ' after promo code redemption');
            }
            
            wp_send_json_success(array(
                'message' => __('הצלחה! הקוד הופעל בהצלחה.', 'school-manager-lite'),
                'student_id' => isset($result['student_id']) ? $result['student_id'] : $result,
                'wp_user_id' => $wp_user_id,
                'redirect' => home_url('/my-courses/')
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
    
    /**
     * Teacher promo code creation shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function teacher_create_promo_codes_shortcode($atts) {
        // Check if user is logged in and has teacher capabilities
        if (!is_user_logged_in()) {
            return '<p>יש להתחבר כדי ליצור קודי הטבה.</p>';
        }
        
        $current_user = wp_get_current_user();
        
        // Check for various instructor and admin roles
        $allowed_roles = array(
            'administrator',
            'school_teacher', 
            'wdm_instructor',
            'instructor',
            'wdm_swd_instructor',
            'swd_instructor'
        );
        
        $has_permission = false;
        foreach ($allowed_roles as $role) {
            if (current_user_can($role) || in_array($role, $current_user->roles)) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            return '<p>אין לך הרשאה ליצור קודי הטבה. תפקיד נוכחי: ' . implode(', ', $current_user->roles) . '</p>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'title' => 'יצירת קודי הטבה',
                'class' => 'teacher-promo-creation',
                'course_id' => '898', // Default course ID
            ),
            $atts,
            'teacher_create_promo_codes'
        );
        
        // Enqueue scripts
        wp_enqueue_style('school-manager-lite-frontend');
        wp_enqueue_script('school-manager-lite-frontend');
        
        // Localize script for AJAX
        wp_localize_script('school-manager-lite-frontend', 'teacher_promo_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teacher_create_promo_codes'),
            'messages' => array(
                'creating' => 'יוצר קודי הטבה...',
                'success' => 'קודי הטבה נוצרו בהצלחה!',
                'error' => 'שגיאה ביצירת קודי הטבה.',
            )
        ));
        
        // Get teacher's classes (or all classes for administrators)
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        if (current_user_can('administrator')) {
            // Administrators can see all classes or create new ones
            $teacher_classes = $class_manager->get_classes(); // Get all classes
        } else {
            // Regular teachers see only their classes
            $teacher_classes = $class_manager->get_classes(array('teacher_id' => $current_user->ID));
        }
        
        // Debug info
        $debug_info = '';
        if (current_user_can('administrator')) {
            $debug_info = '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px;">';
            $debug_info .= '<strong>Debug Info:</strong><br>';
            $debug_info .= 'User ID: ' . $current_user->ID . '<br>';
            $debug_info .= 'User Roles: ' . implode(', ', $current_user->roles) . '<br>';
            $debug_info .= 'Classes Found: ' . count($teacher_classes) . '<br>';
            if (!empty($teacher_classes)) {
                $debug_info .= 'Class Names: ' . implode(', ', array_map(function($c) { return $c->name; }, $teacher_classes)) . '<br>';
            }
            $debug_info .= '</div>';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <?php echo $debug_info; ?>
            
            <form id="teacher-promo-form" method="post">
                <?php wp_nonce_field('teacher_create_promo_codes', 'teacher_promo_nonce'); ?>
                
                <!-- Class Selection or Creation -->
                <div class="form-group">
                    <label>בחר או צור כיתה:</label>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="radio" name="class_option" id="use_existing_class" value="existing" checked>
                        <label for="use_existing_class" style="display: inline; margin-right: 10px;">בחר כיתה קיימת</label>
                    </div>
                    
                    <div id="existing_class_section" style="margin-bottom: 15px;">
                        <select name="class_id" id="promo_class_id">
                            <option value="">בחר כיתה</option>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?php echo esc_attr($class->id); ?>">
                                    <?php echo esc_html($class->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($teacher_classes)): ?>
                            <p style="color: #666; font-size: 12px; margin: 5px 0;">לא נמצאו כיתות קיימות. אנא צור כיתה חדשה.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="radio" name="class_option" id="create_new_class" value="new">
                        <label for="create_new_class" style="display: inline; margin-right: 10px;">צור כיתה חדשה</label>
                    </div>
                    
                    <div id="new_class_section" style="display: none; margin-bottom: 15px;">
                        <input type="text" name="new_class_name" id="new_class_name" placeholder="שם הכיתה החדשה" maxlength="100">
                        <small style="display: block; color: #666; margin-top: 5px;">למשל: כיתה א' 2025, קבוצת בוקר, וכו'</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="promo_quantity">כמות קודים:</label>
                    <input type="number" name="quantity" id="promo_quantity" min="1" max="50" value="5" required>
                </div>
                
                <div class="form-group">
                    <label for="promo_prefix">תחילית קוד (אופציונלי):</label>
                    <input type="text" name="prefix" id="promo_prefix" maxlength="10" placeholder="למשל: CLASS1">
                </div>
                
                <div class="form-group">
                    <label for="promo_expiry">תאריך תוקף:</label>
                    <input type="date" name="expiry_date" id="promo_expiry" value="<?php echo date('Y') . '-06-30'; ?>">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" name="create_learndash_group" id="create_learndash_group" value="1" style="margin-left: 8px;" checked>
                        צור קבוצת LearnDash אוטומטית
                    </label>
                    <small style="color: #666; margin-top: 5px; display: block;">מומלץ להשאיר מסומן לניהול טוב יותר של התלמידים</small>
                </div>
                
                <input type="hidden" name="course_id" value="<?php echo esc_attr($atts['course_id']); ?>">
                <input type="hidden" name="teacher_id" value="<?php echo esc_attr($current_user->ID); ?>">
                
                <div class="form-group">
                    <button type="submit" class="button button-primary">צור קודי הטבה</button>
                </div>
            </form>
            
            <div id="promo-creation-result" style="margin-top: 20px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle class option radio buttons
            $('input[name="class_option"]').on('change', function() {
                if ($(this).val() === 'existing') {
                    $('#existing_class_section').show();
                    $('#new_class_section').hide();
                    $('#promo_class_id').prop('required', true);
                    $('#new_class_name').prop('required', false);
                } else {
                    $('#existing_class_section').hide();
                    $('#new_class_section').show();
                    $('#promo_class_id').prop('required', false);
                    $('#new_class_name').prop('required', true);
                }
            });
            
            $('#teacher-promo-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $result = $('#promo-creation-result');
                var $button = $form.find('button[type="submit"]');
                
                // Validate form
                var classOption = $('input[name="class_option"]:checked').val();
                var classId = $('#promo_class_id').val();
                var newClassName = $('#new_class_name').val().trim();
                
                if (classOption === 'existing' && !classId) {
                    $result.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">אנא בחר כיתה קיימת או צור כיתה חדשה.</div>');
                    return;
                }
                
                if (classOption === 'new' && !newClassName) {
                    $result.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">אנא הזן שם לכיתה החדשה.</div>');
                    return;
                }
                
                $button.prop('disabled', true).text(teacher_promo_ajax.messages.creating);
                $result.html('');
                
                $.ajax({
                    url: teacher_promo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'teacher_create_promo_codes',
                        nonce: teacher_promo_ajax.nonce,
                        class_option: classOption,
                        class_id: classId,
                        new_class_name: newClassName,
                        quantity: $('#promo_quantity').val(),
                        prefix: $('#promo_prefix').val(),
                        expiry_date: $('#promo_expiry').val(),
                        course_id: $('input[name="course_id"]').val(),
                        teacher_id: $('input[name="teacher_id"]').val(),
                        create_learndash_group: $('#create_learndash_group').is(':checked') ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="success-message" style="color: green; padding: 10px; border: 1px solid green; background: #f0fff0;">' + 
                                        '<h4>קודי הטבה נוצרו בהצלחה!</h4>' +
                                        '<p>נוצרו ' + response.data.codes.length + ' קודי הטבה:</p>' +
                                        '<div style="background: #f9f9f9; padding: 10px; margin: 10px 0; font-family: monospace;">' +
                                        response.data.codes.join('<br>') +
                                        '</div>' +
                                        '<p><strong>הוראות:</strong> העבר את הקודים האלה לתלמידים. כל קוד ניתן לשימוש פעם אחת בלבד.</p>' +
                                        '</div>');
                            $form[0].reset();
                        } else {
                            $result.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">' + 
                                        '<strong>שגיאה:</strong> ' + (response.data || teacher_promo_ajax.messages.error) + 
                                        '</div>');
                        }
                    },
                    error: function() {
                        $result.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">' + 
                                    '<strong>שגיאה:</strong> ' + teacher_promo_ajax.messages.error + 
                                    '</div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('צור קודי הטבה');
                    }
                });
            });
        });
        </script>
        
        <style>
        .teacher-promo-creation .form-group {
            margin-bottom: 15px;
        }
        .teacher-promo-creation label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .teacher-promo-creation input,
        .teacher-promo-creation select {
            width: 100%;
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .teacher-promo-creation button {
            padding: 10px 20px;
            font-size: 16px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for teacher promo code creation
     */
    public function ajax_teacher_create_promo_codes() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'teacher_create_promo_codes')) {
            wp_send_json_error('בדיקת אבטחה נכשלה. אנא רענן את העמוד ונסה שוב.');
        }
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error('יש להתחבר כדי ליצור קודי הטבה.');
        }
        
        $current_user = wp_get_current_user();
        
        // Check for various instructor and admin roles
        $allowed_roles = array(
            'administrator',
            'school_teacher', 
            'wdm_instructor',
            'instructor',
            'wdm_swd_instructor',
            'swd_instructor'
        );
        
        $has_permission = false;
        foreach ($allowed_roles as $role) {
            if (current_user_can($role) || in_array($role, $current_user->roles)) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            wp_send_json_error('אין לך הרשאה ליצור קודי הטבה. תפקיד: ' . implode(', ', $current_user->roles));
        }
        
        // Sanitize and validate inputs
        $class_option = isset($_POST['class_option']) ? sanitize_text_field($_POST['class_option']) : 'existing';
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $new_class_name = isset($_POST['new_class_name']) ? sanitize_text_field($_POST['new_class_name']) : '';
        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        $quantity = isset($_POST['quantity']) ? max(1, min(50, intval($_POST['quantity']))) : 5;
        $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
        $expiry_date = !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 898;
        $create_group = isset($_POST['create_learndash_group']) && $_POST['create_learndash_group'] == '1';
        
        // Validate teacher ID
        if (empty($teacher_id) || $teacher_id !== $current_user->ID) {
            wp_send_json_error('שגיאה במזהה המורה.');
        }
        
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = null;
        
        // Handle class creation or selection
        if ($class_option === 'new') {
            // Create new class
            if (empty($new_class_name)) {
                wp_send_json_error('יש להזין שם לכיתה החדשה.');
            }
            
            // Create the new class
            $class_data = array(
                'name' => $new_class_name,
                'teacher_id' => $teacher_id,
                'created_at' => current_time('mysql')
            );
            
            $new_class_id = $class_manager->create_class($class_data);
            if (is_wp_error($new_class_id)) {
                wp_send_json_error('שגיאה ביצירת הכיתה: ' . $new_class_id->get_error_message());
            }
            
            $class_id = $new_class_id;
            $class = $class_manager->get_class($class_id);
            
        } else {
            // Use existing class
            if (empty($class_id)) {
                wp_send_json_error('יש לבחור כיתה קיימת.');
            }
            
            $class = $class_manager->get_class($class_id);
            
            // Verify teacher owns this class (administrators can use any class)
            if (!$class) {
                wp_send_json_error('כיתה לא נמצאה.');
            }
            
            if (!current_user_can('administrator') && intval($class->teacher_id) !== $current_user->ID) {
                wp_send_json_error('אין לך הרשאה ליצור קודים עבור כיתה זו.');
            }
        }
        
        if (!$class) {
            wp_send_json_error('שגיאה בטעינת הכיתה.');
        }
        
        // Create LearnDash group if requested
        $group_id = null;
        if ($create_group) {
            $group_id = $this->create_learndash_group_for_class($class, $current_user->ID);
            if (is_wp_error($group_id)) {
                wp_send_json_error($group_id->get_error_message());
            }
        }
        
        // Create promo codes
        $promo_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $result = $promo_manager->generate_promo_codes(array(
            'quantity' => $quantity,
            'prefix' => $prefix,
            'class_id' => $class_id,
            'teacher_id' => $teacher_id,
            'expiry_date' => $expiry_date,
            'course_id' => $course_id
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Return success with generated codes
        $message = sprintf('נוצרו %d קודי הטבה בהצלחה עבור כיתה %s', count($result), esc_html($class->name));
        
        if ($class_option === 'new') {
            $message .= '<br>נוצרה גם כיתה חדשה: ' . esc_html($class->name);
        }
        
        if ($group_id) {
            $message .= '<br>נוצרה גם קבוצת LearnDash חדשה.';
        }
        
        wp_send_json_success(array(
            'codes' => $result,
            'group_id' => $group_id,
            'message' => $message
        ));
    }
    
    /**
     * Create LearnDash group for class
     */
    private function create_learndash_group_for_class($class, $teacher_id) {
        // Check if LearnDash is active
        if (!function_exists('learndash_get_post_type_slug')) {
            return new WP_Error('learndash_not_active', 'פלאגין LearnDash אינו פעיל.');
        }
        
        // Create the group
        $group_data = array(
            'post_title' => $class->name,
            'post_content' => sprintf('קבוצת לימוד עבור כיתה %s', $class->name),
            'post_status' => 'publish',
            'post_type' => learndash_get_post_type_slug('group'),
            'post_author' => $teacher_id
        );
        
        $group_id = wp_insert_post($group_data);
        
        if (is_wp_error($group_id)) {
            return $group_id;
        }
        
        // Set teacher as group leader
        $leaders = array($teacher_id);
        learndash_set_groups_administrators($group_id, $leaders);
        
        // Update class with group ID
        global $wpdb;
        $class_table = $wpdb->prefix . 'school_classes';
        $wpdb->update(
            $class_table,
            array('group_id' => $group_id),
            array('id' => $class->id),
            array('%d'),
            array('%d')
        );
        
        error_log('School Manager Lite: Created LearnDash group ' . $group_id . ' for class ' . $class->name);
        
        return $group_id;
    }
}

// Initialize shortcodes
function School_Manager_Lite_Shortcodes() {
    return School_Manager_Lite_Shortcodes::instance();
}
