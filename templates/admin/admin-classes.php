<?php
/**
 * Admin Classes Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the list table class
require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-classes-list-table.php';

// Create an instance of our list table
$classes_table = new School_Manager_Lite_Classes_List_Table();

// Process actions and prepare the table items
$classes_table->prepare_items();
?>
<div class="wrap school-manager-admin">
    <h1 class="wp-heading-inline"><?php _e('Classes', 'school-manager-lite'); ?></h1>
    <a href="#" class="page-title-action" id="add-class-toggle"><?php _e('Add New', 'school-manager-lite'); ?></a>
    <a href="#" class="page-title-action" id="sync-instructors-groups" style="background: #00a32a; border-color: #00a32a;"><?php _e('Sync Instructors to Groups', 'school-manager-lite'); ?></a>
    
    <div id="add-class-form" style="display: none;">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Add Class', 'school-manager-lite'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('school_manager_add_class', 'school_manager_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="class_name"><?php _e('Class Name', 'school-manager-lite'); ?></label></th>
                            <td><input type="text" name="class_name" id="class_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="class_description"><?php _e('Description', 'school-manager-lite'); ?></label></th>
                            <td><textarea name="class_description" id="class_description" class="regular-text" rows="4"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="teacher_id"><?php _e('Teacher', 'school-manager-lite'); ?></label></th>
                            <td>
                                <select name="teacher_id" id="teacher_id" required>
                                    <option value=""><?php _e('Select Teacher', 'school-manager-lite'); ?></option>
                                    <?php
                                    $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
                                    $teachers = $teacher_manager->get_teachers();
                                    
                                    if ($teachers) {
                                        foreach ($teachers as $teacher) {
                                            echo '<option value="' . esc_attr($teacher->ID) . '">' . esc_html($teacher->display_name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_class" class="button button-primary" value="<?php _e('Add Class', 'school-manager-lite'); ?>">
                        <button type="button" class="button" id="cancel-add-class"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Search box -->
    <form method="post">
        <?php $classes_table->search_box(__('Search Classes', 'school-manager-lite'), 'search-classes'); ?>
        
        <!-- Display the table with extra filters -->
        <?php $classes_table->display(); ?>
    </form>
</div>

<?php
// Include the teacher assignment modal at the end of the page
require_once SCHOOL_MANAGER_LITE_PATH . 'templates/admin/modals/teacher-assignment-modal.php';
?>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Show/hide the add class form
        $('#add-class-toggle').on('click', function(e) {
            e.preventDefault();
            $('#add-class-form').slideToggle();
        });

        // Cancel add class form
        $('#cancel-add-class').on('click', function(e) {
            e.preventDefault();
            $('#add-class-form').slideUp();
        });
        
        // Sync instructors to groups
        $('#sync-instructors-groups').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            
            if (confirm('<?php _e('This will sync all instructors to their LearnDash groups. Continue?', 'school-manager-lite'); ?>')) {
                button.text('<?php _e('Syncing...', 'school-manager-lite'); ?>').prop('disabled', true);
                
                $.ajax({
                    url: schoolManagerAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'simple_sync_teachers_groups',
                        nonce: schoolManagerAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Sync completed successfully!', 'school-manager-lite'); ?>\n' + response.data.message);
                            location.reload();
                        } else {
                            alert('<?php _e('Sync failed:', 'school-manager-lite'); ?> ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Sync failed. Please try again.', 'school-manager-lite'); ?>');
                    },
                    complete: function() {
                        button.text(originalText).prop('disabled', false);
                    }
                });
            }
        });
    });
</script>

<?php
// Script enqueuing is handled in the admin class via the admin_enqueue_scripts hook
?>
