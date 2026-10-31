<form method="get" class="chart-controls" aria-label="Chart date range">
    <label>From<input class="form-control" type="date" name="chart_start" value="{{ $chartStart->toDateString() }}" required></label>
    <label>To<input class="form-control" type="date" name="chart_end" value="{{ $chartEnd->toDateString() }}" required></label>
    <button class="btn btn-primary" type="submit">Apply dates</button>
    <button class="btn btn-outline-primary" type="button" data-chart-days="7">7 days</button>
    <button class="btn btn-outline-primary" type="button" data-chart-days="30">30 days</button>
    <button class="btn btn-outline-primary" type="button" data-chart-days="90">90 days</button>
</form>
<div class="chart-controls">
    <label>Status<select class="form-select" id="chartStatus"><option value="">All statuses</option>@foreach($leadCounts as $status => $count)<option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></label>
    <label>Chart style<select class="form-select" id="chartType"><option value="area">Area</option><option value="line">Line</option><option value="bar">Bars</option></select></label>
    <label>Group by<select class="form-select" id="chartGroup"><option value="day">Day</option><option value="week">Week</option><option value="month">Month</option></select></label>
    <button type="button" class="btn btn-outline-primary" id="chartZoomIn" aria-label="Zoom in">+</button>
    <button type="button" class="btn btn-outline-primary" id="chartZoomOut" aria-label="Zoom out">-</button>
    <button type="button" class="btn btn-outline-primary" id="chartReset">Reset zoom</button>
    <button type="button" class="btn btn-outline-primary" id="chartExport">Export CSV</button>
</div>
<p class="chart-note">Leads created in the selected period, grouped by their current status. Zoom focuses on the most recent points.</p>
<div class="chart-frame" id="pipelineChart"></div>
<p class="chart-note" id="chartSummary" role="status" aria-live="polite"></p>
<script type="application/json" id="pipelineChartData">@json($chartRows)</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = JSON.parse(document.getElementById('pipelineChartData').textContent);
    const start = @json($chartStart->toDateString()), end = @json($chartEnd->toDateString());
    const status = document.getElementById('chartStatus'), type = document.getElementById('chartType'), group = document.getElementById('chartGroup');
    let zoom = 1, visible = [];
    const iso = date => date.toISOString().slice(0,10);
    function bucket(day) {
        const date = new Date(day + 'T00:00:00Z');
        if (group.value === 'month') return day.slice(0,7) + '-01';
        if (group.value === 'week') date.setUTCDate(date.getUTCDate() - (date.getUTCDay() + 6) % 7);
        return iso(date);
    }
    function draw() {
        const counts = new Map();
        for (let date = new Date(start+'T00:00:00Z'); iso(date) <= end; date.setUTCDate(date.getUTCDate()+1)) counts.set(bucket(iso(date)),0);
        rows.filter(row => !status.value || row.status === status.value).forEach(row => counts.set(bucket(row.day),(counts.get(bucket(row.day)) || 0)+Number(row.total)));
        const all = Array.from(counts.entries());
        visible = all.slice(-Math.max(1,Math.ceil(all.length/zoom)));
        const frame = document.getElementById('pipelineChart');
        const width = Math.max(260, frame.clientWidth), height = 285;
        const left = 62, right = width - 18, top = 18, bottom = 230;
        const axisMax = Math.max(4, Math.ceil(Math.max(...visible.map(point => point[1])) / 4) * 4);
        const step = (right - left) / visible.length;
        const x = i => left + step * (i + .5), y = count => bottom - count / axisMax * (bottom - top);
        const period = {day: 'day', week: 'week', month: 'month'}[group.value];
        let svg = `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" role="img" aria-label="Lead count vertically and lead creation date horizontally, grouped by ${period}"><title>Lead count by creation date</title>`;
        for (let i = 0; i <= 4; i++) {
            const value = axisMax * i / 4, yy = y(value);
            svg += `<path d="M${left} ${yy} H${right}" stroke="#e2e8f0"/><text x="${left-9}" y="${yy+4}" text-anchor="end" fill="#64748b" font-size="11">${value}</text>`;
        }
        svg += `<text x="14" y="${(top+bottom)/2}" transform="rotate(-90 14 ${(top+bottom)/2})" text-anchor="middle" fill="#64748b" font-size="11">Number of leads</text><text x="${(left+right)/2}" y="278" text-anchor="middle" fill="#64748b" font-size="11">Lead creation date (by ${period})</text>`;
        const points = visible.map((point,i)=>`${x(i)},${y(point[1])}`).join(' ');
        if(type.value==='area') svg += `<polygon points="${x(0)},${bottom} ${points} ${x(visible.length-1)},${bottom}" fill="#2563eb" fill-opacity=".12"/>`;
        if(type.value!=='bar') svg += `<polyline points="${points}" fill="none" stroke="#2563eb" stroke-width="2.5"/>`;
        const labelEvery = Math.max(1, Math.ceil(visible.length / Math.max(2, Math.floor((right-left)/75))));
        visible.forEach(([day,count],i)=>{
            const title=`<title>${day}: ${count} leads</title>`;
            svg += type.value==='bar' ? `<rect x="${x(i)-step*.32}" y="${y(count)}" width="${step*.64}" height="${bottom-y(count)}" rx="3" fill="${i === visible.length-1 ? '#2563eb' : '#e9eef3'}">${title}</rect>` : `<circle cx="${x(i)}" cy="${y(count)}" r="3" fill="#2563eb">${title}</circle>`;
            if(i%labelEvery===0) svg += `<text x="${x(i)}" y="250" text-anchor="middle" fill="#64748b" font-size="11">${day.slice(5)}</text>`;
        });
        frame.innerHTML=svg+'</svg>';
        document.getElementById('chartSummary').textContent=`${visible.reduce((sum,p)=>sum+p[1],0)} leads | ${visible[0][0]} to ${visible[visible.length-1][0]} | ${zoom}x zoom`;
        document.getElementById('chartZoomIn').disabled=visible.length<=1;
        document.getElementById('chartZoomOut').disabled=zoom===1;
    }
    [status,type,group].forEach(control=>control.addEventListener('change',()=>{zoom=1;draw();}));
    document.getElementById('chartZoomIn').onclick=()=>{zoom*=2;draw();};
    document.getElementById('chartZoomOut').onclick=()=>{zoom=Math.max(1,zoom/2);draw();};
    document.getElementById('chartReset').onclick=()=>{zoom=1;draw();};
    document.querySelectorAll('[data-chart-days]').forEach(button=>button.onclick=()=>{
        const form=button.closest('form'), date=new Date(@json(now()->toDateString())+'T00:00:00Z');
        form.elements.chart_end.value=iso(date); date.setUTCDate(date.getUTCDate()-Number(button.dataset.chartDays)+1); form.elements.chart_start.value=iso(date); form.requestSubmit();
    });
    document.getElementById('chartExport').onclick=()=>{
        const url=URL.createObjectURL(new Blob(['Date,Leads\n'+visible.map(p=>p.join(',')).join('\n')],{type:'text/csv'}));
        const link=document.createElement('a');link.href=url;link.download='lead-trend.csv';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
    };
    draw();
    new ResizeObserver(draw).observe(document.getElementById('pipelineChart'));
});
</script>
