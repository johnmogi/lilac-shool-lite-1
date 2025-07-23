<?php
/**
 * Instructor Dashboard - Iframe Ready
 * 
 * Embeddable instructor dashboard for main website integration
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/instructor-dashboard-iframe.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Get current user or allow parameter override for testing
$current_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
$current_user = get_user_by('id', $current_user_id);

// Check if user is wdm_instructor
$is_instructor = $current_user && in_array('wdm_instructor', $current_user->roles);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Instructor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { 
            background: white; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 15px 0; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 30px 20px; 
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background: #f8f9fa; 
            font-weight: 600;
            color: #333;
        }
        .btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary { 
            background: #007cba; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #005a87; 
        }
        .btn-success { 
            background: #28a745; 
            color: white; 
        }
        .btn-success:hover { 
            background: #1e7e34; 
        }
        .status-badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.8em; 
            font-weight: bold;
        }
        .status-active { 
            background: #d4edda; 
            color: #155724; 
        }
        .status-inactive { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .no-access { 
            text-align: center; 
            padding: 40px; 
            color: #666; 
        }
        .quiz-item { 
            background: #f8f9fa; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
            border-left: 4px solid #28a745;
        }
        .quiz-title { 
            font-weight: bold; 
            color: #333; 
            margin-bottom: 5px;
        }
        .quiz-meta { 
            font-size: 0.9em; 
            color: #666; 
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .stats-grid { grid-template-columns: 1fr; }
            table { font-size: 0.9em; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <?php if (!$is_instructor): ?>
            <div class="no-access">
                <h2>Access Restricted</h2>
                <p>This dashboard is only available to instructors.</p>
                <?php if (!$current_user): ?>
                    <p><a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">Login</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <div class="header">
                <h1>👨‍🏫 Instructor Dashboard</h1>
                <p>Welcome, <?php echo esc_html($current_user->display_name); ?>!</p>
            </div>
            
            <?php
            // Get instructor's data
            global $wpdb;
            $classes_table = $wpdb->prefix . 'school_classes';
            $students_table = $wpdb->prefix . 'school_student_classes';
            
            // Get instructor's classes
            $instructor_classes = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, description, group_id FROM $classes_table WHERE teacher_id = %d",
                $current_user_id
            ));
            
            // Get total students
            $total_students = 0;
            $total_groups = 0;
            foreach ($instructor_classes as $class) {
                if ($class->group_id) {
                    $total_groups++;
                    if (function_exists('learndash_get_groups_users')) {
                        $group_users = learndash_get_groups_users($class->group_id);
                        $total_students += count($group_users);
                    }
                }
            }
            
            // Get instructor's quizzes
            $instructor_quizzes = get_posts(array(
                'post_type' => 'sfwd-quiz',
                'author' => $current_user_id,
                'post_status' => 'publish',
                'numberposts' => 20
            ));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($instructor_classes); ?></div>
                    <div class="stat-label">My Classes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($instructor_quizzes); ?></div>
                    <div class="stat-label">My Quizzes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_groups; ?></div>
                    <div class="stat-label">Active Groups</div>
                </div>
            </div>
            
            <div class="card">
                <h2>📚 My Classes</h2>
                <?php if (empty($instructor_classes)): ?>
                    <p>No classes assigned yet.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Class Name</th>
                            <th>Students</th>
                            <th>Group Status</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($instructor_classes as $class): ?>
                            <?php
                            $student_count = 0;
                            $group_status = 'No Group';
                            
                            if ($class->group_id) {
                                $group_status = 'Active';
                                if (function_exists('learndash_get_groups_users')) {
                                    $group_users = learndash_get_groups_users($class->group_id);
                                    $student_count = count($group_users);
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($class->name); ?></strong>
                                    <?php if ($class->description): ?>
                                        <br><small><?php echo esc_html($class->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $student_count; ?> students</td>
                                <td>
                                    <span class="status-badge <?php echo $class->group_id ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $group_status; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($class->group_id): ?>
                                        <a href="<?php echo admin_url('post.php?post=' . $class->group_id . '&action=edit'); ?>" 
                                           class="btn btn-primary" target="_parent">Manage Group</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>📝 My Quizzes</h2>
                <div style="margin-bottom: 15px;">
                    <a href="<?php echo admin_url('post-new.php?post_type=sfwd-quiz'); ?>" 
                       class="btn btn-success" target="_parent">+ Create New Quiz</a>
                </div>
                
                <?php if (empty($instructor_quizzes)): ?>
                    <p>No quizzes created yet. <a href="<?php echo admin_url('post-new.php?post_type=sfwd-quiz'); ?>" target="_parent">Create your first quiz!</a></p>
                <?php else: ?>
                    <?php foreach ($instructor_quizzes as $quiz): ?>
                        <?php
                        $quiz_groups = get_post_meta($quiz->ID, 'learndash_quiz_groups', true);
                        if (!is_array($quiz_groups)) {
                            $quiz_groups = array();
                        }
                        
                        $connected_groups = array();
                        foreach ($quiz_groups as $group_id) {
                            $group = get_post($group_id);
                            if ($group) {
                                $connected_groups[] = $group->post_title;
                            }
                        }
                        ?>
                        <div class="quiz-item">
                            <div class="quiz-title"><?php echo esc_html($quiz->post_title); ?></div>
                            <div class="quiz-meta">
                                Created: <?php echo date('M j, Y', strtotime($quiz->post_date)); ?>
                                <?php if (!empty($connected_groups)): ?>
                                    | Connected to: <?php echo implode(', ', $connected_groups); ?>
                                <?php endif; ?>
                                | <a href="<?php echo admin_url('post.php?post=' . $quiz->ID . '&action=edit'); ?>" target="_parent">Edit</a>
                                | <a href="<?php echo get_permalink($quiz->ID); ?>" target="_blank">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>👥 Recent Student Activity</h2>
                <?php if ($total_students == 0): ?>
                    <p>No students enrolled in your classes yet.</p>
                <?php else: ?>
                    <p>You have <?php echo $total_students; ?> students across <?php echo count($instructor_classes); ?> classes.</p>
                    <p><a href="<?php echo admin_url('admin.php?page=learndash-lms-reports'); ?>" 
                          class="btn btn-primary" target="_parent">View Detailed Reports</a></p>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script>
    // Auto-resize iframe if embedded
    function resizeIframe() {
        if (window.parent !== window) {
            const height = document.body.scrollHeight;
            window.parent.postMessage({
                type: 'resize',
                height: height
            }, '*');
        }
    }
    
    // Resize on load and when content changes
    window.addEventListener('load', resizeIframe);
    window.addEventListener('resize', resizeIframe);
    
    // Observe DOM changes for dynamic content
    if (window.MutationObserver) {
        const observer = new MutationObserver(resizeIframe);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true
        });
    }
    </script>
</body>
</html>
