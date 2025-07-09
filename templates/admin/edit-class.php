<?php
/**
 * Edit Class Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Security check
if (!current_user_can('manage_school_classes')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'school-manager-lite'));
}

// Process form submission
if (isset($_POST['save_class']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save_class_' . $class->id)) {
    
    // Get class data
    $class_data = array(
        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'max_students' => isset($_POST['max_students']) ? intval($_POST['max_students']) : 0,
        'teacher_id' => isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0,
    );
    
    // Update class
    $class_manager->update_class($class->id, $class_data);
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Class updated successfully.', 'school-manager-lite') . '</p></div>';
    
    // Refresh class data
    $class = $class_manager->get_class($class->id);
}

// Get all students in this class
$student_manager = School_Manager_Lite_Student_Manager::instance();
$class_students = $student_manager->get_students(array('class_id' => $class->id));

?>
<div class="wrap">
    <h1><?php _e('Edit Class', 'school-manager-lite'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Edit class details and assign teacher.', 'school-manager-lite'); ?></p>
    </div>
    
    <div class="card">
        <h2><?php printf(__('Editing: %s', 'school-manager-lite'), $class->name); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_class_' . $class->id); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Class Name', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" name="name" id="name" value="<?php echo esc_attr($class->name); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="description"><?php _e('Description', 'school-manager-lite'); ?></label></th>
                    <td>
                        <textarea name="description" id="description" class="large-text" rows="3"><?php echo esc_textarea($class->description); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_students"><?php _e('Maximum Students', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="number" name="max_students" id="max_students" value="<?php echo esc_attr($class->max_students); ?>" class="small-text" min="0">
                    </td>
                </tr>
                <tr>
                    <th><label for="teacher_id"><?php _e('Teacher', 'school-manager-lite'); ?></label></th>
                    <td>
                        <select name="teacher_id" id="teacher_id">
                            <option value="0"><?php _e('-- Select Teacher --', 'school-manager-lite'); ?></option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo esc_attr($teacher->ID); ?>" <?php selected($class->teacher_id, $teacher->ID); ?>>
                                    <?php echo esc_html($teacher->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_class" class="button button-primary" value="<?php _e('Update Class', 'school-manager-lite'); ?>">
                <a href="<?php echo admin_url('admin.php?page=school-manager-classes'); ?>" class="button"><?php _e('Cancel', 'school-manager-lite'); ?></a>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3><?php _e('Students in Class', 'school-manager-lite'); ?></h3>
        
        <?php if (!empty($class_students)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Student Name', 'school-manager-lite'); ?></th>
                        <th><?php _e('Email', 'school-manager-lite'); ?></th>
                        <?php if (current_user_can('manage_school_students')): ?>
                            <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_students as $student): ?>
                        <?php 
                        // Get student user if available
                        $student_user = false;
                        if (!empty($student->user_id)) {
                            $student_user = get_userdata($student->user_id);
                        }
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($student->name); ?>
                            </td>
                            <td>
                                <?php echo esc_html($student->email); ?>
                            </td>
                            <?php if (current_user_can('manage_school_students')): ?>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=school-manager-students&action=edit&id=' . $student->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'school-manager-lite'); ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No students in this class.', 'school-manager-lite'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if (current_user_can('manage_options')): ?>
    <div class="card">
        <h3><?php _e('Generate Promo Codes for This Class', 'school-manager-lite'); ?></h3>
        
        <p><?php _e('You can generate promo codes for students to join this class.', 'school-manager-lite'); ?></p>
        
        <a href="<?php echo admin_url('admin.php?page=school-manager-promo-codes&class_id=' . $class->id); ?>" class="button">
            <?php _e('Generate Promo Codes', 'school-manager-lite'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>
