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
            var modal = $('#teacher-assignment-modal');
            if (modal.length === 0) {
                console.error('Teacher assignment modal not found');
                return;
            }
            
            // Store class IDs for later use
            teacherModal.selectedClassIds = classIds;
            
            // Update modal content based on number of classes
            var countText = classIds.length === 1 ? 
                'שיוך מורה לכיתה אחת' : 
                'שיוך מורה ל-' + classIds.length + ' כיתות';
            $('#teacher-assignment-count').text(countText);
            
            // Show modal with fade effect
            modal.fadeIn(300);
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
        },
        
        closeModal: function() {
            $('#teacher-assignment-modal').fadeOut(300);
            $('#teacher-id').val('');
            teacherModal.selectedClassIds = [];
            $('.spinner').removeClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
            $('body').css('overflow', 'auto'); // Restore scrolling
        },
        
        submitAssignment: function() {
            var teacherId = $('#teacher-id').val();
            var classIds = teacherModal.selectedClassIds;
            
            if (!teacherId) {
                alert('אנא בחר מורה / Please select a teacher');
                return;
            }
            
            if (!classIds || classIds.length === 0) {
                alert('לא נבחרו כיתות / No classes selected');
                return;
            }
            
            // Show loading
            $('.spinner').addClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', true);
            
            var classIdArray = classIds;
            
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
                var message = response.data.hebrew || response.data.message || 'מורה שויך בהצלחה!';
                alert(message);
                this.closeModal();
                // Reload page to show changes
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            } else {
                var errorMessage = response.data.hebrew || response.data.message || 'שיוך המורה נכשל. אנא נסה שוב.';
                alert(errorMessage);
            }
        },
        
        hideLoading: function() {
            $('.spinner').removeClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
        }
    };
    
    // Initialize the modal
    teacherModal.init();
    
    // Handle bulk assign teacher action
    $(document).on('submit', 'form', function(e) {
        var action = $(this).find('select[name="action"]').val();
        var selectedClasses = $(this).find('input[name="class_id[]"]:checked');
        
        if (action === 'bulk_assign_teacher') {
            e.preventDefault();
            
            if (selectedClasses.length === 0) {
                alert('Please select at least one class to assign a teacher to.');
                return false;
            }
            
            var classIds = [];
            selectedClasses.each(function() {
                classIds.push($(this).val());
            });
            
            // Show the teacher assignment modal
            teacherModal.showModal(classIds);
            return false;
        }
    });
    
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
    
    // Modal event handlers
    $(document).on('click', '.close-teacher-modal, #cancel-teacher-assign', function(e) {
        e.preventDefault();
        teacherModal.closeModal();
    });
    
    // Close modal when clicking on background
    $(document).on('click', '#teacher-assignment-modal', function(e) {
        if (e.target === this) {
            teacherModal.closeModal();
        }
    });
    
    // Submit teacher assignment
    $(document).on('click', '#assign-teacher-submit', function(e) {
        e.preventDefault();
        teacherModal.submitAssignment();
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
