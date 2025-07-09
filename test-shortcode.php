<?php
/**
 * Test Shortcode Template
 * 
 * This file is for testing the school_manager_redeem shortcode.
 * Access it directly via: /wp-content/plugins/school-manager-lite/test-shortcode.php
 */

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Check if user is logged in, if not, redirect to login
if (!is_user_logged_in()) {
    auth_redirect();
    exit();
}

get_header();
?>

<div class="wrap">
    <h1>School Manager Lite - Shortcode Test</h1>
    
    <div class="test-shortcode">
        <h2>Testing [school_manager_redeem] shortcode:</h2>
        <?php echo do_shortcode('[school_manager_redeem]'); ?>
    </div>
    
    <div class="test-shortcode">
        <h2>Testing [school_promo_code_redemption] shortcode:</h2>
        <?php echo do_shortcode('[school_promo_code_redemption]'); ?>
    </div>
</div>

<?php
get_footer();
?>
