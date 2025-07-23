<?php
/**
 * Database class for School Manager Lite plugin
 * 
 * Handles creation and management of custom database tables
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Manager_Lite_Database {
    /**
     * List of tables created by this plugin
     */
    private $tables = array(
        'school_classes',
        'school_students',
        'school_promo_codes'
    );

    /**
     * Get table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Full table name with prefix
     */
    public function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . $table;
    }

    /**
     * Create plugin tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create school_classes table
        $table_name = $this->get_table_name('school_classes');
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            teacher_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY teacher_id (teacher_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Create school_students table
        $table_name = $this->get_table_name('school_students');
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) unsigned NOT NULL,
            class_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            email varchar(100),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY wp_user_id_class_id (wp_user_id, class_id),
            KEY wp_user_id (wp_user_id),
            KEY class_id (class_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Create school_promo_codes table
        $table_name = $this->get_table_name('school_promo_codes');
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            prefix varchar(10),
            class_id bigint(20) unsigned NOT NULL,
            teacher_id bigint(20) unsigned NOT NULL,
            expiry_date datetime,
            student_id bigint(20) unsigned DEFAULT NULL,
            used_at datetime DEFAULT NULL,
            usage_limit int(11) NOT NULL DEFAULT 1,
            used_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY class_id (class_id),
            KEY teacher_id (teacher_id),
            KEY student_id (student_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Update database version
        $this->update_db_version('1.1.0');
        
        // Run any pending database updates
        $this->maybe_update_database();
    }

    /**
     * Drop plugin tables
     */
    public function drop_tables() {
        global $wpdb;

        foreach ($this->tables as $table) {
            $table_name = $this->get_table_name($table);
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }
    }

    /**
     * Check if tables exist
     * 
     * @return bool True if all tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        foreach ($this->tables as $table) {
            $table_name = $this->get_table_name($table);
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if (!$result) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get database version for migration purposes
     * 
     * @return string Current database version
     */
    public function get_db_version() {
        return get_option('school_manager_lite_db_version', '1.0.0');
    }

    /**
     * Update database version
     * 
     * @param string $version Version to set
     */
    public function update_db_version($version) {
        update_option('school_manager_lite_db_version', $version);
    }
    
    /**
     * Check if database needs to be updated and run updates if needed
     */
    public function maybe_update_database() {
        $current_version = $this->get_db_version();
        
        // Version 1.1.0 - Add usage_limit and used_count to promo codes
        if (version_compare($current_version, '1.1.0', '<')) {
            global $wpdb;
            $table_name = $this->get_table_name('school_promo_codes');
            
            // Add usage_limit column if it doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'usage_limit'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN usage_limit int(11) NOT NULL DEFAULT 1 AFTER used_at");
            }
            
            // Add used_count column if it doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'used_count'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN used_count int(11) NOT NULL DEFAULT 0 AFTER usage_limit");
            }
            
            $this->update_db_version('1.1.0');
        }
        
        // Version 1.2.0 - Add group_id and course_id to classes table for LearnDash integration
        if (version_compare($current_version, '1.2.0', '<')) {
            global $wpdb;
            $table_name = $this->get_table_name('school_classes');
            
            // Add group_id column if it doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'group_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN group_id bigint(20) unsigned NULL AFTER teacher_id");
            }
            
            // Add course_id column if it doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'course_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN course_id bigint(20) unsigned NULL AFTER group_id");
            }
            
            $this->update_db_version('1.2.0');
        }
    }
}
