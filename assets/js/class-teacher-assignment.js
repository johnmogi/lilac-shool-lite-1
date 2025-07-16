/**
 * Class-Teacher Assignment JS
 * Handles bulk teacher assignments to classes
 */

jQuery(document).ready(function($) {
    
    // Initialize bulk teacher assignment functionality
    var bulkTeacherAction = {
        init: function() {
            // Add bulk teacher assignment dropdown to bulk actions
            $('.bulkactions').each(function() {
                var $this = $(this);
                if (!$this.find('select[name^="action"]').find('option[value="bulk_assign_teacher"]').length) {
                    $this.find('select[name^="action"]').append(
                        $('<option>').val('bulk_assign_teacher').text(schoolManagerAdmin.bulk_assign_teacher_text)
                    );
                }
            });
            
            // Handle bulk action selection
            $('#doaction, #doaction2').on('click', function(e) {
                var actionSelect = $(this).prev('select');
                if (actionSelect.val() === 'bulk_assign_teacher') {
                    e.preventDefault();
                    bulkTeacherAction.showTeacherSelection();
                }
            });
            
            // Handle teacher selection form submission
            $(document).on('click', '#assign-teacher-submit', function(e) {
                e.preventDefault();
                bulkTeacherAction.assignTeacher();
            });
            
            // Handle quick assign teacher links
            $('.row-actions .assign-teacher').on('click', function(e) {
                e.preventDefault();
                var classId = $(this).data('class-id');
                bulkTeacherAction.showTeacherSelection([classId]);
            });
            
            // Close modal on cancel or X
            $(document).on('click', '.close-teacher-modal, #cancel-teacher-assign', function() {
                $('#teacher-assignment-modal').hide();
            });
        },
        
        // Show teacher selection modal
        showTeacherSelection: function(classIds) {
            var selectedClasses = classIds || [];
            
            // If no class IDs provided, get them from checked checkboxes
            if (!classIds) {
                $('input[name="class[]"]:checked').each(function() {
                    selectedClasses.push($(this).val());
                });
            }
            
            // Make sure we have classes selected
            if (selectedClasses.length === 0) {
                alert(schoolManagerAdmin.no_classes_selected);
                return;
            }
            
            // Store selected class IDs
            $('#selected-class-ids').val(selectedClasses.join(','));
            
            // Show number of selected classes
            var classCount = selectedClasses.length;
            var message = schoolManagerAdmin.assign_teacher_message.replace('%d', classCount);
            $('#teacher-assignment-count').text(message);
            
            // Show the modal
            $('#teacher-assignment-modal').show();
        },
        
        // Assign teacher to selected classes
        assignTeacher: function() {
            var teacherId = $('#teacher-id').val();
            var classIds = $('#selected-class-ids').val().split(',');
            
            if (!teacherId) {
                alert(schoolManagerAdmin.select_teacher);
                return;
            }
            
            // Show loading indicator
            $('#teacher-assignment-modal .spinner').addClass('is-active');
            $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', true);
            
            // Make AJAX call to assign teacher
            $.ajax({
                url: schoolManagerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_assign_teacher',
                    class_ids: classIds,
                    teacher_id: teacherId,
                    nonce: schoolManagerAdmin.nonce
                },
                success: function(response) {
                    // Hide modal
                    $('#teacher-assignment-modal').hide();
                    
                    if (response.success) {
                        // Show success message
                        var notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                        $('.wrap h1').after(notice);
                        
                        // Update teacher names in the table
                        if (response.data.updates) {
                            $.each(response.data.updates, function(classId, teacherName) {
                                $('.class-row-' + classId + ' .column-teacher').text(teacherName);
                            });
                        }
                        
                        // Remove notices after 5 seconds
                        setTimeout(function() {
                            notice.slideUp('fast', function() {
                                $(this).remove();
                            });
                        }, 5000);
                    } else {
                        // Show error message
                        var notice = $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                        $('.wrap h1').after(notice);
                    }
                    
                    // Reset form
                    $('#teacher-id').val('');
                    $('#selected-class-ids').val('');
                    $('#teacher-assignment-modal .spinner').removeClass('is-active');
                    $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
                },
                error: function() {
                    $('#teacher-assignment-modal').hide();
                    var notice = $('<div class="notice notice-error is-dismissible"><p>' + schoolManagerAdmin.ajax_error + '</p></div>');
                    $('.wrap h1').after(notice);
                    $('#teacher-assignment-modal .spinner').removeClass('is-active');
                    $('#assign-teacher-submit, #cancel-teacher-assign').prop('disabled', false);
                }
            });
        }
    };
    
    // Initialize
    bulkTeacherAction.init();
});
