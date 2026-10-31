(function ($) {
    'use strict';
    const modal = $('#viewLeadModal');
    let generation = 0, leadId, pages = {}, requests = [];
    function cancel() { generation++; requests.forEach(r => r.abort()); requests = []; }
    function request(params) {
        const r = $.getJSON(modal.data('details-url') + '/' + encodeURIComponent(leadId) + '/details', params);
        requests.push(r);
        return r;
    }
    function render(section, page, append) {
        pages[section] = page.page;
        modal.find('[data-count="' + section + '"]').text(page.total);
        const list = modal.find('[data-history="' + section + '"]');
        if (!append) list.empty();
        if (!page.total) $('<p class="text-muted small">').text('No recorded ' + section + '.').appendTo(list);
        page.items.forEach(item => {
            const card = $('<article class="border-bottom py-3">').appendTo(list);
            $('<div class="small fw-semibold">').text(item.type.replaceAll('_', ' ') + ' - ' + item.user_name).appendTo(card);
            $('<div class="small text-muted mb-2">').text([item.created_at, item.user_role].filter(Boolean).join(' | ')).appendTo(card);
            $('<div>').css({whiteSpace: 'pre-wrap', overflowWrap: 'anywhere'}).text(item.message || 'No note recorded.').appendTo(card);
            [['Current assignee', item.assigned_to], ['Follow-up', item.follow_up_date], ['Call outcome', item.call_outcome], ['Phone', item.phone]].forEach(([label, value]) => {
                if (value) $('<div class="small text-muted mt-1">').text(label + ': ' + value).appendTo(card);
            });
            if (item.recording_url) $('<audio controls preload="none" class="mt-2 mw-100">').attr('src', item.recording_url).attr('aria-label', 'Call recording').appendTo(card);
        });
        modal.find('[data-load-history="' + section + '"]').prop('hidden', !page.has_more).prop('disabled', false).text('Load more');
    }
    function open(id) {
        cancel(); leadId = id; pages = {};
        const current = generation;
        modal.find('audio').each(function () { this.pause(); });
        $('#leadViewContent').prop('hidden', true);
        $('#viewLeadModalLabel').text('Lead details');
        $('#leadViewStatus').text('Loading lead details...');
        bootstrap.Tab.getOrCreateInstance(modal.find('[data-bs-target="#leadDetailsPane"]')[0]).show();
        bootstrap.Modal.getOrCreateInstance(modal[0]).show();
        request({}).done(data => {
            if (current !== generation) return;
            $('#viewLeadModalLabel').text(data.name + ' - Lead #' + data.id);
            $('#leadViewSummary').text('Current owner: ' + data.current_owner + ' | ' + data.activity_scope + ' | Times in IST');
            const details = $('#leadViewDetails').empty();
            data.fields.forEach(field => {
                $('<dt class="col-sm-4 text-muted small">').text(field.label).appendTo(details);
                $('<dd class="col-sm-8 small">').text(field.value === null || field.value === '' ? 'Not provided' : String(field.value)).appendTo(details);
            });
            Object.entries(data.sections).forEach(([section, page]) => render(section, page, false));
            $('#leadViewStatus').empty(); $('#leadViewContent').prop('hidden', false);
        }).fail((xhr, status) => {
            if (status === 'abort' || current !== generation) return;
            $('#leadViewStatus').text(xhr.status === 404 || xhr.status === 403 ? 'This lead is unavailable or you do not have access.' : 'Could not load lead details. ');
            if (xhr.status !== 404 && xhr.status !== 403) $('<button type="button" class="btn btn-sm btn-outline-primary">').text('Retry').on('click', () => open(id)).appendTo('#leadViewStatus');
        });
    }
    $(document).on('click', '.viewBtn', function () { open(this.dataset.id); });
    modal.on('click', '[data-load-history]', function () {
        const button = $(this), section = this.dataset.loadHistory, current = generation;
        button.prop('disabled', true).text('Loading...');
        request({section, page: pages[section] + 1}).done(page => {
            if (current === generation) render(section, page, true);
        }).fail((xhr, status) => {
            if (status !== 'abort' && current === generation) button.prop('disabled', false).text('Could not load. Retry');
        });
    });
    modal.on('hidden.bs.modal', () => { cancel(); modal.find('audio').each(function () { this.pause(); }); });
})(jQuery);
