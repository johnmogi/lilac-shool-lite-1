<?php
/**
 * Classes List Table Class
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
 * School_Manager_Lite_Classes_List_Table class
 * 
 * Extends WordPress WP_List_Table class to provide a custom table for classes
 */
class School_Manager_Lite_Classes_List_Table extends WP_List_Table {

    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'class',
            'plural'   => 'classes',
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'name'          => __('Class Name', 'school-manager-lite'),
            'description'   => __('Description', 'school-manager-lite'),
            'teacher'       => __('Teacher', 'school-manager-lite'),
            'students'      => __('Students', 'school-manager-lite'),
            'date_created'  => __('Created', 'school-manager-lite')
        );

        return $columns;
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'name'         => array('name', false),
            'teacher'      => array('teacher_id', false),
            'date_created' => array('created_at', true) // true means it's already sorted
        );
        return $sortable_columns;
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'description':
                return !empty($item->description) ? esc_html($item->description) : '—';
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
            '<input type="checkbox" name="class_id[]" value="%s" />',
            $item->id
        );
    }

    /**
     * Column name
     */
    public function column_name($item) {
        // Build actions
        $actions = array(
            'edit'   => sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=school-manager-classes&action=edit&id=' . $item->id),
                __('Edit', 'school-manager-lite')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=school-manager-classes&action=delete&id=' . $item->id), 'delete_class_' . $item->id),
                __('Are you sure you want to delete this class?', 'school-manager-lite'),
                __('Delete', 'school-manager-lite')
            ),
        );

        return sprintf(
            '<a href="%1$s"><strong>%2$s</strong></a> %3$s',
            admin_url('admin.php?page=school-manager-classes&action=edit&id=' . $item->id),
            esc_html($item->name),
            $this->row_actions($actions)
        );
    }

    /**
     * Column teacher
     */
    public function column_teacher($item) {
        if (empty($item->teacher_id)) {
            return '—';
        }

        $teacher = get_user_by('id', $item->teacher_id);
        if ($teacher) {
            return sprintf(
                '<a href="%s">%s</a>',
                admin_url('user-edit.php?user_id=' . $teacher->ID),
                esc_html($teacher->display_name)
            );
        }

        return __('Unknown Teacher', 'school-manager-lite');
    }

    /**
     * Column students
     */
    public function column_students($item) {
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $students = $student_manager->get_students(array('class_id' => $item->id));
        
        $count = is_array($students) ? count($students) : 0;
        
        return sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=school-manager-students&class_id=' . $item->id),
            sprintf(_n('%d student', '%d students', $count, 'school-manager-lite'), $count)
        );
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
     * Process bulk actions
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
                if (isset($_POST['class_id']) && is_array($_POST['class_id'])) {
                    $class_manager = School_Manager_Lite_Class_Manager::instance();
                    $class_ids = array_map('intval', $_POST['class_id']);
                    foreach ($class_ids as $class_id) {
                        $class_manager->delete_class($class_id);
                    }
                    
                    // Add admin notice
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                            __('Classes deleted successfully.', 'school-manager-lite') . '</p></div>';
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
        $class_manager = School_Manager_Lite_Class_Manager::instance();

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';
        
        // Handle filtering by teacher
        $teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 0;

        // Query args
        $args = array(
            'orderby' => $orderby,
            'order'   => $order,
        );
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        if (!empty($teacher_id)) {
            $args['teacher_id'] = $teacher_id;
        }

        // Get all classes
        $data = $class_manager->get_classes($args);

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
        _e('No classes found.', 'school-manager-lite');
    }

    /**
     * Extra tablenav controls
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            $teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 0;
            $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
            $teachers = $teacher_manager->get_teachers();
            
            if (!empty($teachers)) {
                echo '<div class="alignleft actions">';
                echo '<label class="screen-reader-text" for="filter-by-teacher">' . 
                    __('Filter by teacher', 'school-manager-lite') . '</label>';
                echo '<select name="teacher_id" id="filter-by-teacher">';
                echo '<option value="0">' . __('All Teachers', 'school-manager-lite') . '</option>';
                
                foreach ($teachers as $teacher) {
                    echo '<option value="' . esc_attr($teacher->ID) . '" ' . 
                        selected($teacher->ID, $teacher_id, false) . '>' . 
                        esc_html($teacher->display_name) . '</option>';
                }
                
                echo '</select>';
                echo '<input type="submit" class="button" value="' . 
                    __('Filter', 'school-manager-lite') . '">';
                echo '</div>';
            }
        }
    }
}
