jQuery(document).ready(function($) {
    // Add snippet modal handling
    $('.add-snippet').on('click', function() {
        $('.add-snippet-modal').show();
    });

    $('.cancel-add-snippet').on('click', function(e) {
        e.preventDefault();
        $('.add-snippet-modal').hide();
    });

    // Add snippet form submission
    $('.add-snippet-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        var form = $(this);

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                // Reload snippets list or append new snippet to the list
                alert('Snippet saved successfully!');
                $('.add-snippet-modal').hide();
                // TODO: Update snippets list dynamically
            } else {
                alert('Error saving snippet: ' + response.data.message);
            }
        }).fail(function() {
            alert('Error saving snippet: Error communicating with the server.');
        });
    });

    // Copy snippet functionality
    $('.snippets-list').on('click', '.copy-snippet', function() {
        var snippetId = $(this).data('id');
        var snippetCode = $(this).closest('.snippet-item').find('.snippet-code pre code').text();

        navigator.clipboard.writeText(snippetCode).then(function() {
            alert('Code snippet copied to clipboard!');
        }).catch(function(err) {
            console.error('Failed to copy snippet: ', err);
            alert('Failed to copy code snippet to clipboard.');
        });
    });

    // Insert snippet functionality (placeholder)
    $('.snippets-list').on('click', '.insert-snippet', function() {
        var snippetId = $(this).data('id');
        var snippetCode = $(this).closest('.snippet-item').find('.snippet-code pre code').text();
        // TODO: Implement code insertion into editor
        alert('Insert snippet functionality not implemented yet. Code snippet: \n' + snippetCode);
    });
});
