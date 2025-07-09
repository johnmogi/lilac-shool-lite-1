<?php
/**
 * Teacher Promo Codes Template
 *
 * This template displays the teacher's promo codes in WordPress admin
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if we need to process form submission
$message = '';
$message_type = '';

if (isset($_POST['generate_promo_codes']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'school_generate_promo_codes')) {
    // Handle promo code generation
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
    $expiry_date = isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;
    
    if ($class_id > 0) {
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $result = $promo_code_manager->generate_promo_codes(array(
            'quantity' => $quantity,
            'prefix' => $prefix,
            'class_id' => $class_id,
            'teacher_id' => $teacher_id,
            'expiry_date' => $expiry_date
        ));
        
        if (!is_wp_error($result)) {
            $message = sprintf(
                _n('%s promo code generated successfully.', '%s promo codes generated successfully.', count($result), 'school-manager-lite'), 
                count($result)
            );
            $message_type = 'success';
            
            // Refresh promo codes list
            $promo_codes = $promo_code_manager->get_promo_codes(array(
                'teacher_id' => $teacher_id,
                'class_id' => $filter_class_id > 0 ? $filter_class_id : null
            ));
        } else {
            $message = $result->get_error_message();
            $message_type = 'error';
        }
    } else {
        $message = __('Please select a class to generate promo codes for.', 'school-manager-lite');
        $message_type = 'error';
    }
}
?>
<div class="wrap school-manager-teacher-promo-codes">
    <h1><?php _e('Promo Codes', 'school-manager-lite'); ?></h1>
    
    <?php if (!empty($message)) : ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="school-teacher-promo-codes">
                <label for="class-filter" class="screen-reader-text"><?php _e('Filter by class', 'school-manager-lite'); ?></label>
                <select name="class_id" id="class-filter">
                    <option value="0"><?php _e('All Classes', 'school-manager-lite'); ?></option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo $class->id; ?>" <?php selected($filter_class_id, $class->id); ?>>
                            <?php echo esc_html($class->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'school-manager-lite'); ?>">
            </form>
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s promo code', '%s promo codes', count($promo_codes), 'school-manager-lite'), count($promo_codes)); ?>
            </span>
        </div>
        <br class="clear">
    </div>
    
    <!-- Generate Promo Codes Form -->
    <div class="postbox">
        <h2 class="hndle"><span><?php _e('Generate New Promo Codes', 'school-manager-lite'); ?></span></h2>
        <div class="inside">
            <?php if (!empty($classes)) : ?>
                <form method="post" action="">
                    <?php wp_nonce_field('school_generate_promo_codes'); ?>
                    <input type="hidden" name="generate_promo_codes" value="1">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="class_id"><?php _e('Class', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <select name="class_id" id="class_id" required>
                                    <option value=""><?php _e('Select a class', 'school-manager-lite'); ?></option>
                                    <?php foreach ($classes as $class) : ?>
                                        <option value="<?php echo $class->id; ?>" <?php selected($filter_class_id, $class->id); ?>>
                                            <?php echo esc_html($class->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="quantity"><?php _e('Quantity', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="quantity" id="quantity" value="10" min="1" max="100" required>
                                <p class="description"><?php _e('Number of promo codes to generate (1-100)', 'school-manager-lite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="prefix"><?php _e('Prefix', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="prefix" id="prefix" value="">
                                <p class="description"><?php _e('Optional prefix for your promo codes (e.g., CLASS-)', 'school-manager-lite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="expiry_date"><?php _e('Expiry Date', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <input type="date" name="expiry_date" id="expiry_date" value="">
                                <p class="description"><?php _e('Optional expiry date for the promo codes', 'school-manager-lite'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Generate Promo Codes', 'school-manager-lite'); ?>">
                    </p>
                </form>
            <?php else : ?>
                <p><?php _e('You need to have classes assigned to you before you can generate promo codes.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Promo Codes List -->
    <?php if (!empty($promo_codes)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Promo Code', 'school-manager-lite'); ?></th>
                    <th><?php _e('Class', 'school-manager-lite'); ?></th>
                    <th><?php _e('Expiry Date', 'school-manager-lite'); ?></th>
                    <th><?php _e('Student', 'school-manager-lite'); ?></th>
                    <th><?php _e('Status', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promo_codes as $code) : 
                    // Get class name
                    $class_name = '';
                    foreach ($classes as $class) {
                        if ($class->id === $code->class_id) {
                            $class_name = $class->name;
                            break;
                        }
                    }
                    
                    // Determine status
                    $status = '';
                    $status_class = '';
                    
                    if (!empty($code->used_at)) {
                        $status = __('Used', 'school-manager-lite');
                        $status_class = 'used';
                    } elseif (!empty($code->expiry_date) && strtotime($code->expiry_date) < time()) {
                        $status = __('Expired', 'school-manager-lite');
                        $status_class = 'expired';
                    } else {
                        $status = __('Available', 'school-manager-lite');
                        $status_class = 'available';
                    }
                    
                    // Get student name if used
                    $student_name = '&mdash;';
                    if (!empty($code->student_id)) {
                        $student_manager = School_Manager_Lite_Student_Manager::instance();
                        $student = $student_manager->get_student($code->student_id);
                        if ($student) {
                            $student_name = $student->name;
                        }
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($code->code); ?></strong>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=school-teacher-promo-codes&class_id=' . $code->class_id); ?>">
                            <?php echo esc_html($class_name); ?>
                        </a>
                    </td>
                    <td>
                        <?php 
                        if (!empty($code->expiry_date)) {
                            echo date_i18n(get_option('date_format'), strtotime($code->expiry_date));
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo $student_name; ?>
                    </td>
                    <td>
                        <span class="status-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                        <?php 
                        if (!empty($code->used_at)) {
                            echo ' ' . date_i18n(get_option('date_format'), strtotime($code->used_at));
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- CSV Export -->
        <p>
            <a href="<?php echo admin_url('admin.php?page=school-teacher-promo-codes&action=export_csv&_wpnonce=' . wp_create_nonce('export_promo_codes')); ?>" class="button">
                <?php _e('Export to CSV', 'school-manager-lite'); ?>
            </a>
        </p>
    <?php else : ?>
        <div class="notice notice-info">
            <p>
                <?php 
                if ($filter_class_id > 0) {
                    _e('No promo codes found for this class.', 'school-manager-lite');
                } else {
                    _e('You don\'t have any promo codes yet.', 'school-manager-lite');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style type="text/css">
    .status-available {
        color: green;
        font-weight: bold;
    }
    
    .status-used {
        color: #999;
    }
    
    .status-expired {
        color: red;
    }
</style>
