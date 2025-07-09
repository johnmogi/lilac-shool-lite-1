<?php
/**
 * Fallback registration functionality
 */
class School_Manager_Lite_Registration_Fallback {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only add hooks if the theme's registration files are missing
        if (!class_exists('Registration_Wizard')) {
            add_action('init', array($this, 'handle_registration_shortcodes'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        }
    }
    
    public function handle_registration_shortcodes() {
        // Fallback registration shortcode
        add_shortcode('promo_code_registration', array($this, 'render_registration_form'));
        add_shortcode('promo_code', array($this, 'render_promo_code_form'));
    }
    
    public function enqueue_assets() {
        if (is_page() && (has_shortcode(get_the_content(), 'promo_code_registration') || 
                          has_shortcode(get_the_content(), 'promo_code'))) {
            wp_enqueue_style(
                'school-manager-registration',
                SCHOOL_MANAGER_LITE_URL . 'assets/css/registration.css',
                array(),
                SCHOOL_MANAGER_LITE_VERSION
            );
            
            wp_enqueue_script(
                'school-manager-registration',
                SCHOOL_MANAGER_LITE_URL . 'assets/js/registration.js',
                array('jquery'),
                SCHOOL_MANAGER_LITE_VERSION,
                true
            );
            
            wp_localize_script('school-manager-registration', 'schoolManagerLite', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('school-manager-lite-registration')
            ));
        }
    }
    
    public function render_registration_form($atts) {
        ob_start();
        ?>
        <div class="school-manager-registration-form">
            <h2><?php esc_html_e('Create an Account', 'school-manager-lite'); ?></h2>
            <form id="school-manager-registration" method="post">
                <?php wp_nonce_field('school_manager_register', 'school_manager_nonce'); ?>
                
                <div class="form-group">
                    <label for="first_name"><?php esc_html_e('First Name', 'school-manager-lite'); ?> *</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name"><?php esc_html_e('Last Name', 'school-manager-lite'); ?> *</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><?php esc_html_e('Email Address', 'school-manager-lite'); ?> *</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'school-manager-lite'); ?> *</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <div class="form-group">
                    <label for="promo_code"><?php esc_html_e('Promo Code', 'school-manager-lite'); ?></label>
                    <input type="text" name="promo_code" id="promo_code">
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button"><?php esc_html_e('Register', 'school-manager-lite'); ?></button>
                </div>
                
                <div class="registration-messages"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_promo_code_form($atts) {
        ob_start();
        ?>
        <div class="school-manager-promo-form">
            <h2><?php esc_html_e('Redeem Promo Code', 'school-manager-lite'); ?></h2>
            <form id="school-manager-promo" method="post">
                <?php wp_nonce_field('school_manager_redeem_promo', 'school_manager_nonce'); ?>
                
                <div class="form-group">
                    <label for="promo_code"><?php esc_html_e('Enter your promo code:', 'school-manager-lite'); ?></label>
                    <input type="text" name="promo_code" id="promo_code" required>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button"><?php esc_html_e('Redeem', 'school-manager-lite'); ?></button>
                </div>
                
                <div class="promo-messages"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the fallback registration
function school_manager_lite_init_registration_fallback() {
    // Only initialize if the theme's registration files are missing
    if (!class_exists('Registration_Wizard')) {
        School_Manager_Lite_Registration_Fallback::instance();
    }
}
add_action('plugins_loaded', 'school_manager_lite_init_registration_fallback');
