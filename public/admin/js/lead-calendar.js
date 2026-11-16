(() => {
    const panel = document.getElementById('leadCalendarPanel');
    const toggle = document.getElementById('leadCalendarToggle');
    if (!panel || !toggle) return;
    document.body.append(panel);
    const from = document.getElementById('leadDateFrom');
    const to = document.getElementById('leadDateTo');
    const label = document.getElementById('leadCalendarLabel');
    const months = document.getElementById('leadCalendarMonths');
    const apply = document.getElementById('leadCalendarApply');
    const iso = date => date.toISOString().slice(0, 10);
    const date = value => new Date(value + 'T00:00:00Z');
    const todayParts = Object.fromEntries(new Intl.DateTimeFormat('en', {timeZone: 'Asia/Kolkata', year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(new Date()).map(part => [part.type, part.value]));
    const today = `${todayParts.year}-${todayParts.month}-${todayParts.day}`;
    const shift = (value, days) => { const result = date(value); result.setUTCDate(result.getUTCDate() + days); return iso(result); };
    const monthStart = (value, offset = 0) => { const result = date(value); return new Date(Date.UTC(result.getUTCFullYear(), result.getUTCMonth() + offset, 1)); };
    const display = value => value.split('-').reverse().join('-');
    let start = '', end = '', firstMonth = monthStart(today, -1);

    function close(focus = false) {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (focus) toggle.focus();
    }

    function position() {
        const rect = toggle.getBoundingClientRect();
        panel.style.width = Math.min(560, window.innerWidth - 24) + 'px';
        panel.style.left = Math.max(12, Math.min(rect.left, window.innerWidth - panel.offsetWidth - 12)) + 'px';
        panel.style.maxHeight = Math.max(180, window.innerHeight - 24) + 'px';
        panel.style.top = Math.max(12, Math.min(rect.bottom + 8, window.innerHeight - panel.offsetHeight - 12)) + 'px';
    }

    function render() {
        months.replaceChildren();
        for (let index = 0; index < 2; index++) {
            const current = monthStart(iso(firstMonth), index);
            const month = document.createElement('section');
            month.className = 'lead-calendar-month';
            const heading = document.createElement('div');
            heading.className = 'lead-calendar-heading';
            for (const direction of [-1, 0, 1]) {
                const item = document.createElement(direction ? 'button' : 'strong');
                if (direction) {
                    item.type = 'button';
                    item.textContent = direction < 0 ? '‹' : '›';
                    item.setAttribute('aria-label', direction < 0 ? 'Previous month' : 'Next month');
                    item.onclick = () => { firstMonth = monthStart(iso(firstMonth), direction); render(); position(); };
                } else {
                    item.textContent = current.toLocaleDateString('en-GB', {month: 'long', year: 'numeric', timeZone: 'UTC'});
                }
                heading.append(item);
            }
            const grid = document.createElement('div');
            grid.className = 'lead-calendar-grid';
            for (const day of ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']) {
                const cell = document.createElement('span'); cell.textContent = day; grid.append(cell);
            }
            const firstDay = current.getUTCDay();
            const days = new Date(Date.UTC(current.getUTCFullYear(), current.getUTCMonth() + 1, 0)).getUTCDate();
            for (let cell = 0; cell < 42; cell++) {
                const day = cell - firstDay + 1;
                if (day < 1 || day > days) { grid.append(document.createElement('span')); continue; }
                const value = iso(new Date(Date.UTC(current.getUTCFullYear(), current.getUTCMonth(), day)));
                const button = document.createElement('button');
                button.type = 'button'; button.textContent = String(day); button.dataset.calendarDate = value;
                button.setAttribute('aria-label', display(value));
                button.setAttribute('aria-pressed', String(value === start || value === end));
                if (value === today) button.setAttribute('aria-current', 'date');
                if (start && end && value >= start && value <= end) button.classList.add('in-range');
                if (value === start || value === end) button.classList.add('range-end');
                button.onclick = () => {
                    if (!start || end) { start = value; end = ''; }
                    else { end = value; if (end < start) [start, end] = [end, start]; }
                    render();
                };
                grid.append(button);
            }
            month.append(heading, grid); months.append(month);
        }
        apply.disabled = !(start && end);
        document.getElementById('leadCalendarDraft').textContent = start ? display(start) + (end ? ' – ' + display(end) : ' — choose an end date') : 'Choose a start and end date';
    }

    toggle.addEventListener('click', () => {
        if (!panel.hidden) { close(); return; }
        start = from.value; end = to.value;
        firstMonth = monthStart(start || today, start ? 0 : -1);
        render(); panel.hidden = false; toggle.setAttribute('aria-expanded', 'true'); position();
        panel.querySelector('button').focus();
    });
    panel.querySelectorAll('[data-calendar-preset]').forEach(button => button.addEventListener('click', () => {
        end = today;
        switch (button.dataset.calendarPreset) {
            case 'today': start = today; break;
            case 'yesterday': start = end = shift(today, -1); break;
            case 'last7': start = shift(today, -6); break;
            case 'last30': start = shift(today, -29); break;
            case 'month': start = iso(monthStart(today)); break;
            case 'lastMonth': start = iso(monthStart(today, -1)); end = shift(iso(monthStart(today)), -1); break;
            case 'last3': start = iso(monthStart(today, -2)); break;
            case 'last6': start = iso(monthStart(today, -5)); break;
        }
        firstMonth = monthStart(start); render(); position();
    }));
    apply.addEventListener('click', () => {
        if (!start || !end) return;
        from.value = start; to.value = end;
        label.textContent = display(start) + ' – ' + display(end);
        close(true); window.jQuery(document).trigger('lead:date-range');
    });
    window.clearLeadDateRange = (reload = true) => {
        from.value = to.value = start = end = '';
        label.textContent = 'All dates'; close(true);
        if (reload) window.jQuery(document).trigger('lead:date-range');
    };
    document.getElementById('leadCalendarClear').addEventListener('click', () => window.clearLeadDateRange());
    document.addEventListener('click', event => { if (!event.composedPath().includes(panel) && !toggle.contains(event.target)) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) close(true); });
    window.addEventListener('resize', () => { if (!panel.hidden) position(); });
    window.addEventListener('scroll', event => { if (!panel.hidden && !panel.contains(event.target)) close(); }, true);
})();
