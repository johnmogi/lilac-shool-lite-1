<?php
/**
 * Edit Student Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Security check
if (!current_user_can('manage_school_students')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'school-manager-lite'));
}

// Process form submission
if (isset($_POST['save_student']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save_student_' . $student->id)) {
    
    // Get student data
    $student_data = array(
        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'class_id' => isset($_POST['class_id']) ? intval($_POST['class_id']) : 0,
    );
    
    // Update student
    $student_manager->update_student($student->id, $student_data);
    
    // Update WordPress user if exists
    if (!empty($student->user_id)) {
        $user_id = $student->user_id;
        $user_data = array(
            'ID' => $user_id,
            'display_name' => $student_data['name'],
            'user_email' => $student_data['email']
        );
        wp_update_user($user_data);
    }
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Student updated successfully.', 'school-manager-lite') . '</p></div>';
    
    // Refresh student data
    $student = $student_manager->get_student($student->id);
}

?>
<div class="wrap">
    <h1><?php _e('Edit Student', 'school-manager-lite'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Edit student details and class assignment.', 'school-manager-lite'); ?></p>
    </div>
    
    <div class="card">
        <h2><?php printf(__('Editing: %s', 'school-manager-lite'), $student->name); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_student_' . $student->id); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Student Name', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" name="name" id="name" value="<?php echo esc_attr($student->name); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="email"><?php _e('Email', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="email" name="email" id="email" value="<?php echo esc_attr($student->email); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="class_id"><?php _e('Class', 'school-manager-lite'); ?></label></th>
                    <td>
                        <select name="class_id" id="class_id" required>
                            <option value=""><?php _e('-- Select Class --', 'school-manager-lite'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <?php 
                                // For teachers, only show their classes
                                if (current_user_can('manage_options') || $class->teacher_id == get_current_user_id()): 
                                ?>
                                    <option value="<?php echo esc_attr($class->id); ?>" <?php selected($student->class_id, $class->id); ?>>
                                        <?php echo esc_html($class->name); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_student" class="button button-primary" value="<?php _e('Update Student', 'school-manager-lite'); ?>">
                <a href="<?php echo admin_url('admin.php?page=school-manager-students'); ?>" class="button"><?php _e('Cancel', 'school-manager-lite'); ?></a>
            </p>
        </form>
    </div>
    
    <?php if (!empty($student->user_id) && $student_user = get_userdata($student->user_id)): ?>
        <div class="card">
            <h3><?php _e('WordPress User Information', 'school-manager-lite'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('Username', 'school-manager-lite'); ?></th>
                    <td><?php echo esc_html($student_user->user_login); ?></td>
                </tr>
                <tr>
                    <th><?php _e('User ID', 'school-manager-lite'); ?></th>
                    <td><?php echo esc_html($student->user_id); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Registered', 'school-manager-lite'); ?></th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($student_user->user_registered))); ?></td>
                </tr>
            </table>
            
            <?php if (current_user_can('manage_options')): ?>
                <p>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $student->user_id); ?>" class="button">
                        <?php _e('Edit WordPress User', 'school-manager-lite'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (current_user_can('manage_options')): ?>
        <div class="card">
            <h3><?php _e('Generated Promo Codes', 'school-manager-lite'); ?></h3>
            
            <?php 
            // Get promo codes generated for this student
            $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
            $promo_codes = $promo_code_manager->get_promo_codes(array('user_id' => $student->user_id));
            
            if (!empty($promo_codes)): 
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'school-manager-lite'); ?></th>
                            <th><?php _e('Status', 'school-manager-lite'); ?></th>
                            <th><?php _e('Created', 'school-manager-lite'); ?></th>
                            <th><?php _e('Redeemed', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promo_codes as $code): ?>
                            <tr>
                                <td><?php echo esc_html($code->code); ?></td>
                                <td>
                                    <?php 
                                    if ($code->redeemed) {
                                        echo '<span class="dashicons dashicons-yes" style="color:green;"></span> ' . __('Redeemed', 'school-manager-lite');
                                    } else {
                                        echo '<span class="dashicons dashicons-minus" style="color:orange;"></span> ' . __('Available', 'school-manager-lite');
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($code->created_at))); ?></td>
                                <td>
                                    <?php 
                                    if ($code->redeemed) {
                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($code->redeemed_at))); 
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No promo codes found for this student.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
