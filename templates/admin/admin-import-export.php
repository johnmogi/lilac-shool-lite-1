<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<div class="wrap school-manager-import-export">
    <h1><?php _e('ייבוא/ייצוא נתונים', 'school-manager-lite'); ?></h1>
    
    <?php if (isset($_GET['imported'])) : ?>
        <div class="notice notice-success">
            <p><?php _e('הנתונים יובאו בהצלחה!', 'school-manager-lite'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="import-export-container">
        <div class="import-section">
            <h2><?php _e('Import Data', 'school-manager-lite'); ?></h2>
            <div class="card">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('school_manager_import', 'school_manager_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_type"><?php _e('Import Type', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <select name="import_type" id="import_type" required>
                                    <option value=""><?php _e('Select type to import', 'school-manager-lite'); ?></option>
                                    <option value="students"><?php _e('Students', 'school-manager-lite'); ?></option>
                                    <option value="teachers"><?php _e('Teachers', 'school-manager-lite'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('CSV File', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv" required>
                                <p class="description">
                                    <?php _e('Upload a CSV file with the correct format.', 'school-manager-lite'); ?>
                                    <a href="#" class="download-sample" data-type="students"><?php _e('Download sample CSV', 'school-manager-lite'); ?></a>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Failsafe Options for Student Import -->
                        <tr id="student-failsafe-options" style="display: none;">
                            <td colspan="2">
                                <hr style="margin: 20px 0; border: 1px solid #ddd;" />
                                <h3 style="margin: 10px 0; color: #0073aa;"><?php _e('הגדרות ברירת מחדל לייבוא', 'school-manager-lite'); ?></h3>
                                <p style="color: #666;"><?php _e('הגדרות אלה ישמשו כברירת מחדל עבור תלמידים שחסרים פרטים בקובץ CSV', 'school-manager-lite'); ?></p>
                                
                                <table class="form-table" style="margin-top: 15px;">
                                    <tr>
                                        <th scope="row">
                                            <label for="default_teacher_id"><?php _e('בחר מורה ברירת מחדל', 'school-manager-lite'); ?></label>
                                        </th>
                                        <td>
                                            <select name="default_teacher_id" id="default_teacher_id" class="regular-text">
                                                <option value=""><?php _e('-- בחר מורה (אופציונלי) --', 'school-manager-lite'); ?></option>
                                                <?php
                                                // Get teachers for dropdown
                                                $teachers = get_users(array(
                                                    'role__in' => array('school_teacher', 'wdm_instructor', 'instructor', 'administrator'),
                                                    'orderby' => 'display_name',
                                                    'order' => 'ASC'
                                                ));
                                                foreach ($teachers as $teacher) {
                                                    echo '<option value="' . esc_attr($teacher->ID) . '">' . esc_html($teacher->display_name) . ' (ID: ' . $teacher->ID . ')</option>';
                                                }
                                                ?>
                                            </select>
                                            <p class="description"><?php _e('מורה זה יוקצה לתלמידים שאין להם מורה מוגדר בקובץ CSV', 'school-manager-lite'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="default_course_id"><?php _e('בחר קורס ברירת מחדל', 'school-manager-lite'); ?></label>
                                        </th>
                                        <td>
                                            <select name="default_course_id" id="default_course_id" class="regular-text">
                                                <?php
                                                // Get LearnDash courses for dropdown
                                                $courses = get_posts(array(
                                                    'post_type' => 'sfwd-courses',
                                                    'post_status' => 'publish',
                                                    'numberposts' => -1,
                                                    'orderby' => 'title',
                                                    'order' => 'ASC'
                                                ));
                                                foreach ($courses as $course) {
                                                    $selected = ($course->ID == 898) ? ' selected' : '';
                                                    echo '<option value="' . esc_attr($course->ID) . '"' . $selected . '>' . esc_html($course->post_title) . ' (ID: ' . $course->ID . ')</option>';
                                                }
                                                ?>
                                            </select>
                                            <p class="description"><?php _e('קורס זה יוקצה לתלמידים שאין להם קורס מוגדר בקובץ CSV', 'school-manager-lite'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="default_class_id"><?php _e('מזהה כיתה ברירת מחדל', 'school-manager-lite'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="default_class_id" id="default_class_id" class="regular-text" placeholder="<?php _e('לדוגמה: 1, 2, 3', 'school-manager-lite'); ?>">
                                            <p class="description"><?php _e('מזהה כיתה זה יוקצה לתלמידים שאין להם כיתה מוגדרת בקובץ CSV', 'school-manager-lite'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="import_submit" class="button button-primary">
                            <?php _e('Import', 'school-manager-lite'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="export-section">
            <h2><?php _e('Export Data', 'school-manager-lite'); ?></h2>
            <div class="card">
                <p><?php _e('Export your data to a CSV file.', 'school-manager-lite'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Data Type', 'school-manager-lite'); ?></th>
                            <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Students', 'school-manager-lite'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'school-manager-import-export', 'export' => 'students'), admin_url('admin.php'))); ?>" class="button button-secondary">
                                    <?php _e('Export', 'school-manager-lite'); ?>
                                </a>
                                <a href="#" class="button button-link download-sample" data-type="students">
                                    <?php _e('Sample', 'school-manager-lite'); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('Teachers', 'school-manager-lite'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'school-manager-import-export', 'export' => 'teachers'), admin_url('admin.php'))); ?>" class="button button-secondary">
                                    <?php _e('Export', 'school-manager-lite'); ?>
                                </a>
                                <a href="#" class="button button-link download-sample" data-type="teachers">
                                    <?php _e('Sample', 'school-manager-lite'); ?>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <style>
    .import-export-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    .import-section, .export-section {
        flex: 1;
    }
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 15px;
        margin-bottom: 20px;
    }
    @media (max-width: 960px) {
        .import-export-container {
            flex-direction: column;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle sample CSV download
        $('.download-sample').on('click', function(e) {
            e.preventDefault();
            var type = $(this).data('type');
            window.location.href = '<?php echo esc_js(admin_url('admin-ajax.php?action=download_sample_csv&type=')); ?>' + type;
        });
        
        // Function to toggle failsafe options based on import type
        function toggleFailsafeOptions() {
            var importType = $('#import_type').val();
            var failsafeRow = $('#student-failsafe-options');
            
            if (importType === 'students') {
                failsafeRow.fadeIn();
            } else {
                failsafeRow.fadeOut();
            }
        }
        
        // Initial check on page load
        toggleFailsafeOptions();
        
        // Listen for changes to import type dropdown
        $('#import_type').on('change', function() {
            toggleFailsafeOptions();
        });
        
        // Add RTL support for Hebrew text in failsafe options
        $('#student-failsafe-options').css({
            'direction': 'rtl',
            'text-align': 'right'
        });
        
        // Style the failsafe section for better visibility
        $('#student-failsafe-options h3').css({
            'font-family': 'Arial, sans-serif',
            'font-weight': 'bold',
            'margin-bottom': '10px'
        });
        
        $('#student-failsafe-options .description').css({
            'font-style': 'italic',
            'color': '#666',
            'margin-top': '5px'
        });
        
        // Add visual feedback when failsafe options are shown
        $('#import_type').on('change', function() {
            if ($(this).val() === 'students') {
                $('#student-failsafe-options').addClass('highlight-section');
                setTimeout(function() {
                    $('#student-failsafe-options').removeClass('highlight-section');
                }, 1000);
            }
        });
    });
    </script>
    
    <style>
    #student-failsafe-options {
        background-color: #f9f9f9;
        border-radius: 5px;
        padding: 15px;
        margin-top: 10px;
    }
    
    #student-failsafe-options h3 {
        color: #0073aa;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 5px;
        margin-bottom: 15px;
    }
    
    #student-failsafe-options .form-table {
        background-color: white;
        border-radius: 3px;
        padding: 10px;
    }
    
    .highlight-section {
        animation: highlight 1s ease-in-out;
    }
    
    @keyframes highlight {
        0% { background-color: #f9f9f9; }
        50% { background-color: #e6f3ff; }
        100% { background-color: #f9f9f9; }
    }
    </style>
</div>
