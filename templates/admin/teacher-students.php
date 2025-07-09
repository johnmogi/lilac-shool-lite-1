<?php
/**
 * Teacher Students Template
 *
 * This template displays the teacher's students in WordPress admin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap school-manager-teacher-students">
    <h1><?php _e('My Students', 'school-manager-lite'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="school-teacher-students">
                <label for="class-filter" class="screen-reader-text"><?php _e('Filter by class', 'school-manager-lite'); ?></label>
                <select name="class_id" id="class-filter">
                    <option value="0"><?php _e('All Classes', 'school-manager-lite'); ?></option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo $class->id; ?>" <?php selected($filter_class_id, $class->id); ?>>
                            <?php echo esc_html($class->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'school-manager-lite'); ?>">
            </form>
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s student', '%s students', count($students), 'school-manager-lite'), count($students)); ?>
            </span>
        </div>
        <br class="clear">
    </div>
    
    <?php if (!empty($students)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'school-manager-lite'); ?></th>
                    <th><?php _e('Email', 'school-manager-lite'); ?></th>
                    <th><?php _e('Class', 'school-manager-lite'); ?></th>
                    <th><?php _e('Enrolled', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($student->name); ?></strong>
                    </td>
                    <td>
                        <a href="mailto:<?php echo esc_attr($student->email); ?>"><?php echo esc_html($student->email); ?></a>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-students&class_id=' . $student->class_id); ?>">
                            <?php echo esc_html($student->class_name); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo date_i18n(get_option('date_format'), strtotime($student->created_at)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="notice notice-info">
            <p>
                <?php 
                if ($filter_class_id > 0) {
                    _e('No students found in this class.', 'school-manager-lite');
                } else {
                    _e('You don\'t have any students enrolled in your classes yet.', 'school-manager-lite');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>
