<?php
/**
 * Teacher Dashboard Classes Widget Template
 *
 * This template displays the teacher's classes in a dashboard widget
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="school-manager-classes-widget">
    <?php if (!empty($classes)) : ?>
        <table class="teacher-dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Class Name', 'school-manager-lite'); ?></th>
                    <th><?php _e('Students', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class) : 
                    $student_count = isset($class_stats[$class->id]) ? $class_stats[$class->id] : 0;
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $class->id); ?>">
                                <?php echo esc_html($class->name); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo $student_count; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('You don\'t have any classes assigned to you yet.', 'school-manager-lite'); ?></p>
    <?php endif; ?>
    
    <p class="widget-footer">
        <a href="<?php echo admin_url('admin.php?page=school-teacher-classes'); ?>" class="button button-small">
            <?php _e('View All Classes', 'school-manager-lite'); ?>
        </a>
    </p>
</div>

<style type="text/css">
    .school-manager-classes-widget .teacher-dashboard-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    
    .school-manager-classes-widget .teacher-dashboard-table th,
    .school-manager-classes-widget .teacher-dashboard-table td {
        padding: 8px 5px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .school-manager-classes-widget .teacher-dashboard-table th {
        font-weight: 600;
    }
    
    .school-manager-classes-widget .widget-footer {
        margin-top: 10px;
        text-align: right;
    }
</style>
