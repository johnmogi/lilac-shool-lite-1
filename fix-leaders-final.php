<?php
/**
 * Fix Leaders Final - Force the leader assignments to work
 * 
 * This will use multiple methods to ensure teachers become group leaders
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/fix-leaders-final.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You need administrator privileges.');
}

// Handle action
$action_result = '';
if (isset($_POST['action']) && $_POST['action'] === 'force_fix_leaders') {
    $action_result = force_fix_leaders();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Fix Leaders - Final Solution</title>
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
        button { padding: 15px 30px; margin: 10px 5px; cursor: pointer; font-size: 18px; font-weight: bold; }
        .primary { background: #007cba; color: white; border: none; }
        .success-btn { background: #28a745; color: white; border: none; }
        .big-button { padding: 20px 40px; font-size: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Force Fix Leaders - Final Solution</h1>
    
    <div class="card info">
        <h3>Problem Identified:</h3>
        <p>The previous fix showed "successful" but teachers still show "❌ No" for leader status.<br>
        This suggests the LearnDash functions aren't working as expected.</p>
        
        <p><strong>This fix will use multiple aggressive methods to force the leader assignments.</strong></p>
    </div>
    
    <?php if ($action_result): ?>
        <?php echo $action_result; ?>
    <?php endif; ?>
    
    <div class="card">
        <h2>🚀 FORCE FIX LEADERS</h2>
        <p>This will use multiple methods simultaneously to ensure teachers become group leaders:</p>
        <ul>
            <li>✅ Direct database meta updates</li>
            <li>✅ LearnDash function calls</li>
            <li>✅ WordPress user meta updates</li>
            <li>✅ Group post meta updates</li>
            <li>✅ Verification after each method</li>
        </ul>
        
        <form method="post">
            <input type="hidden" name="action" value="force_fix_leaders">
            <button type="submit" class="success-btn big-button">🔧 FORCE FIX LEADERS NOW</button>
        </form>
    </div>
    
    <div class="card">
        <h2>📊 Current Status Before Fix</h2>
        <?php display_current_status(); ?>
    </div>
    
    <p><a href="<?php echo plugins_url('final-connections.php', __FILE__); ?>">← Back to Final Connections</a></p>
</body>
</html>

<?php

/**
 * Force fix leaders using multiple methods
 */
function force_fix_leaders() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
    );
    
    if (empty($classes)) {
        return '<div class="card error">❌ No classes with teachers and groups found.</div>';
    }
    
    $results = array();
    $success_count = 0;
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        if (!$teacher) continue;
        
        $result = array(
            'class' => $class->name,
            'teacher' => $teacher->display_name,
            'group_id' => $class->group_id,
            'methods_tried' => array(),
            'final_status' => 'Failed'
        );
        
        // Method 1: Direct post meta update
        $current_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
        if (!is_array($current_leaders)) {
            $current_leaders = array();
        }
        
        if (!in_array($class->teacher_id, $current_leaders)) {
            $current_leaders[] = $class->teacher_id;
        }
        
        $meta_updated = update_post_meta($class->group_id, 'learndash_group_leaders', $current_leaders);
        $result['methods_tried'][] = "Post meta update: " . ($meta_updated ? "Success" : "Failed");
        
        // Method 2: User meta update
        $user_groups = get_user_meta($class->teacher_id, 'learndash_group_leaders_' . $class->group_id, true);
        if (!$user_groups) {
            $user_meta_updated = update_user_meta($class->teacher_id, 'learndash_group_leaders_' . $class->group_id, $class->group_id);
            $result['methods_tried'][] = "User meta update: " . ($user_meta_updated ? "Success" : "Failed");
        }
        
        // Method 3: LearnDash function (if available)
        if (function_exists('ld_update_leader_group_access')) {
            $ld_result = ld_update_leader_group_access($class->teacher_id, $class->group_id, true);
            $result['methods_tried'][] = "LearnDash function: " . ($ld_result !== false ? "Success" : "Failed");
        }
        
        // Method 4: Direct database update as backup
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES (%d, 'learndash_group_leaders', %s)",
            $class->group_id,
            serialize(array($class->teacher_id))
        ));
        $result['methods_tried'][] = "Direct DB insert: Attempted";
        
        // Method 5: Ensure group leader capability
        $teacher_user = new WP_User($class->teacher_id);
        $teacher_user->add_cap('group_leader');
        $result['methods_tried'][] = "Added group_leader capability";
        
        // Verify the fix worked
        $verification_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
        if (is_array($verification_leaders) && in_array($class->teacher_id, $verification_leaders)) {
            $result['final_status'] = 'SUCCESS';
            $success_count++;
        }
        
        // Double-check with LearnDash function if available
        if (function_exists('learndash_get_groups_administrator_leaders')) {
            $ld_leaders = learndash_get_groups_administrator_leaders($class->group_id);
            if (in_array($class->teacher_id, $ld_leaders)) {
                $result['final_status'] = 'SUCCESS (LD Verified)';
                $success_count++;
            }
        }
        
        $results[] = $result;
    }
    
    // Generate detailed results
    $message = "<h3>🔧 FORCE FIX RESULTS: {$success_count}/" . count($classes) . " successful</h3>";
    
    foreach ($results as $result) {
        $status_class = ($result['final_status'] === 'Failed') ? 'error' : 'success';
        $message .= "<div class='card {$status_class}'>";
        $message .= "<h4>{$result['class']} - {$result['teacher']} (Group #{$result['group_id']})</h4>";
        $message .= "<p><strong>Final Status: {$result['final_status']}</strong></p>";
        $message .= "<p><strong>Methods tried:</strong><br>" . implode('<br>', $result['methods_tried']) . "</p>";
        $message .= "</div>";
    }
    
    return $message;
}

/**
 * Display current status
 */
function display_current_status() {
    global $wpdb;
    
    $classes_table = $wpdb->prefix . 'school_classes';
    $classes = $wpdb->get_results(
        "SELECT id, name, teacher_id, group_id FROM $classes_table 
         WHERE teacher_id IS NOT NULL AND teacher_id > 0 AND group_id IS NOT NULL"
    );
    
    echo '<table>';
    echo '<tr><th>Class</th><th>Teacher</th><th>Group</th><th>Meta Leaders</th><th>LD Function Check</th></tr>';
    
    foreach ($classes as $class) {
        $teacher = get_user_by('id', $class->teacher_id);
        $teacher_name = $teacher ? $teacher->display_name : 'Unknown';
        
        // Check post meta
        $meta_leaders = get_post_meta($class->group_id, 'learndash_group_leaders', true);
        $in_meta = (is_array($meta_leaders) && in_array($class->teacher_id, $meta_leaders)) ? '✅ Yes' : '❌ No';
        
        // Check LearnDash function
        $ld_check = '❌ Function not available';
        if (function_exists('learndash_get_groups_administrator_leaders')) {
            $ld_leaders = learndash_get_groups_administrator_leaders($class->group_id);
            $ld_check = in_array($class->teacher_id, $ld_leaders) ? '✅ Yes' : '❌ No';
        }
        
        echo '<tr>';
        echo '<td>' . esc_html($class->name) . '</td>';
        echo '<td>' . esc_html($teacher_name) . '</td>';
        echo '<td>Group #' . $class->group_id . '</td>';
        echo '<td>' . $in_meta . '</td>';
        echo '<td>' . $ld_check . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}
?>
