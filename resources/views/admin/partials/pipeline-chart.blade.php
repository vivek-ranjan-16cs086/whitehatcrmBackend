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
    <label>Measure<select class="form-select" id="chartMeasure"><option value="count">Lead count</option><option value="cumulative">Cumulative leads</option></select></label>
    <label class="chart-average-toggle"><span>Trend overlay</span><span><input type="checkbox" id="chartAverage"> 7-period average</span></label>
    <button type="button" class="btn btn-outline-primary" id="chartZoomIn" aria-label="Zoom in">+</button>
    <button type="button" class="btn btn-outline-primary" id="chartZoomOut" aria-label="Zoom out">-</button>
    <button type="button" class="btn btn-outline-primary" id="chartReset">Reset zoom</button>
    <button type="button" class="btn btn-outline-primary" id="chartExport">Export CSV</button>
</div>
<div id="stockChartWorkspace" data-start="{{ $chartStart->toDateString() }}" data-end="{{ $chartEnd->toDateString() }}" data-today="{{ now()->toDateString() }}">
    <div class="stock-chart-head"><div><span class="dashboard-eyebrow">LEAD ACTIVITY</span><strong id="chartHeadline"></strong><span id="chartHeadlineDetail"></span></div><div class="stock-chart-actions"><button type="button" class="btn btn-outline-primary" id="chartSave">Save SVG</button><button type="button" class="btn btn-outline-primary" id="chartFullscreen">Fullscreen</button></div></div>
    <div class="chart-frame stock-chart-frame" id="pipelineChart" tabindex="0" role="group" aria-label="Interactive lead chart. Use plus and minus to zoom, arrow keys to pan, Home to reset."></div>
    <div class="stock-navigator" id="chartNavigator" aria-hidden="true"></div>
    <div class="stock-range-controls"><label>Range start<input type="range" id="chartRangeStart" min="0" max="1" value="0"></label><label>Range end<input type="range" id="chartRangeEnd" min="0" max="1" value="1"></label></div>
    <p class="chart-note" id="chartSummary" role="status" aria-live="polite"></p>
    <p class="chart-note">Scroll over the chart to zoom. Drag to pan, or adjust the range below. Leads are grouped by creation date and current status.</p>
</div>
<script type="application/json" id="pipelineChartData">@json($chartRows)</script>
<script src="{{ url('admin/js/dashboard-chart.js') }}?v={{ filemtime(public_path('admin/js/dashboard-chart.js')) }}" defer></script>
