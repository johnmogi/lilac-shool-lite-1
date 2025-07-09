<?php
/**
 * Teacher Classes Template
 *
 * This template displays the teacher's classes in WordPress admin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap school-manager-teacher-classes">
    <h1><?php _e('My Classes', 'school-manager-lite'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <p><?php printf(_n('You have %s class', 'You have %s classes', count($classes), 'school-manager-lite'), '<strong>' . count($classes) . '</strong>'); ?></p>
        </div>
        <br class="clear">
    </div>
    
    <?php if (!empty($classes)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'school-manager-lite'); ?></th>
                    <th><?php _e('Description', 'school-manager-lite'); ?></th>
                    <th><?php _e('Students', 'school-manager-lite'); ?></th>
                    <th><?php _e('Created', 'school-manager-lite'); ?></th>
                    <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class) : 
                    $student_count = $class_manager->count_students_in_class($class->id);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($class->name); ?></strong>
                    </td>
                    <td>
                        <?php echo !empty($class->description) ? esc_html($class->description) : '&mdash;'; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $class->id); ?>">
                            <?php echo $student_count; ?>
                        </a>
                    </td>
                    <td>
                        <?php echo date_i18n(get_option('date_format'), strtotime($class->created_at)); ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $class->id); ?>" class="button button-small">
                            <?php _e('View Students', 'school-manager-lite'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-promo-codes&class_id=' . $class->id); ?>" class="button button-small">
                            <?php _e('Promo Codes', 'school-manager-lite'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="notice notice-info">
            <p><?php _e('You don\'t have any classes assigned to you yet. Please contact your administrator.', 'school-manager-lite'); ?></p>
        </div>
    <?php endif; ?>
</div>
