<?php
/**
 * Student Profile Customization
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Student_Profile {
    
    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * Get class instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Add student profile fields
        add_action('show_user_profile', array($this, 'add_student_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_student_profile_fields'));
        
        // Save student profile fields
        add_action('personal_options_update', array($this, 'save_student_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_student_profile_fields'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('user-edit.php' !== $hook && 'profile.php' !== $hook) {
            return;
        }
        
        // Add select2 for better dropdowns
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
        
        // Add custom admin CSS
        wp_enqueue_style('school-manager-admin', SCHOOL_MANAGER_LITE_PLUGIN_URL . 'assets/css/admin.css', array(), SCHOOL_MANAGER_LITE_VERSION);
        
        // Add custom admin JS
        wp_enqueue_script('school-manager-admin', SCHOOL_MANAGER_LITE_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'select2'), SCHOOL_MANAGER_LITE_VERSION . '.' . time(), true);
    }
    
    /**
     * Add student profile fields
     */
    public function add_student_profile_fields($user) {
        // Only show for students
        if (!in_array('student_private', (array) $user->roles)) {
            return;
        }
        
        // Get all classes
        $class_manager = School_Manager_Lite_Class_Manager::instance();
        $classes = $class_manager->get_classes();
        
        // Get all teachers
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teachers = $teacher_manager->get_teachers();
        
        // Get current student classes
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        $student_classes = $student_manager->get_student_classes($user->ID);
        $current_class_ids = wp_list_pluck($student_classes, 'id');
        
        // Get current teacher ID if any
        $current_teacher_id = get_user_meta($user->ID, '_school_student_teacher', true);
        
        ?>
        <h2><?php _e('School Information', 'school-manager-lite'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th><label for="school_classes"><?php _e('Classes', 'school-manager-lite'); ?></label></th>
                <td>
                    <select name="school_classes[]" id="school_classes" class="regular-text" multiple="multiple" style="width: 25em;">
                        <?php foreach ($classes as $class) : ?>
                            <option value="<?php echo esc_attr($class->id); ?>" <?php selected(in_array($class->id, $current_class_ids)); ?>>
                                <?php echo esc_html($class->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select the classes this student is enrolled in.', 'school-manager-lite'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="school_teacher"><?php _e('Primary Teacher', 'school-manager-lite'); ?></label></th>
                <td>
                    <select name="school_teacher" id="school_teacher" class="regular-text" style="width: 25em;">
                        <option value=""><?php _e('-- Select Teacher --', 'school-manager-lite'); ?></option>
                        <?php foreach ($teachers as $teacher) : 
                            $teacher_user = get_userdata($teacher->wp_user_id);
                            if (!$teacher_user) continue;
                            ?>
                            <option value="<?php echo esc_attr($teacher->id); ?>" <?php selected($current_teacher_id, $teacher->id); ?>>
                                <?php echo esc_html($teacher_user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select the primary teacher for this student.', 'school-manager-lite'); ?></p>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#school_classes').select2({
                placeholder: '<?php echo esc_js(__('Select classes', 'school-manager-lite')); ?>',
                allowClear: true
            });
            
            $('#school_teacher').select2({
                placeholder: '<?php echo esc_js(__('Select a teacher', 'school-manager-lite')); ?>',
                allowClear: true
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save student profile fields
     */
    public function save_student_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        // Only process for students
        $user = get_userdata($user_id);
        if (!in_array('student_private', (array) $user->roles)) {
            return;
        }
        
        // Get the student manager
        $student_manager = School_Manager_Lite_Student_Manager::instance();
        
        // Save classes
        if (isset($_POST['school_classes']) && is_array($_POST['school_classes'])) {
            $class_ids = array_map('intval', $_POST['school_classes']);
            $class_ids = array_filter($class_ids);
            
            // Get current classes
            $current_classes = $student_manager->get_student_classes($user_id);
            $current_class_ids = wp_list_pluck($current_classes, 'id');
            
            // Classes to add
            $classes_to_add = array_diff($class_ids, $current_class_ids);
            
            // Classes to remove
            $classes_to_remove = array_diff($current_class_ids, $class_ids);
            
            // Add new class assignments
            foreach ($classes_to_add as $class_id) {
                $student_data = array(
                    'wp_user_id' => $user_id,
                    'class_id' => $class_id,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'create_user' => false
                );
                
                $student_manager->create_student($student_data);
            }
            
            // Remove old class assignments
            foreach ($classes_to_remove as $class_id) {
                $student = $student_manager->get_student_by_user_id($user_id, $class_id);
                if ($student) {
                    $student_manager->delete_student($student->id);
                }
            }
        } elseif (isset($_POST['school_classes']) && empty($_POST['school_classes'])) {
            // If no classes are selected, remove all class assignments
            $current_classes = $student_manager->get_student_classes($user_id);
            foreach ($current_classes as $class) {
                $student = $student_manager->get_student_by_user_id($user_id, $class->id);
                if ($student) {
                    $student_manager->delete_student($student->id);
                }
            }
        }
        
        // Save primary teacher
        if (isset($_POST['school_teacher'])) {
            $teacher_id = intval($_POST['school_teacher']);
            update_user_meta($user_id, '_school_student_teacher', $teacher_id);
            
            // If the student is in any classes, update the teacher for those classes
            if (!empty($class_ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'school_students';
                
                foreach ($class_ids as $class_id) {
                    $wpdb->update(
                        $table_name,
                        array('teacher_id' => $teacher_id),
                        array('wp_user_id' => $user_id, 'class_id' => $class_id),
                        array('%d'),
                        array('%d', '%d')
                    );
                }
            }
        }
    }
}

// Initialize the student profile customization
function school_manager_lite_student_profile() {
    return School_Manager_Lite_Student_Profile::instance();
}

// Initialize on admin init
add_action('admin_init', 'school_manager_lite_student_profile');
