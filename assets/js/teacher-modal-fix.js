/**
 * Teacher Modal Fix - Simple working solution
 * 
 * @package School_Manager_Lite
 * @since 1.2.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Teacher assignment modal functionality
    var teacherModal = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Handle assign teacher button clicks
            $(document).on('click', '.assign-teacher', function(e) {
                e.preventDefault();
                var classId = $(this).data('class-id');
                if (classId) {
                    teacherModal.showModal([classId]);
                }
            });
            
            // Handle modal submit
            $(document).on('click', '#assign-teacher-submit', function(e) {
                e.preventDefault();
                teacherModal.submitAssignment();
            });
            
            // Handle modal close
            $(document).on('click', '.close-teacher-modal, #cancel-teacher-assign', function(e) {
                e.preventDefault();
                teacherModal.closeModal();
            });
        },
        
        showModal: function(classIds) {
            if (!classIds || classIds.length === 0) {
                alert('No classes selected');
                return;
            }
            
            // Store class IDs
            $('#selected-class-ids').val(classIds.join(','));
            
            // Update modal text
            var message = classIds.length === 1 ? 
                'Assign teacher to 1 class' : 
                'Assign teacher to ' + classIds.length + ' classes';
            $('#teacher-assignment-count').text(message);
            
            // Show modal
            $('#teacher-assignment-modal').show();
        },
        
        closeModal: function() {
            $('#teacher-assignment-modal').hide();
            $('#teacher-id').val('');
            $('#selected-class-ids').val('');
            $('.spinner').removeClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
        },
        
        submitAssignment: function() {
            var teacherId = $('#teacher-id').val();
            var classIds = $('#selected-class-ids').val();
            
            if (!teacherId) {
                alert('Please select a teacher');
                return;
            }
            
            if (!classIds) {
                alert('No classes selected');
                return;
            }
            
            // Show loading
            $('.spinner').addClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', true);
            
            var classIdArray = classIds.split(',');
            
            if (classIdArray.length === 1) {
                // Single class assignment
                this.assignSingleClass(teacherId, classIdArray[0]);
            } else {
                // Multiple classes assignment
                this.assignMultipleClasses(teacherId, classIdArray);
            }
        },
        
        assignSingleClass: function(teacherId, classId) {
            $.ajax({
                url: schoolManagerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'assign_teacher_to_class',
                    teacher_id: teacherId,
                    class_id: classId,
                    nonce: schoolManagerAdmin.nonce
                },
                success: function(response) {
                    teacherModal.handleResponse(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Server error occurred. Please try again.');
                    teacherModal.hideLoading();
                }
            });
        },
        
        assignMultipleClasses: function(teacherId, classIds) {
            $.ajax({
                url: schoolManagerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_assign_teacher',
                    teacher_id: teacherId,
                    class_ids: classIds,
                    nonce: schoolManagerAdmin.nonce
                },
                success: function(response) {
                    teacherModal.handleResponse(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Server error occurred. Please try again.');
                    teacherModal.hideLoading();
                }
            });
        },
        
        handleResponse: function(response) {
            this.hideLoading();
            
            if (response.success) {
                alert(response.data.message || 'Teacher assigned successfully!');
                this.closeModal();
                // Reload page to show changes
                window.location.reload();
            } else {
                alert(response.data.message || 'Assignment failed. Please try again.');
            }
        },
        
        hideLoading: function() {
            $('.spinner').removeClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
        }
    };
    
    // Initialize the modal
    teacherModal.init();
    
    // Fix for the problematic button that generates invalid URLs
    $(document).on('click', '.assign-teacher-group', function(e) {
        e.preventDefault();
        var teacherId = $(this).data('teacher-id');
        
        if (teacherId) {
            // Show a simple message or redirect to a proper page
            alert('Teacher ID: ' + teacherId + '. Please use the class assignment modal instead.');
        }
        
        return false;
    });
    
    // Fix for thickbox modals that generate invalid URLs
    $(document).on('click', 'a[href*="TB_inline"]', function(e) {
        var href = $(this).attr('href');
        
        // Check if the href contains invalid characters
        if (href && (href.includes('%3C') || href.includes('<div'))) {
            e.preventDefault();
            console.warn('Invalid thickbox URL prevented:', href);
            alert('Modal system error. Please refresh the page and try again.');
            return false;
        }
    });
});
