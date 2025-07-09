<?php
/**
 * Students List Table Class
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
 * School_Manager_Lite_Students_List_Table class
 * 
 * Extends WordPress WP_List_Table class to provide a custom table for students
 */
class School_Manager_Lite_Students_List_Table extends WP_List_Table {

    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'student',
            'plural'   => 'students',
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'name'          => __('Student Name', 'school-manager-lite'),
            'email'         => __('Email', 'school-manager-lite'),
            'classes'       => __('Classes', 'school-manager-lite'),
            'course_id'    => __('Course', 'school-manager-lite'),
            'teacher'       => __('Teacher', 'school-manager-lite'),
            'promo_code'    => __('Promo Code', 'school-manager-lite'),
            'status'        => __('Status', 'school-manager-lite'),
            'registered'    => __('Registered', 'school-manager-lite'),
            'actions'       => __('Actions', 'school-manager-lite')
        );

        return $columns;
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'name'       => array('display_name', false),
            'email'      => array('user_email', false),
            'registered' => array('user_registered', true), // true means it's already sorted
            'status'     => array('status', false),
            'course_id'  => array('course_id', false)
        );
        return $sortable_columns;
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email':
                return esc_html($item->user_email);
            case 'course_id':
                // Get first class course id
                $student_manager = School_Manager_Lite_Student_Manager::instance();
                $classes = $student_manager->get_student_classes($item->ID);
                if ($classes) {
                    $class = $classes[0];
                    if (isset($class->course_id) && $class->course_id) {
                        return esc_html($class->course_id);
                    }
                }
                return '—';
            case 'registered':
                return date_i18n(get_option('date_format'), strtotime($item->user_registered));
            default:
                return isset($item->$column_name) ? $item->$column_name : '';
        }
    }

    /**
     * Column cb
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="student_id[]" value="%s" />',
            $item->ID
        );
    }

    /**
     * Column name
     */
    public function column_name($item) {
        // Get student manager instance
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_classes = $student_manager->get_student_classes($item->ID);
        $current_class_id = !empty($student_classes) ? $student_classes[0]->id : 0;
        
        // Prepare data for Quick Edit
        $student_data = array(
            'id' => $item->ID,
            'name' => $item->display_name,
            'class_id' => $current_class_id
        );
        
        // Build actions
        $actions = array(
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=school-manager-students&action=edit&id=' . $item->ID),
                __('Edit', 'school-manager-lite')
            ),
            'quickedit' => sprintf(
                '<a href="#" class="editinline" aria-label="%s" data-student="%s">%s</a>',
                esc_attr(sprintf(__('Quick edit &#8220;%s&#8221; inline', 'school-manager-lite'), $item->display_name)),
                esc_attr(json_encode($student_data)),
                __('Quick Edit', 'school-manager-lite')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=school-manager-students&action=delete&id=' . $item->ID), 'delete_student_' . $item->ID),
                __('Are you sure you want to delete this student?', 'school-manager-lite'),
                __('Delete', 'school-manager-lite')
            ),
        );

        return sprintf(
            '<div class="student-row" id="student-%d"><a href="%s"><strong>%s</strong></a> %s</div>',
            $item->ID,
            admin_url('user-edit.php?user_id=' . $item->ID),
            esc_html($item->display_name),
            $this->row_actions($actions)
        );
    }

    /**
     * Column classes
     */
    public function column_classes($item) {
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_classes = $student_manager->get_student_classes($item->ID);
        
        if (empty($student_classes)) {
            return '—';
        }
        
        $class_names = array();
        foreach ($student_classes as $class) {
            $class_names[] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=school-manager-classes&action=edit&id=' . $class->id),
                esc_html($class->name)
            );
        }
        
        return implode(', ', $class_names);
    }

    /**
     * Column promo_code
     */
    public function column_promo_code($item) {
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $promo_code = $promo_code_manager->get_user_promo_code($item->ID);
        
        if (empty($promo_code)) {
            return '—';
        }
        
        return sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=school-manager-promo-codes&action=edit&id=' . $promo_code->id),
            esc_html($promo_code->code)
        );
    }
    
    /**
     * Column teacher
     */
    public function column_teacher($item) {
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teacher = $teacher_manager->get_student_teacher($item->ID);
        
        if (empty($teacher)) {
            return '—';
        }
        
        return sprintf(
            '<a href="%s">%s</a>',
            admin_url('user-edit.php?user_id=' . $teacher->ID),
            esc_html($teacher->display_name)
        );
    }
    
    /**
     * Column actions
     */
    public function column_actions($item) {
        return sprintf(
            '<a href="%s" class="button button-small">%s</a> <a href="%s" class="button button-small">%s</a>',
            admin_url('admin.php?page=school-manager-students&action=edit&id=' . $item->ID),
            __('Edit', 'school-manager-lite'),
            admin_url('user-edit.php?user_id=' . $item->ID),
            __('Profile', 'school-manager-lite')
        );
    }

    /**
     * Column status
     */
    public function column_status($item) {
        $is_active = get_user_meta($item->ID, 'school_student_status', true);
        $status = !empty($is_active) && $is_active === 'active' ? 'active' : 'inactive';
        
        $status_labels = array(
            'active'   => '<span class="status-active">' . __('Active', 'school-manager-lite') . '</span>',
            'inactive' => '<span class="status-inactive">' . __('Inactive', 'school-manager-lite') . '</span>',
        );
        
        return isset($status_labels[$status]) ? $status_labels[$status] : $status_labels['inactive'];
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'assign_class' => __('Assign to Class', 'school-manager-lite'),
            'assign_teacher' => __('Assign Teacher', 'school-manager-lite'),
            'activate' => __('Mark as Active', 'school-manager-lite'),
            'deactivate' => __('Mark as Inactive', 'school-manager-lite'),
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
        $student_ids = isset($_POST['student_id']) ? array_map('intval', (array) $_POST['student_id']) : array();
        
        if (empty($student_ids)) {
            return;
        }

        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();

        switch ($action) {
            case 'assign_class':
                if (empty($_POST['bulk_class_id'])) {
                    add_settings_error(
                        'school_manager_messages',
                        'error',
                        __('Please select a class to assign.', 'school-manager-lite'),
                        'error'
                    );
                    return;
                }
                
                $class_id = intval($_POST['bulk_class_id']);
                
                foreach ($student_ids as $student_id) {
                    $student_manager->add_student_to_class($student_id, $class_id);
                }
                
                add_settings_error(
                    'school_manager_messages',
                    'success',
                    sprintf(_n('%d student assigned to class.', '%d students assigned to class.', count($student_ids), 'school-manager-lite'), count($student_ids)),
                    'updated'
                );
                break;
                
            case 'assign_teacher':
                if (empty($_POST['bulk_teacher_id'])) {
                    add_settings_error(
                        'school_manager_messages',
                        'error',
                        __('Please select a teacher to assign.', 'school-manager-lite'),
                        'error'
                    );
                    return;
                }
                
                $teacher_id = intval($_POST['bulk_teacher_id']);
                
                foreach ($student_ids as $student_id) {
                    $teacher_manager->assign_student_to_teacher($student_id, $teacher_id);
                }
                
                add_settings_error(
                    'school_manager_messages',
                    'success',
                    sprintf(_n('%d student assigned to teacher.', '%d students assigned to teacher.', count($student_ids), 'school-manager-lite'), count($student_ids)),
                    'updated'
                );
                break;
                
            case 'activate':
                foreach ($student_ids as $student_id) {
                    update_user_meta($student_id, 'school_student_status', 'active');

                    // Enroll student to LearnDash course(s) associated with their classes
                    if (function_exists('ld_update_course_access')) {
                        $student_classes = $student_manager->get_student_classes($student_id);
                        foreach ($student_classes as $class) {
                            $ld_course_id = 898; // default course ID fallback
                            if (isset($class->course_id) && $class->course_id) {
                                $ld_course_id = $class->course_id;
                            }
                            ld_update_course_access($student_id, $ld_course_id, $remove=false);
                        }
                    }
                }
                
                add_settings_error(
                    'school_manager_messages',
                    'success',
                    sprintf(_n('%d student marked as active.', '%d students marked as active.', count($student_ids), 'school-manager-lite'), count($student_ids)),
                    'updated'
                );
                break;
                
            case 'deactivate':
                foreach ($student_ids as $student_id) {
                    update_user_meta($student_id, 'school_student_status', 'inactive');

                    // Remove student access to LearnDash course(s)
                    if (function_exists('ld_update_course_access')) {
                        $student_classes = $student_manager->get_student_classes($student_id);
                        foreach ($student_classes as $class) {
                            $ld_course_id = 898;
                            if (isset($class->course_id) && $class->course_id) {
                                $ld_course_id = $class->course_id;
                            }
                            ld_update_course_access($student_id, $ld_course_id, $remove=true);
                        }
                    }
                }
                
                add_settings_error(
                    'school_manager_messages',
                    'success',
                    sprintf(_n('%d student marked as inactive.', '%d students marked as inactive.', count($student_ids), 'school-manager-lite'), count($student_ids)),
                    'updated'
                );
                break;
                
            case 'delete':
                if (isset($_POST['student_id']) && is_array($_POST['student_id'])) {
                    $student_manager = School_Manager_Lite_Student_Manager::instance();
                    $student_ids = array_map('intval', $_POST['student_id']);
                    foreach ($student_ids as $student_id) {
                        $student_manager->delete_student($student_id);
                    }
                    
                    // Add admin notice
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                            __('Students deleted successfully.', 'school-manager-lite') . '</p></div>';
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
        
        // Process bulk actions
        $this->process_bulk_action();
        
        // Set pagination arguments
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Define sortable columns
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'display_name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'asc';
        
        // Filter by class if specified
        $filter_class_id = isset($_REQUEST['filter_class_id']) ? intval($_REQUEST['filter_class_id']) : 0;
        
        // Filter by teacher if specified
        $filter_teacher_id = isset($_REQUEST['filter_teacher_id']) ? intval($_REQUEST['filter_teacher_id']) : 0;
        
        // Get student users
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_users = $student_manager->get_student_users(array(
            'search' => isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '',
            'orderby' => $orderby,
            'order' => $order
        ));
        
        $items = $student_users;
        
        // Filter by class
        if ($filter_class_id > 0) {
            $filtered_items = array();
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            
            foreach ($items as $item) {
                $student_classes = $student_manager->get_student_classes($item->ID);
                
                foreach ($student_classes as $class) {
                    if ($class->id == $filter_class_id) {
                        $filtered_items[] = $item;
                        break;
                    }
                }
            }
            
            $items = $filtered_items;
        }
        
        // Filter by teacher
        if ($filter_teacher_id > 0) {
            $filtered_items = array();
            
            foreach ($items as $item) {
                $student_classes = $student_manager->get_student_classes($item->ID);
                
                foreach ($student_classes as $class) {
                    if ($class->teacher_id == $filter_teacher_id) {
                        $filtered_items[] = $item;
                        break;
                    }
                }
            }
            
            $items = $filtered_items;
        }
        
        // Pagination
        $total_items = count($items);
        $this->items = array_slice($items, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Display no items message
     */
    public function no_items() {
        _e('No students found.', 'school-manager-lite');
    }
    
    /**
     * Display rows or placeholder
     */
    public function display_rows_or_placeholder() {
        parent::display_rows_or_placeholder();
        
        // Get classes for quick edit form
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Get teachers for quick edit form
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Get promo codes for quick edit form
        $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
        $promo_codes = $promo_code_manager->get_promo_codes(array('active' => true));
        
        // Output the quick edit form template
        ?>
        <tr id="student-quick-edit" class="inline-edit-row inline-edit-row-student quick-edit-row quick-edit-row-student" style="display: none">
            <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                <fieldset class="inline-edit-col">
                    <legend class="inline-edit-legend"><?php _e('Quick Edit', 'school-manager-lite'); ?></legend>
                    <div class="inline-edit-col">
                        <input type="hidden" name="student_id" value="" id="quick-edit-student-id">
                        <?php wp_nonce_field('school_manager_quick_edit_student', 'school_manager_quick_edit_nonce'); ?>
                        
                        <div class="inline-edit-group wp-clearfix">
                            <label>
                                <span class="title"><?php _e('Student', 'school-manager-lite'); ?></span>
                                <span class="input-text-wrap"><strong id="quick-edit-student-name"></strong></span>
                            </label>
                        </div>
                        
                        <div class="inline-edit-group wp-clearfix">
                            <label>
                                <span class="title"><?php _e('Class', 'school-manager-lite'); ?></span>
                                <select name="class_id" id="quick-edit-class-id">
                                    <option value=""><?php _e('— No Class —', 'school-manager-lite'); ?></option>
                                    <?php foreach ($classes as $class) : ?>
                                        <option value="<?php echo esc_attr($class->id); ?>"><?php echo esc_html($class->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        
                        <div class="inline-edit-group wp-clearfix">
                            <label>
                                <span class="title"><?php _e('Status', 'school-manager-lite'); ?></span>
                                <select name="status" id="quick-edit-status">
                                    <option value="active"><?php _e('Active', 'school-manager-lite'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'school-manager-lite'); ?></option>
                                </select>
                            </label>
                        </div>
                    </div>
                </fieldset>
                <div class="inline-edit-save submit">
                    <button type="button" class="cancel button alignleft"><?php _e('Cancel', 'school-manager-lite'); ?></button>
                    <button type="button" class="save button button-primary alignright"><?php _e('Update', 'school-manager-lite'); ?></button>
                    <span class="spinner"></span>
                    <br class="clear" />
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Extra tablenav controls
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        // Get available classes for bulk assignment and filtering
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Get available teachers for bulk assignment and filtering
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Get current filter values
        $filter_class_id = isset($_REQUEST['filter_class_id']) ? intval($_REQUEST['filter_class_id']) : 0;
        $filter_teacher_id = isset($_REQUEST['filter_teacher_id']) ? intval($_REQUEST['filter_teacher_id']) : 0;
        
        // Output the bulk action controls
        ?>
        <div class="alignleft actions">
            <!-- Bulk Assignment Options -->
            <?php if (!empty($classes)) : ?>
                <label class="screen-reader-text" for="bulk_class_id"><?php _e('Assign to class', 'school-manager-lite'); ?></label>
                <select name="bulk_class_id" id="bulk_class_id">
                    <option value=""><?php _e('Assign to class', 'school-manager-lite'); ?></option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo esc_attr($class->id); ?>"><?php echo esc_html($class->name); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            
            <?php if (!empty($teachers)) : ?>
                <label class="screen-reader-text" for="bulk_teacher_id"><?php _e('Assign teacher', 'school-manager-lite'); ?></label>
                <select name="bulk_teacher_id" id="bulk_teacher_id">
                    <option value=""><?php _e('Assign teacher', 'school-manager-lite'); ?></option>
                    <?php foreach ($teachers as $teacher) : ?>
                        <option value="<?php echo esc_attr($teacher->ID); ?>"><?php echo esc_html($teacher->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            
            <?php submit_button(__('Apply to Selected', 'school-manager-lite'), 'action', 'bulk_action', false, array('id' => 'doaction')); ?>
        </div>
        
        <!-- Filtering Options -->
        <div class="alignleft actions">
            <?php if (!empty($classes)) : ?>
                <label class="screen-reader-text" for="filter_class_id"><?php _e('Filter by class', 'school-manager-lite'); ?></label>
                <select name="filter_class_id" id="filter_class_id">
                    <option value="0"><?php _e('All Classes', 'school-manager-lite'); ?></option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo esc_attr($class->id); ?>" <?php selected($filter_class_id, $class->id); ?>>
                            <?php echo esc_html($class->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            
            <?php if (!empty($teachers)) : ?>
                <label class="screen-reader-text" for="filter_teacher_id"><?php _e('Filter by teacher', 'school-manager-lite'); ?></label>
                <select name="filter_teacher_id" id="filter_teacher_id">
                    <option value="0"><?php _e('All Teachers', 'school-manager-lite'); ?></option>
                    <?php foreach ($teachers as $teacher) : ?>
                        <option value="<?php echo esc_attr($teacher->ID); ?>" <?php selected($filter_teacher_id, $teacher->ID); ?>>
                            <?php echo esc_html($teacher->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            
            <?php submit_button(__('Filter', 'school-manager-lite'), '', 'filter_action', false); ?>
            <?php if (!empty($classes) || !empty($teachers)) : ?>
                <input type="button" id="doaction-bulk-assign" class="button action" value="<?php esc_attr_e('Apply', 'school-manager-lite'); ?>">
            <?php endif; ?>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle bulk assign class/teacher action button
            $('#doaction-bulk-assign').on('click', function() {
                var selected = [];
                var action = '';
                var redirect = '';
                var nonce = '<?php echo wp_create_nonce('bulk-' . $this->_args['plural']); ?>';
                
                // Get all selected checkboxes
                $('input[name="student_id[]"]:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert('<?php esc_html_e('Please select at least one student.', 'school-manager-lite'); ?>');
                    return;
                }
                
                // Check which dropdown has a value
                var class_id = $('#bulk_class_id').val();
                var teacher_id = $('#bulk_teacher_id').val();
                
                if (class_id) {
                    action = 'assign_class';
                    redirect = '<?php echo admin_url('admin.php?page=school-manager-students'); ?>&action=' + action + '&class_id=' + class_id;
                } else if (teacher_id) {
                    action = 'assign_teacher';
                    redirect = '<?php echo admin_url('admin.php?page=school-manager-students'); ?>&action=' + action + '&teacher_id=' + teacher_id;
                } else {
                    alert('<?php esc_html_e('Please select a class or teacher to assign.', 'school-manager-lite'); ?>');
                    return;
                }
                
                // Create a form and submit it
                var $form = $('<form>', {
                    'method': 'post',
                    'action': redirect
                });
                
                // Add nonce
                $form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_wpnonce',
                    'value': nonce
                }));
                
                // Add selected student IDs
                $.each(selected, function(i, student_id) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'student_id[]',
                        'value': student_id
                    }));
                });
                
                // Submit form
                $form.appendTo('body').submit();
            });
        });
        </script>
        <?php
    }
}
