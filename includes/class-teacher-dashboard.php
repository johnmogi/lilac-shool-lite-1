<?php
/**
 * Teacher Dashboard
 */
class School_Manager_Lite_Teacher_Dashboard {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_teacher_menu'));
        add_action('admin_init', array($this, 'handle_redirects'));
        add_action('admin_notices', array($this, 'add_teacher_admin_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_teacher_menu() {
        add_menu_page(
            __('Teacher Dashboard', 'school-manager-lite'),
            __('Teacher Dashboard', 'school-manager-lite'),
            'school_teacher',
            'school-teacher-dashboard',
            array($this, 'render_teacher_dashboard'),
            'dashicons-welcome-learn-more',
            30
        );
    }
    
    public function handle_redirects() {
        // Redirect from old class-management page
        if (isset($_GET['page']) && $_GET['page'] === 'class-management' && 
            current_user_can('school_teacher')) {
            wp_redirect(admin_url('admin.php?page=school-teacher-dashboard'));
            exit;
        }
    }
    
    public function render_teacher_dashboard() {
        if (!current_user_can('school_teacher')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'school-manager-lite'));
        }

        // Increase memory limit for this operation
        wp_raise_memory_limit('admin');
        
        $teacher_id = get_current_user_id();
        
        // Get students with pagination to limit memory usage
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20; // Limit number of students per page
        
        $students = $this->get_teacher_students($teacher_id, $paged, $per_page);
        $total_students = $this->count_teacher_students($teacher_id);
        
        // Calculate pagination
        $total_pages = ceil($total_students / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Teacher Dashboard', 'school-manager-lite'); ?></h1>
            
            <div class="teacher-dashboard-content">
                <div class="teacher-stats">
                    <h2><?php _e('My Students', 'school-manager-lite'); ?></h2>
                    <?php if (!empty($students)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'school-manager-lite'); ?></th>
                                    <th><?php esc_html_e('Email', 'school-manager-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'school-manager-lite'); ?></th>
                                    <th><?php esc_html_e('Actions', 'school-manager-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student) : 
                                    $status = get_user_meta($student->ID, 'school_student_status', true);
                                    $status_label = $status === 'active' ? __('Active', 'school-manager-lite') : __('Inactive', 'school-manager-lite');
                                    $status_class = $status === 'active' ? 'status-active' : 'status-inactive';
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($student->display_name); ?></td>
                                        <td><?php echo esc_html($student->user_email); ?></td>
                                        <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $student->ID)); ?>" class="button button-small">
                                                <?php esc_html_e('View Profile', 'school-manager-lite'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php $this->display_pagination($paged, $total_pages); ?>
                        
                    <?php else : ?>
                        <div class="notice notice-info">
                            <p><?php esc_html_e('No students have been assigned to you yet.', 'school-manager-lite'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <style>
            .status-active { color: #46b450; font-weight: 600; }
            .status-inactive { color: #a00; }
            .teacher-dashboard-content { margin-top: 20px; }
            .teacher-stats { background: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        </style>
        <?php
    }
    
    public function get_teacher_students($teacher_id, $paged = 1, $per_page = 20) {
        global $wpdb;
        
        $offset = ($paged - 1) * $per_page;
        
        // Get students assigned to this teacher with pagination
        $query = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = 'assigned_teacher' AND meta_value = %d 
            ORDER BY umeta_id DESC 
            LIMIT %d, %d",
            $teacher_id,
            $offset,
            $per_page
        );
        
        $student_ids = $wpdb->get_col($query);
        
        if (empty($student_ids)) {
            return array();
        }
        
        // Get user objects for the student IDs
        $args = array(
            'include' => $student_ids,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => 'all_with_meta'
        );
        
        return get_users($args);
    }
    
    public function count_teacher_students($teacher_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
            WHERE meta_key = 'assigned_teacher' AND meta_value = %d",
            $teacher_id
        ));
        
        return (int) $count;
    }
    
    /**
     * Display pagination for the student list
     */
    private function display_pagination($current_page, $total_pages) {
        if ($total_pages <= 1) {
            return;
        }
        
        echo '<div class="tablenav">';
        echo '<div class="tablenav-pages">';
        
        // Previous page link
        if ($current_page > 1) {
            echo sprintf(
                '<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">‹‹</span></a>',
                esc_url(add_query_arg('paged', $current_page - 1)),
                esc_attr__('Previous page', 'school-manager-lite')
            );
        }
        
        // Page numbers
        echo '<span class="paging-input">';
        printf(
            _x('%1$s of %2$s', 'paging'),
            $current_page,
            sprintf(
                '<span class="total-pages">%s</span>',
                number_format_i18n($total_pages)
            )
        );
        echo '</span>';
        
        // Next page link
        if ($current_page < $total_pages) {
            echo sprintf(
                '<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">››</span></a>',
                esc_url(add_query_arg('paged', $current_page + 1)),
                esc_attr__('Next page', 'school-manager-lite')
            );
        }
        
        echo '</div>'; // .tablenav-pages
        echo '</div>'; // .tablenav
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'school-teacher-dashboard') === false) {
            return;
        }
        
        // Add admin styles
        if (file_exists(SCHOOL_MANAGER_LITE_PATH . 'assets/css/admin.css')) {
            wp_enqueue_style(
                'school-manager-admin',
                SCHOOL_MANAGER_LITE_URL . 'assets/css/admin.css',
                array(),
                SCHOOL_MANAGER_LITE_VERSION
            );
        }
        
        // Add custom styles for the teacher dashboard
        wp_add_inline_style('school-manager-admin', '
            .teacher-dashboard-content {
                margin-top: 20px;
            }
            .teacher-stats {
                background: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin-bottom: 20px;
            }
            .teacher-stats h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .status-active {
                color: #46b450;
                font-weight: 600;
            }
            .status-inactive {
                color: #a00;
                font-weight: 600;
            }
            .tablenav {
                margin: 10px 0;
                height: auto;
            }
            .tablenav .tablenav-pages {
                float: none;
                margin: 10px 0;
            }
            .tablenav .tablenav-pages .paging-input {
                float: none;
                margin: 0 10px;
                line-height: 28px;
            }
            .tablenav .tablenav-pages .button {
                margin: 0 5px;
            }
            .notice {
                margin: 10px 0;
                padding: 10px 15px;
            }
            .widefat th, .widefat td {
                vertical-align: middle;
            }
            .widefat .column-status {
                width: 100px;
            }
            .widefat .column-actions {
                width: 120px;
            }
        ');
    }
    
    public function add_teacher_admin_notice() {
        if (current_user_can('school_teacher') && 
            isset($_GET['page']) && 
            $_GET['page'] === 'school-teacher-dashboard') {
            
            echo '<div class="notice notice-info">';
            echo '<p>' . esc_html__('Welcome to your teacher dashboard. Here you can view and manage your assigned students and classes.', 'school-manager-lite') . '</p>';
            echo '</div>';
        }
    }
}

// Initialize the class
School_Manager_Lite_Teacher_Dashboard::instance();
