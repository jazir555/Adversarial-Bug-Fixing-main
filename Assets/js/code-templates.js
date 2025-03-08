jQuery(document).ready(function($) {
    // Template search functionality
    $('#template-search-input').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterTemplates(searchTerm);
    });

    // Category filter functionality
    $('.category-filter').on('click', function() {
        $('.category-filter').removeClass('active');
        $(this).addClass('active');
        var category = $(this).data('category');
        filterTemplates('', category); // Apply category filter, reset search term
    });

    function filterTemplates(searchTerm = '', category = 'all') {
        $('.template-card').each(function() {
            var card = $(this);
            var title = card.find('h3').text().toLowerCase();
            var description = card.find('.template-description').text().toLowerCase();
            var language = card.data('language');
            
            var searchMatch = searchTerm === '' || title.includes(searchTerm) || description.includes(searchTerm);
            var categoryMatch = category === 'all' || language === category;

            if (searchMatch && categoryMatch) {
                card.show();
            } else {
                card.hide();
            }
        });
    }
});
