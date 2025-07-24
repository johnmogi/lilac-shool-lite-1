<?php
/**
 * Instructor Dashboard Widget
 * 
 * Creates an embeddable instructor dashboard widget for WordPress integration
 *
 * @package School_Manager_Lite
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Lite_Instructor_Dashboard_Widget {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add shortcode for embedding
        add_shortcode('instructor_dashboard', array($this, 'render_dashboard_shortcode'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_get_instructor_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_nopriv_get_instructor_dashboard_data', array($this, 'ajax_get_dashboard_data'));
    }
    
    /**
     * Add admin menu
     * Removed broken menu item - functionality may be available elsewhere
     */
    public function add_admin_menu() {
        // Menu item removed - functionality may be available in other admin pages
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Instructor Dashboard Widget</h1>
            
            <div class="card" style="max-width: none; padding: 20px;">
                <h2>📱 Embedding Options</h2>
                
                <h3>1. WordPress Shortcode (Recommended)</h3>
                <p>Use this shortcode in any WordPress post, page, or widget:</p>
                <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">
                    [instructor_dashboard]
                </code>
                
                <h3>2. PHP Template Integration</h3>
                <p>Add this to your theme templates:</p>
                <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">
                    &lt;?php echo do_shortcode('[instructor_dashboard]'); ?&gt;
                </code>
                
                <h3>3. Direct Widget Integration</h3>
                <p>For advanced integration, use this PHP code:</p>
                <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">
                    &lt;?php<br>
                    $widget = School_Manager_Lite_Instructor_Dashboard_Widget::instance();<br>
                    echo $widget->render_dashboard();<br>
                    ?&gt;
                </code>
                
                <h3>4. Iframe Integration (External Sites)</h3>
                <p>For embedding in external websites:</p>
                <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">
                    &lt;iframe src="<?php echo plugins_url('dashboard-iframe.php', dirname(__FILE__)); ?>" 
                            width="100%" height="600" frameborder="0"&gt;&lt;/iframe&gt;
                </code>
            </div>
            
            <div class="card" style="max-width: none; padding: 20px;">
                <h2>📊 Live Preview</h2>
                <p>This is how the dashboard will look when embedded:</p>
                <div style="border: 2px solid #ddd; padding: 20px; margin: 20px 0;">
                    <?php echo $this->render_dashboard(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'instructor-dashboard-widget',
            plugins_url('assets/css/instructor-dashboard.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'instructor-dashboard-widget',
            plugins_url('assets/js/instructor-dashboard.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('instructor-dashboard-widget', 'instructorDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('instructor_dashboard_nonce')
        ));
    }
    
    /**
     * Render dashboard shortcode
     */
    public function render_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'style' => 'full'
        ), $atts);
        
        return $this->render_dashboard($atts['user_id'], $atts['style']);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard($user_id = null, $style = 'full') {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        
        // Check if user is instructor
        if (!$user || !in_array('wdm_instructor', $user->roles)) {
            return $this->render_no_access();
        }
        
        // Get instructor data
        $data = $this->get_instructor_data($user_id);
        
        ob_start();
        ?>
        <div class="instructor-dashboard-widget" data-user-id="<?php echo $user_id; ?>">
            
            <div class="dashboard-header">
                <h2>👨‍🏫 Instructor Dashboard</h2>
                <p>Welcome, <?php echo esc_html($user->display_name); ?>!</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data['classes_count']; ?></div>
                    <div class="stat-label">My Classes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data['students_count']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data['quizzes_count']; ?></div>
                    <div class="stat-label">My Quizzes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data['groups_count']; ?></div>
                    <div class="stat-label">Active Groups</div>
                </div>
            </div>
            
            <?php if ($style === 'full'): ?>
            
            <div class="dashboard-section">
                <h3>📚 My Classes</h3>
                <?php if (empty($data['classes'])): ?>
                    <p>No classes assigned yet.</p>
                <?php else: ?>
                    <div class="classes-grid">
                        <?php foreach ($data['classes'] as $class): ?>
                            <div class="class-card">
                                <h4><?php echo esc_html($class['name']); ?></h4>
                                <p><?php echo $class['students']; ?> students</p>
                                <p>Status: 
                                    <span class="status-badge <?php echo $class['group_id'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $class['group_id'] ? 'Active Group' : 'No Group'; ?>
                                    </span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section">
                <h3>📝 Recent Quizzes</h3>
                <div class="dashboard-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=sfwd-quiz'); ?>" 
                       class="btn btn-primary" target="_blank">+ Create New Quiz</a>
                </div>
                
                <?php if (empty($data['quizzes'])): ?>
                    <p>No quizzes created yet. <a href="<?php echo admin_url('post-new.php?post_type=sfwd-quiz'); ?>" target="_blank">Create your first quiz!</a></p>
                <?php else: ?>
                    <div class="quizzes-list">
                        <?php foreach (array_slice($data['quizzes'], 0, 5) as $quiz): ?>
                            <div class="quiz-item">
                                <h4><?php echo esc_html($quiz['title']); ?></h4>
                                <p>Created: <?php echo date('M j, Y', strtotime($quiz['date'])); ?></p>
                                <p>
                                    <a href="<?php echo admin_url('post.php?post=' . $quiz['id'] . '&action=edit'); ?>" target="_blank">Edit</a> |
                                    <a href="<?php echo get_permalink($quiz['id']); ?>" target="_blank">View</a>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php endif; ?>
            
            <div class="dashboard-footer">
                <p><a href="<?php echo admin_url('admin.php?page=learndash-lms-reports'); ?>" target="_blank">View Detailed Reports</a></p>
            </div>
            
        </div>
        
        <style>
        .instructor-dashboard-widget {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .dashboard-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.8em;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 20px;
            background: white;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007cba;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007cba;
            margin: 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0 0 0;
        }
        
        .dashboard-section {
            background: white;
            padding: 20px;
            margin: 15px 0;
        }
        
        .dashboard-section h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .class-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .class-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quizzes-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .quiz-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #007cba;
        }
        
        .quiz-item h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .btn:hover {
            background: #005a87;
            color: white;
        }
        
        .dashboard-actions {
            margin: 15px 0;
        }
        
        .dashboard-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .classes-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render no access message
     */
    private function render_no_access() {
        ob_start();
        ?>
        <div class="instructor-dashboard-widget">
            <div class="dashboard-header">
                <h2>🔒 Access Restricted</h2>
                <p>This dashboard is only available to instructors.</p>
            </div>
            <div class="dashboard-section" style="text-align: center; padding: 40px;">
                <?php if (!is_user_logged_in()): ?>
                    <p><a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">Login</a></p>
                <?php else: ?>
                    <p>You need instructor privileges to access this dashboard.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get instructor data
     */
    private function get_instructor_data($user_id) {
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        
        // Get instructor's classes
        $classes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, description, group_id FROM $classes_table WHERE teacher_id = %d",
            $user_id
        ));
        
        $classes_data = array();
        $total_students = 0;
        $groups_count = 0;
        
        foreach ($classes as $class) {
            $students_count = 0;
            
            if ($class->group_id) {
                $groups_count++;
                if (function_exists('learndash_get_groups_users')) {
                    $group_users = learndash_get_groups_users($class->group_id);
                    $students_count = count($group_users);
                    $total_students += $students_count;
                }
            }
            
            $classes_data[] = array(
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'group_id' => $class->group_id,
                'students' => $students_count
            );
        }
        
        // Get instructor's quizzes
        $quizzes = get_posts(array(
            'post_type' => 'sfwd-quiz',
            'author' => $user_id,
            'post_status' => 'publish',
            'numberposts' => 10
        ));
        
        $quizzes_data = array();
        foreach ($quizzes as $quiz) {
            $quizzes_data[] = array(
                'id' => $quiz->ID,
                'title' => $quiz->post_title,
                'date' => $quiz->post_date
            );
        }
        
        return array(
            'classes_count' => count($classes),
            'students_count' => $total_students,
            'quizzes_count' => count($quizzes),
            'groups_count' => $groups_count,
            'classes' => $classes_data,
            'quizzes' => $quizzes_data
        );
    }
    
    /**
     * AJAX handler for dashboard data
     */
    public function ajax_get_dashboard_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_dashboard_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = intval($_POST['user_id']);
        $data = $this->get_instructor_data($user_id);
        
        wp_send_json_success($data);
    }
}

// Initialize
School_Manager_Lite_Instructor_Dashboard_Widget::instance();
