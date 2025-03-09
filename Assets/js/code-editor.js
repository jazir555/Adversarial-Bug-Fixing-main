jQuery(document).ready(function($) {
    // Initialize Ace Editor instances
    $('.code-editor-container').each(function() {
        var container = $(this);
        var editorId = container.closest('.adversarial-code-editor-wrapper').attr('id');
        var language = container.data('language');
        var theme = container.data('theme');
        var initialCode = container.text();

        container.empty(); // Clear initial text content

        var editor = ace.edit(container[0]);
        editor.setTheme("ace/theme/" + theme);
        editor.session.setMode("ace/mode/" + language);
        editor.setValue(initialCode);
        editor.clearSelection();

        // Enable line numbers
        editor.renderer.setShowGutter(true);

        // Enable code folding
        editor.getSession().setFoldStyle("markbeginend");

        // Enable autocompletion and live autocompletion
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            enableSnippets: true // Enable code snippets
        });

         // Live autocompletion for keywords and custom snippets
         editor.completers = [
          {
            getCompletions: function(editor, session, pos, prefix, callback) {
              var languageMode = editor.session.$modeId.split('/').pop(); // Extract language from mode ID
              var suggestions = getLanguageKeywords(languageMode); // Fetch keywords based on language

              // Add custom snippet suggestions here if needed, or fetch from another source

              if (prefix.length === 0) {
                  callback(null, []);
                  return;
              }

              var completionList = suggestions.map(function(word) {
                  return {
                      caption: word,
                      value: word,
                      meta: languageMode + " keyword",
                      score: 1
                  };
              });
              callback(null, completionList);
            }
          }
        ];


        // Real-time linting on code change
        editor.getSession().on('change', function(e) {
            var code = editor.getValue();
            var language = editorWrapper.find('.code-editor-container').data('language');

            // Perform linting via AJAX
            $.ajax({
                url: adversarialEditorSettings.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'adversarial_lint_code', // Call the existing ajax_lint_code function in PHP
                    nonce: adversarialEditorSettings.lint_nonce,
                    code: code,
                    language: language
                },
                success: function(response) {
                    if (response.success && response.data.lint_errors) {
                        var annotations = response.data.lint_errors.map(function(error) {
                            // Assuming error format from PHP is compatible with Ace annotations
                            return {
                                row: error.row - 1, // Ace row is 0-based
                                column: error.column - 1, // Ace column is 0-based
                                text: error.text, // Error message
                                type: error.type // 'error' or 'warning'
                            };
                        });
                        editor.session.setAnnotations(annotations);
                    } else {
                        editor.session.clearAnnotations(); // Clear annotations if no errors
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error during linting", status, error);
                    editor.session.clearAnnotations(); // Clear annotations on error
                }
            });
        });

        // Update hidden input on editor changes
        editor.getSession().on('change', function() {
            $('#' + editorId).find('.code-editor-value').val(editor.getValue());
        });
    });

    // Function to get language-specific keywords
    function getLanguageKeywords(languageMode) {
        // This is a basic example, extend this to include more keywords for each language
        if (languageMode === 'python') {
            return ['def', 'class', 'if', 'for', 'while', 'import', 'return', 'try', 'except', 'finally', 'with', 'as', 'lambda', 'global', 'nonlocal', 'assert', 'break', 'continue', 'del', 'elif', 'else', 'except', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'try', 'while', 'with', 'yield'];
        } else if (languageMode === 'javascript') {
            return ['function', 'var', 'let', 'const', 'class', 'if', 'for', 'while', 'return', 'function', 'this', 'new', 'typeof', 'instanceof', 'try', 'catch', 'finally', 'throw', 'debugger', 'with', 'break', 'continue', 'delete', 'in', 'instanceof', 'new', 'return', 'this', 'typeof', 'void', 'while', 'with', 'yield', 'enum', 'export', 'import', 'super', 'implements', 'interface', 'let', 'package', 'private', 'protected', 'public', 'static', 'await', 'async'];
        } else if (languageMode === 'java') {
            return ['public', 'private', 'protected', 'class', 'interface', 'void', 'int', 'String', 'import', 'package', 'boolean', 'byte', 'char', 'double', 'float', 'long', 'short', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'default', 'break', 'continue', 'return', 'try', 'catch', 'finally', 'throw', 'throws', 'abstract', 'final', 'native', 'static', 'strictfp', 'synchronized', 'transient', 'volatile', 'assert', 'enum', 'extends', 'final', 'implements', 'instanceof', 'interface', 'new', 'package', 'private', 'protected', 'public', 'static', 'super', 'switch', 'this', 'throw', 'throws', 'try', 'void', 'while'];
        } else if (languageMode === 'php') {
            return ['$variable', 'function', 'class', 'namespace', 'use', 'echo', 'print', 'array', '__construct', '__destruct', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];
        }
        return [];
    }


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
                button.prop('disabled', false).text('Save');
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
                button.prop('disabled', false).text('Save');
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
                            var listItem = $('<li></li>').text('ID: ' + snippet.id + ', Language: ' + snippet.language + ', Created: ' + snippet.created_at);
                            listItem.data('snippet-id', snippet.id); // Store snippet ID
                            listItem.on('click', function() {
                                var selectedSnippetId = $(this).data('snippet-id');
                                editorWrapper.data('snippet-id', selectedSnippetId); // Update current editor's snippet ID
                                $('.adversarial-snippet-list li').removeClass('selected-snippet'); // Clear previous selections
                                $(this).addClass('selected-snippet'); // Highlight selected item
                                // Load the selected snippet into the editor immediately
                                loadSnippetIntoEditor(editorId, selectedSnippetId);
                            });
                            snippetList.append(listItem);
                        });

                        // Check if a snippet list already exists and replace it, otherwise append
                        var existingList = editorWrapper.find('.adversarial-snippet-list');
                        if(existingList.length) {
                            existingList.replaceWith(snippetList);
                        } else {
                            editorWrapper.find('.code-editor-controls').append(snippetList);
                        }
                    } else {
                        alert('No code snippets saved yet.');
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
                        editorWrapper.find('.adversarial-snippet-list').remove();
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

    // Theme select change
    $(document).on('change', '.adversarial-theme-select', function() {
        var select = $(this);
        var editorId = select.data('editor-id');
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
                action: 'adversarial_save_editor_theme_preference', // Define this action - to be implemented in PHP if needed
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

    // Find code
    $(document).on('click', '.adversarial-find-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('find');
    });

    // Replace code
    $(document).on('click', '.adversarial-replace-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('replace');
    });

    // Undo code
    $(document).on('click', '.adversarial-undo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.undo();
    });

    // Redo code
    $(document).on('click', '.adversarial-redo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.redo();
    });

    // Format code snippet
    $(document).on('click', '.adversarial-format-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        var code = editor.getValue();
        var language = editorWrapper.find('.code-editor-container').data('language');

        button.prop('disabled', true).text('Formatting...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_format_code_snippet',
                nonce: adversarialEditorSettings.format_nonce,
                code: code,
                language: language
            },
            success: function(response) {
                button.prop('disabled', false).text('Format Code');
                if (response.success && response.data.formatted_code) {
                    ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue(response.data.formatted_code);
                    editorWrapper.find('.code-editor-value').val(response.data.formatted_code);
                    alert('Code formatted successfully!');
                } else {
                    alert('Error formatting code: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Format Code');
                console.error("AJAX error", status, error);
                alert('Failed to format code.');
            }
        });
    });

    // Lint code
    $(document).on('click', '.adversarial-lint-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        var code = editor.getValue();
        var language = editorWrapper.find('.code-editor-container').data('language');

        button.prop('disabled', true).text('Linting...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_lint_code',
                nonce: adversarialEditorSettings.lint_nonce,
                code: code,
                language: language
            },
            success: function(response) {
                button.prop('disabled', false).text('Lint Code');
                if (response.success && response.data.lint_errors) {
                    // Handle and display lint errors
                    codeEditorInstance.displayLintErrors(editor, response.data.lint_errors);
                } else if (response.success && response.data.lint_errors && response.data.lint_errors.length === 0) { // Check if lint_errors is defined and has length
                    editor.session.clearAnnotations();
                    alert('No linting errors found.');
                } else {
                    alert('Error during linting: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Lint Code');
                console.error("AJAX error", status, error);
                alert('Failed to perform linting.');
            }
        });
    });

    var codeEditorInstance = {
        handleLinting: function(editor) {
            var codeEditor = this;
            editor.getSession().on('changeAnnotation', function() {
                var annotations = editor.getSession().getAnnotations();
                codeEditor.displayLintErrors(editor, annotations); // Pass editor instance
            });
        },

        displayLintErrors: function(editor, annotations) {
            var errorList = jQuery('#adversarial-lint-errors');
            errorList.empty();
            editor.session.clearAnnotations(); // Clear existing annotations

            if (annotations && annotations.length > 0) {
                var aceAnnotations = [];
                annotations.forEach(function(annotation) {
                    if (annotation.type === 'error' || annotation.type === 'warning') {
                        errorList.append('<li class="' + annotation.type + '">Line ' + annotation.row + ': ' + annotation.text + '</li>');
                        aceAnnotations.push({
                            row: annotation.row - 1, // Ace editor line numbers are 0-based
                            column: annotation.column,
                            text: annotation.text,
                            type: annotation.type
                        });
                    }
                });
                editor.session.setAnnotations(aceAnnotations); // Set annotations for Ace editor
                errorList.show();
            } else {
                errorList.hide();
            }
        }
    };
});
jQuery(document).ready(function($) {
    // Initialize Ace Editor instances
    $('.code-editor-container').each(function() {
        var container = $(this);
        var editorId = container.closest('.adversarial-code-editor-wrapper').attr('id');
        var language = container.data('language');
        var theme = container.data('theme');
        var initialCode = container.text();

        container.empty(); // Clear initial text content

        var editor = ace.edit(container[0]);
        editor.setTheme("ace/theme/" + theme);
        editor.session.setMode("ace/mode/" + language);
        editor.setValue(initialCode);
        editor.clearSelection();

        // Enable line numbers
        editor.renderer.setShowGutter(true);

        // Enable code folding
        editor.getSession().setFoldStyle("markbeginend");

        // Enable autocompletion and live autocompletion
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            enableSnippets: true // Enable code snippets
        });

         // Live autocompletion for keywords and custom snippets
         editor.completers = [
          {
            getCompletions: function(editor, session, pos, prefix, callback) {
              var languageMode = editor.session.$modeId.split('/').pop(); // Extract language from mode ID
              var suggestions = getLanguageKeywords(languageMode); // Fetch keywords based on language

              // Add custom snippet suggestions here if needed, or fetch from another source

              if (prefix.length === 0) {
                  callback(null, []);
                  return;
              }

              var completionList = suggestions.map(function(word) {
                  return {
                      caption: word,
                      value: word,
                      meta: languageMode + " keyword",
                      score: 1
                  };
              });
              callback(null, completionList);
            }
          }
        ];


        // Real-time linting on code change
        editor.getSession().on('change', function(e) {
            var code = editor.getValue();
            var language = editorWrapper.find('.code-editor-container').data('language');

            // Perform linting via AJAX
            $.ajax({
                url: adversarialEditorSettings.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'adversarial_lint_code', // Call the existing ajax_lint_code function in PHP
                    nonce: adversarialEditorSettings.lint_nonce,
                    code: code,
                    language: language
                },
                success: function(response) {
                    if (response.success && response.data.lint_errors) {
                        var annotations = response.data.lint_errors.map(function(error) {
                            // Assuming error format from PHP is compatible with Ace annotations
                            return {
                                row: error.row - 1, // Ace row is 0-based
                                column: error.column - 1, // Ace column is 0-based
                                text: error.text, // Error message
                                type: error.type // 'error' or 'warning'
                            };
                        });
                        editor.session.setAnnotations(annotations);
                    } else {
                        editor.session.clearAnnotations(); // Clear annotations if no errors
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error during linting", status, error);
                    editor.session.clearAnnotations(); // Clear annotations on error
                }
            });
        });

        // Update hidden input on editor changes
        editor.getSession().on('change', function() {
            $('#' + editorId).find('.code-editor-value').val(editor.getValue());
        });
    });

    // Function to get language-specific keywords
    function getLanguageKeywords(languageMode) {
        // This is a basic example, extend this to include more keywords for each language
        if (languageMode === 'python') {
            return ['def', 'class', 'if', 'for', 'while', 'import', 'return', 'try', 'except', 'finally', 'with', 'as', 'lambda', 'global', 'nonlocal', 'assert', 'break', 'continue', 'del', 'elif', 'else', 'except', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'try', 'while', 'with', 'yield'];
        } else if (languageMode === 'javascript') {
            return ['function', 'var', 'let', 'const', 'class', 'if', 'for', 'while', 'return', 'function', 'this', 'new', 'typeof', 'instanceof', 'try', 'catch', 'finally', 'throw', 'debugger', 'with', 'break', 'continue', 'delete', 'in', 'instanceof', 'new', 'return', 'this', 'typeof', 'void', 'while', 'with', 'yield', 'enum', 'export', 'import', 'super', 'implements', 'interface', 'let', 'package', 'private', 'protected', 'public', 'static', 'await', 'async'];
        } else if (languageMode === 'java') {
            return ['public', 'private', 'protected', 'class', 'interface', 'void', 'int', 'String', 'import', 'package', 'boolean', 'byte', 'char', 'double', 'float', 'long', 'short', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'default', 'break', 'continue', 'return', 'try', 'catch', 'finally', 'throw', 'throws', 'abstract', 'final', 'native', 'static', 'strictfp', 'synchronized', 'transient', 'volatile', 'assert', 'enum', 'extends', 'final', 'implements', 'instanceof', 'interface', 'new', 'package', 'private', 'protected', 'public', 'static', 'super', 'switch', 'this', 'throw', 'throws', 'try', 'void', 'while'];
        } else if (languageMode === 'php') {
            return ['$variable', 'function', 'class', 'namespace', 'use', 'echo', 'print', 'array', '__construct', '__destruct', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];
        }
        return [];
    }


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
                button.prop('disabled', false).text('Save');
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
                button.prop('disabled', false).text('Save');
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
                            var listItem = $('<li></li>').text('ID: ' + snippet.id + ', Language: ' + snippet.language + ', Created: ' + snippet.created_at);
                            listItem.data('snippet-id', snippet.id); // Store snippet ID
                            listItem.on('click', function() {
                                var selectedSnippetId = $(this).data('snippet-id');
                                editorWrapper.data('snippet-id', selectedSnippetId); // Update current editor's snippet ID
                                $('.adversarial-snippet-list li').removeClass('selected-snippet'); // Clear previous selections
                                $(this).addClass('selected-snippet'); // Highlight selected item
                                // Load the selected snippet into the editor immediately
                                loadSnippetIntoEditor(editorId, selectedSnippetId);
                            });
                            snippetList.append(listItem);
                        });

                        // Check if a snippet list already exists and replace it, otherwise append
                        var existingList = editorWrapper.find('.adversarial-snippet-list');
                        if(existingList.length) {
                            existingList.replaceWith(snippetList);
                        } else {
                            editorWrapper.find('.code-editor-controls').append(snippetList);
                        }
                    } else {
                        alert('No code snippets saved yet.');
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
                        editorWrapper.find('.adversarial-snippet-list').remove();
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

    // Theme select change
    $(document).on('change', '.adversarial-theme-select', function() {
        var select = $(this);
        var editorId = select.data('editor-id');
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
                action: 'adversarial_save_editor_theme_preference', // Define this action - to be implemented in PHP if needed
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

    // Find code
    $(document).on('click', '.adversarial-find-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('find');
    });

    // Replace code
    $(document).on('click', '.adversarial-replace-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('replace');
    });

    // Undo code
    $(document).on('click', '.adversarial-undo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.undo();
    });

    // Redo code
    $(document).on('click', '.adversarial-redo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.redo();
    });

    // Format code snippet
    $(document).on('click', '.adversarial-format-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        var code = editor.getValue();
        var language = editorWrapper.find('.code-editor-container').data('language');

        button.prop('disabled', true).text('Formatting...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_format_code_snippet',
                nonce: adversarialEditorSettings.format_nonce,
                code: code,
                language: language
            },
            success: function(response) {
                button.prop('disabled', false).text('Format Code');
                if (response.success && response.data.formatted_code) {
                    ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue(response.data.formatted_code);
                    editorWrapper.find('.code-editor-value').val(response.data.formatted_code);
                    alert('Code formatted successfully!');
                } else {
                    alert('Error formatting code: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Format Code');
                console.error("AJAX error", status, error);
                alert('Failed to format code.');
            }
        });
    });

    // Lint code
    $(document).on('click', '.adversarial-lint-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        var code = editor.getValue();
        var language = editorWrapper.find('.code-editor-container').data('language');

        button.prop('disabled', true).text('Linting...');

        $.ajax({
            url: adversarialEditorSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'adversarial_lint_code',
                nonce: adversarialEditorSettings.lint_nonce,
                code: code,
                language: language
            },
            success: function(response) {
                button.prop('disabled', false).text('Lint Code');
                if (response.success && response.data.lint_errors) {
                    // Handle and display lint errors
                    codeEditorInstance.displayLintErrors(editor, response.data.lint_errors);
                } else if (response.success && response.data.lint_errors.length === 0) {
                    editor.session.clearAnnotations();
                    alert('No linting errors found.');
                } else {
                    alert('Error during linting: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Lint Code');
                console.error("AJAX error", status, error);
                alert('Failed to perform linting.');
            }
        });
    });

    var codeEditorInstance = {
        handleLinting: function(editor) {
            var codeEditor = this;
            editor.getSession().on('changeAnnotation', function() {
                var annotations = editor.getSession().getAnnotations();
                codeEditor.displayLintErrors(editor, annotations); // Pass editor instance
            });
        },

        displayLintErrors: function(editor, annotations) {
            var errorList = jQuery('#adversarial-lint-errors');
            errorList.empty();
            editor.session.clearAnnotations(); // Clear existing annotations

            if (annotations && annotations.length > 0) {
                var aceAnnotations = [];
                annotations.forEach(function(annotation) {
                    if (annotation.type === 'error' || annotation.type === 'warning') {
                        errorList.append('<li class="' + annotation.type + '">Line ' + annotation.row + ': ' + annotation.text + '</li>');
                        aceAnnotations.push({
                            row: annotation.row - 1, // Ace editor line numbers are 0-based
                            column: annotation.column,
                            text: annotation.text,
                            type: annotation.type
                        });
                    }
                });
                editor.session.setAnnotations(aceAnnotations); // Set annotations for Ace editor
                errorList.show();
            } else {
                errorList.hide();
            }
        }
    };
});
jQuery(document).ready(function($) {
    // Initialize Ace Editor instances
    $('.code-editor-container').each(function() {
        var container = $(this);
        var editorId = container.closest('.adversarial-code-editor-wrapper').attr('id');
        var language = container.data('language');
        var theme = container.data('theme');
        var initialCode = container.text();

        container.empty(); // Clear initial text content

        var editor = ace.edit(container[0]);
        editor.setTheme("ace/theme/" + theme);
        editor.session.setMode("ace/mode/" + language);
        editor.setValue(initialCode);
        editor.clearSelection();

        // Enable line numbers
        editor.renderer.setShowGutter(true);

        // Enable code folding
        editor.getSession().setFoldStyle("markbeginend");

        // Enable autocompletion and live autocompletion
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            enableSnippets: true // Enable code snippets
        });

         // Live autocompletion for keywords and custom snippets
         editor.completers = [
          {
            getCompletions: function(editor, session, pos, prefix, callback) {
              var languageMode = editor.session.$modeId.split('/').pop(); // Extract language from mode ID
              var suggestions = getLanguageKeywords(languageMode); // Fetch keywords based on language

              // Add custom snippet suggestions here if needed, or fetch from another source

              if (prefix.length === 0) {
                  callback(null, []);
                  return;
              }

              var completionList = suggestions.map(function(word) {
                  return {
                      caption: word,
                      value: word,
                      meta: languageMode + " keyword",
                      score: 1
                  };
              });
              callback(null, completionList);
            }
          }
        ];


        // Real-time linting on code change
        editor.getSession().on('change', function(e) {
            var code = editor.getValue();
            var language = editorWrapper.find('.code-editor-container').data('language');

            // Perform linting via AJAX
            $.ajax({
                url: adversarialEditorSettings.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'adversarial_lint_code', // Call the existing ajax_lint_code function in PHP
                    nonce: adversarialEditorSettings.lint_nonce,
                    code: code,
                    language: language
                },
                success: function(response) {
                    if (response.success && response.data.lint_errors) {
                        var annotations = response.data.lint_errors.map(function(error) {
                            // Assuming error format from PHP is compatible with Ace annotations
                            return {
                                row: error.row - 1, // Ace row is 0-based
                                column: error.column - 1, // Ace column is 0-based
                                text: error.text, // Error message
                                type: error.type // 'error' or 'warning'
                            };
                        });
                        editor.session.setAnnotations(annotations);
                    } else {
                        editor.session.clearAnnotations(); // Clear annotations if no errors
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error during linting", status, error);
                    editor.session.clearAnnotations(); // Clear annotations on error
                }
            });
        });

        // Update hidden input on editor changes
        editor.getSession().on('change', function() {
            $('#' + editorId).find('.code-editor-value').val(editor.getValue());
        });
    });

    // Function to get language-specific keywords
    function getLanguageKeywords(languageMode) {
        // This is a basic example, extend this to include more keywords for each language
        if (languageMode === 'python') {
            return ['def', 'class', 'if', 'for', 'while', 'import', 'return', 'try', 'except', 'finally', 'with', 'as', 'lambda', 'global', 'nonlocal', 'assert', 'break', 'continue', 'del', 'elif', 'else', 'except', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'try', 'while', 'with', 'yield'];
        } else if (languageMode === 'javascript') {
            return ['function', 'var', 'let', 'const', 'class', 'if', 'for', 'while', 'return', 'function', 'this', 'new', 'typeof', 'instanceof', 'try', 'catch', 'finally', 'throw', 'debugger', 'with', 'break', 'continue', 'delete', 'in', 'instanceof', 'new', 'return', 'this', 'typeof', 'void', 'while', 'with', 'yield', 'enum', 'export', 'import', 'super', 'implements', 'interface', 'let', 'package', 'private', 'protected', 'public', 'static', 'await', 'async'];
        } else if (languageMode === 'java') {
            return ['public', 'private', 'protected', 'class', 'interface', 'void', 'int', 'String', 'import', 'package', 'boolean', 'byte', 'char', 'double', 'float', 'long', 'short', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'default', 'break', 'continue', 'return', 'try', 'catch', 'finally', 'throw', 'throws', 'abstract', 'final', 'native', 'static', 'strictfp', 'synchronized', 'transient', 'volatile', 'assert', 'enum', 'extends', 'final', 'implements', 'instanceof', 'interface', 'new', 'package', 'private', 'protected', 'public', 'static', 'super', 'switch', 'this', 'throw', 'throws', 'try', 'void', 'while'];
        } else if (languageMode === 'php') {
            return ['$variable', 'function', 'class', 'namespace', 'use', 'echo', 'print', 'array', '__construct', '__destruct', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];
        }
        return [];
    }


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
                button.prop('disabled', false).text('Save');
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
                button.prop('disabled', false).text('Save');
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
                            var listItem = $('<li></li>').text('ID: ' + snippet.id + ', Language: ' + snippet.language + ', Created: ' + snippet.created_at);
                            listItem.data('snippet-id', snippet.id); // Store snippet ID
                            listItem.on('click', function() {
                                var selectedSnippetId = $(this).data('snippet-id');
                                editorWrapper.data('snippet-id', selectedSnippetId); // Update current editor's snippet ID
                                $('.adversarial-snippet-list li').removeClass('selected-snippet'); // Clear previous selections
                                $(this).addClass('selected-snippet'); // Highlight selected item
                                // Load the selected snippet into the editor immediately
                                loadSnippetIntoEditor(editorId, selectedSnippetId);
                            });
                            snippetList.append(listItem);
                        });

                        // Check if a snippet list already exists and replace it, otherwise append
                        var existingList = editorWrapper.find('.adversarial-snippet-list');
                        if(existingList.length) {
                            existingList.replaceWith(snippetList);
                        } else {
                            editorWrapper.find('.code-editor-controls').append(snippetList);
                        }
                    } else {
                        alert('No code snippets saved yet.');
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
                        editorWrapper.find('.adversarial-snippet-list').remove();
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

    // Theme select change
    $(document).on('change', '.adversarial-theme-select', function() {
        var select = $(this);
        var editorId = select.data('editor-id');
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
                action: 'adversarial_save_editor_theme_preference', // Define this action - to be implemented in PHP if needed
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

    // Find code
    $(document).on('click', '.adversarial-find-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('find');
    });

    // Replace code
    $(document).on('click', '.adversarial-replace-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.execCommand('replace');
    });

    // Undo code
    $(document).on('click', '.adversarial-undo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.undo();
    });

    // Redo code
    $(document).on('click', '.adversarial-redo-code', function(e) {
        e.preventDefault();
        var editorId = $(this).data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        editor.redo();
        });
    },

    handleLinting: function(editor) {
        var codeEditor = this;
        editor.getSession().on('changeAnnotation', function() {
            var annotations = editor.getSession().getAnnotations();
            codeEditor.displayLintErrors(editor, annotations); // Pass editor instance
        });
    },

    displayLintErrors: function(editor, annotations) {
        var errorList = jQuery('#adversarial-lint-errors');
        errorList.empty();
        editor.session.clearAnnotations(); // Clear existing annotations

        if (annotations && annotations.length > 0) {
            var aceAnnotations = [];
            annotations.forEach(function(annotation) {
                if (annotation.type === 'error' || annotation.type === 'warning') {
                    errorList.append('<li class="' + annotation.type + '">Line ' + annotation.row + ': ' + annotation.text + '</li>');
                    aceAnnotations.push({
                        row: annotation.row - 1, // Ace editor line numbers are 0-based
                        column: annotation.column,
                        text: annotation.text,
                        type: annotation.type
                    });
                }
            });
            editor.session.setAnnotations(aceAnnotations); // Set annotations for Ace editor
            errorList.show();
        } else {
            errorList.hide();
        }
    },
            success: function(response) {
                button.prop('disabled', false).text('Format Code');
                if (response.success && response.data.formatted_code) {
                    ace.edit(editorWrapper.find('.code-editor-container')[0]).setValue(response.data.formatted_code);
                    editorWrapper.find('.code-editor-value').val(editor.data.formatted_code);
                    alert('Code formatted successfully!');
                } else {
                    alert('Error formatting code: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).text('Format Code');
                console.error("AJAX error", status, error);
                alert('Failed to format code.');
            }
        });
    });

    // Lint code
    $(document).on('click', '.adversarial-lint-code', function(e) {
        e.preventDefault();
        var button = $(this);
        var editorId = button.data('editor-id');
        var editorWrapper = $('#' + editorId);
        var editor = ace.edit(editorWrapper.find('.code-editor-container')[0]);
        var code = editor.getValue();
        var language = editorWrapper.find('.code-editor-container').data('language');

        if (language === 'javascript') {
            var lintResults = JSHINT(code); // Run JSHint
            if (!lintResults) {
                var errors = JSHINT.errors;
                var errorString = 'Linting errors:\n';
                for (var i = 0; i < errors.length; i++) {
                    var error = errors[i];
                    errorString += 'Line ' + error.line + ', Character ' + error.character + ': ' + error.reason + '\n';
                }
                alert(errorString); // Display errors in an alert
            } else {
                alert('No linting errors found.');
            }
        } else {
            alert('Linting is currently only supported for JavaScript.');
        }
    });
});
