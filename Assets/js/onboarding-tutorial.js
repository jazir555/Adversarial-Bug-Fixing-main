jQuery(document).ready(function($) {
    $('.next-step').on('click', function(e) {
        e.preventDefault();
        var currentStep = $(this).closest('.tutorial-step');
        var nextStep = currentStep.next('.tutorial-step');
        
        if (nextStep.length) {
            currentStep.removeClass('active');
            nextStep.addClass('active');
        }
    });

    $('.complete-tutorial').on('click', function(e) {
        e.preventDefault();
        var data = {
            action: 'adversarial_complete_onboarding',
            nonce: ajax_object.nonce
        };

        $.post(ajax_object.ajax_url, data, function(response) {
            $('.adversarial-onboarding-tutorial').fadeOut(500, function() {
                $(this).remove();
            });
        });
    });
});
