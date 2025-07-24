<?php
/**
 * Promo Code Generator Admin Page
 *
 * @package School_Manager_Lite
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$class_manager       = School_Manager_Lite_Class_Manager::instance();
$classes             = $class_manager->get_classes();
$teacher_manager     = School_Manager_Lite_Teacher_Manager::instance();

// Handle form submission
if (isset($_POST['generate_promo_codes'])) {
    check_admin_referer('school_manager_generate_promo_codes', 'school_manager_nonce');

    // Sanitize inputs
    $class_id   = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $quantity   = isset($_POST['quantity']) ? max(1, min(1000, intval($_POST['quantity']))) : 1;
    $prefix     = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
    $expiry     = !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;

    $promo_manager = School_Manager_Lite_Promo_Code_Manager::instance();
    $result        = $promo_manager->generate_promo_codes([
        'quantity'     => $quantity,
        'prefix'       => $prefix,
        'class_id'     => $class_id,
        'teacher_id'   => $teacher_id,
        'expiry_date'  => $expiry,
    ]);

    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>';
        printf(__('‎%1$s קודי הטבה נוצרו בהצלחה.', 'school-manager-lite'), number_format_i18n(count($result)));
        echo '</p></div>';
    }
}

?>
<div class="wrap school-manager-admin">
    <h1 class="wp-heading-inline"><?php _e('יצירת קודי הטבה', 'school-manager-lite'); ?></h1> 

    <form method="post" class="promo-generator-form">
        <?php wp_nonce_field('school_manager_generate_promo_codes', 'school_manager_nonce'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="class_id"><?php _e('‎כיתה', 'school-manager-lite'); ?></label></th>
                    <td>
                        <select name="class_id" id="class_id" required>
                            <option value=""><?php _e('בחר כיתה', 'school-manager-lite'); ?></option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?php echo esc_attr($class->id); ?>"><?php echo esc_html($class->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="teacher_id"><?php _e('‎מורה', 'school-manager-lite'); ?></label></th>
                    <td>
                        <select name="teacher_id" id="teacher_id" required>
                            <option value=""><?php _e('בחר מורה', 'school-manager-lite'); ?></option>
                            <?php $teachers = $teacher_manager->get_teachers();
                            foreach ($teachers as $teacher) : ?>
                                <option value="<?php echo esc_attr($teacher->ID); ?>"><?php echo esc_html($teacher->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="quantity"><?php _e('‎כמות', 'school-manager-lite'); ?></label></th>
                    <td><input type="number" min="1" max="1000" name="quantity" id="quantity" value="25" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="prefix"><?php _e('‎תחילית קוד', 'school-manager-lite'); ?></label></th>
                    <td><input type="text" name="prefix" id="prefix" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="expiry_date"><?php _e('‎תוקף', 'school-manager-lite'); ?></label></th>
                    <?php
                        $year        = date('Y');
                        $default_expiry = $year . '-06-30';
                        if (strtotime($default_expiry) < time()) {
                            $default_expiry = ($year + 1) . '-06-30';
                        }
                        ?>
                        <td><input type="date" name="expiry_date" id="expiry_date" value="<?php echo esc_attr($default_expiry); ?>"></td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="generate_promo_codes" class="button-primary" value="<?php _e('‏צור קודי הטבה', 'school-manager-lite'); ?>">         </p>
    </form>
</div>
