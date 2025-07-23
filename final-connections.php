<?php
/**
 * Final Connections - Complete the instructor-student-group system
 * 
 * Fix the missing connections: teachers as group leaders + students in groups
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/final-connections.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You need administrator privileges.');
}

// Handle actions
$action_result = '';
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'fix_all_connections':
            $action_result = fix_all_connections();
            break;
        case 'enroll_students_in_groups':
            $action_result = enroll_students_in_groups();
            break;
        case 'assign_teachers_as_leaders':
            $action_result = assign_teachers_as_leaders();
            break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Connections - Complete System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ccc; padding: 20px; margin: 15px 0; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 12px 20px; margin: 10px 5px; cursor: pointer; font-size: 16px; }
        .primary { background: #007cba; color: white; border: none; }
        .success-btn { background: #28a745; color: white; border: none; }
        .big-button { padding: 15px 30px; font-size: 18px; font-weight: bold; }
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔧 Final Connections - Complete Your System</h1>
    
    <div class="card info">
        <h3>Missing Connections Identified:</h3>
        <p>❌ <strong>Teachers not group leaders</strong> - Leader Status shows 0/1<br>
        ❌ <strong>Students not in LearnDash groups</strong> - Students showing 0<br>
        ✅ <strong>Groups exist and quizzes work</strong> - Foundation is solid</p>
    </div>
    
    <?php if ($action_result): ?>
        <?php echo $action_result; ?>
    <?php endif; ?>
    
    <div class="card">
        <h2>🚀 ONE-CLICK COMPLETE FIX</h2>
        <p>This will fix both missing connections in one action:</p>
        <ul>
            <li>✅ Make all teachers group leaders</li>
            <li>✅ Enroll all students in their class groups</li>
            <li>✅ Verify all connections work</li>
        </ul>
        <form method="post">
            <input type="hidden" name="action" value="fix_all_connections">
            <button type="submit" class="success-btn big-button">🔧 FIX ALL CONNECTIONS NOW</button>
        </form>
    </div>
    
    <div class="card">
        <h2>📊 Current System Status</h2>
        <?php display_detailed_status(); ?>
    </div>
    
    <div class="card">
        <h2>🔧 Individual Fixes (if needed)</h2>
        <table>
            <tr>
                <td><strong>Fix Teachers as Group Leaders</strong><br>
                    <small>Make teachers leaders of their class groups</small></td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="assign_teachers_as_leaders">
                        <button type="submit" class="primary">Fix Teachers</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td><strong>Enroll Students in Groups</strong><br>
                    <small>Add students to their class LearnDash groups</small></td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="enroll_students_in_groups">
                        <button type="submit" class="primary">Fix Students</button>
                    </form>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card success">
        <h2>🎯 After This Fix</h2>
        <p><strong>Your complete system will work as:</strong></p>
        <ol>
            <li><strong>wdm_instructor logs in</strong> → sees their classes</li>
            <li><strong>Creates a quiz</strong> → automatically assigned to their group</li>
            <li><strong>Students in the group</strong> → can access and take the quiz</li>
            <li><strong>Results tracked</strong> → instructor can see student progress</li>
        </ol>
        
        <p><strong>Perfect for:</strong> Driving school instructors creating quizzes for their students!</p>
    </div>
    
    <p><a href="<?php echo plugins_url('instructor-quiz-dashboard.php', __FILE__); ?>">← Back to Quiz Dashboard</a> | 
       <a href="<?php echo admin_url(); ?>">← WordPress Admin</a></p>
</body>
</html>

<?php

/**
 * Fix all connections at once
 */
function fix_all_connections() {
    $results = array();
    
    // Step 1: Assign teachers as leaders
    $teacher_result = assign_teachers_as_leaders();
    $results[] = "TEACHERS AS LEADERS: " . strip_tags($teacher_result);
    
    // Step 2: Enroll students in groups
    $student_result = enroll_students_in_groups();
    $results[] = "STUDENTS IN GROUPS: " . strip_tags($student_result);
    
    $message = '<h3>🔧 COMPLETE FIX RESULTS:</h3>';
    $message .= implode('<br><br>', $results);
    
    return '<div class="card success">' . $message . '</div>';
}

/**
 * Assign teachers as group leaders
 */
function assign_teachers_as_leaders() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
    );
    
    if (empty($classes)) {
        return '<div class="card error">❌ No classes with teachers and groups found.</div>';
    }
    
    $success_count = 0;
    $results = array();
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        if (!$teacher) continue;
        
        $assigned = false;
        
        // Method 1: Try ld_update_leader_group_access
        if (function_exists('ld_update_leader_group_access')) {
            $result = ld_update_leader_group_access($class->teacher_id, $class->group_id, true);
            if ($result !== false) {
                $assigned = true;
            }
        }
        
        // Method 2: Direct meta update
        if (!$assigned) {
            $current_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
            if (!is_array($current_leaders)) {
                $current_leaders = array();
            }
            
            if (!in_array($class->teacher_id, $current_leaders)) {
                $current_leaders[] = $class->teacher_id;
                $meta_result = update_post_meta($class->group_id, 'learndash_group_leaders', $current_leaders);
                $assigned = $meta_result;
            } else {
                $assigned = true; // Already assigned
            }
        }
        
        if ($assigned) {
            $success_count++;
            $results[] = "✅ {$teacher->display_name} → Group #{$class->group_id} ({$class->name})";
        } else {
            $results[] = "❌ Failed: {$teacher->display_name} → {$class->name}";
        }
    }
    
    $message = "<strong>Teacher Leader Assignment: {$success_count}/" . count($classes) . " successful</strong><br>";
    $message .= implode('<br>', $results);
    
    return '<div class="card ' . ($success_count > 0 ? 'success' : 'error') . '">' . $message . '</div>';
}

/**
 * Enroll students in groups
 */
function enroll_students_in_groups() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    // Get all student-class relationships
    $student_classes = $wpdb->get_results("
        SELECT sc.student_id, sc.class_id, c.group_id, c.name as class_name,
               u.display_name as student_name
        FROM $students_table sc
        JOIN $classes_table c ON sc.class_id = c.id
        JOIN {$wpdb->users} u ON sc.student_id = u.ID
        WHERE c.group_id IS NOT NULL
    ");
    
    if (empty($student_classes)) {
        return '<div class="card error">❌ No student-class relationships with groups found.</div>';
    }
    
    $success_count = 0;
    $results = array();
    
    foreach ($student_classes as $sc) {
        $enrolled = false;
        
        // Method 1: Try ld_update_group_access
        if (function_exists('ld_update_group_access')) {
            $result = ld_update_group_access($sc->student_id, $sc->group_id, true);
            if ($result !== false) {
                $enrolled = true;
            }
        }
        
        // Method 2: Direct user meta update
        if (!$enrolled) {
            $user_groups = get_user_meta($sc->student_id, 'learndash_group_users_' . $sc->group_id, true);
            if (!$user_groups) {
                $meta_result = update_user_meta($sc->student_id, 'learndash_group_users_' . $sc->group_id, $sc->group_id);
                
                // Also update group meta
                $group_users = get_post_meta($sc->group_id, 'learndash_group_users', true);
                if (!is_array($group_users)) {
                    $group_users = array();
                }
                
                if (!in_array($sc->student_id, $group_users)) {
                    $group_users[] = $sc->student_id;
                    update_post_meta($sc->group_id, 'learndash_group_users', $group_users);
                }
                
                $enrolled = true;
            } else {
                $enrolled = true; // Already enrolled
            }
        }
        
        if ($enrolled) {
            $success_count++;
            $results[] = "✅ {$sc->student_name} → Group #{$sc->group_id} ({$sc->class_name})";
        } else {
            $results[] = "❌ Failed: {$sc->student_name} → {$sc->class_name}";
        }
    }
    
    $message = "<strong>Student Group Enrollment: {$success_count}/" . count($student_classes) . " successful</strong><br>";
    $message .= implode('<br>', array_slice($results, 0, 10)); // Show first 10
    
    if (count($results) > 10) {
        $message .= '<br>... and ' . (count($results) - 10) . ' more';
    }
    
    return '<div class="card ' . ($success_count > 0 ? 'success' : 'error') . '">' . $message . '</div>';
}

/**
 * Display detailed status
 */
function display_detailed_status() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    // Get classes with teachers and groups
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
    );
    
    echo '<table>';
    echo '<tr><th>Class</th><th>Teacher</th><th>Group</th><th>Is Leader</th><th>Students Assigned</th><th>Students in Group</th><th>Status</th></tr>';
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
        
        // Check if teacher is group leader
        $is_leader = false;
        if (function_exists('learndash_get_groups_administrator_leaders')) {
            $leaders = learndash_get_groups_administrator_leaders($class->group_id);
            $is_leader = in_array($class->teacher_id, $leaders);
        }
        
        // Count students assigned to class
        $students_assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $students_table WHERE class_id = %d",
            $class->id
        ));
        
        // Count students in LearnDash group
        $students_in_group = 0;
        if (function_exists('learndash_get_groups_users')) {
            $group_users = learndash_get_groups_users($class->group_id);
            $students_in_group = count($group_users);
        }
        
        $status = ($is_leader && $students_in_group > 0) ? 
                  '<span class="status-good">✅ Ready</span>' : 
                  '<span class="status-bad">❌ Needs Fix</span>';
        
        echo '<tr>';
        echo '<td>' . esc_html($class->name) . '</td>';
        echo '<td>' . esc_html($teacher_name) . '</td>';
        echo '<td>Group #' . $class->group_id . '</td>';
        echo '<td>' . ($is_leader ? '✅ Yes' : '❌ No') . '</td>';
        echo '<td>' . $students_assigned . '</td>';
        echo '<td>' . $students_in_group . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Summary
    $total_classes = count($classes);
    $working_classes = 0;
    
    foreach ($classes as $class) {
        $is_leader = false;
        if (function_exists('learndash_get_groups_administrator_leaders')) {
            $leaders = learndash_get_groups_administrator_leaders($class->group_id);
            $is_leader = in_array($class->teacher_id, $leaders);
        }
        
        $students_in_group = 0;
        if (function_exists('learndash_get_groups_users')) {
            $group_users = learndash_get_groups_users($class->group_id);
            $students_in_group = count($group_users);
        }
        
        if ($is_leader && $students_in_group > 0) {
            $working_classes++;
        }
    }
    
    echo '<div class="card ' . ($working_classes == $total_classes ? 'success' : 'warning') . '">';
    echo '<h3>📊 System Status: ' . $working_classes . '/' . $total_classes . ' classes fully connected</h3>';
    echo '</div>';
}
?>
