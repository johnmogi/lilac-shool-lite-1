<?php
/**
 * Quick Student Assignment Helper
 * 
 * Temporary script to quickly assign students to classes for testing
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/quick-student-assignment.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Student Assignment</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        .primary { background: #007cba; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Quick Student Assignment Helper</h1>
    
    <?php
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $students_table = $wpdb->prefix . 'school_student_classes';
    
    // Handle form submission
    if (isset($_POST['assign_students'])) {
        $class_id = intval($_POST['class_id']);
        $student_ids = array_map('intval', $_POST['student_ids']);
        
        $success_count = 0;
        $errors = array();
        
        foreach ($student_ids as $student_id) {
            if ($student_id > 0) {
                $result = $wpdb->insert(
                    $students_table,
                    array(
                        'student_id' => $student_id,
                        'class_id' => $class_id,
                        'enrolled_date' => current_time('mysql'),
                        'status' => 'active'
                    ),
                    array('%d', '%d', '%s', '%s')
                );
                
                if ($result) {
                    $success_count++;
                } else {
                    $errors[] = "Failed to assign student ID: $student_id";
                }
            }
        }
        
        if ($success_count > 0) {
            echo '<div class="card success">Successfully assigned ' . $success_count . ' students to class.</div>';
        }
        
        if (!empty($errors)) {
            echo '<div class="card error">' . implode('<br>', $errors) . '</div>';
        }
    }
    
    // Get classes
    $classes = $wpdb->get_results("SELECT id, name, teacher_id FROM $classes_table ORDER BY name");
    
    // Get students
    $students = get_users(array('role' => 'student', 'number' => 50));
    
    ?>
    
    <div class="card">
        <h2>Current Status</h2>
        <p><strong>Classes:</strong> <?php echo count($classes); ?></p>
        <p><strong>Students:</strong> <?php echo count($students); ?></p>
        <p><strong>Student-Classes table exists:</strong> 
            <?php 
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$students_table'") == $students_table;
            echo $table_exists ? '✓ Yes' : '✗ No'; 
            ?>
        </p>
    </div>
    
    <?php if (!$table_exists): ?>
    <div class="card error">
        <h3>Student-Classes Table Missing</h3>
        <p>The student-classes relationship table is missing. Go to the <strong>Instructor Quiz Setup</strong> page and click "Create Student Table" first.</p>
        <p><a href="<?php echo admin_url('admin.php?page=instructor-quiz-setup'); ?>">Go to Instructor Quiz Setup</a></p>
    </div>
    <?php else: ?>
    
    <div class="card">
        <h2>Quick Student Assignment</h2>
        <form method="post">
            <p>
                <label><strong>Select Class:</strong></label><br>
                <select name="class_id" required>
                    <option value="">Choose a class...</option>
                    <?php foreach ($classes as $class): ?>
                        <?php 
                        $teacher = get_user_by('id', $class->teacher_id);
                        $teacher_name = $teacher ? $teacher->display_name : 'No teacher';
                        ?>
                        <option value="<?php echo $class->id; ?>">
                            <?php echo esc_html($class->name . ' (' . $teacher_name . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label><strong>Select Students:</strong></label><br>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                    <?php foreach ($students as $student): ?>
                        <label style="display: block;">
                            <input type="checkbox" name="student_ids[]" value="<?php echo $student->ID; ?>">
                            <?php echo esc_html($student->display_name . ' (' . $student->user_email . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </p>
            
            <p>
                <button type="submit" name="assign_students" class="primary">Assign Selected Students</button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>Current Assignments</h2>
        <?php
        $assignments = $wpdb->get_results("
            SELECT sc.*, c.name as class_name, u.display_name as student_name 
            FROM $students_table sc
            JOIN $classes_table c ON sc.class_id = c.id
            JOIN {$wpdb->users} u ON sc.student_id = u.ID
            ORDER BY c.name, u.display_name
        ");
        
        if (empty($assignments)): ?>
            <p>No student assignments found.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Class</th>
                    <th>Student</th>
                    <th>Enrolled Date</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td><?php echo esc_html($assignment->class_name); ?></td>
                    <td><?php echo esc_html($assignment->student_name); ?></td>
                    <td><?php echo esc_html($assignment->enrolled_date); ?></td>
                    <td><?php echo esc_html($assignment->status); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
    
    <div class="card">
        <h2>Next Steps</h2>
        <ol>
            <li><a href="<?php echo admin_url('admin.php?page=instructor-quiz-setup'); ?>">Go to Instructor Quiz Setup</a> - Fix leader assignments</li>
            <li>Assign students to classes using the form above</li>
            <li>Test instructor access to LearnDash groups</li>
            <li>Create quizzes as instructors</li>
        </ol>
    </div>
    
    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>
