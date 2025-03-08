jQuery(document).ready(function($) {
    $('.compare-button').on('click', function() {
        var originalCode = ace.edit($('.original-code .code-editor')[0]).getValue();
        var modifiedCode = ace.edit($('.modified-code .code-editor')[0]).getValue();

        var diff2htmlUi = new Diff2HtmlUI({
            diff: generateDiff(originalCode, modifiedCode),
            outputFormat: 'line-by-line',
            synchronisedScroll: true,
            showFiles: false,
            matching: 'lines',
            highlight: true,
        });

        diff2htmlUi.draw('.comparison-diff-output', {});
    });

    function generateDiff(original, modified) {
        var diff = JsDiff.diffLines(original, modified);
        var diffText = '';
        for (var i = 0; i < diff.length; i++) {
            var part = diff[i];
            var lines = part.value.split('\\n');
            for (var j = 0; j < lines.length; j++) {
                if (lines[j].length > 0) {
                    if (part.added) {
                        diffText += '+ ' + lines[j] + '\\n';
                    } else if (part.removed) {
                        diffText += '- ' + lines[j] + '\\n';
                    } else {
                        diffText += '  ' + lines[j] + '\\n';
                    }
                }
            }
        }
        return diffText;
    }
});
