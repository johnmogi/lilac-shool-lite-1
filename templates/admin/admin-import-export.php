<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap school-manager-import-export">
    <h1><?php _e('Import/Export', 'school-manager-lite'); ?></h1>
    
    <?php if (isset($_GET['imported'])) : ?>
        <div class="notice notice-success">
            <p><?php _e('Data imported successfully!', 'school-manager-lite'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="import-export-container">
        <div class="import-section">
            <h2><?php _e('Import Data', 'school-manager-lite'); ?></h2>
            <div class="card">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('school_manager_import', 'school_manager_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_type"><?php _e('Import Type', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <select name="import_type" id="import_type" required>
                                    <option value=""><?php _e('Select type to import', 'school-manager-lite'); ?></option>
                                    <option value="students"><?php _e('Students', 'school-manager-lite'); ?></option>
                                    <option value="teachers"><?php _e('Teachers', 'school-manager-lite'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('CSV File', 'school-manager-lite'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv" required>
                                <p class="description">
                                    <?php _e('Upload a CSV file with the correct format.', 'school-manager-lite'); ?>
                                    <a href="#" class="download-sample" data-type="students"><?php _e('Download sample CSV', 'school-manager-lite'); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="import_submit" class="button button-primary">
                            <?php _e('Import', 'school-manager-lite'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="export-section">
            <h2><?php _e('Export Data', 'school-manager-lite'); ?></h2>
            <div class="card">
                <p><?php _e('Export your data to a CSV file.', 'school-manager-lite'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Data Type', 'school-manager-lite'); ?></th>
                            <th><?php _e('Actions', 'school-manager-lite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Students', 'school-manager-lite'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'school-manager-import-export', 'export' => 'students'), admin_url('admin.php'))); ?>" class="button button-secondary">
                                    <?php _e('Export', 'school-manager-lite'); ?>
                                </a>
                                <a href="#" class="button button-link download-sample" data-type="students">
                                    <?php _e('Sample', 'school-manager-lite'); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('Teachers', 'school-manager-lite'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'school-manager-import-export', 'export' => 'teachers'), admin_url('admin.php'))); ?>" class="button button-secondary">
                                    <?php _e('Export', 'school-manager-lite'); ?>
                                </a>
                                <a href="#" class="button button-link download-sample" data-type="teachers">
                                    <?php _e('Sample', 'school-manager-lite'); ?>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <style>
    .import-export-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    .import-section, .export-section {
        flex: 1;
    }
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 15px;
        margin-bottom: 20px;
    }
    @media (max-width: 960px) {
        .import-export-container {
            flex-direction: column;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle sample CSV download
        $('.download-sample').on('click', function(e) {
            e.preventDefault();
            var type = $(this).data('type');
            window.location.href = '<?php echo esc_js(admin_url('admin-ajax.php?action=download_sample_csv&type=')); ?>' + type;
        });
    });
    </script>
</div>
