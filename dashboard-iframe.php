<?php
/**
 * Dashboard Iframe - Integrated within plugin
 * 
 * Embeddable iframe version of instructor dashboard
 * Access: https://207lilac.local/wp-content/plugins/school-manager-lite/dashboard-iframe.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Get current user or allow parameter override
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();

// Load the dashboard widget class
if (class_exists('School_Manager_Lite_Instructor_Dashboard_Widget')) {
    $widget = School_Manager_Lite_Instructor_Dashboard_Widget::instance();
    $dashboard_content = $widget->render_dashboard($user_id, 'full');
} else {
    $dashboard_content = '<div style="padding: 40px; text-align: center; color: #666;">Dashboard widget not available. Please ensure the School Manager plugin is active.</div>';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Instructor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            margin: 0; 
            padding: 20px; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        
        /* Additional iframe-specific styles */
        .instructor-dashboard-widget {
            box-shadow: none !important;
            border-radius: 0 !important;
            background: white !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body { padding: 10px; }
        }
    </style>
</head>
<body>
    <?php echo $dashboard_content; ?>
    
    <script>
    // Auto-resize iframe functionality
    function resizeIframe() {
        if (window.parent !== window) {
            const height = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );
            
            window.parent.postMessage({
                type: 'resize',
                height: height + 50 // Add some padding
            }, '*');
        }
    }
    
    // Resize on load and content changes
    window.addEventListener('load', resizeIframe);
    window.addEventListener('resize', resizeIframe);
    
    // Observe DOM changes
    if (window.MutationObserver) {
        const observer = new MutationObserver(resizeIframe);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true
        });
    }
    
    // Resize every few seconds as fallback
    setInterval(resizeIframe, 3000);
    </script>
</body>
</html>
