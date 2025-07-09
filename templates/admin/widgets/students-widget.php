<?php
/**
 * Teacher Dashboard Students Widget Template
 *
 * This template displays the teacher's recent students in a dashboard widget
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="school-manager-students-widget">
    <?php if (!empty($students)) : ?>
        <table class="teacher-dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Name', 'school-manager-lite'); ?></th>
                    <th><?php _e('Class', 'school-manager-lite'); ?></th>
                    <th><?php _e('Enrolled', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Only show up to 5 recent students
                $recent_students = array_slice($students, 0, 5);
                
                foreach ($recent_students as $student) : 
                    // Get class name
                    $class_name = '';
                    foreach ($classes as $class) {
                        if ($class->id === $student->class_id) {
                            $class_name = $class->name;
                            break;
                        }
                    }
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($student->name); ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $student->class_id); ?>">
                                <?php echo esc_html($class_name); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo date_i18n(get_option('date_format'), strtotime($student->created_at)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($students) > 5) : ?>
            <p><?php printf(__('Showing 5 of %d students', 'school-manager-lite'), count($students)); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <p><?php _e('You don\'t have any students enrolled in your classes yet.', 'school-manager-lite'); ?></p>
    <?php endif; ?>
    
    <p class="widget-footer">
        <a href="<?php echo admin_url('admin.php?page=school-teacher-students'); ?>" class="button button-small">
            <?php _e('View All Students', 'school-manager-lite'); ?>
        </a>
    </p>
</div>

<style type="text/css">
    .school-manager-students-widget .teacher-dashboard-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    
    .school-manager-students-widget .teacher-dashboard-table th,
    .school-manager-students-widget .teacher-dashboard-table td {
        padding: 8px 5px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .school-manager-students-widget .teacher-dashboard-table th {
        font-weight: 600;
    }
    
    .school-manager-students-widget .widget-footer {
        margin-top: 10px;
        text-align: right;
    }
</style>
