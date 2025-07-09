<?php
/**
 * Admin Promo Codes Template
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the list table class
require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-promo-codes-list-table.php';

// Create an instance of our list table
$promo_codes_table = new School_Manager_Lite_Promo_Codes_List_Table();

// Process actions and prepare the table items
$promo_codes_table->prepare_items();
?>
<div class="wrap school-manager-admin">
    <h1 class="wp-heading-inline"><?php _e('Promo Codes', 'school-manager-lite'); ?></h1>
    <a href="#" class="page-title-action" id="add-promo-code-toggle"><?php _e('Add New', 'school-manager-lite'); ?></a>
    
    <div id="add-promo-code-form" style="display: none;">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Add Promo Code', 'school-manager-lite'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('school_manager_add_promo_code', 'school_manager_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="code"><?php _e('Code', 'school-manager-lite'); ?></label></th>
                            <td>
                                <input type="text" name="code" id="code" class="regular-text" required>
                                <button type="button" class="button" id="generate-code"><?php _e('Generate Code', 'school-manager-lite'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="discount_type"><?php _e('Discount Type', 'school-manager-lite'); ?></label></th>
                            <td>
                                <select name="discount_type" id="discount_type" required>
                                    <option value="percentage"><?php _e('Percentage', 'school-manager-lite'); ?></option>
                                    <option value="fixed"><?php _e('Fixed Amount', 'school-manager-lite'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="discount_amount"><?php _e('Discount Amount', 'school-manager-lite'); ?></label></th>
                            <td><input type="number" min="0" step="0.01" name="discount_amount" id="discount_amount" class="small-text" required> <span id="discount-symbol">%</span></td>
                        </tr>
                        <tr>
                            <th><label for="class_id"><?php _e('Apply to Class', 'school-manager-lite'); ?></label></th>
                            <td>
                                <select name="class_id" id="class_id">
                                    <option value=""><?php _e('All Classes', 'school-manager-lite'); ?></option>
                                    <?php
                                    $class_manager = School_Manager_Lite_Class_Manager::instance();
                                    $classes = $class_manager->get_classes();
                                    
                                    if ($classes) {
                                        foreach ($classes as $class) {
                                            echo '<option value="' . esc_attr($class->id) . '">' . esc_html($class->name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="expiry_date"><?php _e('Expiry Date', 'school-manager-lite'); ?></label></th>
                            <td><input type="date" name="expiry_date" id="expiry_date" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="usage_limit"><?php _e('Usage Limit', 'school-manager-lite'); ?></label></th>
                            <td><input type="number" min="0" step="1" name="usage_limit" id="usage_limit" class="small-text" value="1"> <span class="description"><?php _e('0 for unlimited', 'school-manager-lite'); ?></span></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_promo_code" class="button button-primary" value="<?php _e('Add Promo Code', 'school-manager-lite'); ?>">
                        <button type="button" class="button" id="cancel-add-promo-code"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Search box -->
    <form method="post">
        <?php $promo_codes_table->search_box(__('Search Promo Codes', 'school-manager-lite'), 'search-promo-codes'); ?>
        
        <!-- Display the table with extra filters -->
        <?php $promo_codes_table->display(); ?>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Show/hide the add promo code form
        $('#add-promo-code-toggle').on('click', function(e) {
            e.preventDefault();
            $('#add-promo-code-form').slideToggle();
        });
        
        // Hide the form on cancel
        $('#cancel-add-promo-code').on('click', function(e) {
            e.preventDefault();
            $('#add-promo-code-form').slideUp();
        });
        
        // Update discount symbol based on discount type
        $('#discount_type').on('change', function() {
            var type = $(this).val();
            if (type === 'percentage') {
                $('#discount-symbol').text('%');
            } else {
                $('#discount-symbol').text('$');
            }
        });
        
        // Generate random promo code
        $('#generate-code').on('click', function(e) {
            e.preventDefault();
            var length = 8;
            var charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            var code = "";
            for (var i = 0; i < length; i++) {
                code += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            $('#code').val(code);
        });

        // Confirm bulk actions
        $('#doaction').click(function() {
            if ($('#bulk-action-selector-top').val() == 'delete') {
                return confirm('<?php _e("Are you sure you want to delete the selected promo codes?", "school-manager-lite"); ?>');
            }
        });

        // Copy code to clipboard
        $('.copy-code').on('click', function() {
            var code = $(this).data('code');
            navigator.clipboard.writeText(code).then(function() {
                alert('<?php _e("Promo code copied to clipboard: ", "school-manager-lite"); ?>' + code);
            });
        });
    });
</script>

<style type="text/css">
    /* Status Labels */
    .status-active, .status-used, .status-expired, .status-inactive {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #dff0d8;
        color: #3c763d;
    }
    
    .status-used {
        background-color: #d9edf7;
        color: #31708f;
    }
    
    .status-expired {
        background-color: #fcf8e3;
        color: #8a6d3b;
    }
    
    .status-inactive {
        background-color: #f2dede;
        color: #a94442;
    }

    /* Form styling */
    #add-promo-code-form {
        margin-top: 20px;
    }
</style>
