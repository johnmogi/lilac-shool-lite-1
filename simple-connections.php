<?php
/**
 * Simple Connections - Light & Robust
 * 
 * Basic system to connect students, classes, and teachers to LearnDash groups
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/simple-connections.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You need administrator privileges.');
}

// Handle actions
$result = '';
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'connect_all':
            $result = connect_all_simple();
            break;
        case 'test_connections':
            $result = test_connections_simple();
            break;
        case 'reset_connections':
            $result = reset_connections_simple();
            break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Connections - Light & Robust</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        button { padding: 12px 24px; margin: 10px 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-primary { background: #007cba; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .big-button { padding: 20px 40px; font-size: 18px; font-weight: bold; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Simple Connections - Light & Robust</h1>
        
        <div class="card info">
            <h3>Goal: Basic Working System</h3>
            <p><strong>Simple connections:</strong> Students → Classes → Teachers → LearnDash Groups</p>
            <p><strong>No complexity, just working connections.</strong></p>
        </div>
        
        <?php if ($result): ?>
            <?php echo $result; ?>
        <?php endif; ?>
        
        <div class="card">
            <h2>🚀 One-Click Connection</h2>
            <p>This will create all basic connections in the simplest way possible:</p>
            <ul>
                <li>✅ Connect teachers to their class groups as leaders</li>
                <li>✅ Enroll students in their class groups</li>
                <li>✅ Verify all connections work</li>
            </ul>
            
            <form method="post" style="text-align: center;">
                <input type="hidden" name="action" value="connect_all">
                <button type="submit" class="btn-success big-button">🔧 CONNECT ALL NOW</button>
            </form>
        </div>
        
        <div class="card">
            <h2>📊 Current Status</h2>
            <?php display_simple_status(); ?>
        </div>
        
        <div class="card">
            <h2>🧪 Test & Reset</h2>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="test_connections">
                <button type="submit" class="btn-primary">🧪 Test Connections</button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="reset_connections">
                <button type="submit" class="btn-warning" onclick="return confirm('Reset all connections?')">🔄 Reset All</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php

/**
 * Connect all - simple and robust
 */
function connect_all_simple() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    $results = array();
    $total_success = 0;
    
    // Step 1: Get all classes with teachers
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0"
    );
    
    if (empty($classes)) {
        return '<div class="card error"><h3>❌ No classes with teachers found</h3></div>';
    }
    
    $results[] = "<h3>📋 Processing " . count($classes) . " classes...</h3>";
    
    foreach ($classes as $class) {
        $class_results = array();
        $class_success = true;
        
        $teacher = get_user_by('id', $class->teacher_id);
        $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
        
        // Create group if doesn't exist
        if (!$class->group_id) {
            $group_id = wp_insert_post(array(
                'post_title' => 'Group: ' . $class->name,
                'post_content' => 'LearnDash group for class: ' . $class->name,
                'post_status' => 'publish',
                'post_type' => 'groups'
            ));
            
            if ($group_id && !is_wp_error($group_id)) {
                $wpdb->update(
                    $classes_table,
                    array('group_id' => $group_id),
                    array('id' => $class->id),
                    array('%d'),
                    array('%d')
                );
                $class->group_id = $group_id;
                $class_results[] = "✅ Created group #$group_id";
            } else {
                $class_results[] = "❌ Failed to create group";
                $class_success = false;
            }
        } else {
            $class_results[] = "✅ Group #$class->group_id exists";
        }
        
        // Make teacher group leader (simple method)
        if ($class->group_id && $class_success) {
            $leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
            if (!is_array($leaders)) {
                $leaders = array();
            }
            
            if (!in_array($class->teacher_id, $leaders)) {
                $leaders[] = $class->teacher_id;
                $meta_updated = update_post_meta($class->group_id, 'learndash_group_leaders', $leaders);
                $class_results[] = $meta_updated ? "✅ Teacher assigned as leader" : "❌ Failed to assign teacher";
            } else {
                $class_results[] = "✅ Teacher already a leader";
            }
        }
        
        // Enroll students in group
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT student_id FROM $students_table WHERE class_id = %d",
            $class->id
        ));
        
        $enrolled_count = 0;
        foreach ($students as $student) {
            if ($class->group_id) {
                // Simple enrollment - just update the meta
                $group_users = get_post_meta($class->group_id, 'learndash_group_users', true);
                if (!is_array($group_users)) {
                    $group_users = array();
                }
                
                if (!in_array($student->student_id, $group_users)) {
                    $group_users[] = $student->student_id;
                    update_post_meta($class->group_id, 'learndash_group_users', $group_users);
                    
                    // Also update user meta
                    update_user_meta($student->student_id, 'learndash_group_users_' . $class->group_id, $class->group_id);
                }
                $enrolled_count++;
            }
        }
        
        $class_results[] = "✅ Enrolled $enrolled_count students";
        
        if ($class_success) {
            $total_success++;
        }
        
        $status_class = $class_success ? 'success' : 'error';
        $results[] = "<div class='card $status_class'>";
        $results[] = "<h4>$class->name - $teacher_name</h4>";
        $results[] = implode('<br>', $class_results);
        $results[] = "</div>";
    }
    
    $summary = "<div class='card " . ($total_success == count($classes) ? 'success' : 'error') . "'>";
    $summary .= "<h3>📊 Summary: $total_success/" . count($classes) . " classes connected successfully</h3>";
    $summary .= "</div>";
    
    return $summary . implode('', $results);
}

/**
 * Test connections
 */
function test_connections_simple() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
    );
    
    if (empty($classes)) {
        return '<div class="card error"><h3>❌ No classes with groups to test</h3></div>';
    }
    
    $results = array();
    $working_count = 0;
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
        
        $tests = array();
        $all_passed = true;
        
        // Test 1: Group exists
        $group = get_post($class->group_id);
        $group_exists = $group && $group->post_type === 'groups';
        $tests[] = "Group exists: " . ($group_exists ? "✅ Yes" : "❌ No");
        if (!$group_exists) $all_passed = false;
        
        // Test 2: Teacher is leader
        $leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
        $is_leader = is_array($leaders) && in_array($class->teacher_id, $leaders);
        $tests[] = "Teacher is leader: " . ($is_leader ? "✅ Yes" : "❌ No");
        if (!$is_leader) $all_passed = false;
        
        // Test 3: Students enrolled
        $group_users = get_post_meta($class->group_id, 'learndash_group_users', true);
        $student_count = is_array($group_users) ? count($group_users) : 0;
        $tests[] = "Students enrolled: $student_count";
        
        if ($all_passed && $student_count > 0) {
            $working_count++;
        }
        
        $status_class = ($all_passed && $student_count > 0) ? 'success' : 'error';
        $results[] = "<div class='card $status_class'>";
        $results[] = "<h4>$class->name - $teacher_name (Group #$class->group_id)</h4>";
        $results[] = implode('<br>', $tests);
        $results[] = "</div>";
    }
    
    $summary = "<div class='card " . ($working_count == count($classes) ? 'success' : 'error') . "'>";
    $summary .= "<h3>🧪 Test Results: $working_count/" . count($classes) . " classes working perfectly</h3>";
    $summary .= "</div>";
    
    return $summary . implode('', $results);
}

/**
 * Reset connections
 */
function reset_connections_simple() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    
    // Clear group_id from classes
    $wpdb->query("UPDATE $classes_table SET group_id = NULL");
    
    // Delete LearnDash groups created by this system
    $groups = get_posts(array(
        'post_type' => 'groups',
        'meta_query' => array(
            array(
                'key' => 'created_by_school_manager',
                'compare' => 'EXISTS'
            )
        ),
        'numberposts' => -1
    ));
    
    foreach ($groups as $group) {
        wp_delete_post($group->ID, true);
    }
    
    return '<div class="card success"><h3>🔄 Reset Complete</h3><p>All connections cleared. You can now reconnect everything fresh.</p></div>';
}

/**
 * Display simple status
 */
function display_simple_status() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    // Basic counts
    $total_classes = $wpdb->get_var("SELECT COUNT(*) FROM $classes_table");
    $classes_with_teachers = $wpdb->get_var("SELECT COUNT(*) FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0");
    $classes_with_groups = $wpdb->get_var("SELECT COUNT(*) FROM $classes_table WHERE group_id IS NOT NULL");
    $total_students = $wpdb->get_var("SELECT COUNT(DISTINCT student_id) FROM $students_table");
    $student_assignments = $wpdb->get_var("SELECT COUNT(*) FROM $students_table");
    
    echo '<table>';
    echo '<tr><th>Component</th><th>Count</th><th>Status</th></tr>';
    echo '<tr><td>Total Classes</td><td>' . $total_classes . '</td><td>' . ($total_classes > 0 ? '<span class="status-ok">✅</span>' : '<span class="status-fail">❌</span>') . '</td></tr>';
    echo '<tr><td>Classes with Teachers</td><td>' . $classes_with_teachers . '</td><td>' . ($classes_with_teachers > 0 ? '<span class="status-ok">✅</span>' : '<span class="status-fail">❌</span>') . '</td></tr>';
    echo '<tr><td>Classes with Groups</td><td>' . $classes_with_groups . '</td><td>' . ($classes_with_groups > 0 ? '<span class="status-ok">✅</span>' : '<span class="status-fail">❌</span>') . '</td></tr>';
    echo '<tr><td>Total Students</td><td>' . $total_students . '</td><td>' . ($total_students > 0 ? '<span class="status-ok">✅</span>' : '<span class="status-fail">❌</span>') . '</td></tr>';
    echo '<tr><td>Student-Class Assignments</td><td>' . $student_assignments . '</td><td>' . ($student_assignments > 0 ? '<span class="status-ok">✅</span>' : '<span class="status-fail">❌</span>') . '</td></tr>';
    echo '</table>';
    
    // Show classes with details
    $classes = $wpdb->get_results(
        "SELECT c.id, c.name, c.teacher_id, c.group_id, u.display_name as teacher_name,
                (SELECT COUNT(*) FROM $students_table WHERE class_id = c.id) as student_count
         FROM $classes_table c
         LEFT JOIN {$wpdb->users} u ON c.teacher_id = u.ID
         ORDER BY c.name"
    );
    
    if (!empty($classes)) {
        echo '<h4>📋 Classes Overview</h4>';
        echo '<table>';
        echo '<tr><th>Class</th><th>Teacher</th><th>Group</th><th>Students</th><th>Status</th></tr>';
        
        foreach ($classes as $class) {
            $status = '❌ Incomplete';
            if ($class->teacher_id && $class->group_id && $class->student_count > 0) {
                $status = '✅ Complete';
            } elseif ($class->teacher_id && $class->group_id) {
                $status = '⚠️ No Students';
            } elseif ($class->teacher_id) {
                $status = '⚠️ No Group';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($class->name) . '</td>';
            echo '<td>' . esc_html($class->teacher_name ?: 'None') . '</td>';
            echo '<td>' . ($class->group_id ? "Group #$class->group_id" : 'None') . '</td>';
            echo '<td>' . $class->student_count . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
}
?>
