<?php
/**
 * Wizard Teachers List Table Class
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
 * School_Manager_Lite_Wizard_Teachers_List_Table class
 * 
 * Extends WordPress WP_List_Table class to provide a custom table for teacher selection in wizard
 */
class School_Manager_Lite_Wizard_Teachers_List_Table extends WP_List_Table {

    /**
     * Selected teacher ID
     */
    private $selected_teacher_id = 0;

    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'wizard_teacher',
            'plural'   => 'wizard_teachers',
            'ajax'     => false
        ));

        $this->selected_teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 0;
    }

    /**
     * Get columns
     */
    public function get_columns() {
        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'display_name' => __('Name', 'school-manager-lite'),
            'user_email'   => __('Email', 'school-manager-lite'),
            'role'         => __('Role', 'school-manager-lite')
        );

        return $columns;
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'display_name' => array('display_name', false),
            'user_email'   => array('user_email', false)
        );
        return $sortable_columns;
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'user_email':
                return $item->user_email;
            case 'role':
                $roles = array_map(function($role) {
                    $role_obj = get_role($role);
                    return translate_user_role($role_obj->name);
                }, $item->roles);
                return implode(', ', $roles);
            default:
                return isset($item->$column_name) ? $item->$column_name : '';
        }
    }

    /**
     * Column cb
     */
    public function column_cb($item) {
        $checked = $this->selected_teacher_id == $item->ID ? ' checked="checked"' : '';
        return sprintf(
            '<input type="radio" name="teacher_id" value="%s"%s />',
            $item->ID,
            $checked
        );
    }

    /**
     * Column display_name
     */
    public function column_display_name($item) {
        return sprintf(
            '<label for="user-%s"><strong>%s</strong></label>',
            $item->ID,
            $item->display_name
        );
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

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'display_name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';

        // Get only users with school_teacher role
        $args = array(
            'role__in' => array('school_teacher'),
            'orderby' => $orderby,
            'order' => $order,
            'search' => '*' . $search . '*',
            'search_columns' => array('display_name', 'user_email'),
            'fields' => 'all_with_meta'
        );

        // Get all matching users
        $data = get_users($args);

        // Pagination
        $per_page = 10;
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
        _e('No users found.', 'school-manager-lite');
    }
}
