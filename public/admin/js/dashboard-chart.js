(() => {
    const init = () => {
        const workspace = document.getElementById('stockChartWorkspace');
        if (!workspace) return;
        const $ = id => document.getElementById(id);
        const frame = $('pipelineChart'), rows = JSON.parse($('pipelineChartData').textContent);
        const status = $('chartStatus'), type = $('chartType'), group = $('chartGroup'), measure = $('chartMeasure');
        const from = $('chartRangeStart'), to = $('chartRangeEnd');
        let all = [], visible = [], left = 0, right = 0, geometry, drag = null;
        const iso = date => date.toISOString().slice(0, 10);
        const clamp = (n, min, max) => Math.max(min, Math.min(max, n));
        const format = n => n.toLocaleString(undefined, {maximumFractionDigits: 1});
        function bucket(day) {
            if (group.value === 'month') return day.slice(0, 7) + '-01';
            const date = new Date(day + 'T00:00:00Z');
            if (group.value === 'week') date.setUTCDate(date.getUTCDate() - (date.getUTCDay() + 6) % 7);
            return iso(date);
        }
        function rebuild() {
            const counts = new Map();
            for (let date = new Date(workspace.dataset.start + 'T00:00:00Z'); iso(date) <= workspace.dataset.end; date.setUTCDate(date.getUTCDate() + 1)) counts.set(bucket(iso(date)), 0);
            rows.filter(row => !status.value || row.status === status.value).forEach(row => counts.set(bucket(row.day), (counts.get(bucket(row.day)) || 0) + Number(row.total)));
            let cumulative = 0;
            all = Array.from(counts, ([date, count]) => ({date, count, value: measure.value === 'cumulative' ? (cumulative += count) : count}));
            left = 0; right = all.length - 1;
            from.max = to.max = right;
            from.disabled = to.disabled = all.length <= 1;
            draw();
        }
        function draw() {
            visible = all.slice(left, right + 1);
            const width = Math.max(320, frame.clientWidth), height = 420;
            const x0 = 12, x1 = width - 66, y0 = 24, y1 = height - 36;
            const maxValue = Math.max(1, ...visible.map(p => p.value));
            const magnitude = 10 ** Math.floor(Math.log10(maxValue));
            const top = Math.ceil(maxValue * 1.12 / magnitude) * magnitude;
            const step = (x1 - x0) / Math.max(1, visible.length - 1);
            const x = i => visible.length === 1 ? (x0 + x1) / 2 : x0 + step * i;
            const y = value => y1 - value / top * (y1 - y0);
            geometry = {width, height, x0, x1, y0, y1, x, y, step};
            let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" role="img" aria-label="Lead activity from ${visible[0].date} to ${visible.at(-1).date}"><title>Lead activity</title><defs><linearGradient id="stockAreaFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#2563eb" stop-opacity=".24"/><stop offset="100%" stop-color="#2563eb" stop-opacity=".015"/></linearGradient><clipPath id="stockPlotClip"><rect x="${x0-3}" y="${y0}" width="${x1-x0+6}" height="${y1-y0+3}"/></clipPath></defs><rect width="100%" height="100%" fill="white"/>`;
            for (let i = 0; i <= 4; i++) {
                const value = top * i / 4, yy = y(value);
                svg += `<path d="M${x0} ${yy}H${x1}" stroke="#edf1f5" stroke-dasharray="3 5"/><text x="${x1+12}" y="${yy+4}" fill="#8490a2" font-size="11" font-family="Arial">${format(value)}</text>`;
            }
            const labelEvery = Math.max(1, Math.ceil(visible.length / Math.max(2, Math.floor(width / 115))));
            visible.forEach((p, i) => {
                if (i % labelEvery !== 0 && i !== visible.length - 1) return;
                if (i === visible.length - 1 && i > 0 && i % labelEvery < labelEvery / 2) return;
                svg += `<text x="${x(i)}" y="${height-12}" text-anchor="${i===0?'start':'middle'}" fill="#8490a2" font-size="11" font-family="Arial">${p.date.slice(5)}</text>`;
            });
            const points = visible.map((p,i) => `${x(i)},${y(p.value)}`).join(' ');
            svg += '<g clip-path="url(#stockPlotClip)">';
            if (type.value === 'area') svg += `<polygon points="${x(0)},${y1} ${points} ${x(visible.length-1)},${y1}" fill="url(#stockAreaFill)"/>`;
            if (type.value !== 'bar') svg += `<polyline points="${points}" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>`;
            else visible.forEach((p,i) => { const barWidth = Math.max(2,Math.min(40,(x1-x0)/visible.length*.65)); svg += `<rect x="${clamp(x(i)-barWidth/2,x0,x1-barWidth)}" y="${y(p.value)}" width="${barWidth}" height="${y1-y(p.value)}" rx="2" fill="#2563eb" fill-opacity=".7"/>`; });
            if ($('chartAverage').checked) {
                const averages = visible.map((p,i) => {
                    const history = all.slice(Math.max(0,left+i-6),left+i+1);
                    return `${x(i)},${y(history.reduce((sum,p)=>sum+p.value,0)/history.length)}`;
                }).join(' ');
                svg += `<polyline points="${averages}" fill="none" stroke="#64748b" stroke-dasharray="5 4" stroke-width="1.6"/>`;
            }
            const last = visible.at(-1);
            svg += `<path d="M${x0} ${y(last.value)}H${x1}" stroke="#2563eb" stroke-opacity=".3" stroke-dasharray="4 4"/><circle cx="${x(visible.length-1)}" cy="${y(last.value)}" r="3.5" fill="#2563eb"/></g>`;
            svg += `<g id="stockCrosshair" visibility="hidden" pointer-events="none"><path id="stockCrossX" stroke="#94a3b8" stroke-dasharray="4 4"/><path id="stockCrossY" stroke="#94a3b8" stroke-dasharray="4 4"/><circle id="stockCrossDot" r="5" fill="#2563eb" stroke="white" stroke-width="2"/></g></svg>`;
            frame.innerHTML = svg + '<div class="stock-tooltip" hidden></div>';
            const total = visible.reduce((sum,p)=>sum+p.count,0);
            $('chartHeadline').textContent = format(total) + ' leads';
            $('chartHeadlineDetail').textContent = `Latest: ${format(last.value)} | High: ${format(Math.max(...visible.map(p=>p.value)))} | Low: ${format(Math.min(...visible.map(p=>p.value)))}`;
            $('chartSummary').textContent = `${visible[0].date} to ${last.date} | ${visible.length} of ${all.length} periods | ${format(all.length/visible.length)}x zoom${total===0?' | No leads in this window':''}`;
            $('chartZoomIn').disabled = visible.length <= Math.min(3,all.length);
            $('chartZoomOut').disabled = visible.length === all.length;
            from.value = left; to.value = right;
            navigator();
        }
        function navigator() {
            const max = Math.max(1,...all.map(p=>p.value));
            const points = all.map((p,i)=>`${i/Math.max(1,all.length-1)*1000},${50-p.value/max*42}`).join(' ');
            const a = all.length === 1 ? 0 : left/(all.length-1)*1000, b = all.length === 1 ? 1000 : right/(all.length-1)*1000;
            $('chartNavigator').innerHTML = `<svg viewBox="0 0 1000 56" preserveAspectRatio="none"><polygon points="0,56 ${points} 1000,56" fill="#f1f5f9"/><polyline points="${points}" fill="none" stroke="#94a3b8" stroke-width="1.2"/><rect x="${a}" y="1" width="${Math.max(1,b-a)}" height="54" fill="#2563eb" fill-opacity=".04" stroke="#94a3b8"/></svg>`;
        }
        function zoom(factor,anchor=.5) {
            const min = Math.min(3,all.length), count = clamp(Math.round(visible.length*factor),min,all.length);
            const focus = left+(visible.length-1)*anchor;
            left = clamp(Math.round(focus-(count-1)*anchor),0,all.length-count); right = left+count-1; draw();
        }
        function pan(delta) {
            const count = right-left; left = clamp(left+delta,0,all.length-count-1); right = left+count; draw();
        }
        frame.addEventListener('wheel', event => {
            event.preventDefault();
            const rect = frame.getBoundingClientRect();
            zoom(event.deltaY<0?.8:1.25,clamp((event.clientX-rect.left-geometry.x0)/(geometry.x1-geometry.x0),0,1));
        }, {passive:false});
        frame.addEventListener('pointerdown',event=>{
            if(event.button!==0) return;
            drag={x:event.clientX,left,right};frame.setPointerCapture(event.pointerId);frame.classList.add('is-panning');
        });
        const stop = ()=>{drag=null;frame.classList.remove('is-panning');};
        frame.addEventListener('pointerup',stop); frame.addEventListener('pointercancel',stop);
        frame.addEventListener('pointermove',event=>{
            if(drag) {
                const delta=Math.round((drag.x-event.clientX)/geometry.step),count=drag.right-drag.left;
                left=clamp(drag.left+delta,0,all.length-count-1);right=left+count;draw();return;
            }
            const rect=frame.getBoundingClientRect(), px=event.clientX-rect.left;
            const index=clamp(Math.round((px-geometry.x0)/geometry.step),0,visible.length-1),point=visible[index];
            const xx=geometry.x(index),yy=geometry.y(point.value);
            $('stockCrosshair').setAttribute('visibility','visible');
            $('stockCrossX').setAttribute('d',`M${xx} ${geometry.y0}V${geometry.y1}`);
            $('stockCrossY').setAttribute('d',`M${geometry.x0} ${yy}H${geometry.x1}`);
            $('stockCrossDot').setAttribute('cx',xx);$('stockCrossDot').setAttribute('cy',yy);
            const tooltip=frame.querySelector('.stock-tooltip');tooltip.hidden=false;
            tooltip.textContent=`${point.date} | ${format(point.value)} ${measure.value==='cumulative'?'cumulative leads':'leads'}`;
            tooltip.style.left=clamp(xx+15,8,geometry.width-240)+'px';tooltip.style.top=Math.max(8,yy-48)+'px';
        });
        frame.addEventListener('pointerleave',()=>{if($('stockCrosshair')) $('stockCrosshair').setAttribute('visibility','hidden');frame.querySelector('.stock-tooltip').hidden=true;});
        frame.addEventListener('keydown',event=>{
            if(!['+','=','-','ArrowLeft','ArrowRight','Home'].includes(event.key)) return;
            event.preventDefault();
            if(event.key==='+'||event.key==='=') zoom(.7);
            else if(event.key==='-') zoom(1.4);
            else if(event.key==='Home') {left=0;right=all.length-1;draw();}
            else pan((event.key==='ArrowLeft'?-1:1)*Math.max(1,Math.round(visible.length/5)));
        });
        from.oninput=()=>{left=Math.min(Number(from.value),right);draw();};
        to.oninput=()=>{right=Math.max(Number(to.value),left);draw();};
        [status,group,measure].forEach(control=>control.addEventListener('change',rebuild));
        [type,$('chartAverage')].forEach(control=>control.addEventListener('change',draw));
        $('chartZoomIn').onclick=()=>zoom(.7);$('chartZoomOut').onclick=()=>zoom(1.4);
        $('chartReset').onclick=()=>{left=0;right=all.length-1;draw();};
        function download(content,mime,name) {
            const url=URL.createObjectURL(new Blob([content],{type:mime}));const a=document.createElement('a');a.href=url;a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
        }
        $('chartExport').onclick=()=>download('Date,Leads,Displayed value\n'+visible.map(p=>`${p.date},${p.count},${p.value}`).join('\n'),'text/csv','lead-activity.csv');
        $('chartSave').onclick=()=>{const svg=frame.querySelector('svg').cloneNode(true);svg.querySelector('#stockCrosshair').remove();download(new XMLSerializer().serializeToString(svg),'image/svg+xml','lead-activity.svg');};
        $('chartFullscreen').hidden=!workspace.requestFullscreen;
        $('chartFullscreen').onclick=async()=>{
            try { if(document.fullscreenElement) await document.exitFullscreen(); else await workspace.requestFullscreen(); }
            catch { $('chartSummary').textContent='Fullscreen is unavailable in this browser window.'; }
        };
        document.addEventListener('fullscreenchange',()=>{$('chartFullscreen').textContent=document.fullscreenElement?'Exit fullscreen':'Fullscreen';draw();});
        document.querySelectorAll('[data-chart-days]').forEach(button=>button.onclick=()=>{
            const form=button.closest('form'),date=new Date(workspace.dataset.today+'T00:00:00Z');
            form.elements.chart_end.value=iso(date);date.setUTCDate(date.getUTCDate()-Number(button.dataset.chartDays)+1);form.elements.chart_start.value=iso(date);form.requestSubmit();
        });
        new ResizeObserver(()=>{if(all.length) draw();}).observe(frame);
        rebuild();
    };
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init);else init();
})();
