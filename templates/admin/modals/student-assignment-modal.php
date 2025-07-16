<?php
/**
 * Student Assignment Modal Template
 * 
 * Displays a modal for assigning students to a class
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get all unassigned students
$student_manager = School_Manager_Lite_Student_Manager::instance();
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$students = $student_manager->get_unassigned_students($class_id);

?>
<div id="student-assignment-modal" class="school-modal">
    <div class="school-modal-content">
        <div class="school-modal-header">
            <span class="close-modal">&times;</span>
            <h2><?php _e('Assign Students to Class', 'school-manager-lite'); ?> / <span lang="he">שייך תלמידים לכיתה</span></h2>
        </div>
        <div class="school-modal-body">
            <?php if (empty($students)): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('No unassigned students found.', 'school-manager-lite'); ?> / 
                        <span lang="he">לא נמצאו תלמידים לא משויכים.</span>
                    </p>
                </div>
            <?php else: ?>
                <div class="student-search-container">
                    <input type="text" id="student-search" placeholder="<?php _e('Search students...', 'school-manager-lite'); ?>" />
                    <div class="selection-controls">
                        <label>
                            <input type="checkbox" id="select-all-students" />
                            <?php _e('Select all visible students', 'school-manager-lite'); ?> / 
                            <span lang="he">בחר את כל התלמידים הנראים</span>
                        </label>
                        <div class="selected-count">
                            <?php _e('Selected:', 'school-manager-lite'); ?> <span id="selected-count">0</span>
                        </div>
                    </div>
                </div>

                <div id="no-students-found" class="notice notice-warning" style="display:none;">
                    <p>
                        <?php _e('No students match your search.', 'school-manager-lite'); ?> /
                        <span lang="he">לא נמצאו תלמידים התואמים את החיפוש שלך.</span>
                    </p>
                </div>

                <form id="assign-students-form" method="post">
                    <?php wp_nonce_field('school_manager_assign_students', 'school_manager_assign_students_nonce'); ?>
                    <input type="hidden" name="class_id" value="<?php echo esc_attr($class_id); ?>" />
                    
                    <div class="students-list">
                        <?php foreach ($students as $student): ?>
                            <div class="student-item">
                                <label>
                                    <input type="checkbox" name="student_ids[]" value="<?php echo esc_attr($student->ID); ?>" class="student-checkbox" />
                                    <span class="student-name"><?php echo esc_html($student->display_name); ?></span>
                                    <?php if (!empty($student->user_email)): ?>
                                        <span class="student-email">(<?php echo esc_html($student->user_email); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="school-modal-footer">
                        <button type="button" class="button close-modal"><?php _e('Cancel', 'school-manager-lite'); ?> / <span lang="he">ביטול</span></button>
                        <button type="submit" id="add-students-btn" name="add_students" class="button button-primary" disabled>
                            <?php _e('Add Selected Students', 'school-manager-lite'); ?> / <span lang="he">הוסף תלמידים נבחרים</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
