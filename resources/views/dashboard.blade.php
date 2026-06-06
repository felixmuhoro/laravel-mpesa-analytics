<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{--green:#00a651;--green-d:#007a3d;--green-l:#e6f5ee;--red:#e53935;--red-l:#fdecea;--amber:#f59e0b;--blue:#2563eb;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--radius:10px;--shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);}
        body{font-family:-apple-system,BlinkMacSystemFont,Roboto,sans-serif;background:var(--gray-50);color:var(--gray-700);min-height:100vh;}
        .header{background:var(--green);color:#fff;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100;}
        .header-brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:1.1rem;}
        .header-actions{display:flex;gap:8px;}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;text-decoration:none;}
        .btn-white{background:#fff;color:var(--green-d);}
        .btn-white:hover{background:var(--green-l);}
        .page{padding:24px;max-width:1400px;margin:0 auto;}
        .filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:22px;flex-wrap:wrap;}
        .filter-bar label{font-size:.8rem;font-weight:600;color:var(--gray-500);}
        select,input[type=text]{padding:7px 12px;border:1px solid var(--gray-200);border-radius:7px;font-size:.85rem;background:#fff;color:var(--gray-700);outline:none;cursor:pointer;}
        select:focus,input[type=text]:focus{border-color:var(--green);}
        .date-range-wrap{display:flex;align-items:center;gap:6px;}
        #apply-btn{background:var(--green);color:#fff;padding:7px 16px;border-radius:7px;border:none;font-size:.85rem;font-weight:600;cursor:pointer;}
        #apply-btn:hover{background:var(--green-d);}
        .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:22px;}
        .kpi{background:#fff;border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;}
        .kpi::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:var(--green);}
        .kpi.red::before{background:var(--red);}
        .kpi.amber::before{background:var(--amber);}
        .kpi.blue::before{background:var(--blue);}
        .kpi-label{font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--gray-500);}
        .kpi-value{font-size:1.75rem;font-weight:800;letter-spacing:-.03em;color:var(--gray-900);line-height:1;}
        .kpi-sub{font-size:.78rem;color:var(--gray-500);}
        .badge{display:inline-flex;align-items:center;gap:3px;font-size:.72rem;font-weight:700;padding:2px 7px;border-radius:20px;width:fit-content;}
        .badge-green{background:var(--green-l);color:var(--green-d);}
        .badge-red{background:var(--red-l);color:var(--red);}
        .charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;}
        .card{background:#fff;border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow);}
        .card-title{font-size:.82rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:var(--gray-500);margin-bottom:14px;}
        .chart-wrap{position:relative;width:100%;}
        .chart-wrap canvas{max-width:100%;}
        table{width:100%;border-collapse:collapse;font-size:.84rem;}
        th{text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--gray-500);padding:8px 12px;border-bottom:1px solid var(--gray-200);}
        td{padding:9px 12px;border-bottom:1px solid var(--gray-100);color:var(--gray-700);}
        tr:last-child td{border-bottom:none;}
        tr:hover td{background:var(--gray-50);}
        .phone{font-family:monospace;}
        #loading{display:none;position:fixed;inset:0;background:rgba(255,255,255,.65);z-index:999;align-items:center;justify-content:center;}
        #loading.active{display:flex;}
        .spinner{width:40px;height:40px;border:4px solid var(--gray-200);border-top-color:var(--green);border-radius:50%;animation:spin .7s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg);}}
        @media(max-width:900px){.charts-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div id="loading"><div class="spinner"></div></div>
<header class="header">
    <div class="header-brand">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <rect width="24" height="24" rx="6" fill="rgba(255,255,255,0.2)"/>
            <path d="M7 17l3-3 2 2 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="7" cy="17" r="1.2" fill="#fff"/>
            <circle cx="10" cy="14" r="1.2" fill="#fff"/>
            <circle cx="12" cy="16" r="1.2" fill="#fff"/>
            <circle cx="17" cy="11" r="1.2" fill="#fff"/>
        </svg>
        M-Pesa Analytics
    </div>
    <div class="header-actions">
        <a id="export-csv-btn" href="#" class="btn btn-white">&#8595; Export CSV</a>
    </div>
</header>
<div class="page">
    <div class="filter-bar">
        <label>Period</label>
        <select id="range-select">
            @foreach($ranges as $key => $label)
            <option value="{{ $key }}" {{ request('range', config('mpesa-analytics.default_range','last_30_days')) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
            <option value="custom">Custom range&hellip;</option>
        </select>
        <div class="date-range-wrap" id="custom-range" style="display:none">
            <input type="text" id="date-from" placeholder="From" style="width:130px">
            <span>&rarr;</span>
            <input type="text" id="date-to" placeholder="To" style="width:130px">
        </div>
        <button id="apply-btn">Apply</button>
    </div>
    <div class="kpi-grid">
        <div class="kpi"><div class="kpi-label">Total Revenue</div><div class="kpi-value" id="kpi-revenue">&#8212;</div><div class="kpi-sub" id="kpi-revenue-sub"></div></div>
        <div class="kpi"><div class="kpi-label">Avg Transaction</div><div class="kpi-value" id="kpi-avg">&#8212;</div><div class="kpi-sub" id="kpi-avg-sub"></div></div>
        <div class="kpi blue"><div class="kpi-label">Total Transactions</div><div class="kpi-value" id="kpi-total">&#8212;</div><div class="kpi-sub" id="kpi-total-sub"></div></div>
        <div class="kpi"><div class="kpi-label">Success Rate</div><div class="kpi-value" id="kpi-success">&#8212;</div><div class="kpi-sub" id="kpi-success-sub"></div></div>
        <div class="kpi red"><div class="kpi-label">Failed</div><div class="kpi-value" id="kpi-failed">&#8212;</div><div class="kpi-sub" id="kpi-failed-sub"></div></div>
        <div class="kpi amber"><div class="kpi-label">Revenue Growth</div><div class="kpi-value" id="kpi-growth">&#8212;</div><div class="kpi-sub">vs previous period</div></div>
    </div>
    <div class="charts-grid">
        <div class="card"><div class="card-title">Revenue over time</div><div class="chart-wrap"><canvas id="revenueChart" height="220"></canvas></div></div>
        <div class="card"><div class="card-title">Success Rate</div><div class="chart-wrap"><canvas id="gaugeChart" height="220"></canvas></div></div>
    </div>
    <div class="charts-grid">
        <div class="card"><div class="card-title">Transaction Volume</div><div class="chart-wrap"><canvas id="volumeChart" height="200"></canvas></div></div>
        <div class="card"><div class="card-title">Hourly Pattern</div><div class="chart-wrap"><canvas id="hourlyChart" height="200"></canvas></div></div>
    </div>
    <div class="charts-grid">
        <div class="card">
            <div class="card-title">Top Payers by Amount</div>
            <table>
                <thead><tr><th>#</th><th>Phone</th><th>Total (KSh)</th><th>Txns</th><th>Avg (KSh)</th></tr></thead>
                <tbody id="payers-tbody"><tr><td colspan="5" style="text-align:center;color:var(--gray-500)">Loading&#8230;</td></tr></tbody>
            </table>
        </div>
        <div class="card"><div class="card-title">Failure Reasons</div><div class="chart-wrap"><canvas id="failureChart" height="240"></canvas></div></div>
    </div>
</div>
<script>
var CURRENCY='{{ config("mpesa-analytics.currency_symbol","KSh") }}';
var MASK={{ config("mpesa-analytics.mask_phones",true)?"true":"false" }};
var DATA_URL='{{ route("mpesa-analytics.data") }}';
var CSV_URL='{{ route("mpesa-analytics.export.csv") }}';
var revenueChart,volumeChart,hourlyChart,failureChart,gaugeChart;
function fmt(n){return CURRENCY+' '+Number(n).toLocaleString('en-KE',{minimumFractionDigits:2,maximumFractionDigits:2});}
function fmtNum(n){return Number(n).toLocaleString('en-KE');}
function maskPhone(p){if(!MASK||!p||p.length<8)return p;return p.slice(0,5)+'****'+p.slice(-4);}
function buildRevenue(data){
    if(revenueChart)revenueChart.destroy();
    revenueChart=new Chart(document.getElementById('revenueChart'),{type:'line',data:{labels:data.map(function(d){return d.date;}),datasets:[{label:'Revenue',data:data.map(function(d){return d.total;}),fill:true,borderColor:'#00a651',backgroundColor:'rgba(0,166,81,.08)',tension:.38,pointRadius:data.length>30?0:3,pointHoverRadius:5}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return fmt(c.parsed.y);}}}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8}},y:{ticks:{callback:function(v){return CURRENCY+' '+(v>=1000?(v/1000).toFixed(0)+'k':v);}}}}}});
}
function buildVolume(data){
    if(volumeChart)volumeChart.destroy();
    volumeChart=new Chart(document.getElementById('volumeChart'),{type:'bar',data:{labels:data.map(function(d){return d.date;}),datasets:[{label:'Successful',data:data.map(function(d){return d.successful;}),backgroundColor:'rgba(0,166,81,.75)',borderRadius:4},{label:'Failed',data:data.map(function(d){return d.failed;}),backgroundColor:'rgba(229,57,53,.65)',borderRadius:4}]},options:{responsive:true,plugins:{legend:{position:'top',labels:{boxWidth:12}}},scales:{x:{stacked:true,grid:{display:false},ticks:{maxTicksLimit:8}},y:{stacked:true,ticks:{precision:0}}}}});
}
function buildHourly(data){
    if(hourlyChart)hourlyChart.destroy();
    hourlyChart=new Chart(document.getElementById('hourlyChart'),{type:'bar',data:{labels:data.map(function(d){return d.label;}),datasets:[{label:'Transactions',data:data.map(function(d){return d.count;}),backgroundColor:'rgba(37,99,235,.65)',borderRadius:3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8}},y:{ticks:{precision:0}}}}});
}
function buildGauge(rate){
    if(gaugeChart)gaugeChart.destroy();
    gaugeChart=new Chart(document.getElementById('gaugeChart'),{type:'doughnut',data:{labels:['Success','Failure'],datasets:[{data:[rate,100-rate],backgroundColor:['#00a651','#e53935'],borderWidth:0,circumference:270,rotation:-135}]},options:{responsive:true,cutout:'72%',plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return c.label+': '+c.parsed.toFixed(1)+'%';}}}}},plugins:[{id:'gc',afterDraw:function(chart){var ctx=chart.ctx,ca=chart.chartArea,cx=(ca.left+ca.right)/2,cy=(ca.top+ca.bottom)/2+28;ctx.save();ctx.textAlign='center';ctx.fillStyle='#0f172a';ctx.font='bold 2rem sans-serif';ctx.fillText(rate.toFixed(1)+'%',cx,cy);ctx.fillStyle='#64748b';ctx.font='.75rem sans-serif';ctx.fillText('success rate',cx,cy+22);ctx.restore();}}]});
}
function buildFailure(data){
    if(failureChart)failureChart.destroy();
    if(!data.length)return;
    failureChart=new Chart(document.getElementById('failureChart'),{type:'pie',data:{labels:data.map(function(d){return d.description;}),datasets:[{data:data.map(function(d){return d.count;}),backgroundColor:['#e53935','#f59e0b','#2563eb','#7c3aed','#db2777','#059669','#0891b2','#d97706'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,plugins:{legend:{position:'right',labels:{font:{size:11},boxWidth:12,padding:10}},tooltip:{callbacks:{label:function(c){return c.label+': '+c.parsed+' txns';}}}}}});
}
function updateKPIs(p){
    var r=p.revenue,t=p.transactions;
    document.getElementById('kpi-revenue').textContent=fmt(r.total);
    var dir=r.growth_percent>=0?String.fromCharCode(9650):String.fromCharCode(9660),cls=r.growth_percent>=0?'badge-green':'badge-red';
    document.getElementById('kpi-revenue-sub').innerHTML='<span class="badge '+cls+'">'+dir+' '+Math.abs(r.growth_percent)+'%</span> vs prev period';
    document.getElementById('kpi-avg').textContent=fmt(r.average);
    document.getElementById('kpi-avg-sub').textContent='Median: '+fmt(r.median);
    document.getElementById('kpi-total').textContent=fmtNum(t.total);
    document.getElementById('kpi-total-sub').textContent=fmtNum(t.successful)+' successful';
    document.getElementById('kpi-success').textContent=t.success_rate.toFixed(1)+'%';
    document.getElementById('kpi-success-sub').textContent=fmtNum(t.successful)+' txns';
    document.getElementById('kpi-failed').textContent=fmtNum(t.failed);
    document.getElementById('kpi-failed-sub').textContent=t.failure_rate.toFixed(1)+'% failure rate';
    document.getElementById('kpi-growth').textContent=(r.growth_percent>=0?'+':'')+r.growth_percent.toFixed(1)+'%';
}
function updatePayersTable(payers){
    var tbody=document.getElementById('payers-tbody');
    if(!payers.length){tbody.innerHTML='<tr><td colspan="5" style="text-align:center;color:var(--gray-500)">No data</td></tr>';return;}
    tbody.innerHTML=payers.map(function(p,i){return '<tr><td>'+(i+1)+'</td><td class="phone">'+maskPhone(p.phone)+'</td><td><strong>'+fmt(p.total_amount)+'</strong></td><td>'+fmtNum(p.transaction_count)+'</td><td>'+fmt(p.avg_amount)+'</td></tr>';}).join('');
}
function buildQuery(){
    var range=document.getElementById('range-select').value,q=new URLSearchParams();
    if(range==='custom'){var from=document.getElementById('date-from').value,to=document.getElementById('date-to').value;if(from)q.set('from',from);if(to)q.set('to',to);}else{q.set('range',range);}
    return q.toString();
}
function fetchData(){
    document.getElementById('loading').classList.add('active');
    fetch(DATA_URL+'?'+buildQuery(),{headers:{'Accept':'application/json'}})
        .then(function(res){return res.json();})
        .then(function(p){updateKPIs(p);buildRevenue(p.daily_revenue);buildVolume(p.daily_transactions);buildHourly(p.hourly_pattern);buildGauge(p.transactions.success_rate);buildFailure(p.failure_reasons);updatePayersTable(p.top_payers);document.getElementById('export-csv-btn').href=CSV_URL+'?'+buildQuery();})
        .catch(function(e){console.error('Analytics error:',e);})
        .finally(function(){document.getElementById('loading').classList.remove('active');});
}
document.getElementById('range-select').addEventListener('change',function(){document.getElementById('custom-range').style.display=this.value==='custom'?'flex':'none';});
document.getElementById('apply-btn').addEventListener('click',fetchData);
flatpickr('#date-from',{dateFormat:'Y-m-d',maxDate:'today'});
flatpickr('#date-to',{dateFormat:'Y-m-d',maxDate:'today'});
fetchData();
</script>
</body>
</html>