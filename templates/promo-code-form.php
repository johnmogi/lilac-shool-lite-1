<?php
/**
 * Template for the promo code form shortcode
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Default values
$title = isset($atts['title']) ? $atts['title'] : __('Register with Promo Code', 'school-manager-lite');
$description = isset($atts['description']) ? $atts['description'] : __('Enter your promo code to register for the class.', 'school-manager-lite');
$redirect = isset($atts['redirect']) ? $atts['redirect'] : '';

// Check if we have a promo code from session
$promo_code = '';
if (isset($_SESSION['school_promo_code'])) {
    $promo_code = $_SESSION['school_promo_code'];
}

// Unique ID for the form
$form_id = 'school-promo-code-form-' . uniqid();
?>

<div class="school-promo-code-form-wrapper">
    <h2><?php echo esc_html($title); ?></h2>
    <p><?php echo esc_html($description); ?></p>
    
    <div class="school-messages"></div>
    
    <form id="<?php echo esc_attr($form_id); ?>" class="school-promo-code-form" method="post">
        <?php wp_nonce_field('school_redeem_promo', 'school_nonce'); ?>
        <input type="hidden" name="action" value="school_redeem_promo" />
        <?php if (!empty($redirect)) : ?>
        <input type="hidden" name="redirect" value="<?php echo esc_url($redirect); ?>" />
        <?php endif; ?>
        
        <div class="form-group">
            <label for="promo_code"><?php _e('Promo Code', 'school-manager-lite'); ?></label>
            <input type="text" name="promo_code" id="promo_code" value="<?php echo esc_attr($promo_code); ?>" required />
            <button type="button" class="validate-code-btn"><?php _e('Validate', 'school-manager-lite'); ?></button>
        </div>
        
        <div class="form-group code-validated" style="display: none;">
            <label for="student_name"><?php _e('Your Name', 'school-manager-lite'); ?></label>
            <input type="text" name="student_name" id="student_name" required />
        </div>
        
        <div class="form-group code-validated" style="display: none;">
            <label for="student_email"><?php _e('Your Email', 'school-manager-lite'); ?></label>
            <input type="email" name="student_email" id="student_email" required />
        </div>
        
        <div class="form-group code-validated" style="display: none;">
            <label>
                <input type="checkbox" name="create_account" value="1" checked />
                <?php _e('Create account to track progress', 'school-manager-lite'); ?>
            </label>
        </div>
        
        <div class="form-submit code-validated" style="display: none;">
            <button type="submit" class="submit-btn"><?php _e('Register for Class', 'school-manager-lite'); ?></button>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var form = $('#<?php echo esc_attr($form_id); ?>');
    var messages = form.siblings('.school-messages');
    
    // Validate code button
    form.find('.validate-code-btn').on('click', function() {
        var code = form.find('#promo_code').val();
        
        if (!code) {
            messages.html('<div class="error"><?php _e('Please enter a promo code', 'school-manager-lite'); ?></div>');
            return;
        }
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'validate_promo_code',
                _wpnonce: '<?php echo wp_create_nonce('validate_promo_code'); ?>',
                promo_code: code
            },
            beforeSend: function() {
                messages.html('<div class="info"><?php _e('Validating...', 'school-manager-lite'); ?></div>');
            },
            success: function(response) {
                if (response.success) {
                    messages.html('<div class="success">' + response.data.message + ': ' + response.data.class_name + '</div>');
                    form.find('.code-validated').slideDown();
                } else {
                    messages.html('<div class="error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                messages.html('<div class="error"><?php _e('Error validating code. Please try again.', 'school-manager-lite'); ?></div>');
            }
        });
    });
    
    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        var code = form.find('#promo_code').val();
        var name = form.find('#student_name').val();
        var email = form.find('#student_email').val();
        var create_account = form.find('input[name="create_account"]').is(':checked');
        
        if (!code || !name || !email) {
            messages.html('<div class="error"><?php _e('All fields are required', 'school-manager-lite'); ?></div>');
            return;
        }
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'redeem_promo_code',
                _wpnonce: '<?php echo wp_create_nonce('redeem_promo_code'); ?>',
                promo_code: code,
                student_name: name,
                student_email: email,
                create_account: create_account ? 1 : 0
            },
            beforeSend: function() {
                messages.html('<div class="info"><?php _e('Processing registration...', 'school-manager-lite'); ?></div>');
            },
            success: function(response) {
                if (response.success) {
                    messages.html('<div class="success">' + response.data.message + '</div>');
                    form.hide();
                    
                    // Redirect if needed
                    <?php if (!empty($redirect)) : ?>
                    setTimeout(function() {
                        window.location.href = '<?php echo esc_url($redirect); ?>';
                    }, 2000);
                    <?php endif; ?>
                } else {
                    messages.html('<div class="error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                messages.html('<div class="error"><?php _e('Error processing registration. Please try again.', 'school-manager-lite'); ?></div>');
            }
        });
    });
});
</script>

<style type="text/css">
.school-promo-code-form-wrapper {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
}

.school-promo-code-form .form-group {
    margin-bottom: 15px;
}

.school-promo-code-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.school-promo-code-form input[type="text"],
.school-promo-code-form input[type="email"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.school-promo-code-form button {
    padding: 8px 15px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.school-promo-code-form button:hover {
    background: #005177;
}

.school-messages .success {
    padding: 10px;
    margin: 10px 0;
    background: #dff0d8;
    border: 1px solid #d6e9c6;
    color: #3c763d;
    border-radius: 3px;
}

.school-messages .error {
    padding: 10px;
    margin: 10px 0;
    background: #f2dede;
    border: 1px solid #ebccd1;
    color: #a94442;
    border-radius: 3px;
}

.school-messages .info {
    padding: 10px;
    margin: 10px 0;
    background: #d9edf7;
    border: 1px solid #bce8f1;
    color: #31708f;
    border-radius: 3px;
}
</style>
