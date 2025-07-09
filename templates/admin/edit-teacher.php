<?php
/**
 * Edit Teacher Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'school-manager-lite'));
}

// Process form submission
if (isset($_POST['save_teacher']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save_teacher_' . $teacher->ID)) {
    
    // Get teacher data
    $teacher_data = array(
        'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
        'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
        'display_name' => isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '',
        'user_email' => isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '',
    );
    
    // Update user data
    $teacher_data['ID'] = $teacher->ID;
    wp_update_user($teacher_data);
    
    // Process class assignments
    if (isset($_POST['assigned_classes']) && is_array($_POST['assigned_classes'])) {
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $assigned_classes = array_map('intval', $_POST['assigned_classes']);
        
        // Get all classes
        $all_classes = $class_manager->get_classes();
        
        // Update each class teacher
        foreach ($all_classes as $class) {
            if (in_array($class->id, $assigned_classes)) {
                // Assign teacher to class
                $class_manager->update_class($class->id, array('teacher_id' => $teacher->ID));
            } elseif ($class->teacher_id == $teacher->ID) {
                // Unassign teacher from class
                $class_manager->update_class($class->id, array('teacher_id' => 0));
            }
        }
    }
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Teacher updated successfully.', 'school-manager-lite') . '</p></div>';
}

// Get all classes assigned to this teacher
$class_manager = School_Manager_Lite_Class_Manager::instance();
$all_classes = $class_manager->get_classes();
$teacher_classes = $class_manager->get_classes(array('teacher_id' => $teacher->ID));
$teacher_class_ids = array_map(function($class) { return $class->id; }, $teacher_classes);

// Get all students in this teacher's classes
$student_manager = School_Manager_Lite_Student_Manager::instance();
$teacher_students = array();
foreach ($teacher_classes as $class) {
    $class_students = $student_manager->get_students(array('class_id' => $class->id));
    $teacher_students = array_merge($teacher_students, $class_students);
}

?>
<div class="wrap">
    <h1><?php _e('Edit Teacher', 'school-manager-lite'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Edit teacher details and assign classes.', 'school-manager-lite'); ?></p>
    </div>
    
    <div class="card">
        <h2><?php printf(__('Editing: %s', 'school-manager-lite'), $teacher->display_name); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_teacher_' . $teacher->ID); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="first_name"><?php _e('First Name', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($teacher->first_name); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="last_name"><?php _e('Last Name', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($teacher->last_name); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="display_name"><?php _e('Display Name', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" name="display_name" id="display_name" value="<?php echo esc_attr($teacher->display_name); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="user_email"><?php _e('Email', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="email" name="user_email" id="user_email" value="<?php echo esc_attr($teacher->user_email); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Assigned Classes', 'school-manager-lite'); ?></h3>
            
            <?php if (!empty($all_classes)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Assign', 'school-manager-lite'); ?></th>
                            <th><?php _e('Class Name', 'school-manager-lite'); ?></th>
                            <th><?php _e('Description', 'school-manager-lite'); ?></th>
                            <th><?php _e('Students', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_classes as $class): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="assigned_classes[]" value="<?php echo esc_attr($class->id); ?>" <?php checked(in_array($class->id, $teacher_class_ids)); ?>>
                                </td>
                                <td>
                                    <?php echo esc_html($class->name); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($class->description); ?>
                                </td>
                                <td>
                                    <?php 
                                    $class_students = $student_manager->get_students(array('class_id' => $class->id));
                                    echo count($class_students);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No classes available.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" name="save_teacher" class="button button-primary" value="<?php _e('Update Teacher', 'school-manager-lite'); ?>">
                <a href="<?php echo admin_url('admin.php?page=school-manager-teachers'); ?>" class="button"><?php _e('Cancel', 'school-manager-lite'); ?></a>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3><?php _e('Students in Teacher\'s Classes', 'school-manager-lite'); ?></h3>
        
        <?php if (!empty($teacher_students)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'school-manager-lite'); ?></th>
                        <th><?php _e('Email', 'school-manager-lite'); ?></th>
                        <th><?php _e('Class', 'school-manager-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teacher_students as $student): ?>
                        <tr>
                            <td>
                                <?php echo esc_html($student->name); ?>
                            </td>
                            <td>
                                <?php echo esc_html($student->email); ?>
                            </td>
                            <td>
                                <?php 
                                $student_class = $class_manager->get_class($student->class_id);
                                echo $student_class ? esc_html($student_class->name) : '';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No students in teacher\'s classes.', 'school-manager-lite'); ?></p>
        <?php endif; ?>
    </div>
</div>
