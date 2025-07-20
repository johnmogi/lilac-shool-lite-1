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
        
        $class_links = array();
        foreach ($classes as $class) {
            $class_links[] = sprintf(
                '<a href="%s" title="%s">%s</a>',
                admin_url('admin.php?page=school-manager-classes&action=edit&id=' . $class->id),
                esc_attr(sprintf(__('Edit class: %s', 'school-manager-lite'), $class->name)),
                esc_html($class->name)
            );
        }
        
        return implode(', ', $class_links);
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
            'assign_to_class' => __('Assign to Class', 'school-manager-lite'),
            'delete' => __('Delete', 'school-manager-lite')
        );
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        $action = $this->current_action();
        
        // Debug: Show what's being submitted
        if (!empty($_POST)) {
            error_log('BULK ACTION DEBUG: POST data = ' . print_r($_POST, true));
            error_log('BULK ACTION DEBUG: Current action = ' . $action);
            
            // Also show visible debug on admin page
            add_action('admin_notices', function() use ($action) {
                echo '<div class="notice notice-info"><p>DEBUG: process_bulk_action called. Action: ' . esc_html($action) . '</p></div>';
            });
        }
        
        if (!$action) {
            return;
        }
        
        if ('delete' === $action && isset($_POST['teacher_ids'])) {
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
        
        if ('assign_to_class' === $action && isset($_POST['assign_class_id'])) {
            error_log('BULK ACTION DEBUG: Processing assign_to_class action');
            // Get teacher IDs from either teacher_ids array or teacher_ids_hidden string
            $teacher_ids = array();
            if (isset($_POST['teacher_ids']) && is_array($_POST['teacher_ids'])) {
                $teacher_ids = array_map('absint', $_POST['teacher_ids']);
            } elseif (isset($_POST['teacher_ids_hidden']) && !empty($_POST['teacher_ids_hidden'])) {
                $teacher_ids = array_map('absint', explode(',', $_POST['teacher_ids_hidden']));
            }
            
            $class_id = intval($_POST['assign_class_id']);
            
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk-teachers')) {
                wp_die(__('Security check failed.', 'school-manager-lite'));
            }
            
            if (!empty($teacher_ids) && $class_id > 0) {
                $class_manager = School_Manager_Lite_Class_Manager::instance();
                $assigned_count = 0;
                
                foreach ($teacher_ids as $teacher_id) {
                    // Assign teacher to class
                    if ($class_manager->assign_teacher_to_class($teacher_id, $class_id)) {
                        $assigned_count++;
                    }
                }
                
                // Add admin notice
                add_action('admin_notices', function() use ($assigned_count) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         sprintf(_n('%d teacher assigned to class.', '%d teachers assigned to class.', $assigned_count, 'school-manager-lite'), $assigned_count) . 
                         '</p></div>';
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
        
        // Handle class filter
        $class_filter = isset($_REQUEST['class_filter']) ? intval($_REQUEST['class_filter']) : 0;
        
        // Set up query args (no role restriction - let teacher manager handle it)
        $args = array(
            'orderby' => $orderby,
            'order' => $order,
            'number' => $per_page,
            'offset' => $offset,
            'search' => !empty($search) ? '*' . $search . '*' : '',
        );
        
        // Apply class filter if selected
        if ($class_filter > 0) {
            $args['class_filter'] = $class_filter;
        }
        
        // Get all teachers to count total items
        $all_teachers = $teacher_manager->get_teachers(array('number' => -1));
        $total_items = count($all_teachers);
        
        $this->items = $teacher_manager->get_teachers($args);
        
        // Set up pagination args
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    /**
     * Display filter dropdowns above the table
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $classes = $class_manager->get_classes();
            $selected_class = isset($_REQUEST['class_filter']) ? intval($_REQUEST['class_filter']) : 0;
            
            echo '<div class="alignleft actions">';
            echo '<select name="class_filter" id="class_filter">';
            echo '<option value="0">' . __('All Classes', 'school-manager-lite') . '</option>';
            
            foreach ($classes as $class) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $class->id,
                    selected($selected_class, $class->id, false),
                    esc_html($class->name)
                );
            }
            
            echo '</select>';
            submit_button(__('Filter', 'school-manager-lite'), 'button', 'filter_action', false, array('id' => 'post-query-submit'));
            echo '</div>';
            
            // Add modal for class assignment
            $this->render_assign_class_modal($classes);
        }
    }
    
    /**
     * Render the assign class modal
     */
    private function render_assign_class_modal($classes) {
        ?>
        <div id="assign-class-modal" style="display: none;">
            <div class="assign-class-modal-content">
                <h3><?php _e('Assign Teachers to Class', 'school-manager-lite'); ?></h3>
                <form id="assign-class-form" method="post" action="<?php echo admin_url('admin.php?page=school-manager-teachers'); ?>">
                    <p>
                        <label for="assign_class_id"><?php _e('Select Class:', 'school-manager-lite'); ?></label>
                        <select name="assign_class_id" id="assign_class_id">
                            <option value=""><?php _e('Choose a class...', 'school-manager-lite'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo esc_attr($class->id); ?>">
                                    <?php echo esc_html($class->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Assign', 'school-manager-lite'); ?></button>
                        <button type="button" class="button" onclick="tb_remove();"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    </p>
                    <input type="hidden" name="action" value="assign_to_class">
                    <input type="hidden" name="teacher_ids_hidden" id="teacher_ids_hidden">
                    <?php wp_nonce_field('bulk-teachers'); ?>
                </form>
            </div>
        </div>
        
        <style>
        .assign-class-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            max-width: 400px;
            margin: 0 auto;
        }
        .assign-class-modal-content h3 {
            margin-top: 0;
        }
        .assign-class-modal-content select {
            width: 100%;
            margin-top: 5px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle bulk action form submission
            $('#posts-filter').on('submit', function(e) {
                var action1 = $('select[name="action"]').val();
                var action2 = $('select[name="action2"]').val();
                
                if (action1 === 'assign_to_class' || action2 === 'assign_to_class') {
                    e.preventDefault();
                    
                    // Get selected teacher IDs
                    var selectedTeachers = [];
                    $('input[name="teacher_ids[]"]:checked').each(function() {
                        selectedTeachers.push($(this).val());
                    });
                    
                    if (selectedTeachers.length === 0) {
                        alert('<?php echo esc_js(__('Please select at least one teacher.', 'school-manager-lite')); ?>');
                        return;
                    }
                    
                    // Set hidden field with teacher IDs
                    $('#teacher_ids_hidden').val(selectedTeachers.join(','));
                    
                    // Show modal using WordPress thickbox
                    tb_show('<?php echo esc_js(__('Assign Teachers to Class', 'school-manager-lite')); ?>', '#TB_inline?inlineId=assign-class-modal&width=450&height=300');
                }
            });
            
            // Handle modal form submission
            $('#assign-class-form').on('submit', function(e) {
                e.preventDefault();
                
                var classId = $('#assign_class_id').val();
                if (!classId) {
                    alert('<?php echo esc_js(__('Please select a class.', 'school-manager-lite')); ?>');
                    return;
                }
                
                // Get teacher IDs from hidden field and add them to the form
                var teacherIds = $('#teacher_ids_hidden').val().split(',');
                var form = $(this);
                
                // Remove any existing teacher_ids[] inputs
                form.find('input[name="teacher_ids[]"]').remove();
                
                // Add teacher IDs as hidden inputs
                teacherIds.forEach(function(id) {
                    if (id.trim()) {
                        form.append('<input type="hidden" name="teacher_ids[]" value="' + id.trim() + '">');
                    }
                });
                
                // Submit the form normally
                form.off('submit').submit();
            });
        });
        </script>
        <?php
    }
    
    /**
     * No items found text
     */
    public function no_items() {
        _e('No teachers found.', 'school-manager-lite');
    }
}
