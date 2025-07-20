<?php
/**
 * Admin Teachers Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the list table class
require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-teachers-list-table.php';

// Create an instance of our list table
$teachers_table = new School_Manager_Lite_Teachers_List_Table();

// Process bulk actions first
$teachers_table->process_bulk_action();

// Process actions and prepare the table items
$teachers_table->prepare_items();
?>
<div class="wrap school-manager-admin">
    <h1 class="wp-heading-inline"><?php _e('Teachers', 'school-manager-lite'); ?></h1>
    <a href="#" class="page-title-action" id="add-teacher-toggle"><?php _e('Add New', 'school-manager-lite'); ?></a>
    
    <div id="add-teacher-form" style="display: none;">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Add Teacher', 'school-manager-lite'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('school_manager_add_teacher', 'school_manager_nonce'); ?>
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
                            <th><label for="phone"><?php _e('Phone', 'school-manager-lite'); ?></label></th>
                            <td><input type="text" name="phone" id="phone" class="regular-text"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_teacher" class="button button-primary" value="<?php _e('Add Teacher', 'school-manager-lite'); ?>">
                        <button type="button" class="button" id="cancel-add-teacher"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Search box and table -->
    <form method="post" id="posts-filter">
        <?php $teachers_table->search_box(__('Search Teachers', 'school-manager-lite'), 'search-teachers'); ?>
        
        <!-- Display the table -->
        <?php $teachers_table->display(); ?>
        
        <?php wp_nonce_field('bulk-teachers'); ?>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Show/hide the add teacher form
        $('#add-teacher-toggle').on('click', function(e) {
            e.preventDefault();
            $('#add-teacher-form').slideToggle();
        });
        
        // Hide the form on cancel
        $('#cancel-add-teacher').on('click', function(e) {
            e.preventDefault();
            $('#add-teacher-form').slideUp();
        });
    });
</script>
