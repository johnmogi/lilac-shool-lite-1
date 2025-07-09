<?php
/**
 * Teachers List Table
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Teachers List Table Class
 * 
 * Displays teachers in a WordPress admin table with search, pagination, and sorting
 */
class School_Manager_Lite_Teachers_List_Table extends WP_List_Table {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'teacher',
            'plural'   => 'teachers',
            'ajax'     => false
        ));
    }
    
    /**
     * Get table columns
     */
    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'name'        => __('Name', 'school-manager-lite'),
            'email'       => __('Email', 'school-manager-lite'),
            'classes'     => __('Classes', 'school-manager-lite'),
            'students'    => __('Students', 'school-manager-lite'),
            'date'        => __('Registered', 'school-manager-lite')
        );
    }
    
    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return array(
            'name'  => array('display_name', true),
            'email' => array('user_email', false),
            'date'  => array('user_registered', false)
        );
    }
    
    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email':
                return esc_html($item->user_email);
            case 'date':
                return date_i18n(get_option('date_format'), strtotime($item->user_registered));
            default:
                return print_r($item, true); // For debugging
        }
    }
    
    /**
     * Column name
     */
    public function column_name($item) {
        // Build row actions
        $actions = array(
            'edit'   => sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=school-manager-teachers&action=edit&id=' . $item->ID), __('Edit', 'school-manager-lite')),
            'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>', wp_nonce_url(admin_url('admin.php?page=school-manager-teachers&action=delete&id=' . $item->ID), 'delete_teacher_' . $item->ID), esc_js(__('Are you sure you want to delete this teacher?', 'school-manager-lite')), __('Delete', 'school-manager-lite')),
        );
        
        return sprintf('<strong><a href="%s">%s</a></strong> %s', 
            esc_url(admin_url('admin.php?page=school-manager-teachers&action=edit&id=' . $item->ID)),
            $item->display_name,
            $this->row_actions($actions)
        );
    }
    
    /**
     * Column checkbox
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="teacher_ids[]" value="%s" />', $item->ID);
    }
    
    /**
     * Column classes
     */
    public function column_classes($item) {
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes(array('teacher_id' => $item->ID));
        
        if (empty($classes)) {
            return '<span class="na">&ndash;</span>';
        }
        
        return count($classes);
    }
    
    /**
     * Column students
     */
    public function column_students($item) {
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes(array('teacher_id' => $item->ID));
        
        if (empty($classes)) {
            return '<span class="na">&ndash;</span>';
        }
        
        $student_count = 0;
        foreach ($classes as $class) {
            $student_count += $class_manager->count_class_students($class->id);
        }
        
        return $student_count;
    }
    
    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'school-manager-lite')
        );
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        if ('delete' === $this->current_action() && isset($_POST['teacher_ids'])) {
            $teacher_ids = array_map('absint', $_POST['teacher_ids']);
            
            if (!empty($teacher_ids)) {
                $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
                
                foreach ($teacher_ids as $teacher_id) {
                    $teacher_manager->delete_teacher($teacher_id);
                }
                
                // Add admin notice
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Teachers deleted.', 'school-manager-lite') . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Process bulk actions
        $this->process_bulk_action();
        
        // Get teachers with pagination and sorting
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Handle ordering
        $orderby = (isset($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'display_name';
        $order = (isset($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'ASC';
        
        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Set up query args
        $args = array(
            'role' => 'school_teacher',
            'orderby' => $orderby,
            'order' => $order,
            'number' => $per_page,
            'offset' => $offset,
            'search' => !empty($search) ? '*' . $search . '*' : '',
        );
        
        $total_items = count_users(array('role' => 'school_teacher'));
        $total_items = $total_items['avail_roles']['school_teacher'] ?? 0;
        
        $this->items = $teacher_manager->get_teachers($args);
        
        // Set up pagination args
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    /**
     * No items found text
     */
    public function no_items() {
        _e('No teachers found.', 'school-manager-lite');
    }
}
