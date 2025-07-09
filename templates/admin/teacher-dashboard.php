<?php
/**
 * Teacher Dashboard Template
 *
 * This template displays the main teacher dashboard page within WordPress admin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap school-manager-teacher-dashboard">
    <h1><?php _e('Teacher Dashboard', 'school-manager-lite'); ?></h1>
    
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('Welcome to Your Teacher Dashboard', 'school-manager-lite'); ?></h2>
            <p class="about-description"><?php _e('Here you can manage your classes, students, and promo codes.', 'school-manager-lite'); ?></p>
            
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('Quick Summary', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><?php printf(_n('You have %s class', 'You have %s classes', count($classes), 'school-manager-lite'), '<strong>' . count($classes) . '</strong>'); ?></li>
                        <li><?php printf(_n('You have %s student', 'You have %s students', $total_students, 'school-manager-lite'), '<strong>' . $total_students . '</strong>'); ?></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column">
                    <h3><?php _e('Quick Actions', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=school-teacher-classes'); ?>" class="button button-primary"><?php _e('View My Classes', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-teacher-students'); ?>" class="button button-primary"><?php _e('View My Students', 'school-manager-lite'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=school-teacher-promo-codes'); ?>" class="button button-primary"><?php _e('Manage Promo Codes', 'school-manager-lite'); ?></a></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column welcome-panel-last">
                    <h3><?php _e('Help & Support', 'school-manager-lite'); ?></h3>
                    <ul>
                        <li><?php _e('If you need assistance, please contact your administrator.', 'school-manager-lite'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="school-manager-dashboard-widgets">
        <div class="school-manager-widget-column">
            <!-- Classes Overview -->
            <div class="school-manager-widget">
                <h2 class="school-manager-widget-title"><?php _e('Your Classes', 'school-manager-lite'); ?></h2>
                <div class="school-manager-widget-content">
                    <?php if (!empty($classes)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Name', 'school-manager-lite'); ?></th>
                                    <th><?php _e('Students', 'school-manager-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_stats as $class) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $class['id']); ?>">
                                                <?php echo esc_html($class['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $class['student_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('You don\'t have any classes assigned to you yet.', 'school-manager-lite'); ?></p>
                    <?php endif; ?>
                    
                    <p class="school-manager-widget-footer">
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-classes'); ?>" class="button">
                            <?php _e('View All Classes', 'school-manager-lite'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="school-manager-widget-column">
            <!-- Recent Activity -->
            <div class="school-manager-widget">
                <h2 class="school-manager-widget-title"><?php _e('Recent Activity', 'school-manager-lite'); ?></h2>
                <div class="school-manager-widget-content">
                    <p><?php _e('Recent student enrollments and promo code usage will be displayed here.', 'school-manager-lite'); ?></p>
                    
                    <p class="school-manager-widget-footer">
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-students'); ?>" class="button">
                            <?php _e('View All Students', 'school-manager-lite'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .school-manager-dashboard-widgets {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .school-manager-widget-column {
        flex: 1;
        min-width: 300px;
        padding: 0 10px;
        margin-bottom: 20px;
    }
    
    .school-manager-widget {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
    }
    
    .school-manager-widget-title {
        border-bottom: 1px solid #ccd0d4;
        margin: 0;
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .school-manager-widget-content {
        padding: 12px;
    }
    
    .school-manager-widget-footer {
        border-top: 1px solid #f1f1f1;
        padding-top: 12px;
        margin-bottom: 0;
        text-align: right;
    }
</style>
