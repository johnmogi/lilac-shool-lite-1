<?php
/**
 * Teacher Dashboard Promo Codes Widget Template
 *
 * This template displays the teacher's promo codes in a dashboard widget
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="school-manager-promo-codes-widget">
    <?php if (!empty($promo_codes)) : ?>
        <table class="teacher-dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Promo Code', 'school-manager-lite'); ?></th>
                    <th><?php _e('Class', 'school-manager-lite'); ?></th>
                    <th><?php _e('Status', 'school-manager-lite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Only show up to 5 recent promo codes
                $recent_codes = array_slice($promo_codes, 0, 5);
                
                foreach ($recent_codes as $code) : 
                    // Get class name
                    $class_name = '';
                    foreach ($classes as $class) {
                        if ($class->id === $code->class_id) {
                            $class_name = $class->name;
                            break;
                        }
                    }
                    
                    // Determine status
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
                            <span class="status-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($promo_codes) > 5) : ?>
            <p><?php printf(__('Showing 5 of %d promo codes', 'school-manager-lite'), count($promo_codes)); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <p><?php _e('You don\'t have any promo codes for your classes yet.', 'school-manager-lite'); ?></p>
    <?php endif; ?>
    
    <p class="widget-footer">
        <a href="<?php echo admin_url('admin.php?page=school-teacher-promo-codes'); ?>" class="button button-small">
            <?php _e('Generate & Manage Promo Codes', 'school-manager-lite'); ?>
        </a>
    </p>
</div>

<style type="text/css">
    .school-manager-promo-codes-widget .teacher-dashboard-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    
    .school-manager-promo-codes-widget .teacher-dashboard-table th,
    .school-manager-promo-codes-widget .teacher-dashboard-table td {
        padding: 8px 5px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .school-manager-promo-codes-widget .teacher-dashboard-table th {
        font-weight: 600;
    }
    
    .school-manager-promo-codes-widget .widget-footer {
        margin-top: 10px;
        text-align: right;
    }
    
    .school-manager-promo-codes-widget .status-available {
        color: green;
        font-weight: bold;
    }
    
    .school-manager-promo-codes-widget .status-used {
        color: #999;
    }
    
    .school-manager-promo-codes-widget .status-expired {
        color: red;
    }
</style>
