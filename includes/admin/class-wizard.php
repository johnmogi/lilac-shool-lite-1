<?php
/**
 * Admin Wizard
 *
 * @package School_Manager_Lite
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * School_Manager_Lite_Admin_Wizard Class
 * 
 * Handles the admin wizard for creating teachers, classes, and promo codes
 */
class School_Manager_Lite_Admin_Wizard {
    
    /**
     * The single instance of the class.
     */
    private static $instance = null;
    
    /**
     * Current step
     *
     * @var string
     */
    private $current_step;
    
    /**
     * Steps for the wizard
     *
     * @var array
     */
    private $steps = array();
    
    /**
     * Main School_Manager_Lite_Admin_Wizard Instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Define steps
        $this->steps = array(
            'welcome'    => array(
                'name'    => __('ברוכים הבאים', 'school-manager-lite'),
                'view'     => array($this, 'welcome_step'),
                'handler'  => array($this, 'welcome_handler'),
            ),
            'teacher'    => array(
                'name'    => __('מורה', 'school-manager-lite'),
                'view'     => array($this, 'teacher_step'),
                'handler'  => array($this, 'teacher_handler'),
            ),
            'class'      => array(
                'name'    => __('כיתה', 'school-manager-lite'),
                'view'     => array($this, 'class_step'),
                'handler'  => array($this, 'class_handler'),
            ),
            'promo_code' => array(
                'name'    => __('קוד קופון', 'school-manager-lite'),
                'view'     => array($this, 'promo_code_step'),
                'handler'  => array($this, 'promo_code_handler'),
            ),
            'done'       => array(
                'name'    => __('סיום', 'school-manager-lite'),
                'view'     => array($this, 'done_step'),
                'handler'  => '',
            ),
        );
        
        // Initialize
        add_action('admin_menu', array($this, 'admin_menu'), 20);
        add_action('admin_init', array($this, 'wizard_handler'), 10);
    }

    /**
     * Add admin menus/screens.
     */
    public function admin_menu() {
        add_submenu_page(
            'school-manager-lite',
            __('אשף התקנה', 'school-manager-lite'),
            __('אשף התקנה', 'school-manager-lite'),
            'manage_options',
            'school-manager-wizard',
            array($this, 'wizard_page')
        );
    }
    
    /**
     * Handle wizard submissions.
     */
    public function wizard_handler() {
        if (!isset($_GET['page']) || 'school-manager-wizard' !== $_GET['page']) {
            return;
        }
        
        // Get current step
        $this->current_step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));
        
        // Security check
        if (isset($_POST['school_manager_wizard_nonce']) && wp_verify_nonce($_POST['school_manager_wizard_nonce'], 'school_manager_wizard')) {
            // Call step handler if exists
            if (!empty($this->steps[$this->current_step]['handler'])) {
                call_user_func($this->steps[$this->current_step]['handler']);
            }
        }
    }
    
    /**
     * Display wizard page.
     */
    public function wizard_page() {
        $this->wizard_header();
        $this->wizard_steps();
        $this->wizard_content();
        $this->wizard_footer();
    }
    
    /**
     * Display wizard header.
     */
    public function wizard_header() {
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php _e('ניהול בית ספר &rsaquo; אשף התקנה', 'school-manager-lite'); ?></title>
            <?php wp_print_scripts('jquery'); ?>
            <?php do_action('admin_print_styles'); ?>
            <?php do_action('admin_head'); ?>
            <style type="text/css">
                .school-manager-wizard-wrap {
                    max-width: 800px;
                    margin: 2em auto;
                    text-align: right;
                    direction: rtl;
                    background: #fff;
                    padding: 2em;
                    box-shadow: 0 1px 3px rgba(0,0,0,.13);
                }
                .school-manager-wizard-steps {
                    display: flex;
                    margin-bottom: 2em;
                    justify-content: center;
                }
                .school-manager-wizard-steps li {
                    padding: 0 1em;
                    margin: 0;
                    position: relative;
                }
                .school-manager-wizard-steps li::after {
                    content: '\2192';
                    position: absolute;
                    right: -0.5em;
                    top: 0;
                }
                .school-manager-wizard-steps li:last-child::after {
                    content: '';
                }
                .school-manager-wizard-steps li.active {
                    font-weight: bold;
                    color: #0073aa;
                }
                .school-manager-wizard-content {
                    margin-bottom: 2em;
                }
                .school-manager-wizard-content form {
                    text-align: right;
                }
                .school-manager-wizard-content .form-table th {
                    width: 30%;
                    padding: 15px 0 15px 10px;
                    text-align: right;
                }
                .school-manager-wizard-buttons {
                    text-align: left;
                    margin-top: 20px;
                }
                .school-manager-wizard-buttons .button-primary {
                    margin-left: 10px;
                }
            </style>
        </head>
        <body class="wp-core-ui">
            <div class="school-manager-wizard-wrap" style="direction: rtl;">
                <h1><?php _e('אשף התקנת ניהול בית ספר', 'school-manager-lite'); ?></h1>
        <?php
    }
    
    /**
     * Display wizard footer.
     */
    public function wizard_footer() {
        ?>  </div><!-- .school-manager-wizard-wrap -->
        </body>
        </html>
        <?php
    }
    
    /**
     * Display wizard steps.
     */
    public function wizard_steps() {
        ?>  <ol class="school-manager-wizard-steps" style="direction: rtl;">
                <?php foreach ($this->steps as $step_key => $step) : ?>
                    <li class="<?php echo $step_key === $this->current_step ? 'active' : ''; ?>">
                        <?php echo esc_html($step['name']); ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php
    }
    
    /**
     * Display wizard content for current step.
     */
    public function wizard_content() {
        echo '<div class="school-manager-wizard-content">';
        if (!empty($this->steps[$this->current_step]['view'])) {
            call_user_func($this->steps[$this->current_step]['view']);
        }
        echo '</div>';
    }
    
    /**
     * Get next step URL.
     *
     * @param string $current_step
     * @return string
     */
    public function get_next_step_url($current_step = '') {
        if (!$current_step) {
            $current_step = $this->current_step;
        }
        
        $steps = array_keys($this->steps);
        $step_index = array_search($current_step, $steps);
        
        if (false === $step_index || !isset($steps[$step_index + 1])) {
            return admin_url('admin.php?page=school-manager-lite');
        }
        
        return add_query_arg('step', $steps[$step_index + 1], admin_url('admin.php?page=school-manager-wizard'));
    }
    
    /*
     * Step Views
     */
    
    /**
     * Welcome step view.
     */
    public function welcome_step() {
        ?>  <h2><?php _e('ברוכים הבאים למערכת ניהול בית ספר!', 'school-manager-lite'); ?></h2>
            <p><?php _e('אשף זה יעזור לך להגדיר את מערכת ניהול בית הספר שלך במהירות ובקלות.', 'school-manager-lite'); ?></p>
            <p><?php _e('תוכל להוסיף מורים, ליצור כיתות וליצור קודי קופון לרישום תלמידים.', 'school-manager-lite'); ?></p>
            
            <form method="post">
                <?php wp_nonce_field('school_manager_wizard', 'school_manager_wizard_nonce'); ?>
                <div class="school-manager-wizard-buttons">
                    <!-- Skip Setup button removed by request -->
                    <button class="button-primary" type="submit"><?php _e('התחל התקנה', 'school-manager-lite'); ?></button>
                </div>
            </form>
        <?php
    }
    
    /**
     * Welcome step handler.
     */
    public function welcome_handler() {
        wp_safe_redirect($this->get_next_step_url());
        exit;
    }
    
    /**
     * Teacher step view.
     */
    public function teacher_step() {
        // Include the list table class
        if (!class_exists('School_Manager_Lite_Wizard_Teachers_List_Table')) {
            require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-wizard-teachers-list-table.php';
        }
        
        // Create an instance of our teachers list table
        $teachers_table = new School_Manager_Lite_Wizard_Teachers_List_Table();
        
        // Process actions and prepare the table items
        $teachers_table->prepare_items();
        
        // Check for error messages
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        ?>  <h2><?php _e('הגדרת מורה', 'school-manager-lite'); ?></h2>
            <p><?php _e('בחר משתמש קיים כדי להפוך אותו למורה או צור חשבון מורה חדש.', 'school-manager-lite'); ?></p>
            
            <?php if ($error === 'no_selection') : ?>
                <div class="notice notice-error">
                    <p><?php _e('אנא בחר משתמש קיים או צור מורה חדש.', 'school-manager-lite'); ?></p>
                </div>
            <?php elseif ($error === 'creation_failed') : ?>
                <div class="notice notice-error">
                    <p><?php _e('אירעה שגיאה ביצירת או הקצאת תפקיד המורה. אנא נסה שוב.', 'school-manager-lite'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field('school_manager_wizard', 'school_manager_wizard_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <?php $teachers_table->search_box(__('חפש משתמשים', 'school-manager-lite'), 'search-teachers'); ?>
                    </div>
                    <br class="clear">
                </div>
                
                <h3><?php _e('בחר משתמש קיים', 'school-manager-lite'); ?></h3>
                
                <?php $teachers_table->display(); ?>
                
                <h3><?php _e('או צור מורה חדש', 'school-manager-lite'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="teacher_name"><?php _e('שם מלא', 'school-manager-lite'); ?></label></th>
                        <td>
                            <input type="text" id="teacher_name" name="teacher_name" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="teacher_email"><?php _e('אימייל', 'school-manager-lite'); ?></label></th>
                        <td>
                            <input type="email" id="teacher_email" name="teacher_email" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <div class="school-manager-wizard-buttons">
                    <a href="<?php echo admin_url('admin.php?page=school-manager-lite'); ?>" class="button"><?php _e('ביטול', 'school-manager-lite'); ?></a>
                    <input type="submit" class="button-primary" value="<?php _e('המשך', 'school-manager-lite'); ?>" name="save_step">
                </div>
            </form>
            
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Highlight selected row
                    $('input[name="teacher_id"]').change(function() {
                        $('.wp-list-table tr').removeClass('selected');
                        $(this).closest('tr').addClass('selected');
                    });
                    
                    // Style selected row
                    $('<style>.wp-list-table tr.selected { background-color: #f7fcfe; }</style>').appendTo('head');
                });
            </script>
        <?php
    }
    
    /**
     * Teacher step handler.
     */
    public function teacher_handler() {
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        $teacher_id = 0;
        
        // Handle existing user selected from list table
        if (!empty($_POST['teacher_id'])) {
            $user_id = absint($_POST['teacher_id']);
            $teacher = $teacher_manager->assign_teacher_role($user_id);
            if (!is_wp_error($teacher)) {
                $teacher_id = $user_id;
                // Store teacher ID in session for next step
                update_option('school_manager_wizard_teacher_id', $teacher_id, false);
                
                wp_safe_redirect($this->get_next_step_url());
                exit;
            }
        }
        
        // Handle new teacher creation
        if (!empty($_POST['teacher_name']) && !empty($_POST['teacher_email'])) {
            $teacher = $teacher_manager->create_teacher(array(
                'name' => sanitize_text_field($_POST['teacher_name']),
                'email' => sanitize_email($_POST['teacher_email']),
            ));
            
            if (!is_wp_error($teacher)) {
                $teacher_id = $teacher->ID;
                // Store teacher ID in session for next step
                update_option('school_manager_wizard_teacher_id', $teacher_id, false);
                
                wp_safe_redirect($this->get_next_step_url());
                exit;
            }
        }
        
        // If we get here, there was a problem or no selection was made
        if (empty($_POST['teacher_id']) && (empty($_POST['teacher_name']) || empty($_POST['teacher_email']))) {
            // No teacher selected or created
            wp_safe_redirect(add_query_arg('error', 'no_selection', admin_url('admin.php?page=school-manager-wizard&step=teacher')));
        } else {
            // Error during assignment or creation
            wp_safe_redirect(add_query_arg('error', 'creation_failed', admin_url('admin.php?page=school-manager-wizard&step=teacher')));
        }
        exit;
    }
    
    /**
     * Class step view.
     */
    public function class_step() {
        $teacher_id = get_option('school_manager_wizard_teacher_id', 0);
        $teacher_name = '';
        
        if ($teacher_id) {
            $teacher = get_userdata($teacher_id);
            if ($teacher) {
                $teacher_name = $teacher->display_name;
            }
        }
        
        // Include the list table class
        if (!class_exists('School_Manager_Lite_Wizard_Classes_List_Table')) {
            require_once SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-wizard-classes-list-table.php';
        }
        
        // Create an instance of our classes list table
        $classes_table = new School_Manager_Lite_Wizard_Classes_List_Table($teacher_id);
        
        $classes_table->prepare_items();
        
        // Check for error messages
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        ?>
        <h2><?php _e('בחירת כיתה', 'school-manager-lite'); ?></h2>
        <p>
            <?php 
            if ($teacher_name) {
                printf(__('צור כיתה חדשה או בחר כיתה קיימת למורה: %s', 'school-manager-lite'), '<strong>' . esc_html($teacher_name) . '</strong>'); 
            } else {
                _e('צור כיתה חדשה או בחר כיתה קיימת.', 'school-manager-lite');
            }
            ?>
        </p>
        
        <?php if ($error === 'no_selection') : ?>
            <div class="notice notice-error">
                <p><?php _e('אנא בחר כיתה קיימת או צור כיתה חדשה.', 'school-manager-lite'); ?></p>
            </div>
        <?php elseif ($error === 'creation_failed') : ?>
            <div class="notice notice-error">
                <p><?php _e('אירעה שגיאה ביצירה או עדכון הכיתה. אנא נסה שוב.', 'school-manager-lite'); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <?php wp_nonce_field('school_manager_wizard', 'school_manager_wizard_nonce'); ?>
            <input type="hidden" name="teacher_id" value="<?php echo esc_attr($teacher_id); ?>">
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <?php $classes_table->search_box(__('חפש כיתות', 'school-manager-lite'), 'search-classes'); ?>
                </div>
                <br class="clear">
            </div>
            
            <h3><?php _e('בחר כיתה קיימת', 'school-manager-lite'); ?></h3>
            
            <?php $classes_table->display(); ?>
            
            <h3><?php _e('או צור כיתה חדשה', 'school-manager-lite'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="class_name"><?php _e('שם הכיתה', 'school-manager-lite'); ?></label></th>
                    <td>
                        <input type="text" id="class_name" name="class_name" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="class_description"><?php _e('תיאור', 'school-manager-lite'); ?></label></th>
                    <td>
                        <textarea id="class_description" name="class_description" class="large-text" rows="5"></textarea>
                    </td>
                </tr>
            </table>
            
            <div class="school-manager-wizard-buttons">
                <a href="<?php echo admin_url('admin.php?page=school-manager-wizard&step=teacher'); ?>" class="button"><?php _e('חזור', 'school-manager-lite'); ?></a>
                <input type="submit" class="button-primary" value="<?php _e('המשך', 'school-manager-lite'); ?>" name="save_step">
            </div>
        </form>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Highlight selected row
                $('input[name="class_id"]').change(function() {
                    $('.wp-list-table tr').removeClass('selected');
                    $(this).closest('tr').addClass('selected');
                    
                    // Clear the create new class form when an existing class is selected
                    if($(this).is(':checked')) {
                        $('#class_name').val('');
                        $('#class_description').val('');
                    }
                });
                
                // Clear selection if user starts typing in the new class form
                $('#class_name, #class_description').on('input', function() {
                    $('input[name="class_id"]').prop('checked', false);
                    $('.wp-list-table tr').removeClass('selected');
                });
                
                // Style selected row
                $('<style>.wp-list-table tr.selected { background-color: #f7fcfe; }</style>').appendTo('head');
            });
        </script>
        <?php
    }
    
    /**
     * Class step handler.
     */
    public function class_handler() {
        $class_id = 0;
        $teacher_id = isset($_POST['teacher_id']) ? absint($_POST['teacher_id']) : 0;
        
        // Handle existing class selected from list table
        if (!empty($_POST['class_id'])) {
            $class_id = absint($_POST['class_id']);
            
            // Verify the group exists
            $group = get_post($class_id);
            
            if ($group && $group->post_type === 'groups') {
                // Store class ID for next step
                update_option('school_manager_wizard_class_id', $class_id, false);
                
                // If the teacher is different, update the group author
                if ($teacher_id && $group->post_author != $teacher_id) {
                    wp_update_post(array(
                        'ID' => $class_id,
                        'post_author' => $teacher_id
                    ));
                }
                
                wp_safe_redirect($this->get_next_step_url());
                exit;
            }
        }
        
        // Handle new class creation
        if (!empty($_POST['class_name']) && !empty($_POST['teacher_id'])) {
            // Create new LearnDash group
            $group_id = wp_insert_post(array(
                'post_title'   => sanitize_text_field($_POST['class_name']),
                'post_content' => isset($_POST['class_description']) ? sanitize_textarea_field($_POST['class_description']) : '',
                'post_status'  => 'publish',
                'post_type'    => 'groups',
                'post_author'  => $teacher_id,
            ));
            
            if (!is_wp_error($group_id)) {
                // Store class ID for next step
                update_option('school_manager_wizard_class_id', $group_id, false);
                
                // Set the group leader if this is a teacher
                if (function_exists('ld_update_leader_group_access')) {
                    ld_update_leader_group_access($teacher_id, $group_id);
                }
                
                wp_safe_redirect($this->get_next_step_url());
                exit;
            }
        }
        
        // If we get here, there was a problem or no selection was made
        if (empty($_POST['class_id']) && empty($_POST['class_name'])) {
            // No class selected or created
            wp_safe_redirect(add_query_arg('error', 'no_selection', admin_url('admin.php?page=school-manager-wizard&step=class')));
        } else {
            // Error during retrieval or creation
            wp_safe_redirect(add_query_arg('error', 'creation_failed', admin_url('admin.php?page=school-manager-wizard&step=class')));
        }
        exit;
    }
    
    /**
     * Promo code step view.
     */
    public function promo_code_step() {
        $class_id = get_option('school_manager_wizard_class_id', 0);
        $teacher_id = get_option('school_manager_wizard_teacher_id', 0);
        $class_name = '';
        
        // Get class name
        if ($class_id) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class = $class_manager->get_class($class_id);
            if ($class) {
                $class_name = $class->name;
            }
        }
        
        ?>  <h2><?php _e('Generate Promo Codes', 'school-manager-lite'); ?></h2>
            <p>
                <?php 
                if ($class_name) {
                    printf(__('Generate promo codes for class: %s', 'school-manager-lite'), '<strong>' . esc_html($class_name) . '</strong>'); 
                } else {
                    _e('Generate promo codes for your class.', 'school-manager-lite');
                }
                ?>
            </p>
            
            <form method="post">
                <?php wp_nonce_field('school_manager_wizard', 'school_manager_wizard_nonce'); ?>
                <input type="hidden" name="class_id" value="<?php echo esc_attr($class_id); ?>">
                <input type="hidden" name="teacher_id" value="<?php echo esc_attr($teacher_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="quantity"><?php _e('Quantity', 'school-manager-lite'); ?></label></th>
                        <td>
                            <input type="number" id="quantity" name="quantity" value="10" min="1" max="100" class="small-text">
                            <p class="description"><?php _e('Number of promo codes to generate (maximum 100)', 'school-manager-lite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="prefix"><?php _e('Prefix', 'school-manager-lite'); ?></label></th>
                        <td>
                            <input type="text" id="prefix" name="prefix" class="regular-text">
                            <p class="description"><?php _e('Optional prefix for your promo codes (e.g., CLASS-)', 'school-manager-lite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="expiry_date"><?php _e('Expiry Date', 'school-manager-lite'); ?></label></th>
                        <td>
                            <?php 
                            // Set default expiry to June 30th of current year
                            $default_expiry = date('Y') . '-06-30';
                            // If current date is after June 30th, set to next year
                            if (time() > strtotime($default_expiry)) {
                                $default_expiry = (date('Y') + 1) . '-06-30';
                            }
                            ?>
                            <input type="date" id="expiry_date" name="expiry_date" class="regular-text" value="<?php echo esc_attr($default_expiry); ?>">
                            <p class="description"><?php _e('Expiry date for the promo codes (defaults to June 30th)', 'school-manager-lite'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="school-manager-wizard-buttons">
                    <a href="<?php echo admin_url('admin.php?page=school-manager-wizard&step=class'); ?>" class="button"><?php _e('Back', 'school-manager-lite'); ?></a>
                    <input type="submit" class="button-primary" value="<?php _e('Generate Codes', 'school-manager-lite'); ?>" name="save_step">
                </div>
            </form>
        <?php
    }
    
    /**
     * Promo code step handler.
     */
    public function promo_code_handler() {
        if (!empty($_POST['class_id']) && !empty($_POST['teacher_id']) && isset($_POST['quantity'])) {
            $promo_code_manager = School_Manager_Lite_Promo_Code_Manager::instance();
            
            $promo_codes = $promo_code_manager->generate_promo_codes(array(
                'class_id' => absint($_POST['class_id']),
                'teacher_id' => absint($_POST['teacher_id']),
                'quantity' => absint($_POST['quantity']),
                'prefix' => isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '',
                'expiry_date' => isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null,
                'usage_limit' => 1, // Force single-use promo codes
                'used_count' => 0,  // Initialize with 0 uses
            ));
            
            if (!is_wp_error($promo_codes) && !empty($promo_codes)) {
                // Store generated codes for completion step
                update_option('school_manager_wizard_promo_codes', $promo_codes, false);
                
                wp_safe_redirect($this->get_next_step_url());
                exit;
            }
        }
        
        // If we get here, there was a problem
        wp_safe_redirect(add_query_arg('error', '1', admin_url('admin.php?page=school-manager-wizard&step=promo_code')));
        exit;
    }
    
    /**
     * Done step view.
     */
    public function done_step() {
        $promo_codes = get_option('school_manager_wizard_promo_codes', array());
        $class_id = get_option('school_manager_wizard_class_id', 0);
        $teacher_id = get_option('school_manager_wizard_teacher_id', 0);
        
        $class_name = '';
        $teacher_name = '';
        
        // Get class name
        if ($class_id) {
            $class_manager = School_Manager_Lite_Class_Manager::instance();
            $class = $class_manager->get_class($class_id);
            if ($class) {
                $class_name = $class->name;
            }
        }
        
        // Get teacher name
        if ($teacher_id) {
            $teacher = get_userdata($teacher_id);
            if ($teacher) {
                $teacher_name = $teacher->display_name;
            }
        }
        
        ?>  <h2><?php _e('Setup Complete!', 'school-manager-lite'); ?></h2>
            
            <div class="notice notice-success">
                <p><?php _e('Your school has been set up successfully.', 'school-manager-lite'); ?></p>
            </div>
            
            <h3><?php _e('Summary', 'school-manager-lite'); ?></h3>
            <ul>
                <li><?php printf(__('Teacher: %s', 'school-manager-lite'), '<strong>' . esc_html($teacher_name) . '</strong>'); ?></li>
                <li><?php printf(__('Class: %s', 'school-manager-lite'), '<strong>' . esc_html($class_name) . '</strong>'); ?></li>
                <li><?php printf(_n('%s promo code generated', '%s promo codes generated', count($promo_codes), 'school-manager-lite'), '<strong>' . count($promo_codes) . '</strong>'); ?></li>
            </ul>
            
            <?php if (!empty($promo_codes)) : ?>
                <h3><?php _e('Generated Promo Codes', 'school-manager-lite'); ?></h3>
                <textarea readonly class="large-text code" rows="5"><?php echo esc_textarea(implode("\n", $promo_codes)); ?></textarea>
                <p class="description"><?php _e('Copy these codes and share them with students to enroll in the class.', 'school-manager-lite'); ?></p>
            <?php endif; ?>
            
            <p><?php _e('You can now:', 'school-manager-lite'); ?></p>
            <ul>
                <li><?php _e('Add more classes and teachers', 'school-manager-lite'); ?></li>
                <li><?php _e('Distribute promo codes to students', 'school-manager-lite'); ?></li>
                <li><?php _e('Monitor student enrollments', 'school-manager-lite'); ?></li>
            </ul>
            
            <div class="school-manager-wizard-buttons">
                <a href="<?php echo admin_url('admin.php?page=school-manager-lite'); ?>" class="button button-primary"><?php _e('Go to Dashboard', 'school-manager-lite'); ?></a>
            </div>
        <?php
        
        // Clear session data
        delete_option('school_manager_wizard_teacher_id');
        delete_option('school_manager_wizard_class_id');
        delete_option('school_manager_wizard_promo_codes');
    }
}

// Initialize the admin wizard
School_Manager_Lite_Admin_Wizard::instance();
