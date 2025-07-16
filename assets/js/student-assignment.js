/**
 * Student Assignment JS
 * Handles bulk assigning students to a class
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
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
    
    // Handle form submission for student assignment
    $('#assign-students-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#add-students-btn');
        var selectedStudents = $('.student-checkbox:checked');
        
        if (selectedStudents.length === 0) {
            return; // No students selected
        }
        
        // Disable button and show loading
        submitBtn.prop('disabled', true).addClass('button-busy');
        submitBtn.html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span> ' + 
                     schoolManagerAdmin.i18n.processing);
        
        // Collect selected student IDs
        var studentIds = [];
        selectedStudents.each(function() {
            studentIds.push($(this).val());
        });
        
        // Send AJAX request
        $.ajax({
            url: schoolManagerAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'bulk_assign_students',
                nonce: schoolManagerAdmin.nonce,
                student_ids: studentIds,
                class_id: $('input[name="class_id"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    var message = response.data.message;
                    if (response.data.hebrew) {
                        message += ' / ' + response.data.hebrew;
                    }
                    
                    // Close modal
                    modal.hide();
                    
                    // Reload page with success parameters
                    window.location.href = window.location.pathname + 
                        window.location.search.replace(/[&?]students_added=[^&]+/, '') + 
                        (window.location.search.indexOf('?') === -1 ? '?' : '&') + 
                        'students_added=' + response.data.success_count;
                } else {
                    // Show error message
                    alert(response.data.message);
                    submitBtn.prop('disabled', false).removeClass('button-busy');
                    submitBtn.html(schoolManagerAdmin.i18n.add_selected_students);
                }
            },
            error: function() {
                alert(schoolManagerAdmin.i18n.server_error);
                submitBtn.prop('disabled', false).removeClass('button-busy');
                submitBtn.html(schoolManagerAdmin.i18n.add_selected_students);
            }
        });
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
});
