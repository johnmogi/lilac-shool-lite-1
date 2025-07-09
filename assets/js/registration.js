/**
 * School Manager Lite - Registration Form Handling
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Handle registration form submission
        $('#school-manager-registration').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $messages = $form.find('.registration-messages');
            
            // Clear previous messages
            $messages.removeClass('error success').empty();
            
            // Validate form
            let isValid = true;
            $form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                showMessage($messages, 'Please fill in all required fields.', 'error');
                return;
            }
            
            // Prepare form data
            const formData = $form.serialize();
            
            // Submit form via AJAX
            $.ajax({
                type: 'POST',
                url: schoolManagerLite.ajaxurl,
                data: {
                    action: 'school_manager_register_user',
                    nonce: schoolManagerLite.nonce,
                    form_data: formData
                },
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true).text('Registering...');
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($messages, response.data.message, 'success');
                        $form[0].reset();
                        
                        // Redirect if URL is provided
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        showMessage($messages, response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Registration error:', error);
                    showMessage($messages, 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Register');
                }
            });
        });
        
        // Handle promo code form submission
        $('#school-manager-promo').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $promoInput = $form.find('input[name="promo_code"]');
            const $messages = $form.find('.promo-messages');
            
            // Clear previous messages
            $messages.removeClass('error success').empty();
            
            // Validate promo code
            const promoCode = $promoInput.val().trim();
            if (!promoCode) {
                showMessage($messages, 'Please enter a promo code.', 'error');
                $promoInput.addClass('error');
                return;
            }
            
            $promoInput.removeClass('error');
            
            // Submit promo code via AJAX
            $.ajax({
                type: 'POST',
                url: schoolManagerLite.ajaxurl,
                data: {
                    action: 'school_manager_redeem_promo',
                    nonce: schoolManagerLite.nonce,
                    promo_code: promoCode
                },
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($messages, response.data.message, 'success');
                        $form[0].reset();
                        
                        // Redirect if URL is provided
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        showMessage($messages, response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Promo code error:', error);
                    showMessage($messages, 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Redeem');
                }
            });
        });
        
        // Helper function to show messages
        function showMessage($container, message, type) {
            $container
                .removeClass('error success')
                .addClass(type)
                .html('<p>' + message + '</p>')
                .show();
                
            // Scroll to message
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 500);
        }
    });
    
})(jQuery);
