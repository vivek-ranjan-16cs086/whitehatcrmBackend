(function ($) {
    'use strict';
    if (!$ || !$.fn.dataTable) return;
    const table = $.fn.dataTable;
    const chevron = direction => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + (direction === 'previous' ? 'm15 18-6-6 6-6' : 'm9 18 6-6-6-6') + '"/></svg>';
    // A sliding window of three numbers, without extra first/last numbers.
    table.ext.pager.admin_compact = function (page, pages) {
        const count = Math.min(3, pages);
        const start = Math.max(0, Math.min(page - 1, pages - count));
        const numbers = Array.from({length: count}, (_, index) => start + index);
        return ['previous', numbers, 'next'];
    };
    $.extend(true, table.defaults, {
        pagingType: 'admin_compact',
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search records...',
            paginate: {previous: chevron('previous'), next: chevron('next')},
            aria: {paginate: {previous: 'Previous page', next: 'Next page'}}
        }
    });
    // Apply even when a module supplies its own pagination or language options.
    $(document).on('preInit.dt', function (event, settings) {
        settings.sPaginationType = 'admin_compact';
        settings.oLanguage.oPaginate.sPrevious = chevron('previous');
        settings.oLanguage.oPaginate.sNext = chevron('next');
        settings.oLanguage.sSearch = '_INPUT_';
        settings.oLanguage.oAria.paginate = Object.assign({}, settings.oLanguage.oAria.paginate, {previous: 'Previous page', next: 'Next page'});
    });
    $(document).on('init.dt', function (event, settings) {
        const input = settings.nTableWrapper.querySelector('.dataTables_filter input');
        if (input) input.setAttribute('aria-label', 'Search records');
    });
})(window.jQuery);
