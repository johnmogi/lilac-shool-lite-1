<?php
/**
 * Handles teacher-student relationships
 */
class School_Manager_Lite_Teacher_Student_Relationships {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize hooks
        add_action('admin_init', array($this, 'setup_teacher_capabilities'));
        add_action('add_meta_boxes', array($this, 'add_teacher_meta_boxes'));
        add_action('save_post_teacher', array($this, 'save_teacher_students'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Hide WooCommerce guest checkout notice for teachers
        add_filter('learndash_woocommerce_admin_notices', array($this, 'filter_woocommerce_notices'), 20);
    }
    
    public function filter_woocommerce_notices($notices) {
        if (current_user_can('school_teacher')) {
            return array();
        }
        return $notices;
    }
    
    public function setup_teacher_capabilities() {
        $role = get_role('school_teacher');
        
        if ($role) {
            // Basic capabilities
            $caps = array(
                'read' => true,
                'edit_posts' => false,
                'upload_files' => true,
                'publish_posts' => false,
                'delete_posts' => false,
            );
            
            foreach ($caps as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
            
            // Custom capabilities
            $custom_caps = array(
                'view_school_dashboard' => true,
                'edit_students' => true,
                'view_students' => true,
                'edit_courses' => true,
                'view_courses' => true,
            );
            
            foreach ($custom_caps as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }
    }
    
    public function add_teacher_meta_boxes() {
        add_meta_box(
            'teacher_students',
            __('Assigned Students', 'school-manager-lite'),
            array($this, 'render_teacher_students_meta_box'),
            'teacher',
            'normal',
            'default'
        );
    }
    
    public function render_teacher_students_meta_box($post) {
        // Get all students
        $students = get_users(array(
            'role__in' => array('student_private', 'student_school'),
            'number' => -1,
            'orderby' => 'display_name',
        ));

        // Get currently assigned students
        $assigned_students = $this->get_teacher_students($post->ID);
        ?>
        <div class="teacher-students-assignment">
            <h4><?php _e('Assign Students', 'school-manager-lite'); ?></h4>
            <select name="assigned_students[]" multiple="multiple" class="teacher-students-select" style="width: 100%; min-height: 200px;">
                <?php foreach ($students as $student) : ?>
                    <option value="<?php echo esc_attr($student->ID); ?>" 
                        <?php selected(in_array($student->ID, $assigned_students)); ?>>
                        <?php echo esc_html($student->display_name); ?>
                        (<?php echo esc_html($student->user_email); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php _e('Hold Ctrl/Cmd to select multiple students', 'school-manager-lite'); ?>
            </p>
        </div>
        <?php
    }
    
    private function get_teacher_students($teacher_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'school_teacher_students';
        return $wpdb->get_col($wpdb->prepare(
            "SELECT student_id FROM $table WHERE teacher_id = %d",
            $teacher_id
        ));
    }
    
    public function save_teacher_students($teacher_id) {
        if (!current_user_can('edit_teacher', $teacher_id)) {
            return;
        }

        if (isset($_POST['assigned_students'])) {
            $assigned_students = array_map('intval', (array)$_POST['assigned_students']);
            $this->update_teacher_students($teacher_id, $assigned_students);
        }
    }
    
    private function update_teacher_students($teacher_id, $student_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'school_teacher_students';
        
        // Remove existing assignments
        $wpdb->delete($table, array('teacher_id' => $teacher_id), array('%d'));
        
        // Add new assignments
        foreach ($student_ids as $student_id) {
            $wpdb->insert($table, array(
                'teacher_id' => $teacher_id,
                'student_id' => $student_id,
                'date_assigned' => current_time('mysql')
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
            
            // Initialize Select2
            wp_add_inline_script('select2', '
                jQuery(document).ready(function($) {
                    $(".teacher-students-select").select2({
                        placeholder: "' . esc_js(__('Search for students...', 'school-manager-lite')) . '",
                        allowClear: true,
                        width: "100%"
                    });
                });
            ');
        }
    }
}

// Initialize the class
School_Manager_Lite_Teacher_Student_Relationships::instance();
