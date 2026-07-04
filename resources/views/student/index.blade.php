@extends('layouts.student.app')

@section('css')
<style>
  :root{
    --bg:#f6f8fb; --fg:#0f172a; --muted:#6b7280;
    --card:#ffffff; --card-bd:#e5e7eb;
    --brand:#007a33; --brand2:#005a26; --accent:#0f172a;
    --ok:#16a34a; --warn:#f59e0b; --bad:#ef4444;
    --shadow:0 10px 28px rgba(2,6,23,.08);
  }
  [data-theme="dark"]{
    --bg:#0b1220; --fg:#e5e7eb; --muted:#94a3b8;
    --card:#0f172a; --card-bd:#1f2a44;
    --brand:#4ade80; --brand2:#22c55e; --accent:#e5e7eb;
    --ok:#22c55e; --warn:#f59e0b; --bad:#ef4444;
    --shadow:0 18px 44px rgba(0,0,0,.45);
  }
  html,body{background:var(--bg);color:var(--fg)}
  .page-header{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;justify-content:space-between}

  /* header chips */
  .chip{
    display:inline-flex; align-items:center; gap:.4rem;
    background:var(--card); border:1px solid var(--card-bd); color:var(--fg);
    border-radius:999px; padding:.25rem .6rem; font-weight:700; box-shadow:var(--shadow);
    font-size:.82rem;
  }
  .chip i{opacity:.9}
  .chip .label{color:var(--muted); font-weight:800; text-transform:uppercase; letter-spacing:.03em}

  /* ===== KPI cards (equal size) ===== */
  .kpi{
    display:flex; align-items:center; gap:.9rem; flex:1;
    padding:1rem 1.1rem; border-radius:14px; min-height:90px;
    background:var(--card); border:1px solid var(--card-bd); box-shadow:var(--shadow);
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .kpi:hover{ transform:translateY(-2px); box-shadow:0 14px 34px rgba(2,6,23,.12); }
  .kpi .ico{
    width:42px;height:42px;border-radius:12px;display:grid;place-items:center;
    background:rgba(0,122,51,.08); color:var(--brand);
    flex:0 0 42px; border:1px solid var(--card-bd);
  }
  [data-theme="dark"] .kpi .ico{ background:rgba(74,222,128,.15) }
  .kpi .meta{display:flex;flex-direction:column;line-height:1.2}
  .kpi .lbl{font-weight:800;letter-spacing:.02em;font-size:.8rem;color:var(--muted);text-transform:uppercase}
  .kpi .num{font-weight:900;font-size:1.35rem;color:var(--fg)}

  /* Equal card height across grid */
  .row.g-3>[class*="col"]{display:flex}
  .row.g-3>[class*="col"]>.kpi{width:100%}

  /* KPI border variants */
  .bd-brand{ border-color: rgba(0,122,51,.55) !important }
  .bd-ok   { border-color: rgba(22,163,74,.55) !important }
  .bd-warn { border-color: rgba(245,158,11,.55) !important }
  .bd-bad  { border-color: rgba(239,68,68,.55) !important }

  .ico.brand{ color:var(--brand) }
  .ico.ok   { color:var(--ok) }
  .ico.warn { color:var(--warn) }
  .ico.bad  { color:var(--bad) }

  /* ===== Cards ===== */
  .card{border:1px solid var(--card-bd);border-radius:14px;background:var(--card);box-shadow:var(--shadow)}
  .card-header{
    border-bottom:1px solid var(--card-bd);
    background:linear-gradient(180deg, rgba(248,250,252,.6), rgba(255,255,255,0));
    display:flex; align-items:center; justify-content:space-between; gap:.75rem;
  }
  .card-title{display:flex;align-items:center;gap:.5rem;margin-bottom:0;font-weight:800}
  .card-title i{opacity:.9}

  /* ===== Table ===== */
  .table thead th{white-space:nowrap}
  #recentTable tbody tr{transition:background .15s ease}
  #recentTable tbody tr:hover{background:rgba(0,122,51,.07)}
  [data-theme="dark"] #recentTable tbody tr:hover{background:rgba(96,165,250,.12)}
  .row-title{font-weight:700}
  .row-sub{font-size:.85rem;color:var(--muted)}

  /* Pills & progress */
  .pill{display:inline-block;font-size:.75rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;border:1px solid var(--card-bd)}
  .pill.good{background:rgba(22,163,74,.12)} .pill.warn{background:rgba(245,158,11,.12)} .pill.bad{background:rgba(239,68,68,.12)}
  .progress-thin{height:.5rem;background:transparent;border:1px solid var(--card-bd);border-radius:999px;overflow:hidden}
  .progress-thin>.bar{height:100%;background:linear-gradient(90deg,var(--brand),var(--brand2))}
</style>

<script>
  // Respect system theme on first load; persist toggles
  (()=>{ 
    const k='studentTheme';
    const saved=localStorage.getItem(k);
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    if(saved==='dark' || (!saved && prefersDark)){ document.documentElement.setAttribute('data-theme','dark'); }
  })();
</script>
@endsection

@section('body')
<div class="container-fluid py-4">

  <!-- Header -->
  <div class="page-header mb-3">
    <div>
      <h4 class="mb-1">Student Dashboard</h4>

      <!-- Department & Degree Level line -->
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="chip">
          <i class="ri-bank-line"></i>
          <span class="label">Department</span>
          <span>{{ $departmentName ?? '—' }}</span>
        </span>
        <span class="chip">
          <i class="ri-graduation-cap-line"></i>
          <span class="label">Degree Level</span>
          <span>{{ $degreeLevelName ?? '—' }}</span>
        </span>
      </div>
      <!-- /Department & Degree Level line -->
    </div>
    <div>
      <button id="themeBtn" class="btn btn-sm btn-outline-secondary">
        <i class="ri-contrast-drop-2-line me-1"></i> Theme
      </button>
    </div>
  </div>

  <!-- KPI GRID (no "Graded" / no "Pending total") -->
  <div class="row g-3 mb-2">
    <!-- Department KPIs -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi bd-brand">
        <div class="ico brand"><i class="ri-book-2-line"></i></div>
        <div class="meta"><div class="lbl">Dept Modules</div><div class="num">{{ $deptModulesCount }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi">
        <div class="ico brand"><i class="ri-file-list-2-line"></i></div>
        <div class="meta"><div class="lbl">Dept Lessons</div><div class="num">{{ $deptLessonsCount }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi">
        <div class="ico brand"><i class="ri-timer-line"></i></div>
        <div class="meta"><div class="lbl">Dept Exams</div><div class="num">{{ $deptExamsCount }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi">
        <div class="ico brand"><i class="ri-question-answer-line"></i></div>
        <div class="meta"><div class="lbl">Dept Quizzes</div><div class="num">{{ $deptQuizzesCount }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi">
        <div class="ico brand"><i class="ri-clipboard-line"></i></div>
        <div class="meta"><div class="lbl">Dept Assign.</div><div class="num">{{ $deptAssignmentsCount }}</div></div>
      </div>
    </div>

    <!-- Student KPIs -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi">
        <div class="ico"><i class="ri-team-line"></i></div>
        <div class="meta"><div class="lbl">My Classes</div><div class="num">{{ $classesCount }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi bd-ok">
        <div class="ico ok"><i class="ri-line-chart-line"></i></div>
        <div class="meta"><div class="lbl">Average %</div><div class="num">{{ $avgPercent !== null ? $avgPercent.'%' : '—' }}</div></div>
      </div>
    </div>

    <!-- Pending by type -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi bd-bad">
        <div class="ico bad"><i class="ri-timer-flash-line"></i></div>
        <div class="meta"><div class="lbl">Exam Pending</div><div class="num">{{ $pendingByType['Exams'] ?? 0 }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi bd-warn">
        <div class="ico warn"><i class="ri-questionnaire-line"></i></div>
        <div class="meta"><div class="lbl">Quiz Pending</div><div class="num">{{ $pendingByType['Quizzes'] ?? 0 }}</div></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi bd-warn">
        <div class="ico warn"><i class="ri-draft-line"></i></div>
        <div class="meta"><div class="lbl">Assign. Pending</div><div class="num">{{ $pendingByType['Assignments'] ?? 0 }}</div></div>
      </div>
    </div>
  </div>

  <!-- ===== Charts ===== -->
  <div class="row g-3">
    <!-- Overall Average (replaces Monthly Average) -->
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0"><i class="ri-speed-up-line"></i> Overall Average (%)</h6>
          <small class="text-muted">All submissions</small>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div style="max-width:340px;width:100%"><canvas id="chartOverall"></canvas></div>
        </div>
      </div>
    </div>

    <!-- Assessment Mix -->
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0"><i class="ri-pie-chart-2-line"></i> Your Assessment Mix</h6>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div style="max-width:340px;width:100%"><canvas id="chartMix"></canvas></div>
        </div>
      </div>
    </div>

    <!-- Pending by Type -->
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0"><i class="ri-bar-chart-horizontal-line"></i> My Pending by Type</h6>
        </div>
        <div class="card-body" style="height:320px">
          <canvas id="chartPending"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Recent Results ===== -->
  <div class="card mt-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="card-title mb-0"><i class="ri-list-check-2"></i> Recent Results</h6>
      <div class="input-group input-group-sm" style="max-width:420px">
        <span class="input-group-text"><i class="ri-search-line"></i></span>
        <input id="qSearch" type="search" class="form-control" placeholder="Search by title, course, type…">
        <button class="btn btn-outline-secondary" id="clearSearch" type="button">Clear</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0" id="recentTable">
        <thead class="table-light">
          <tr>
            <th style="width:20px">#</th>
            <th>Assessment</th>
            <th>Course</th>
            <th class="text-center">Score</th>
            <th style="width:240px">Progress</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recent as $i => $r)
            @php
              $pct = $r->pct;
              $pill = $pct === null ? '' : ($pct >= 70 ? 'good' : ($pct >= 40 ? 'warn' : 'bad'));
            @endphp
            <tr data-title="{{ strtolower(($r->title ?? '').' '.($r->course ?? '').' '.$r->type) }}">
              <td>{{ $i+1 }}</td>
              <td>
                <div class="row-title">{{ $r->title }}</div>
                <div class="row-sub">
                  <span class="badge {{ $r->type==='Exam'?'badge-soft-danger':($r->type==='Quiz'?'badge-soft-success':'badge-soft-secondary') }}">{{ $r->type }}</span>
                  <span class="ms-2 pill {{ $pill }}">{{ $pct !== null ? $pct.'%' : 'Pending' }}</span>
                </div>
              </td>
              <td class="text-muted">{{ $r->course ?? '—' }}</td>
              <td class="text-center">
                @if($pct !== null)
                  <span class="fw-bold">{{ rtrim(rtrim(number_format((float)$r->score, 2, '.', ''), '0'), '.') }}</span>
                  <span class="text-muted">/ {{ $r->total }}</span>
                @else <span class="text-muted">—</span> @endif
              </td>
              <td>
                @if($pct !== null)
                  <div class="progress-thin" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                    <div class="bar" style="width: {{ $pct }}%"></div>
                  </div>
                  <div class="small text-muted mt-1">{{ $pct }}%</div>
                @else
                  <span class="small text-muted">Not graded yet</span>
                @endif
              </td>
              <td class="text-muted">{{ optional($r->created_at)->format('d M Y, H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No recent data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
  // Theme toggle
  const KEY='studentTheme';
  document.getElementById('themeBtn')?.addEventListener('click', ()=>{
    const cur = document.documentElement.getAttribute('data-theme')==='dark' ? 'dark' : 'light';
    const next = cur==='dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem(KEY, next);
  });

  // Data (safe fallbacks)
  const mixObj          = @json($mix ?? []);
  const pendingObj      = @json($pendingByType ?? []);
  const overallAvg      = Number(@json($avgPercent ?? null));

  // Colors
  const cs = getComputedStyle(document.documentElement);
  const fg = cs.getPropertyValue('--fg').trim() || '#0f172a';
  const bd = cs.getPropertyValue('--card-bd').trim() || '#e5e7eb';
  const brand = cs.getPropertyValue('--brand').trim() || '#007a33';
  const brand2 = cs.getPropertyValue('--brand2').trim() || '#005a26';

  // Overall Average (single-value doughnut gauge)
  const overallCtx = document.getElementById('chartOverall')?.getContext('2d');
  if (overallCtx) {
    const target = Number.isFinite(overallAvg) ? Math.max(0, Math.min(100, overallAvg)) : null;

    if (target === null) {
      new Chart(overallCtx, {
        type:'doughnut',
        data:{ labels:['No data'], datasets:[{ data:[100], borderWidth:0, backgroundColor:['#e5e7eb'] }]},
        options:{ cutout:'70%', plugins:{ legend:{ display:false }, tooltip:{ enabled:false } } }
      });
    } else {
      new Chart(overallCtx, {
        type:'doughnut',
        data:{
          labels:['Achieved','Remaining'],
          datasets:[{
            data:[ target, 100 - target ],
            borderWidth:0,
            backgroundColor:[ brand, '#e5e7eb' ]
          }]
        },
        options:{
          cutout:'70%',
          plugins:{
            legend:{ display:false },
            tooltip:{ callbacks:{ label:ctx => (ctx.raw ?? 0).toFixed(1) + '%' } }
          }
        },
        plugins: [{
          id:'centerText',
          afterDraw(chart){
            const {ctx} = chart;
            const meta = chart.getDatasetMeta(0).data[0];
            if (!meta) return;
            ctx.save();
            ctx.textAlign='center';
            ctx.textBaseline='middle';
            ctx.fillStyle = fg;
            ctx.font = '700 26px system-ui, -apple-system, Segoe UI, Roboto';
            ctx.fillText(target.toFixed(1) + '%', meta.x, meta.y);
            ctx.font = '600 12px system-ui, -apple-system, Segoe UI, Roboto';
            ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#6b7280';
            ctx.fillText('Average', meta.x, meta.y + 20);
            ctx.restore();
          }
        }]
      });
    }
  }

  // Doughnut: Assessment Mix
  const mixLabels = Object.keys(mixObj);
  const mixData   = Object.values(mixObj).map(v=>+v||0);
  new Chart(document.getElementById('chartMix').getContext('2d'), {
    type:'doughnut',
    data:{ labels: mixLabels, datasets:[{ data: mixData, borderWidth:0, backgroundColor:[brand, brand2, '#94a3b8'] }]},
    options:{ cutout:'62%', plugins:{ legend:{ position:'bottom', labels:{ color:fg } } } }
  });

  // Bar: Pending by Type
  const pLabels = Object.keys(pendingObj);
  const pData   = Object.values(pendingObj).map(v=>+v||0);
  new Chart(document.getElementById('chartPending').getContext('2d'), {
    type:'bar',
    data:{ labels:pLabels, datasets:[{ label:'Pending', data:pData, backgroundColor:[ '#ef4444', '#f59e0b', '#f59e0b' ], borderWidth:0 }] },
    options:{
      maintainAspectRatio:false,
      plugins:{ legend:{ display:false } },
      scales:{ y:{ beginAtZero:true, precision:0, ticks:{ color:fg }, grid:{ color:bd }}, x:{ ticks:{ color:fg }, grid:{ color:bd }} }
    }
  });

  // Live search
  const q=document.getElementById('qSearch'), clearBtn=document.getElementById('clearSearch');
  const rows=[...document.querySelectorAll('#recentTable tbody tr')];
  const norm=s=>(s||'').toString().toLowerCase().trim();
  function apply(){
    const term=norm(q?.value); let vis=0;
    rows.forEach(tr=>{const t=tr.getAttribute('data-title')||''; const show=!term||t.includes(term); tr.style.display=show?'':'none'; if(show) vis++;});
    let empty=document.getElementById('noRowsDash');
    if(!empty){ empty=document.createElement('tr'); empty.id='noRowsDash'; empty.innerHTML='<td colspan="6" class="text-center text-muted py-4">No results match your search.</td>'; document.querySelector('#recentTable tbody').appendChild(empty); }
    empty.style.display=vis?'none':'';
  }
  let t; q?.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(apply, 120); });
  clearBtn?.addEventListener('click', ()=>{ q.value=''; q.focus(); apply(); });
  apply();
})();
</script>
@endsection
