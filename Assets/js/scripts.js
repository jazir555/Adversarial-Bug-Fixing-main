jQuery(document).ready(function($) {

    // Theme select change
    $(document).on('change', '.adversarial-theme-select', function() {
        var select = $(this);
        var editorId = select.data('editor-id');
        var theme = select.val();
        var editorWrapper = $('#' + editorId);
        var editorContainer = editorWrapper.find('.code-editor-container');
        var editor = ace.edit(editorContainer[0]);

        editor.setTheme("ace/theme/" + theme);
        editorContainer.data('theme', theme); // Update data attribute

        // Save theme preference to user meta - AJAX call
        $.ajax({
            url: adversarialEditorSettings.ajax_url, // Or a specific action URL for settings
            type: 'POST',
            data: {
                action: 'adversarial_save_editor_theme_preference',
                nonce: adversarialEditorSettings.save_nonce, // Or settings nonce - adjust nonce if needed
                theme: theme
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Failed to save theme preference.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error saving theme preference', status, error);
            }
        });
    });

    // Save code snippet
    $(document).on('click', '.adversarial-save-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var code = editorWrapper.find('.code-editor-value').val();
        var language = editorWrapper.find('.code-editor-container').data('language');
        var snippetId = editorWrapper.data('snippet-id'); // Get snippet ID if available for updates

        button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_save_code_snippet',
                nonce: adversarialEditorSettings.save_nonce,
                code: code,
                language: language,
                snippet_id: snippetId // Send snippet ID for updates
            },
            success: function(response) {
                button.prop('disabled', false).text('Save Code');
                if (response.success) {
                    alert(response.data.message);
                    if (!snippetId && response.data.snippet_id) {
                        editorWrapper.data('snippet-id', response.data.snippet_id); // Store new snippet ID for future updates
                    }
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Save Code');
                console.error("AJAX error", status, error);
                alert('Failed to save code snippet.');
            }
        });
    });


    // Load code snippet
    $(document).on('click', '.adversarial-load-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var snippetId = editorWrapper.data('snippet-id');

        if (!snippetId) {
            alert('No snippet ID specified to load.');
            return;
        }

        button.prop('disabled', true).text('Loading...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_load_code_snippet',
                nonce: adversarialEditorSettings.load_nonce,
                snippet_id: snippetId
            },
            success: function(response) {
                button.prop('disabled', false).text('Load Code');
                if (response.success) {
                    ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue(response.data.code);
                    editorWrapper.find('.code-editor-value').val(response.data.code);
                    alert('Code snippet loaded successfully!');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Load Code');
                console.error("AJAX error", status, error);
                alert('Failed to load code snippet.');
            }
        });
    });

    // Load code snippet list
    $(document).on('click', '.adversarial-load-list-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var snippetListButton = $(this); // Keep track of the button
        var snippetListContainer = editorWrapper.find('.code-editor-snippet-list-container');


        button.prop('disabled', true).text('Loading List...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_load_code_snippet_list',
                nonce: adversarialEditorSettings.load_list_nonce
            },
            success: function(response) {
                button.prop('disabled', false).text('Load List');
                if (response.success) {
                    if (response.data.snippets && response.data.snippets.length > 0) {
                        var snippetList = $('<ul class="adversarial-snippet-list"></ul>');
                        response.data.snippets.forEach(function(snippet) {
                            var listItem = $('<li></li>').html('ID: ' + snippet.id + ', Language: ' + snippet.language + ', Created: ' + snippet.created_at + ' <button class="button button-small adversarial-load-snippet-item" data-snippet-id="' + snippet.id + '">Load</button>');
                            listItem.data('snippet-id', snippet.id); // Store snippet ID
                            snippetList.append(listItem);
                        });

                        // Check if a snippet list already exists and replace it, otherwise append
                        if(snippetListContainer.length) {
                            snippetListContainer.empty().append(snippetList);
                        } else {
                            editorWrapper.find('.code-editor-controls').after('<div class="code-editor-snippet-list-container"></div>');
                            editorWrapper.find('.code-editor-snippet-list-container').append(snippetList);
                        }
                    } else {
                        alert('No code snippets saved yet.');
                         if(snippetListContainer.length) {
                            snippetListContainer.empty().text('No code snippets saved yet.');
                        } else {
                            editorWrapper.find('.code-editor-controls').after('<div class="code-editor-snippet-list-container">No code snippets saved yet.</div>');
                        }
                    }
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Load List');
                console.error("AJAX error", status, error);
                alert('Failed to load code snippet list.');
            }
        });
    });

    // Load snippet item from list
    $(document).on('click', '.adversarial-load-snippet-item', function(e) {
        e.preventDefault();
        var button = $(this);
        var snippetId = button.data('snippet-id');
        var editorWrapper = button.closest('.adversarial-code-editor-wrapper');
        var editorId = editorWrapper.attr('id');

        loadSnippetIntoEditor(editorId, snippetId);
        editorWrapper.data('snippet-id', snippetId); // Update current editor's snippet ID
        $('.adversarial-snippet-list li').removeClass('selected-snippet'); // Clear previous selections
        button.closest('li').addClass('selected-snippet'); // Highlight selected item
    });


    // Function to load snippet into editor
    function loadSnippetIntoEditor(editorId, snippetId) {
        var editorWrapper = $('#' + editorId);
        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_load_code_snippet',
                nonce: adversarialEditorSettings.load_nonce,
                snippet_id: snippetId
            },
            success: function(response) {
                if (response.success) {
                    ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue(response.data.code);
                    editorWrapper.find('.code-editor-value').val(response.data.code);
                    alert('Code snippet loaded successfully!');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error", status, error);
                alert('Failed to load code snippet.');
            }
        });
    }


    // Delete code snippet
    $(document).on('click', '.adversarial-delete-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var snippetId = editorWrapper.data('snippet-id');

        if (!snippetId) {
            alert('No snippet loaded to delete.');
            return;
        }

        if (confirm('Are you sure you want to delete this code snippet?')) {
            button.prop('disabled', true).text('Deleting...');
            $.ajax({
                url: adversarialEditorSettings.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'adversarial_delete_code_snippet',
                    nonce: adversarialEditorSettings.delete_nonce,
                    snippet_id: snippetId
                },
                success: function(response) {
                    button.prop('disabled', false).text('Delete Code');
                    if (response.success) {
                        alert(response.data.message);
                        // Optionally clear editor and snippet ID after deletion
                        ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue('');
                        editorWrapper.find('.code-editor-value').val('');
                        editorWrapper.data('snippet-id', null);
                        // Remove snippet list if it exists to refresh list on next load
                        editorWrapper.find('.adversarial-snippet-list-container').remove();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    button.prop('disabled', false).text('Delete Code');
                    console.error("AJAX error", status, error);
                    alert('Failed to delete code snippet.');
                }
            });
        }
    });

    // Clear code editor
    $(document).on('click', '.adversarial-clear-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);

        editor.setValue(''); // Clear Ace Editor content
        editorWrapper.find('.code-editor-value').val(''); // Clear hidden input value
    });

    // Copy code to clipboard
    $(document).on('click', '.adversarial-copy-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var code = editorWrapper.find('.code-editor-value').val();

        if (code) {
            navigator.clipboard.writeText(code).then(function() {
                alert('Code copied to clipboard!');
            }).catch(function(err) {
                console.error('Failed to copy code: ', err);
                alert('Failed to copy code to clipboard.');
            });
        } else {
            alert('No code to copy.');
        }
    });

    // Download code snippet
    $(document).on('click', '.adversarial-download-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var code = editorWrapper.find('.code-editor-value').val();
        var language = editorWrapper.find('.code-editor-container').data('language') || 'text'; // Default to text if language not set
        var filename = 'code-snippet-' + editorId + '.' + language; // Basic filename

        if (code) {
            var blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var downloadLink = $('<a></a>');

            downloadLink.attr('href', url);
            downloadLink.attr('download', filename);
            downloadLink[0].click(); // Programmatically trigger download

            URL.revokeObjectURL(url); // Clean up URL object
        } else {
            alert('No code to download.');
        }
    });

    // Language select change
    $(document).on('change', '.adversarial-language-select', function() {
        var select = $(this);
        var editorId = select.data('editor-id');
        var language = select.val();
        var editorWrapper = $('#' + editorId);
        var editorContainer = editorWrapper.find('.code-editor-container');
        var editor = ace.edit(editorContainer[0]);

        editor.session.setMode("ace/mode/" + language);
        editorContainer.data('language', language); // Update data attribute
    });
});
