jQuery(document).ready(function($) {
    $('#history-search, #history-language-filter').on('input change', function() {
        var searchTerm = $('#history-search').val().toLowerCase();
        var languageFilter = $('#history-language-filter').val();

        $('.history-item').each(function() {
            var item = $(this);
            var code = item.find('.code-preview').text().toLowerCase();
            var prompt = item.find('.history-prompt p').text().toLowerCase();
            var language = item.find('.history-language').text().toLowerCase();
            var languageMatch = languageFilter === 'all' || language.includes(languageFilter);
            var searchMatch = code.includes(searchTerm) || prompt.includes(searchTerm);

            if (languageMatch && searchMatch) {
                item.show();
            } else {
                item.hide();
            }
        });
    });
});
