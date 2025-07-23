<?php
/**
 * Test Group Connection
 * 
 * Simple diagnostic script to test teacher-group connections
 */

// WordPress environment
require_once('../../../wp-config.php');

echo "<h1>School Manager - Group Connection Test</h1>";

// Check if LearnDash is active
if (function_exists('learndash_get_groups') || class_exists('SFWD_LMS')) {
    echo "<p style='color: green;'>✓ LearnDash is active</p>";
} else {
    echo "<p style='color: red;'>✗ LearnDash is NOT active</p>";
}

// Check database tables
global $wpdb;

$classes_table = $wpdb->prefix . 'school_classes';
$students_table = $wpdb->prefix . 'school_student_classes';

echo "<h2>Database Tables</h2>";

// Check classes table
$classes_exists = $wpdb->get_var("SHOW TABLES LIKE '$classes_table'") == $classes_table;
echo "<p>Classes table ($classes_table): " . ($classes_exists ? "<span style='color: green;'>✓ EXISTS</span>" : "<span style='color: red;'>✗ MISSING</span>") . "</p>";

if ($classes_exists) {
    $classes_count = $wpdb->get_var("SELECT COUNT(*) FROM $classes_table");
    echo "<p>Total classes: $classes_count</p>";
    
    $classes_with_teachers = $wpdb->get_var("SELECT COUNT(*) FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0");
    echo "<p>Classes with teachers: $classes_with_teachers</p>";
    
    if ($classes_with_teachers > 0) {
        echo "<h3>Classes with Teachers:</h3>";
        $classes = $wpdb->get_results("SELECT id, name, teacher_id, group_id FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0 LIMIT 10");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Teacher ID</th><th>Teacher Name</th><th>Group ID</th><th>Group Exists</th></tr>";
        
        foreach ($classes as $class) {
            $teacher = get_user_by('id', $class->teacher_id);
            $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
            
            $group_exists = '';
            if ($class->group_id) {
                $group_post = get_post($class->group_id);
                $group_exists = ($group_post && $group_post->post_type === 'groups') ? '✓' : '✗';
            } else {
                $group_exists = 'No group';
            }
            
            echo "<tr>";
            echo "<td>$class->id</td>";
            echo "<td>$class->name</td>";
            echo "<td>$class->teacher_id</td>";
            echo "<td>$teacher_name</td>";
            echo "<td>" . ($class->group_id ?: 'None') . "</td>";
            echo "<td>$group_exists</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check students table
$students_exists = $wpdb->get_var("SHOW TABLES LIKE '$students_table'") == $students_table;
echo "<p>Student-Classes table ($students_table): " . ($students_exists ? "<span style='color: green;'>✓ EXISTS</span>" : "<span style='color: red;'>✗ MISSING</span>") . "</p>";

if ($students_exists) {
    $student_class_count = $wpdb->get_var("SELECT COUNT(*) FROM $students_table");
    echo "<p>Total student-class relationships: $student_class_count</p>";
}

// Check user roles
echo "<h2>User Roles</h2>";
$instructor_roles = array('instructor', 'Instructor', 'wdm_instructor', 'swd_instructor', 'school_teacher');

foreach ($instructor_roles as $role) {
    $users = get_users(array('role' => $role));
    echo "<p>$role: " . count($users) . " users</p>";
}

// Test simple connection
echo "<h2>Test Connection</h2>";
if (class_exists('School_Manager_Lite_Simple_Group_Connector')) {
    echo "<p style='color: green;'>✓ Simple Group Connector class loaded</p>";
    
    $connector = School_Manager_Lite_Simple_Group_Connector::instance();
    $classes = $connector->get_classes_with_teachers();
    
    echo "<p>Classes found by connector: " . count($classes) . "</p>";
    
    if (!empty($classes)) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='test_sync' value='1'>";
        echo "<button type='submit' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Test Sync First Class</button>";
        echo "</form>";
        
        if (isset($_POST['test_sync'])) {
            echo "<h3>Test Sync Results:</h3>";
            $first_class = $classes[0];
            
            echo "<p>Testing class: {$first_class->name} (ID: {$first_class->id})</p>";
            
            // Create group
            $group_id = $connector->create_group_for_class($first_class);
            if ($group_id) {
                echo "<p style='color: green;'>✓ Group created/found: ID $group_id</p>";
                
                // Assign teacher
                if ($connector->assign_teacher_as_leader($first_class->teacher_id, $group_id)) {
                    echo "<p style='color: green;'>✓ Teacher assigned as leader</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to assign teacher as leader</p>";
                }
                
                // Get students
                $students = $connector->get_class_students($first_class->id);
                echo "<p>Students found: " . count($students) . "</p>";
                
                if (!empty($students)) {
                    $enrolled = $connector->enroll_students_in_group($students, $group_id);
                    echo "<p style='color: green;'>✓ Students enrolled: $enrolled</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to create group</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>✗ Simple Group Connector class NOT loaded</p>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='javascript:location.reload()'>Refresh</a></p>";
?>
