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
    <h1><?php _e('Edit Class', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">ערוך כיתה</span></h1>
    
    <?php 
    // Display status messages for student assignments
    if (isset($_GET['added']) && is_numeric($_GET['added']) && $_GET['added'] > 0) {
        $added_count = intval($_GET['added']);
        $error_count = isset($_GET['errors']) ? intval($_GET['errors']) : 0;
        $message = sprintf(
            _n(
                '%d student successfully added to class.', 
                '%d students successfully added to class.', 
                $added_count, 
                'school-manager-lite'
            ),
            $added_count
        );
        
        if ($error_count > 0) {
            $message .= ' ' . sprintf(
                _n(
                    '%d student could not be added.', 
                    '%d students could not be added.', 
                    $error_count, 
                    'school-manager-lite'
                ),
                $error_count
            );
        }
        
        echo '<div class="status-message success">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    if (isset($_GET['removed']) && $_GET['removed'] == 1) {
        echo '<div class="status-message success">';
        echo '<p>' . esc_html__('Student successfully removed from class.', 'school-manager-lite') . '</p>';
        echo '</div>';
    }
    
    if (isset($_GET['error']) && $_GET['error'] === 'no_students_selected') {
        echo '<div class="status-message error">';
        echo '<p>' . esc_html__('Error: No students were selected.', 'school-manager-lite') . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="notice notice-info">
        <p><?php _e('Edit class details and assign teacher.', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">עריכת פרטי הכיתה ושיוך מורה</span></p>
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
                    <th><label for="teacher_id"><?php _e('Teacher', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">מורה</span></label></th>
                    <td>
                        <select name="teacher_id" id="teacher_id" class="teacher-select">
                            <option value="0"><?php _e('-- Select Teacher --', 'school-manager-lite'); ?> / בחר מורה</option>
                            <?php foreach ($teachers as $teacher): 
                                // Get teacher specialty if exists
                                $teacher_meta = get_user_meta($teacher->ID, 'teacher_specialty', true);
                                $specialty = !empty($teacher_meta) ? ' - ' . esc_html($teacher_meta) : '';
                            ?>
                                <option value="<?php echo esc_attr($teacher->ID); ?>" <?php selected($class->teacher_id, $teacher->ID); ?>>
                                    <?php echo esc_html($teacher->display_name) . $specialty; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select a teacher to assign to this class.', 'school-manager-lite'); ?> / 
                            <span lang="he" dir="rtl">בחר מורה לשייך לכיתה זו</span>
                        </p>
                        <?php if ($class->teacher_id > 0): 
                            $current_teacher = get_user_by('id', $class->teacher_id);
                            if ($current_teacher): ?>
                                <div class="current-teacher-info">
                                    <p><strong><?php _e('Current Teacher', 'school-manager-lite'); ?>: / מורה נוכחי:</strong>
                                    <?php echo esc_html($current_teacher->display_name); ?></p>
                                    <?php 
                                    // Show additional teacher info if available
                                    $phone = get_user_meta($current_teacher->ID, 'teacher_phone', true);
                                    if (!empty($phone)): ?>
                                        <p><strong><?php _e('Phone', 'school-manager-lite'); ?>: / טלפון:</strong> 
                                        <?php echo esc_html($phone); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
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
        <h3><?php _e('Students in Class', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">תלמידים בכיתה</span></h3>
        
        <?php if (current_user_can('manage_school_students')): ?>
            <div class="class-actions">
                <a href="#" class="button" id="assign-students-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Add Students', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">הוסף תלמידים</span>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($class_students)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Student Name', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">שם התלמיד</span></th>
                        <th><?php _e('Email', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">דואר אלקטרוני</span></th>
                        <?php if (current_user_can('manage_school_students')): ?>
                            <th><?php _e('Actions', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">פעולות</span></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_students as $student): ?>
                        <?php 
                        // Get student user if available
                        $student_user = false;
                        if (!empty($student->wp_user_id)) {
                            $student_user = get_userdata($student->wp_user_id);
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
                                        <?php _e('Edit', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">ערוך</span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=school-manager-classes&action=remove_student&class_id=' . $class->id . '&student_id=' . $student->id), 'remove_student_' . $student->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to remove this student from the class?', 'school-manager-lite'); ?>')">
                                        <?php _e('Remove', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">הסר</span>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No students in this class.', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">אין תלמידים בכיתה זו</span></p>
        <?php endif; ?>
        
        <!-- Student Assignment Modal -->
        <div id="add-students-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="close-modal dashicons dashicons-no-alt"></span>
                    <h3><?php _e('Add Students to Class', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">הוסף תלמידים לכיתה</span></h3>
                </div>
                <div class="modal-body">
                    <div class="student-search-container">
                        <input type="text" id="student-search" placeholder="<?php _e('Search students...', 'school-manager-lite'); ?> / חפש תלמידים..." class="widefat">
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('add_students_to_class_' . $class->id); ?>
                        <input type="hidden" name="class_id" value="<?php echo $class->id; ?>">
                        
                        <div class="students-list">
                            <?php 
                            // Get all students not in this class
                            $all_students = $student_manager->get_students(array('class_id_not' => $class->id));
                            
                            if (!empty($all_students)): ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th class="check-column"><input type="checkbox" id="students-select-all"></th>
                                            <th><?php _e('Student Name', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">שם התלמיד</span></th>
                                            <th><?php _e('Email', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">דואר אלקטרוני</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_students as $student): ?>
                                            <tr class="student-item">
                                                <td><input type="checkbox" name="student_ids[]" value="<?php echo esc_attr($student->id); ?>"></td>
                                                <td class="student-name"><?php echo esc_html($student->name); ?></td>
                                                <td><?php echo esc_html($student->email); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p><?php _e('No students available to add to this class.', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">אין תלמידים זמינים להוספה לכיתה זו</span></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="button close-modal"><?php _e('Cancel', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">ביטול</span></button>
                            <button type="submit" name="add_students_to_class" class="button button-primary"><?php _e('Add Selected Students', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">הוסף תלמידים נבחרים</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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

<?php
// Include student assignment modal
require_once SCHOOL_MANAGER_LITE_DIR . 'templates/admin/modals/student-assignment-modal.php';
?>
