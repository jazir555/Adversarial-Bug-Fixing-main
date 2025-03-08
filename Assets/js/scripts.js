jQuery(document).ready(function($) {
    // Code editor initialization (if needed, or can be in separate file)

    // Code review form handling
    $('.code-review-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        var form = $(this);

        form.find('.loading-indicator').show();
        form.find('.notice-error').hide(); // Hide previous error messages, if any

        $.post(ajaxurl, formData, function(response) {
            form.find('.loading-indicator').hide();
            if (response.success) {
                $('.review-results').html('<pre>' + response.data.review + '</pre>').show();
                 $('.notice-success').show(); // Show success message
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error.';
                form.find('.notice-error').text('Review generation failed: ' + errorMessage).show();
                $('.review-results').empty().hide(); // Hide review results area on error
                 $('.notice-success').hide(); // Hide success message if error occurs
            }
        }).fail(function() {
            form.find('.loading-indicator').hide();
            form.find('.notice-error').text('Error communicating with the server.').show();
            $('.review-results').empty().hide(); // Hide review results area on error
             $('.notice-success').hide(); // Hide success message if error occurs
        });
    });

    // CI Pipeline actions (placeholders)
    $('.pipelines-list').on('click', '.run-pipeline', function() {
        var pipelineId = $(this).data('id');
        alert('Run pipeline functionality not implemented yet for pipeline ID: ' + pipelineId);
    });

    $('.pipelines-list').on('click', '.edit-pipeline', function() {
        var pipelineId = $(this).data('id');
        alert('Edit pipeline functionality not implemented yet for pipeline ID: ' + pipelineId);
    });

    $('.pipelines-list').on('click', '.delete-pipeline', function() {
        var pipelineId = $(this).data('id');
        alert('Delete pipeline functionality not implemented yet for pipeline ID: ' + pipelineId);
    });
});
