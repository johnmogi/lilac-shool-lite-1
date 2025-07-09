<?php
/**
 * Admin Dashboard Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap school-manager-admin-dashboard">
    <h1><?php _e('School Manager Dashboard', 'school-manager-lite'); ?></h1>
    
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('Welcome to School Manager Lite', 'school-manager-lite'); ?></h2>
            <p class="about-description"><?php _e('Easily manage teachers, classes, students, and promo codes for your school.', 'school-manager-lite'); ?></p>
            
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('Quick Summary', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><?php printf(_n('You have %s teacher', 'You have %s teachers', count($teachers), 'school-manager-lite'), '<strong>' . count($teachers) . '</strong>'); ?></li>
                        <li><?php printf(_n('You have %s class', 'You have %s classes', count($classes), 'school-manager-lite'), '<strong>' . count($classes) . '</strong>'); ?></li>
                        <li><?php printf(_n('You have %s student', 'You have %s students', count($students), 'school-manager-lite'), '<strong>' . count($students) . '</strong>'); ?></li>
                        <li><?php printf(_n('You have %s promo code', 'You have %s promo codes', count($promo_codes), 'school-manager-lite'), '<strong>' . count($promo_codes) . '</strong>'); ?></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column">
                    <h3><?php _e('Quick Actions', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-teachers'); ?>" class="button button-primary"><?php _e('Manage Teachers', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-classes'); ?>" class="button button-primary"><?php _e('Manage Classes', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-students'); ?>" class="button button-primary"><?php _e('Manage Students', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-promo-codes'); ?>" class="button button-primary"><?php _e('Manage Promo Codes', 'school-manager-lite'); ?></a></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column welcome-panel-last">
                    <h3><?php _e('Getting Started', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-wizard'); ?>" class="button button-primary"><?php _e('Run Setup Wizard', 'school-manager-lite'); ?></a></li>
                        <li><?php _e('Need help? Check the README.md file for documentation.', 'school-manager-lite'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .school-manager-admin-dashboard .welcome-panel {
        padding: 23px 10px 0;
    }
    
    .school-manager-admin-dashboard .welcome-panel-content {
        max-width: 1300px;
    }
</style>
