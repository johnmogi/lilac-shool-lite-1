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
    <h1><?php _e('לוח בקרת בית הספר', 'school-manager-lite'); ?></h1>
    
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('ברוכים הבאים למערכת ניהול בית ספר', 'school-manager-lite'); ?></h2>
            <p class="about-description"><?php _e('ניהול קל של מורים, כיתות, תלמידים וקודי הנחה עבור בית הספר שלך.', 'school-manager-lite'); ?></p>
            
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('סיכום מהיר', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><?php printf(_n('יש לך %s מורה', 'יש לך %s מורים', count($teachers), 'school-manager-lite'), '<strong>' . count($teachers) . '</strong>'); ?></li>
                        <li><?php printf(_n('יש לך %s כיתה', 'יש לך %s כיתות', count($classes), 'school-manager-lite'), '<strong>' . count($classes) . '</strong>'); ?></li>
                        <li><?php printf(_n('יש לך %s תלמיד', 'יש לך %s תלמידים', count($students), 'school-manager-lite'), '<strong>' . count($students) . '</strong>'); ?></li>
                        <li><?php printf(_n('יש לך %s קוד הנחה', 'יש לך %s קודי הנחה', count($promo_codes), 'school-manager-lite'), '<strong>' . count($promo_codes) . '</strong>'); ?></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column">
                    <h3><?php _e('פעולות מהירות', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-teachers'); ?>" class="button button-primary"><?php _e('ניהול מורים', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-classes'); ?>" class="button button-primary"><?php _e('ניהול כיתות', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-students'); ?>" class="button button-primary"><?php _e('ניהול תלמידים', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-promo-codes'); ?>" class="button button-primary"><?php _e('ניהול קודי הנחה', 'school-manager-lite'); ?></a></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column welcome-panel-last">
                    <h3><?php _e('התחלה מהירה', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=school-manager-wizard'); ?>" class="button button-primary"><?php _e('הפעל אשף התקנה', 'school-manager-lite'); ?></a></li>
                        <li><?php _e('צריך עזרה? בדוק את קובץ README.md לתיעוד.', 'school-manager-lite'); ?></li>
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
