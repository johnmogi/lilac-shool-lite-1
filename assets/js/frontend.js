/**
 * School Manager Lite Frontend Scripts
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initPromoCodeRedemption();
    });
    
    /**
     * Initialize promo code redemption form
     */
    function initPromoCodeRedemption() {
        const $form = $('#school-promo-form');
        
        if (!$form.length) {
            return;
        }
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const $button = $('#school-redeem-button');
            const $loading = $('.school-loading');
            const $messageContainer = $('.school-message-container');
            
            // Get form data
            const promoCode = $('#promo_code').val().trim();
            const studentName = $('#student_name').val().trim();
            const studentPhone = $('#student_phone').val().trim();
            const studentId = $('#student_id').val().trim();
            
            // Basic validation
            if (!promoCode) {
                showMessage(school_manager_lite.i18n.code_required, 'error');
                return;
            }
            
            if (!studentName || !studentPhone || !studentId) {
                showMessage(school_manager_lite.i18n.all_fields_required, 'error');
                return;
            }
            
            // Disable form and show loading
            $button.prop('disabled', true);
            $loading.show();
            $messageContainer.empty();
            
            // Send AJAX request
            $.ajax({
                url: school_manager_lite.ajax_url,
                type: 'POST',
                data: {
                    action: 'school_redeem_promo_code',
                    promo_code: promoCode,
                    student_name: studentName,
                    student_phone: studentPhone,
                    student_id: studentId,
                    nonce: school_manager_lite.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showMessage(response.data.message, 'success');
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Redirect after successful login
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else if (school_manager_lite.redirect_url) {
                            setTimeout(function() {
                                window.location.href = school_manager_lite.redirect_url;
                            }, 2000);
                        }
                    } else {
                        // Show error message
                        showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage(school_manager_lite.i18n.error, 'error');
                },
                complete: function() {
                    // Re-enable form and hide loading
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        });
        
        /**
         * Show message in the form
         * 
         * @param {string} message Message text
         * @param {string} type Message type (error|success)
         */
        function showMessage(message, type) {
            const $messageContainer = $('.school-message-container');
            $messageContainer.html('<div class="school-' + type + '">' + message + '</div>');
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $messageContainer.offset().top - 100
            }, 200);
        }
    }
    
})(jQuery);
