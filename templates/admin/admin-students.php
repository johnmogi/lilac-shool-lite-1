<?php
/**
 * Admin Students Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include admin header for consistency
include_once 'admin-header.php';

// Get manager instances
$student_manager = School_Manager_Lite_Student_Manager::instance();
$class_manager = School_Manager_Lite_Class_Manager::instance();
$teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
$promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();

// Get classes and promo codes for modals and forms
$classes = $class_manager->get_classes();
$all_promo_codes = $promo_code_manager->get_promo_codes(array('active' => true));

// Include the list table class
require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-students-list-table.php';

// Create an instance of our list table
$students_table = new School_Manager_Lite_Students_List_Table();

// Process actions and prepare the table items
$students_table->prepare_items();
?>
<div class="wrap school-manager-admin">
    <h1 class="wp-heading-inline"><?php _e('Students', 'school-manager-lite'); ?></h1>
    <a href="#" class="page-title-action" id="add-student-toggle"><?php _e('Add New', 'school-manager-lite'); ?></a>
    
    <div id="add-student-form" style="display: none;">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Add Student', 'school-manager-lite'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('school_manager_add_student', 'school_manager_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="first_name"><?php _e('First Name', 'school-manager-lite'); ?></label></th>
                            <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="last_name"><?php _e('Last Name', 'school-manager-lite'); ?></label></th>
                            <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="email"><?php _e('Email', 'school-manager-lite'); ?></label></th>
                            <td><input type="email" name="email" id="email" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="class_id"><?php _e('Class', 'school-manager-lite'); ?></label></th>
                            <td>
                                <select name="class_id" id="class_id" required>
                                    <option value=""><?php _e('Select Class', 'school-manager-lite'); ?></option>
                                    <?php
                                    if ($classes) {
                                        foreach ($classes as $class) {
                                            echo '<option value="' . esc_attr($class->id) . '">' . esc_html($class->name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_student" class="button button-primary" value="<?php _e('Add Student', 'school-manager-lite'); ?>">
                        <button type="button" class="button" id="cancel-add-student"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Class Modal -->
    <div id="assign-class-modal" class="school-modal" style="display: none;">
        <div class="school-modal-content">
            <div class="school-modal-header">
                <span class="school-modal-close">&times;</span>
                <h2><?php _e('Assign Class to Student', 'school-manager-lite'); ?></h2>
                <p id="assign-class-student-name"></p>
            </div>
            <div class="school-modal-body">
                <form id="assign-class-form" method="post">
                    <?php wp_nonce_field('school_manager_assign_class', 'school_manager_assign_class_nonce'); ?>
                    <input type="hidden" name="student_id" id="assign-class-student-id">
                    <input type="hidden" name="action" value="assign_class_to_student">
                    
                    <p>
                        <label for="class_id"><?php _e('Select Class', 'school-manager-lite'); ?></label>
                        <select name="class_id" id="class_id" required>
                            <option value=""><?php _e('- Select Class -', 'school-manager-lite'); ?></option>
                            <?php foreach ($all_classes as $class) : ?>
                                <option value="<?php echo esc_attr($class->id); ?>"><?php echo esc_html($class->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Assign', 'school-manager-lite'); ?></button>
                        <button type="button" class="button cancel-modal"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Promo Modal -->
    <div id="assign-promo-modal" class="school-modal" style="display: none;">
        <div class="school-modal-content">
            <div class="school-modal-header">
                <span class="school-modal-close">&times;</span>
                <h2><?php _e('Assign Promo Code to Student', 'school-manager-lite'); ?></h2>
                <p id="assign-promo-student-name"></p>
            </div>
            <div class="school-modal-body">
                <form id="assign-promo-form" method="post">
                    <?php wp_nonce_field('school_manager_assign_promo', 'school_manager_assign_promo_nonce'); ?>
                    <input type="hidden" name="student_id" id="assign-promo-student-id">
                    <input type="hidden" name="action" value="assign_promo_to_student">
                    
                    <p>
                        <label for="promo_id"><?php _e('Select Promo Code', 'school-manager-lite'); ?></label>
                        <select name="promo_id" id="promo_id" required>
                            <option value=""><?php _e('- Select Promo Code -', 'school-manager-lite'); ?></option>
                            <?php foreach ($all_promo_codes as $promo) : ?>
                                <option value="<?php echo esc_attr($promo->id); ?>"><?php echo esc_html($promo->code); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Assign', 'school-manager-lite'); ?></button>
                        <button type="button" class="button cancel-modal"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Search box -->
    <form method="post">
        <?php $students_table->search_box(__('Search Students', 'school-manager-lite'), 'search-students'); ?>
        
        <!-- Display the table with extra filters -->
        <?php $students_table->display(); ?>
    </form>
</div>

<!-- Debug information for quick edit -->
<div id="debug-info" style="background: #f8f8f8; border: 1px solid #ccc; padding: 10px; margin: 10px 0; display: none;">
    <h3>Debug Info</h3>
    <p>Quick Edit Form: <span id="quick-edit-form-status">Checking...</span></p>
    <p>Inline Edit Links: <span id="inline-edit-links-count">Checking...</span></p>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('Students page loaded');
        
        // Debug info
        setTimeout(function() {
            $('#quick-edit-form-status').text($('#student-quick-edit').length ? 'Found' : 'Not Found');
            $('#inline-edit-links-count').text($('.editinline').length + ' links found');
            
            // Log all editinline links
            $('.editinline').each(function(i) {
                console.log('Inline edit link ' + i + ':', $(this).attr('class'), $(this).data());
            });
        }, 1000);
        // Show/hide the add student form
        $('#add-student-toggle').on('click', function(e) {
            e.preventDefault();
            $('#add-student-form').slideToggle();
        });
        
        // Hide the form on cancel
        $('#cancel-add-student').on('click', function(e) {
            e.preventDefault();
            $('#add-student-form').slideUp();
        });
        
        // Auto-submit when filter dropdowns are changed
        $('#filter_class_id, #filter_teacher_id').on('change', function() {
            if ($(this).val() !== '') {
                $('#filter_action').click();
            }
        });
        
        // Enhance bulk actions UI
        $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function() {
            const action = $(this).val();
            
            // Show appropriate bulk selection fields based on action
            if (action === 'assign_class') {
                $('#bulk_class_id').parent().fadeIn(200);
                $('#bulk_teacher_id').parent().fadeOut(200);
            } 
            else if (action === 'assign_teacher') {
                $('#bulk_teacher_id').parent().fadeIn(200);
                $('#bulk_class_id').parent().fadeOut(200);
            } 
            else {
                $('#bulk_class_id').parent().fadeOut(200);
                $('#bulk_teacher_id').parent().fadeOut(200);
            }
        });
        
        // Initialize bulk actions
        $('#bulk-action-selector-top').trigger('change');
        
        // Assign Class button handler
        $('.assign-class-button').on('click', function(e) {
            e.preventDefault();
            
            // Get student info
            var studentId = $(this).data('student-id');
            var studentName = $(this).data('student-name');
            
            // Set values in modal
            $('#assign-class-student-id').val(studentId);
            $('#assign-class-student-name').text(studentName);
            
            // Show modal
            $('#assign-class-modal').show();
        });
        
        // Assign Promo button handler
        $('.assign-promo-button').on('click', function(e) {
            e.preventDefault();
            
            // Get student info
            var studentId = $(this).data('student-id');
            var studentName = $(this).data('student-name');
            
            // Set values in modal
            $('#assign-promo-student-id').val(studentId);
            $('#assign-promo-student-name').text(studentName);
            
            // Show modal
            $('#assign-promo-modal').show();
        });
        
        // Close modal when clicking on X or Cancel
        $('.school-modal-close, .cancel-modal').on('click', function() {
            $('.school-modal').hide();
        });
        
        // Close modal when clicking outside of it
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('school-modal')) {
                $('.school-modal').hide();
            }
        });
        
        // Handle form submissions with AJAX
        $('#assign-class-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        alert(response.data.message);
                        // Hide modal
                        $('#assign-class-modal').hide();
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message);
                    }
                }
            });
        });
        
        $('#assign-promo-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        alert(response.data.message);
                        // Hide modal
                        $('#assign-promo-modal').hide();
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message);
                    }
                }
            });
        });
        // Make sure Quick Edit is properly initialized
        if (typeof inlineEditStudent !== 'undefined') {
            console.log('Manually initializing inlineEditStudent');
            inlineEditStudent.init();
            
            // Re-bind click handlers directly
            $('.editinline').on('click', function(e) {
                console.log('Quick edit link clicked', this);
                e.preventDefault();
                inlineEditStudent.edit(this);
                return false;
            });
        } else {
            console.error('inlineEditStudent object not found!');
        }
    });
</script>

<!-- Enable debug display -->
<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add debug toggle button
        $('<button class="button" id="toggle-debug">Toggle Debug Info</button>').insertAfter('h1.wp-heading-inline');
        
        $('#toggle-debug').on('click', function() {
            $('#debug-info').toggle();
        });
    });
</script>

<style>
    .school-manager-admin-students .required {
        color: red;
    }
    
    /* Status styling */
    .status-active {
        background-color: #d4f4e2;
        color: #0a6b35;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .status-inactive {
        background-color: #f4d4d4;
        color: #6b0a0a;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    /* Table improvements */
    .wp-list-table .column-status {
        width: 10%;
    }
    
    .wp-list-table .column-actions {
        width: 25%;
    }
    
    /* Filter styling */
    .alignleft.actions select {
        margin-right: 6px;
    }
    
    /* Improve add student form */
    #add-student-form {
        margin: 15px 0;
    }
    
    /* Modal styles */
    .school-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .school-modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #ddd;
        width: 50%;
        max-width: 500px;
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .school-modal-header {
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }
    
    .school-modal-header h2 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .school-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        margin-top: -10px;
    }
    
    .school-modal-close:hover,
    .school-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    
    .school-modal-body select,
    .school-modal-body input[type="text"] {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
    }
</style>
