<?php
/**
 * Complete Setup Helper
 * 
 * One-page solution to set up the entire instructor-quiz-student system
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/complete-setup.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You need administrator privileges.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Instructor-Quiz-Student Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        .primary { background: #007cba; color: white; border: none; }
        .secondary { background: #6c757d; color: white; border: none; }
        .success-btn { background: #28a745; color: white; border: none; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007cba; }
        .completed { border-left-color: #28a745; background: #f8fff9; }
    </style>
</head>
<body>
    <h1>🎯 Complete Instructor-Quiz-Student Setup</h1>
    
    <div class="card info">
        <h3>Goal: Connect wdm_instructor users to LearnDash groups so they can create quizzes for their students</h3>
        <p><strong>Current Status Analysis:</strong></p>
        <ul>
            <li>✅ 19 wdm_instructor users found</li>
            <li>✅ 5 classes with teachers assigned</li>
            <li>✅ LearnDash is active</li>
            <li>❌ Student-Classes table missing</li>
            <li>❌ No students in system</li>
            <li>❌ Teacher leader assignment failing</li>
        </ul>
    </div>
    
    <?php
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    // Handle actions
    $action_result = '';
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_student_table':
                $action_result = create_student_table($wpdb, $students_table);
                break;
            case 'create_demo_students':
                $action_result = create_demo_students();
                break;
            case 'fix_leader_assignments':
                $action_result = fix_leader_assignments($wpdb, $classes_table);
                break;
            case 'assign_students_to_classes':
                $action_result = assign_students_to_classes($wpdb, $classes_table, $students_table);
                break;
            case 'test_instructor_access':
                $action_result = test_instructor_access($wpdb, $classes_table);
                break;
        }
    }
    
    if ($action_result) {
        echo $action_result;
    }
    
    // Check current status
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$students_table'") == $students_table;
    $students = get_users(array('role' => 'student', 'number' => 10));
    $classes = $wpdb->get_results("SELECT id, name, teacher_id, group_id FROM $classes_table WHERE teacher_id IS NOT NULL ORDER BY name");
    $instructors = get_users(array('role' => 'wdm_instructor', 'number' => 5));
    ?>
    
    <div class="step <?php echo $table_exists ? 'completed' : ''; ?>">
        <h2>Step 1: Create Student-Classes Table</h2>
        <p>Status: <?php echo $table_exists ? '✅ Table exists' : '❌ Table missing'; ?></p>
        <?php if (!$table_exists): ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="create_student_table">
                <button type="submit" class="primary">Create Student Table</button>
            </form>
        <?php else: ?>
            <p class="success">✅ Student-Classes table is ready!</p>
        <?php endif; ?>
    </div>
    
    <div class="step <?php echo count($students) > 0 ? 'completed' : ''; ?>">
        <h2>Step 2: Create Demo Students</h2>
        <p>Status: <?php echo count($students); ?> students found</p>
        <?php if (count($students) < 5): ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="create_demo_students">
                <button type="submit" class="primary">Create 10 Demo Students</button>
            </form>
        <?php else: ?>
            <p class="success">✅ Students are available!</p>
        <?php endif; ?>
    </div>
    
    <div class="step">
        <h2>Step 3: Fix Teacher Leader Assignments</h2>
        <p>Fix the "Failed to assign teacher as leader" issue from your diagnostic.</p>
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="fix_leader_assignments">
            <button type="submit" class="primary">Fix Leader Assignments</button>
        </form>
    </div>
    
    <div class="step">
        <h2>Step 4: Assign Students to Classes</h2>
        <p>Automatically assign demo students to classes with teachers.</p>
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="assign_students_to_classes">
            <button type="submit" class="primary">Auto-Assign Students</button>
        </form>
    </div>
    
    <div class="step">
        <h2>Step 5: Test Instructor Access</h2>
        <p>Verify that wdm_instructor users can access groups and create quizzes.</p>
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="test_instructor_access">
            <button type="submit" class="secondary">Test Access</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Current System Status</h2>
        <table>
            <tr><th>Component</th><th>Status</th><th>Count</th></tr>
            <tr><td>Student-Classes Table</td><td><?php echo $table_exists ? '✅' : '❌'; ?></td><td><?php echo $table_exists ? 'Exists' : 'Missing'; ?></td></tr>
            <tr><td>Students</td><td><?php echo count($students) > 0 ? '✅' : '❌'; ?></td><td><?php echo count($students); ?></td></tr>
            <tr><td>Classes with Teachers</td><td><?php echo count($classes) > 0 ? '✅' : '❌'; ?></td><td><?php echo count($classes); ?></td></tr>
            <tr><td>wdm_instructor Users</td><td><?php echo count($instructors) > 0 ? '✅' : '❌'; ?></td><td><?php echo count($instructors); ?></td></tr>
        </table>
    </div>
    
    <?php if (count($classes) > 0): ?>
    <div class="card">
        <h2>Classes with Teachers</h2>
        <table>
            <tr><th>Class</th><th>Teacher</th><th>Role</th><th>Group</th><th>Students</th></tr>
            <?php foreach ($classes as $class): ?>
                <?php 
                $teacher = get_user_by('id', $class->teacher_id);
                $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
                $teacher_roles = $teacher ? implode(', ', $teacher->roles) : 'None';
                $group_exists = $class->group_id ? (get_post($class->group_id) ? 'Yes' : 'No') : 'No';
                
                $student_count = 0;
                if ($table_exists) {
                    $student_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $students_table WHERE class_id = %d",
                        $class->id
                    ));
                }
                ?>
                <tr>
                    <td><?php echo esc_html($class->name); ?></td>
                    <td><?php echo esc_html($teacher_name); ?></td>
                    <td><?php echo esc_html($teacher_roles); ?></td>
                    <td><?php echo $group_exists; ?></td>
                    <td><?php echo $student_count; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="card info">
        <h2>🎯 Next: Instructor-Quiz Connection</h2>
        <p>Once the above steps are completed, you'll have:</p>
        <ul>
            <li>✅ wdm_instructor users connected to LearnDash groups as leaders</li>
            <li>✅ Students assigned to classes and enrolled in groups</li>
            <li>✅ Instructors able to create quizzes for their groups</li>
            <li>✅ Foundation for "students to instructor created quiz" functionality</li>
        </ul>
        <p><strong>The instructor-quiz relation will be:</strong> Instructor → LearnDash Group → Quiz → Students in Group</p>
    </div>
    
    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>

<?php

/**
 * Create student table
 */
function create_student_table($wpdb, $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        student_id int(11) NOT NULL,
        class_id int(11) NOT NULL,
        enrolled_date datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'active',
        PRIMARY KEY (id),
        KEY student_id (student_id),
        KEY class_id (class_id),
        UNIQUE KEY student_class (student_id, class_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $table_created = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_created) {
        return '<div class="card success">✅ Student-Classes table created successfully!</div>';
    } else {
        return '<div class="card error">❌ Failed to create Student-Classes table.</div>';
    }
}

/**
 * Create demo students
 */
function create_demo_students() {
    $student_names = array(
        'Alice Johnson', 'Bob Smith', 'Charlie Brown', 'Diana Prince', 'Edward Norton',
        'Fiona Green', 'George Wilson', 'Hannah Davis', 'Ian Thompson', 'Julia Roberts'
    );
    
    $created_count = 0;
    $errors = array();
    
    foreach ($student_names as $name) {
        $username = strtolower(str_replace(' ', '.', $name));
        $email = $username . '@example.com';
        
        // Check if user already exists
        if (username_exists($username) || email_exists($email)) {
            continue;
        }
        
        $user_id = wp_create_user($username, 'password123', $email);
        
        if (!is_wp_error($user_id)) {
            // Update display name
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => explode(' ', $name)[0],
                'last_name' => explode(' ', $name)[1]
            ));
            
            // Assign student role
            $user = new WP_User($user_id);
            $user->set_role('student');
            
            $created_count++;
        } else {
            $errors[] = "Failed to create: $name";
        }
    }
    
    $message = "✅ Created $created_count demo students.";
    if (!empty($errors)) {
        $message .= '<br>❌ Errors: ' . implode(', ', $errors);
    }
    
    return '<div class="card success">' . $message . '</div>';
}

/**
 * Fix leader assignments
 */
function fix_leader_assignments($wpdb, $classes_table) {
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table WHERE teacher_id IS NOT NULL AND teacher_id > 0"
    );
    
    if (empty($classes)) {
        return '<div class="card error">❌ No classes with teachers found.</div>';
    }
    
    $results = array();
    $success_count = 0;
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        if (!$teacher) continue;
        
        // Create group if it doesn't exist
        if (!$class->group_id) {
            $group_data = array(
                'post_title' => 'Class: ' . $class->name,
                'post_content' => 'LearnDash group for class: ' . $class->name,
                'post_status' => 'publish',
                'post_type' => 'groups'
            );
            
            $group_id = wp_insert_post($group_data);
            
            if (!is_wp_error($group_id) && $group_id > 0) {
                $wpdb->update(
                    $classes_table,
                    array('group_id' => $group_id),
                    array('id' => $class->id),
                    array('%d'),
                    array('%d')
                );
                $class->group_id = $group_id;
            }
        }
        
        if ($class->group_id) {
            // Method 1: Try ld_update_leader_group_access
            $leader_assigned = false;
            if (function_exists('ld_update_leader_group_access')) {
                $result = ld_update_leader_group_access($class->teacher_id, $class->group_id, true);
                if ($result !== false) {
                    $leader_assigned = true;
                }
            }
            
            // Method 2: Direct meta update if first method failed
            if (!$leader_assigned) {
                $current_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
                if (!is_array($current_leaders)) {
                    $current_leaders = array();
                }
                
                if (!in_array($class->teacher_id, $current_leaders)) {
                    $current_leaders[] = $class->teacher_id;
                    $meta_result = update_post_meta($class->group_id, 'learndash_group_leaders', $current_leaders);
                    $leader_assigned = $meta_result;
                }
            }
            
            if ($leader_assigned) {
                $success_count++;
                $results[] = "✅ {$class->name}: {$teacher->display_name} assigned as leader";
            } else {
                $results[] = "❌ {$class->name}: Failed to assign {$teacher->display_name}";
            }
        }
    }
    
    $message = "Leader assignment completed: $success_count out of " . count($classes) . " successful.<br>";
    $message .= implode('<br>', $results);
    
    return '<div class="card ' . ($success_count > 0 ? 'success' : 'error') . '">' . $message . '</div>';
}

/**
 * Assign students to classes
 */
function assign_students_to_classes($wpdb, $classes_table, $students_table) {
    $classes = $wpdb->get_results("SELECT id, name FROM $classes_table WHERE teacher_id IS NOT NULL ORDER BY id LIMIT 5");
    $students = get_users(array('role' => 'student', 'number' => 10));
    
    if (empty($classes) || empty($students)) {
        return '<div class="card error">❌ Need both classes and students to assign.</div>';
    }
    
    $assignments = 0;
    $errors = array();
    
    // Assign 2-3 students per class
    $student_index = 0;
    foreach ($classes as $class) {
        $students_per_class = rand(2, 3);
        
        for ($i = 0; $i < $students_per_class && $student_index < count($students); $i++) {
            $student = $students[$student_index];
            
            $result = $wpdb->insert(
                $students_table,
                array(
                    'student_id' => $student->ID,
                    'class_id' => $class->id,
                    'enrolled_date' => current_time('mysql'),
                    'status' => 'active'
                ),
                array('%d', '%d', '%s', '%s')
            );
            
            if ($result) {
                $assignments++;
                
                // Also enroll in LearnDash group if it exists
                $group_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT group_id FROM $classes_table WHERE id = %d",
                    $class->id
                ));
                
                if ($group_id && function_exists('ld_update_group_access')) {
                    ld_update_group_access($student->ID, $group_id, true);
                }
            } else {
                $errors[] = "Failed to assign {$student->display_name} to {$class->name}";
            }
            
            $student_index++;
        }
    }
    
    $message = "✅ Successfully assigned $assignments students to classes.";
    if (!empty($errors)) {
        $message .= '<br>❌ Errors: ' . implode('<br>', $errors);
    }
    
    return '<div class="card success">' . $message . '</div>';
}

/**
 * Test instructor access
 */
function test_instructor_access($wpdb, $classes_table) {
    $instructors = get_users(array('role' => 'wdm_instructor', 'number' => 5));
    
    if (empty($instructors)) {
        return '<div class="card error">❌ No wdm_instructor users found.</div>';
    }
    
    $results = array();
    $working_count = 0;
    
    foreach ($instructors as $instructor) {
        // Check if instructor has classes
        $classes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, group_id FROM $classes_table WHERE teacher_id = %d",
            $instructor->ID
        ));
        
        $instructor_status = array(
            'name' => $instructor->display_name,
            'classes' => count($classes),
            'groups' => 0,
            'leader_status' => 0,
            'can_create_quiz' => user_can($instructor, 'edit_sfwd-quiz') ? 'Yes' : 'No'
        );
        
        foreach ($classes as $class) {
            if ($class->group_id) {
                $instructor_status['groups']++;
                
                if (function_exists('learndash_get_groups_administrator_leaders')) {
                    $leaders = learndash_get_groups_administrator_leaders($class->group_id);
                    if (in_array($instructor->ID, $leaders)) {
                        $instructor_status['leader_status']++;
                    }
                }
            }
        }
        
        if ($instructor_status['leader_status'] > 0) {
            $working_count++;
        }
        
        $results[] = $instructor_status;
    }
    
    $message = "Instructor access test: $working_count out of " . count($instructors) . " instructors ready for quiz creation.<br><br>";
    $message .= '<table><tr><th>Instructor</th><th>Classes</th><th>Groups</th><th>Leader Status</th><th>Can Create Quiz</th></tr>';
    
    foreach ($results as $result) {
        $message .= sprintf(
            '<tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td>%s</td></tr>',
            $result['name'],
            $result['classes'],
            $result['groups'],
            $result['leader_status'],
            $result['can_create_quiz']
        );
    }
    
    $message .= '</table>';
    
    return '<div class="card ' . ($working_count > 0 ? 'success' : 'warning') . '">' . $message . '</div>';
}
?>
