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
            case 'teacher':
                if (empty($item->teacher_id)) {
                    return '—';
                }
                $teacher = get_user_by('id', $item->teacher_id);
                return $teacher ? esc_html($teacher->display_name) : __('Unknown Teacher', 'school-manager-lite');
            case 'students':
                return sprintf(
                    _n('%d student', '%d students', $item->student_count, 'school-manager-lite'),
                    $item->student_count
                );
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

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'title';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';
        
        // Query args for LearnDash groups
        $args = array(
            'post_type'      => learndash_get_post_type_slug('group'),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => $order,
            's'              => $search,
        );

        // If teacher ID is provided, get groups for that teacher
        if ($this->teacher_id > 0) {
            $args['author'] = $this->teacher_id;
        }

        // Get all groups
        $groups = get_posts($args);
        
        // Format the groups to match expected format
        $formatted_groups = array();
        foreach ($groups as $group) {
            $formatted_group = new stdClass();
            $formatted_group->id = $group->ID;
            $formatted_group->name = $group->post_title;
            $formatted_group->description = $group->post_content;
            $formatted_group->teacher_id = $group->post_author;
            $formatted_group->created_at = $group->post_date;
            
            // Count students in this group
            $group_users = learndash_get_groups_user_ids($group->ID);
            $formatted_group->student_count = is_array($group_users) ? count($group_users) : 0;
            
            $formatted_groups[] = $formatted_group;
        }
        
        $this->items = $formatted_groups;
    }

    /**
     * Display no items message
     */
    public function no_items() {
        _e('No classes found.', 'school-manager-lite');
    }
}
