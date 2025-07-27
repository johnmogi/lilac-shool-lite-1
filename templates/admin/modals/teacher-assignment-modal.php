<?php
/**
 * Teacher Assignment Modal Template
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get all teachers
$teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
$teachers = $teacher_manager->get_teachers();
?>

<div id="teacher-assignment-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; max-width: 90%; border-radius: 5px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
            <h3 style="margin: 0;"><?php _e('Assign Teacher to Classes', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">שייך מורה לכיתות</span></h3>
            <span class="close-teacher-modal" style="cursor: pointer; font-size: 24px; font-weight: bold; color: #999;">&times;</span>
        </div>
        <div class="modal-body">
            <p id="teacher-assignment-count"></p>
            
            <input type="hidden" id="selected-class-ids" value="">
            
            <div class="form-field">
                <label for="teacher-id"><?php _e('Select Teacher', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">בחר מורה</span></label>
                <select id="teacher-id" class="teacher-select">
                    <option value=""><?php _e('-- Select Teacher --', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">בחר מורה</span></option>
                    <?php foreach ($teachers as $teacher) : 
                        $specialty = get_user_meta($teacher->ID, 'teacher_specialty', true);
                        $specialty_text = !empty($specialty) ? ' - ' . esc_html($specialty) : '';
                    ?>
                        <option value="<?php echo esc_attr($teacher->ID); ?>">
                            <?php echo esc_html($teacher->display_name) . $specialty_text; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php _e('The selected teacher will be assigned to all selected classes.', 'school-manager-lite'); ?> / 
                    <span lang="he" dir="rtl">המורה הנבחר ישוייך לכל הכיתות שנבחרו</span>
                </p>
            </div>
        </div>
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
            <span class="spinner" style="float: left; margin-top: 5px;"></span>
            <button type="button" id="cancel-teacher-assign" class="button" style="margin-left: 10px;"><?php _e('Cancel', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">ביטול</span></button>
            <button type="button" id="assign-teacher-submit" class="button button-primary"><?php _e('Assign Teacher', 'school-manager-lite'); ?> / <span lang="he" dir="rtl">שייך מורה</span></button>
        </div>
    </div>
</div>
