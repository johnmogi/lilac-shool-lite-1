/**
 * Class Edit JS
 * Enhances the class edit page functionality
 */

jQuery(document).ready(function($) {
    // Style the teacher selection dropdown
    $('.teacher-select').css({
        'min-width': '300px'
    });

    // Add highlight to the current teacher info box
    $('.current-teacher-info').css({
        'background-color': '#f8f8f8',
        'border-left': '4px solid #2271b1',
        'padding': '10px 15px',
        'margin-top': '10px'
    });

    // Teacher selection enhancement
    $('#teacher_id').on('change', function() {
        var teacherId = $(this).val();
        if (teacherId) {
            // Show loading indicator
            $('#teacher-info').html('<span class="spinner is-active"></span>');
            
            // Get teacher info via AJAX
            $.ajax({
                url: schoolManagerAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_teacher_info',
                    teacher_id: teacherId,
                    nonce: schoolManagerAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('#teacher-info').html(response.data.html).fadeIn();
                    } else {
                        $('#teacher-info').html('').hide();
                    }
                },
                error: function() {
                    $('#teacher-info').html('').hide();
                }
            });
        } else {
            // Clear teacher info if no teacher selected
            $('#teacher-info').html('').hide();
        }
    });
    
    // Initialize current teacher info if present
    if ($('#teacher_id').val()) {
        $('#teacher_id').trigger('change');
    }
    
    // Initialize modal functionality
    var modal = $('#student-assignment-modal');
    var closeModal = $('.close-modal');
    
    // Show modal
    $('#assign-students-btn').on('click', function(e) {
        e.preventDefault();
        modal.show();
        // Focus on search field
        $('#student-search').focus();
    });
    
    // Hide modal on close button click
    closeModal.on('click', function() {
        modal.hide();
    });
    
    // Hide modal when clicking outside of it
    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.hide();
        }
    });
    
    // Filter students
    $('#student-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.student-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
        
        // Show/hide no results message
        var visibleStudents = $('.student-item:visible').length;
        if (visibleStudents === 0 && value !== '') {
            $('#no-students-found').show();
        } else {
            $('#no-students-found').hide();
        }
    });
    
    // Select/deselect all students
    $('#select-all-students').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.student-checkbox:visible').prop('checked', isChecked);
        updateSelectedCount();
    });
    
    // Update selected count when individual checkboxes change
    $(document).on('change', '.student-checkbox', function() {
        updateSelectedCount();
    });
    
    // Update the count of selected students
    function updateSelectedCount() {
        var selectedCount = $('.student-checkbox:checked').length;
        $('#selected-count').text(selectedCount);
        
        // Enable/disable add button
        if (selectedCount > 0) {
            $('#add-students-btn').prop('disabled', false);
        } else {
            $('#add-students-btn').prop('disabled', true);
        }
    }
    
    // Initialize the selected count
    updateSelectedCount();
    
    // Handle student removal confirmation
    $('.remove-student-btn').on('click', function(e) {
        if (!confirm(schoolManagerAdmin.i18n.confirm_remove_student)) {
            e.preventDefault();
        }
    });
    
    // Apply RTL styling to Hebrew elements
    $('[lang="he"]').each(function() {
        $(this).css({
            'font-family': 'Open Sans Hebrew, Arial, sans-serif',
            'direction': 'rtl',
            'unicode-bidi': 'embed',
            'text-align': 'right'
        });
    });

    // Add students to class functionality
    $('#add-students-to-class-button').on('click', function(e) {
        e.preventDefault();
        $('#add-students-modal').show();
    });

    // Close modal
    $('.close-modal').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.modal').hide();
    });

    // Filter students list in modal
    $('#student-search').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        $('.student-item').each(function() {
            var studentName = $(this).find('.student-name').text().toLowerCase();
            if (studentName.indexOf(searchText) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Add teacher info toggle
    $('.toggle-teacher-info').on('click', function(e) {
        e.preventDefault();
        $('#teacher-details').slideToggle();
    });

});
