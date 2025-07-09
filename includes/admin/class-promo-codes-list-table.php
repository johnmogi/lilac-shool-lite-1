<?php
/**
 * Promo Codes List Table Class
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * School_Manager_Lite_Promo_Codes_List_Table class
 * 
 * Extends WordPress WP_List_Table class to provide a custom table for promo codes
 */
class School_Manager_Lite_Promo_Codes_List_Table extends WP_List_Table {

    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'promo_code',
            'plural'   => 'promo_codes',
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'code'          => __('Promo Code', 'school-manager-lite'),
                        'class'         => __('Class', 'school-manager-lite'),
            'student'       => __('Student', 'school-manager-lite'),
            'uses'          => __('Uses', 'school-manager-lite'),
            'status'        => __('Status', 'school-manager-lite'),
            'expiry'        => __('Expiry Date', 'school-manager-lite'),
            'date_created'  => __('Created', 'school-manager-lite')
        );

        return $columns;
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'code'         => array('code', false),
                        'uses'         => array('used_count', false),
            'expiry'       => array('expiry_date', false),
            'date_created' => array('created_at', true) // true means it's already sorted
        );
        return $sortable_columns;
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
                        case 'uses':
                if (isset($item->usage_limit) && $item->usage_limit > 0) {
                    return sprintf('%d / %d', $item->used_count, $item->usage_limit);
                } else {
                    return sprintf('%d / %s', $item->used_count, __('Unlimited', 'school-manager-lite'));
                }
            case 'expiry':
                return !empty($item->expiry_date) ? date_i18n(get_option('date_format'), strtotime($item->expiry_date)) : __('No expiry', 'school-manager-lite');
            case 'date_created':
                return date_i18n(get_option('date_format'), strtotime($item->created_at));
            default:
                return isset($item->$column_name) ? $item->$column_name : '';
        }
    }

    /**
     * Column cb
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="promo_code_id[]" value="%s" />',
            $item->id
        );
    }

    /**
     * Column code
     */
    public function column_code($item) {
        // Build actions
        $actions = array(
            'edit'   => sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=school-manager-promo-codes&action=edit&id=' . $item->id),
                __('Edit', 'school-manager-lite')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=school-manager-promo-codes&action=delete&id=' . $item->id), 'delete_promo_code_' . $item->id),
                __('Are you sure you want to delete this promo code?', 'school-manager-lite'),
                __('Delete', 'school-manager-lite')
            ),
        );

        return sprintf(
            '<span class="promo-code"><strong>%1$s</strong></span> %2$s',
            esc_html($item->code),
            $this->row_actions($actions)
        );
    }

    /**
     * Column class
     */
    public function column_class($item) {
        if (empty($item->class_id)) {
            return '—';
        }

        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $class = $class_manager->get_class($item->class_id);
        
        if ($class) {
            return sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=school-manager-classes&action=edit&id=' . $class->id),
                esc_html($class->name)
            );
        }

        return __('Class not found', 'school-manager-lite');
    }

    /**
     * Column student
     */
    public function column_student($item) {
        if ( empty( $item->student_id ) ) {
            return '—';
        }

        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_row     = $student_manager->get_student( $item->student_id );
        if ( ! $student_row || empty( $student_row->wp_user_id ) ) {
            return __('Student not found', 'school-manager-lite');
        }

        $wp_user = get_user_by( 'id', $student_row->wp_user_id );
        if ( $wp_user ) {
            return sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'user-edit.php?user_id=' . $wp_user->ID ),
                esc_html( $wp_user->display_name )
            );
        }

        return __('Student not found', 'school-manager-lite');
    }

    /**
     * Column status
     */
    public function column_status($item) {
        $status = isset($item->status) ? $item->status : '';
        
        $status_labels = array(
            'active'   => '<span class="status-active">' . __('Active', 'school-manager-lite') . '</span>',
            'used'     => '<span class="status-used">' . __('Used', 'school-manager-lite') . '</span>',
            'expired'  => '<span class="status-expired">' . __('Expired', 'school-manager-lite') . '</span>',
            'inactive' => '<span class="status-inactive">' . __('Inactive', 'school-manager-lite') . '</span>',
        );
        
        // Determine status if not explicitly set
        if ($status !== 'inactive') {
            // If has expiry date and it's passed
            if (!empty($item->expiry_date) && strtotime($item->expiry_date) < time()) {
                $status = 'expired';
            }
            // If has usage limit and reached it
            elseif (isset($item->usage_limit) && $item->usage_limit > 0 && isset($item->used_count) && $item->used_count >= $item->usage_limit) {
                $status = 'used';
            }
            // Default to active
            else {
                $status = 'active';
            }
        }
        
        return isset($status_labels[$status]) ? $status_labels[$status] : $status_labels['inactive'];
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'delete' => __('Delete', 'school-manager-lite')
        );
        return $actions;
    }

    /**
     * Process bulk action
     */
    public function process_bulk_action() {
        // Security check
        if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {
            $nonce  = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
            $action = 'bulk-' . $this->_args['plural'];

            if (!wp_verify_nonce($nonce, $action)) {
                wp_die('Security check failed!');
            }
        }

        $action = $this->current_action();

        switch ($action) {
            case 'delete':
                if (isset($_POST['promo_code_id']) && is_array($_POST['promo_code_id'])) {
                    $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
                    $promo_code_ids = array_map('intval', $_POST['promo_code_id']);
                    foreach ($promo_code_ids as $promo_code_id) {
                        $promo_code_manager->delete_promo_code($promo_code_id);
                    }
                    
                    // Add admin notice
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                            __('Promo codes deleted successfully.', 'school-manager-lite') . '</p></div>';
                    });
                }
                break;
        }
    }

    /**
     * Prepare items
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // Column headers
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Process actions
        $this->process_bulk_action();

        // Get data
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Handle filtering by class or student
        $class_id = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

        // Query args
        $args = array(
            'search' => $search,
            'orderby' => $orderby,
            'order' => $order,
        );
        
        if (!empty($class_id)) {
            $args['class_id'] = $class_id;
        }
        
        if (!empty($user_id)) {
            $args['user_id'] = $user_id;
        }

        // Get all promo codes
        $data = $promo_code_manager->get_promo_codes($args);

        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        // Slice data for pagination
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
    }

    /**
     * Display no items message
     */
    public function no_items() {
        _e('No promo codes found.', 'school-manager-lite');
    }

    /**
     * Extra tablenav controls
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            // Class filter
            $class_id = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $classes = $class_manager->get_classes();
            
            echo '<div class="alignleft actions">';
            
            if (!empty($classes)) {
                echo '<label class="screen-reader-text" for="filter-by-class">' . 
                    __('Filter by class', 'school-manager-lite') . '</label>';
                echo '<select name="class_id" id="filter-by-class">';
                echo '<option value="0">' . __('All Classes', 'school-manager-lite') . '</option>';
                
                foreach ($classes as $class) {
                    echo '<option value="' . esc_attr($class->id) . '" ' . 
                        selected($class->id, $class_id, false) . '>' . 
                        esc_html($class->name) . '</option>';
                }
                
                echo '</select> ';
            }
            
            // Status filter
            $status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
            $statuses = array(
                '' => __('All Statuses', 'school-manager-lite'),
                'active' => __('Active', 'school-manager-lite'),
                'used' => __('Used', 'school-manager-lite'),
                'expired' => __('Expired', 'school-manager-lite'),
                'inactive' => __('Inactive', 'school-manager-lite'),
            );
            
            echo '<label class="screen-reader-text" for="filter-by-status">' . 
                __('Filter by status', 'school-manager-lite') . '</label>';
            echo '<select name="status" id="filter-by-status">';
            
            foreach ($statuses as $status_value => $status_label) {
                echo '<option value="' . esc_attr($status_value) . '" ' . 
                    selected($status_value, $status, false) . '>' . 
                    esc_html($status_label) . '</option>';
            }
            
            echo '</select>';
            
            echo '<input type="submit" class="button" value="' . 
                __('Filter', 'school-manager-lite') . '">';
            echo '</div>';
        }
    }
}
