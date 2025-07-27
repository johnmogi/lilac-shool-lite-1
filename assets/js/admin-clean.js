/**
 * School Manager Lite Admin JavaScript
 * Clean and optimized version
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Ensure schoolManagerLite is defined
    window.schoolManagerLite = window.schoolManagerLite || {};
    schoolManagerLite.i18n = schoolManagerLite.i18n || {};

    /**
     * Teacher Assignment Modal
     * Handles the teacher group assignment functionality
     */
    var teacherAssignmentModal = {
        /**
         * Initialize the modal
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Handle click on assign teacher group button
            $(document).on('click', '.assign-teacher-group', function(e) {
                e.preventDefault();
                self.openModal($(this));
            });
            
            // Handle save group assignment
            $(document).on('click', '.save-group-assignment', function() {
                self.saveGroupAssignment($(this));
            });
        },

        /**
         * Open the teacher group assignment modal
         * @param {jQuery} $button - The button that was clicked
         */
        openModal: function($button) {
            var teacherId = $button.data('teacher-id');
            var teacherName = $button.data('teacher-name') || '';
            var $modalContainer = $('#teacher-group-assignment-modal');
            
            // Create modal if it doesn't exist
            if ($modalContainer.length === 0) {
                $modalContainer = $('<div id="teacher-group-assignment-modal"></div>').appendTo('body');
            }
            
            // Show loading state
            $modalContainer.html('<div class="loading-spinner"></div>');
            
            // Show the thickbox
            var modalTitle = schoolManagerLite.i18n.assignGroupsTo || 'Assign Groups to';
            modalTitle += ' ' + teacherName;
            
            // Make AJAX request to get the form
            $.ajax({
                url: schoolManagerLite.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_teacher_group_assignment_form',
                    teacher_id: teacherId,
                    nonce: schoolManagerLite.nonce || ''
                },
                success: function(response) {
                    try {
                        // Handle both direct HTML and JSON responses
                        var htmlContent = '';
                        if (typeof response === 'string') {
                            htmlContent = response;
                        } else if (response && response.data) {
                            htmlContent = response.data;
                        }
                        
                        // Update the modal content
                        $modalContainer.html(htmlContent);
                        
                        // Show the thickbox after content is loaded
                        tb_show(modalTitle, '#' + $modalContainer.attr('id'));
                        
                    } catch (e) {
                        console.error('Error processing response:', e);
                        $modalContainer.html(
                            '<div class="notice notice-error">' +
                            '<p>' + (schoolManagerLite.i18n.errorLoadingForm || 'Error loading form. Please try again.') + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr);
                    $modalContainer.html(
                        '<div class="notice notice-error">' +
                        '<p>' + (schoolManagerLite.i18n.errorLoadingForm || 'Error loading form. Please try again.') + '</p>' +
                        '</div>'
                    );
                }
            });
        },

        /**
         * Save group assignment
         * @param {jQuery} $button - The save button that was clicked
         */
        saveGroupAssignment: function($button) {
            var $form = $button.closest('form');
            var $spinner = $button.find('.spinner');
            
            // Show loading state
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Submit form via AJAX
            $.ajax({
                url: schoolManagerLite.ajax_url || ajaxurl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response && response.success) {
                        // Show success message
                        $form.prepend(
                            '<div class="notice notice-success">' +
                            '<p>' + (response.data && response.data.message || 'Settings saved successfully.') + '</p>' +
                            '</div>'
                        );
                        
                        // Close the modal after a short delay
                        setTimeout(function() {
                            tb_remove();
                            $('#teacher-group-assignment-modal').remove();
                            // Refresh the page to show updated groups
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        var errorMessage = response && response.data && response.data.message ? 
                            response.data.message : 'Error saving settings.';
                            
                        $form.prepend(
                            '<div class="notice notice-error">' +
                            '<p>' + errorMessage + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving settings:', status, error);
                    $form.prepend(
                        '<div class="notice notice-error">' +
                        '<p>Error saving settings. Please try again.</p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    // Restore button state
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        }
    };

    // Initialize the plugin when the DOM is ready
    $(document).ready(function() {
        console.log('School Manager admin.js loaded');
        
        // Initialize the teacher assignment modal
        teacherAssignmentModal.init();
        
        // Initialize Select2 for student profile page if it exists
        if ($('.school-manager-student-profile').length) {
            $('#school_classes').select2({
                width: '100%',
                placeholder: 'Select a class',
                allowClear: true
            });
        }

        // Copy to clipboard functionality
        $(document).on('click', '.copy-code', function() {
            var code = $(this).data('code');
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(code).select();
            
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    alert('Copied to clipboard: ' + code);
                } else {
                    alert('Failed to copy. Please copy it manually: ' + code);
                }
            } catch (err) {
                alert('Error copying to clipboard: ' + err);
            }
            
            $temp.remove();
        });
    });

})(jQuery);
