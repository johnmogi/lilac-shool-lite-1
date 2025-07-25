/**
 * School Manager Lite Admin JavaScript
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Quick Edit functionality
    // Expose globally so inline scripts in list table can access it
    window.inlineEditStudent = {
        init: function() {
            const self = this;
            
            // Quick Edit button click - use delegated event handling for dynamically added elements
            $(document).on('click', '.editinline', function(e) {
                e.preventDefault();
                console.log('Quick Edit link clicked (delegated)', this);
                self.edit(this);
                return false;
            });
            
            // Cancel button click
            $('#student-quick-edit .cancel').on('click', function() {
                inlineEditStudent.revert();
                return false;
            });
            
            // Save/Update button click
            $('#student-quick-edit .save').on('click', function() {
                inlineEditStudent.save(this);
                return false;
            });
            
            // Handle row clicks
            $('#the-list').on('click', function(e) {
                let target = $(e.target);
                let row = target.closest('tr');
                
                // If the clicked element is in the quick edit row, do nothing
                if (row.attr('id') === 'student-quick-edit') {
                    return;
                }
                
                // If clicking outside a quick edit form that's open, close it
                if (row.attr('id') !== 'student-quick-edit' && $('#student-quick-edit').is(':visible')) {
                    inlineEditStudent.revert();
                }
            });
            
            // Handle page clicks (close quick edit when clicking outside)
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#the-list').length && $('#student-quick-edit').is(':visible')) {
                    inlineEditStudent.revert();
                }
            });
        },
        
        // Edit a student
        edit: function(element) {
            try {
                // Get the student row
                const editRow = $('#student-quick-edit');
                if (editRow.length === 0) {
                    console.error('Quick edit row not found!');
                    return;
                }
                
                const studentRow = $(element).closest('tr');
                if (studentRow.length === 0) {
                    console.error('Student row not found!');
                    return;
                }
                
                console.log('Element:', element);
                console.log('Data attribute:', $(element).data());
                
                // Try to get student data
                let studentData = $(element).data('student');
                if (!studentData) {
                    // Try parsing data from HTML attribute as fallback
                    try {
                        const dataAttr = $(element).attr('data-student');
                        if (dataAttr) {
                            studentData = JSON.parse(dataAttr);
                        }
                    } catch(err) {
                        console.error('Error parsing student data:', err);
                    }
                }
                
                if (!studentData) {
                    console.error('Student data not found!');
                    alert('Error: Student data not found. Please refresh the page and try again.');
                    return;
                }
                
                console.log('Student data:', studentData);
                
                // Position the quick edit row
                editRow.attr('style', '');
                studentRow.after(editRow);
                
                // Populate quick edit fields
                $('#quick-edit-student-id').val(studentData.id);
                $('#quick-edit-student-name').text(studentData.name);
                $('#quick-edit-class-id').val(studentData.class_id);
                
                // Hide the student row and show edit row
                studentRow.hide();
                editRow.show();
                
                // Focus first field
                $('#quick-edit-class-id').focus();
            } catch(err) {
                console.error('Error in edit function:', err);
                alert('Error opening Quick Edit. Please check the browser console for details.');
            }
        },
        
        // Save student changes
        save: function(element) {
            // Show spinner
            $('#student-quick-edit .spinner').addClass('is-active');
            
            // Get form data
            const studentId = $('#quick-edit-student-id').val();
            const classId = $('#quick-edit-class-id').val();
            const status = $('#quick-edit-status').val();
            
            // Save via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'quick_edit_student',
                    student_id: studentId,
                    class_id: classId,
                    status: status,
                    nonce: $('#school_manager_quick_edit_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated data
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Error updating student');
                        inlineEditStudent.revert();
                    }
                },
                error: function() {
                    alert('Error updating student. Please try again.');
                    inlineEditStudent.revert();
                }
            });
        },
        
        // Revert/cancel quick edit
        revert: function() {
            // Hide quick edit row
            const editRow = $('#student-quick-edit');
            editRow.hide();
            
            // Show the student row that was being edited
            editRow.prev('tr').show();
            
            // Hide spinner
            $('#student-quick-edit .spinner').removeClass('is-active');
        }
    };
    // Ensure legacy plugins/themes referring to global var also work
    var inlineEditStudent = window.inlineEditStudent;
    
    // Document ready
    $(document).ready(function() {
        console.log('School Manager admin.js loaded');
        console.log('Body classes:', $('body').attr('class'));

        // Teacher Assignment Modal
        const teacherAssignmentModal = {
            init: function() {
                this.bindEvents();
            },
            
            bindEvents: function() {
                // Handle show teacher button click
                $('.assign-teacher-button').on('click', function(e) {
                    e.preventDefault();
                    const classId = $(this).data('class-id');
                    
                    // Show loading
                    $('#TB_ajaxContent').html('<div class="loading-spinner"></div>');
                    
                    // Get modal content via AJAX
                    $.ajax({
                        url: schoolManagerAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_teacher_assignment_modal',
                            class_id: classId,
                            nonce: schoolManagerAjax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update modal content
                                $('#TB_ajaxContent').html(response.data.content);
                                
                                // Initialize modal handlers
                                teacherAssignmentModal.initModalHandlers();
                            } else {
                                $('#TB_ajaxContent').html('<p>Error loading modal content</p>');
                            }
                        },
                        error: function() {
                            $('#TB_ajaxContent').html('<p>Error loading modal content</p>');
                        }
                    });
                });
                
                // Close modal when X is clicked
                $('.close-teacher-modal').on('click', this.closeModal.bind(this));
                
                // Close modal when cancel button is clicked
                $('#cancel-teacher-assign').on('click', this.closeModal.bind(this));
                
                // Handle save assignment
                $('#save-teacher-assign').on('click', this.saveAssignment.bind(this));
                
                // Handle teacher selection change
                $('#teacher-id').on('change', this.updateAssignmentCount.bind(this));
            },
            
            initModalHandlers: function() {
                // Re-bind modal handlers since content was dynamically loaded
                this.bindEvents();
            },
            
            closeModal: function() {
                tb_remove();
            },
            
            updateAssignmentCount: function() {
                const teacherId = $('#teacher-id').val();
                const classIds = $('#selected-class-ids').val();
                
                if (!teacherId || !classIds) {
                    $('#teacher-assignment-count').text('');
                    return;
                }
                
                const classCount = (classIds.match(/,/g) || []).length + 1;
                $('#teacher-assignment-count').text(
                    `${classCount} ${classCount === 1 ? 'class' : 'classes'} selected` +
                    ' / ' +
                    `<span lang="he" dir="rtl">${classCount} ${classCount === 1 ? 'כיתה' : 'כיתות'} נבחרו</span>`
                );
            },
            
            saveAssignment: function() {
                const teacherId = $('#teacher-id').val();
                const classId = $('#selected-class-ids').val();
                
                if (!teacherId || !classId) {
                    alert('Please select both a teacher and at least one class');
                    return;
                }
                
                // Show spinner
                $('.spinner').addClass('is-active');
                
                // Save via AJAX
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
                        if (response.success) {
                            alert('Teacher assigned successfully');
                            // Close modal and reload page
                            teacherAssignmentModal.closeModal();
                            window.location.reload();
                        } else {
                            alert(response.data.message || 'Error assigning teacher');
                        }
                    },
                    error: function() {
                        alert('Error assigning teacher. Please try again.');
                    },
                    complete: function() {
                        // Hide spinner
                        $('.spinner').removeClass('is-active');
                    }
                });
            }
        };
        
        // Initialize teacher assignment modal
        teacherAssignmentModal.init();
        
        // Always initialize Quick Edit on any admin page that might contain students list
        inlineEditStudent.init();
        console.log('Quick Edit initialized');
        
        // Log if we can find Quick Edit elements
        console.log('Quick Edit links found:', $('.editinline').length);
        console.log('Quick Edit form found:', $('#student-quick-edit').length);
        // Common form handling for add/edit forms
        $('.school-manager-form').each(function() {
            const $form = $(this);
            
            // Toggle visibility of forms when buttons are clicked
            $form.find('.toggle-form').on('click', function(e) {
                e.preventDefault();
                $form.slideToggle();
            });
            
            // Hide forms when cancel buttons are clicked
            $form.find('.cancel-form').on('click', function(e) {
                e.preventDefault();
                $form.slideUp();
            });
        });
        
        // Copy promo code to clipboard
        $('.copy-code').on('click', function() {
            const code = $(this).data('code');
            
            // Use modern clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function() {
                    alert('Promo code copied to clipboard: ' + code);
                }).catch(function() {
                    // Fallback for browsers that don't support clipboard API
                    fallbackCopyToClipboard(code);
                });
            } else {
                fallbackCopyToClipboard(code);
            }
        });
        
        // Fallback copy to clipboard function
        function fallbackCopyToClipboard(text) {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('Promo code copied to clipboard: ' + text);
                } else {
                    alert('Failed to copy promo code. Please copy it manually: ' + text);
                }
            } catch (err) {
                alert('Failed to copy promo code. Please copy it manually: ' + text);
            }
            
            $temp.remove();
        }
        
        // Initialize Select2 for student profile page
        if ($('.school-manager-student-profile').length) {
            // Initialize class selection
            $('#school_classes').select2({
                placeholder: school_manager_lite_vars.i18n.select_classes || 'Select classes',
                allowClear: true,
                width: '100%'
            });
            
            // Initialize teacher selection
            $('#school_teacher').select2({
                placeholder: school_manager_lite_vars.i18n.select_teacher || 'Select a teacher',
                allowClear: true,
                width: '100%'
            });
            
            // Handle class selection change to update available teachers
            $('#school_classes').on('change', function() {
                const selectedClasses = $(this).val() || [];
                
                if (selectedClasses.length > 0) {
                    // Enable teacher selection
                    $('#school_teacher').prop('disabled', false);
                    
                    // If we want to filter teachers by selected classes in the future
                    // we can make an AJAX call here to get teachers for the selected classes
                } else {
                    // Disable teacher selection if no classes are selected
                    $('#school_teacher').val(null).trigger('change');
                    $('#school_teacher').prop('disabled', true);
                }
            });
            
            // Trigger change on page load to set initial state
            $('#school_classes').trigger('change');
        }
        
        // Date picker for expiry date fields (if datepicker is available)
        if ($.datepicker) {
            $('input[type="date"]').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
    });
    
})(jQuery);
