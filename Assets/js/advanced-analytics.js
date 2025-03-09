jQuery(document).ready(function($) {

    function trackFrontendEvent(eventType, eventDetails) {
        $.ajax({
            url: advancedAnalyticsSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'adversarial_log_frontend_analytics',
                nonce: advancedAnalyticsSettings.nonce,
                event_type: eventType,
                event_details: eventDetails
            },
            success: function(response) {
                if(response.success) {
                    console.log('Frontend analytics event logged successfully');
                } else {
                    console.error('Failed to log frontend analytics event', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed', textStatus, errorThrown);
            }
        });
    }

    // Example usage: Track page view
    trackFrontendEvent('page_view', document.title);

    // Example usage: Track button clicks (you can attach this to specific buttons)
    $('button').on('click', function() {
        trackFrontendEvent('button_click', $(this).text());
    });

    // Add more event tracking as needed (e.g., form submissions, link clicks, etc.)

});
