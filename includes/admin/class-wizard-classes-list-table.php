<?php
/**
 * Wizard Classes List Table Class
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
 * School_Manager_Lite_Wizard_Classes_List_Table class
 * 
 * Extends WordPress WP_List_Table class to provide a custom table for class selection in wizard
 */
class School_Manager_Lite_Wizard_Classes_List_Table extends WP_List_Table {

    /**
     * Selected class ID
     */
    private $selected_class_id = 0;
    
    /**
     * Teacher ID filter
     */
    private $teacher_id = 0;

    /**
     * Class Constructor
     */
    public function __construct($teacher_id = 0) {
        parent::__construct(array(
            'singular' => 'wizard_class',
            'plural'   => 'wizard_classes',
            'ajax'     => false
        ));

        $this->selected_class_id = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;
        $this->teacher_id = $teacher_id;
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
        $checked = $this->selected_class_id == $item->id ? ' checked="checked"' : '';
        return sprintf(
            '<input type="radio" name="class_id" value="%s"%s />',
            $item->id,
            $checked
        );
    }

    /**
     * Column name
     */
    public function column_name($item) {
        return sprintf(
            '<label for="class-%s"><strong>%s</strong></label>',
            $item->id,
            esc_html($item->name)
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
            return esc_html($teacher->display_name);
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
            _n('%d student', '%d students', $count, 'school-manager-lite'),
            $count
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

        // Get data
        $class_manager = School_Manager_Lite_Class_Manager::instance();

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';
        
        // Query args
        $args = array(
            'orderby' => $orderby,
            'order'   => $order,
        );
        
        if ($this->teacher_id > 0) {
            $args['teacher_id'] = $this->teacher_id;
        }
        
        if (!empty($search)) {
            $args['search'] = $search;
        }

        // Get all classes
        $data = $class_manager->get_classes($args);

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
        _e('No classes found.', 'school-manager-lite');
    }
}
