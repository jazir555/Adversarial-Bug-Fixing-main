jQuery(document).ready(function($) {
    $('.compare-button').on('click', function() {
        var originalCode = ace.edit($('.original-code .code-editor')[0]).getValue();
        var modifiedCode = ace.edit($('.modified-code .code-editor')[0]).getValue();

        $.ajax({
            url: adversarialEditorSettings.ajax_url, // Assuming adversarialEditorSettings is localized in CodeEditor.php
            type: 'POST',
            data: {
                action: 'adversarial_compare_code',
                nonce: codeComparisonSettings.nonce, // Assuming codeComparisonSettings is localized in CodeComparison.php
                code1: originalCode,
                code2: modifiedCode
            },
            success: function(response) {
                if (response.success) {
                    var diffHtml = Diff2Html.getPrettyHtml(response.data.diff_text, {
                        inputFormat: 'diff',
                        outputFormat: 'line-by-line',
                        synchronisedScroll: true,
                        showFiles: false,
                        matching: 'lines',
                        highlight: true,
                    });
                    $('.comparison-diff-output').html(diffHtml);
                } else {
                    $('.comparison-diff-output').html('<p>Error generating diff: ' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('.comparison-diff-output').html('<p>AJAX error: ' + textStatus + ', ' + errorThrown + '</p>');
            }
        });
    });
});
