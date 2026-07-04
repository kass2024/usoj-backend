@extends('layouts.app')

@section('css')
<style>
  :root{
    --bg:#f7f9fc; --surface:#ffffff; --surface-2:#fbfcff; --ink:#0b1220; --muted:#6b7280;
    --line:#e0e6f0; --line-strong:#d5dbe8;
    --brand:#007a33; --accent:#005a26; --ok:#16a34a; --warn:#f59e0b; --bad:#ef4444;
    --radius:16px; --shadow:0 16px 34px rgba(10,20,40,.06);
  }
  body{background:var(--bg); color:var(--ink)}
  .wrap{max-width:1500px;margin:0 auto;padding:1.25rem 1rem}
  .page-header{display:flex;align-items:end;justify-content:space-between;gap:1rem;margin-bottom:1rem}
  .title{margin:0;font-weight:800;letter-spacing:.2px}
  .subtle{color:var(--muted)}
  .small-subtle{color:var(--muted);font-size:.9rem}

  /* Cards */
  .card {
    border:1.5px solid var(--line-strong)!important;
    border-radius:var(--radius)!important;
    background:var(--surface);
    box-shadow:var(--shadow);
    overflow:hidden;
  }
  .card-header{
    background:var(--surface-2);
    border-bottom:1.5px solid var(--line-strong);
    display:flex;align-items:center;justify-content:space-between;
    padding:.9rem 1rem
  }
  .card-body{padding:1rem}

  .grid{display:grid;gap:1rem}
  .grid--kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
  @media(min-width:992px){ .grid--kpi{grid-template-columns:repeat(4,minmax(0,1fr))} }
  .grid--2cols{grid-template-columns:1fr}
  @media(min-width:992px){ .grid--2cols{grid-template-columns:1fr 1fr} }

  /* KPI cards (no blue line) */
  .kpi{
    display:flex;align-items:center;gap:1rem;
    border:1.5px solid var(--line-strong);
    border-radius:14px;
    padding:1rem;
    background:var(--surface);
    box-shadow:var(--shadow);
  }
  .kpi .icon{
    width:56px;height:56px;border-radius:14px;display:grid;place-items:center;
    background:var(--surface-2);
    border:1.5px solid var(--line);
    color:var(--brand);
  }
  .kpi .num{font-size:2.1rem;line-height:1;font-weight:900;letter-spacing:.2px}
  .kpi canvas,
  .kpi [data-mini="spark"] { display:none !important; } /* ensure no internal line */

  /* Tables */
  .table thead th{font-weight:700;color:#111827;border-bottom:1.5px solid var(--line-strong)!important;background:var(--surface-2); padding:.85rem .75rem}
  .table tbody td{border-color:var(--line)!important; padding:.70rem .75rem}
  .table-hover tbody tr:hover{background:#f0fdf4}
  .table-striped tbody tr:nth-of-type(odd){background:#fcfdff}
  .table-sticky thead th{position:sticky;top:0;z-index:2}
  .table-wrap{border-top:1px solid var(--line-strong)}

  /* Search + filters */
  .search-wrap{position:relative;min-width:260px}
  .search-input{border-radius:999px!important;padding-left:2.3rem!important;height:40px}
  .search-wrap .bi-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.55}
  .filters{display:flex;flex-wrap:wrap;gap:.6rem}
  .filters .form-select, .filters .form-control{height:40px}
  .filters .btn{height:40px}

  .legend-pill{font-size:.8rem;border:1.5px solid var(--line-strong);border-radius:999px;padding:.15rem .55rem;background:var(--surface-2)}
  .chart-empty{display:grid;place-items:center;height:100%;color:var(--muted);font-weight:600;border:1px dashed var(--line);border-radius:12px}

  mark.hl{background:#fff3b0;padding:.05rem .2rem;border-radius:.2rem}
</style>
@endsection

@section('body')
<div class="wrap" id="dashSnow">
  <div class="page-header">
    <div>
      <h1 class="title h3 mb-1">Admin Dashboard</h1>
      <div class="subtle">Overview of students, departments, courses, assignments, quizzes & exams</div>
    </div>
  </div>

  @php
    $kpis = [
      ['Students',$totalStudents ?? 0,'bi-people'],
      ['Departments',$totalDepartments ?? 0,'bi-bank'],
      ['Courses',$totalCourses ?? 0,'bi-journal-text'],
      ['Assignments',$totalAssignments ?? 0,'bi-check2-square'],
      ['Exams',$totalExams ?? 0,'bi-file-earmark-text'],
      ['Quizzes',$totalQuizzes ?? 0,'bi-patch-question'],
      ['Modules',$totalModules ?? 0,'bi-diagram-3'],
      ['Lessons',$totalLessons ?? 0,'bi-collection-play'],
    ];
  @endphp

  <!-- KPI row -->
  <div class="grid grid--kpi mb-3">
    @foreach($kpis as [$label,$value,$icon])
      <div class="kpi">
        <div class="icon"><i class="bi {{ $icon }}"></i></div>
        <div class="flex-grow-1">
          <div class="subtle small">{{ $label }}</div>
          <div class="num"><span class="count-up" data-target="{{ (int)$value }}">0</span></div>
        </div>
      </div>
    @endforeach
  </div>

  <!-- CHARTS -->
  <div class="grid grid--2cols mb-3">
    <!-- 1. Enrollment Trend -->
    <div class="card">
      <div class="card-header">
        <strong>Enrollment Trend — {{ now()->year }}</strong>
        <span class="legend-pill">7-point MA</span>
      </div>
      <div class="card-body">
        <div style="height:360px"><canvas id="enrollArea"></canvas></div>
      </div>
    </div>

    <!-- 2. Students Enrolled by Department -->
    <div class="card">
      <div class="card-header"><strong>Students Enrolled by Department</strong></div>
      <div class="card-body">
        <div id="deptStudentsWrap" style="height:360px;position:relative">
          <canvas id="deptStudents"></canvas>
          <div id="deptStudentsEmpty" class="chart-empty d-none">No enrollment data</div>
        </div>
        <div class="small-subtle mt-2">Sorted by highest enrollment. Values shown at the end of each bar.</div>
      </div>
    </div>

    <!-- 3. Courses by Department -->
    <div class="card">
      <div class="card-header"><strong>Courses by Department</strong></div>
      <div class="card-body">
        <div style="height:360px"><canvas id="deptCourses"></canvas></div>
      </div>
    </div>

    <!-- 4. Assessments Overview -->
    <div class="card">
      <div class="card-header"><strong>Assessments Overview (Totals)</strong></div>
      <div class="card-body">
        <div style="height:360px"><canvas id="aqeDonut"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Students Table + Filters + Live Search -->
  <div class="card">
    <div class="card-header">
      <strong>Students</strong>

      <div class="d-flex align-items-center gap-2">
        <!-- Live text search -->
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input id="studentSearch" class="form-control search-input" placeholder="Search Name, Department, RegNumber, Email…">
        </div>

        <!-- Filters -->
        <div class="filters">
          <select id="filterYear" class="form-select">
            <option value="">Year</option>
            @foreach(($distinctYears ?? []) as $y)
              <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
          </select>

          <select id="filterMonth" class="form-select">
            <option value="">Month</option>
            @for($m=1;$m<=12;$m++)
              <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('M') }}</option>
            @endfor
          </select>

          <input type="date" id="filterFrom" class="form-control" placeholder="From">
          <input type="date" id="filterTo" class="form-control" placeholder="To">
          <button id="applyFilters" class="btn btn-primary">Apply</button>
          <button id="clearFilters" class="btn btn-outline-secondary">Clear</button>
        </div>
      </div>
    </div>

    <div class="card-body p-0 table-wrap">
      <div class="table-responsive" style="max-height:560px">
        <table class="table table-hover table-striped align-middle mb-0 table-sticky" id="studentsTable">
          <thead>
            <tr>
              <th style="width:52px">#</th>
              <th>Name</th>
              <th>Department</th>
              <th>RegNumber</th>
              <th>Email</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
          @foreach(($students ?? []) as $i=>$s)
            <tr data-joined="{{ optional($s->created_at)?->toDateString() }}">
              <td class="subtle">{{ $i+1 }}</td>
              <td class="fw-semibold">{{ $s->name }}</td>
              <td>{{ optional($s->department)->name ?? '—' }}</td>
              <td>{{ $s->reg_number ?? '—' }}</td>
              <td>{{ $s->email ?? '—' }}</td>
              <td>{{ optional($s->created_at)?->format('Y-m-d') }}</td>
            </tr>
          @endforeach
          </tbody>
          <tbody id="studentsNoRows" class="d-none">
            <tr><td colspan="6" class="text-center subtle">No results</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('js')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const $ = (sel,ctx=document)=>ctx.querySelector(sel);
  const $$ = (sel,ctx=document)=>Array.from(ctx.querySelectorAll(sel));

  // Count-up
  $$('.count-up').forEach(el=>{
    const target = +el.dataset.target || 0;
    const dur = 700, start = performance.now();
    const tick = (now)=>{
      const p = Math.min(1, (now-start)/dur);
      el.textContent = Math.floor(target*p).toLocaleString();
      if(p<1) requestAnimationFrame(tick); else el.textContent = target.toLocaleString();
    };
    requestAnimationFrame(tick);
  });

  // remove any leftover spark canvases (defensive)
  document.querySelectorAll('.kpi canvas,[data-mini="spark"]').forEach(el=>el.remove());

  // ===== Data (from PHP) =====
  const months            = @json($months ?? []);
  const enrollments       = (@json($enrollments ?? []) || []).map(x=>Number(x||0));
  const deptLabels        = @json($deptLabels ?? []);                 // department names (for courses)
  const deptTotals        = (@json($deptTotals ?? []) || []).map(x=>Number(x||0)); // course counts
  const deptStudentLabels = @json($deptStudentLabels ?? []);          // department names (for students)
  const deptStudentTotals = (@json($deptStudentTotals ?? []) || []).map(x=>Number(x||0)); // student counts
  const totalAssignments  = Number(@json($totalAssignments ?? 0));
  const totalExams        = Number(@json($totalExams ?? 0));
  const totalQuizzes      = Number(@json($totalQuizzes ?? 0));

  // 7-point moving average helper
  const ma7 = (arr)=>{
    const out=[]; for(let i=0;i<arr.length;i++){
      const a=Math.max(0,i-3), b=Math.min(arr.length-1,i+3);
      const slice=arr.slice(a,b+1);
      out.push(slice.reduce((s,x)=>s+Number(x||0),0)/slice.length);
    } return out;
  };

  // ---------- Chart 1: Enrollment Trend ----------
  (function(){
    const ctx = document.getElementById('enrollArea').getContext('2d');
    const gradFill = ctx.createLinearGradient(0,0,0,360);
    gradFill.addColorStop(0,'rgba(10,92,255,.18)');
    gradFill.addColorStop(1,'rgba(10,92,255,0)');
    new Chart(ctx, {
      type:'line',
      data:{
        labels: months,
        datasets:[
          { label:'Enrollments', data: enrollments, borderWidth:2.4, borderColor:'#0a5cff', pointRadius:0, fill:true, backgroundColor:gradFill, tension:.32 },
          { label:'7-point MA', data: ma7(enrollments), borderWidth:1.8, borderDash:[5,4], pointRadius:0, fill:false, borderColor:'#16a34a' }
        ]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'bottom' }, tooltip:{ mode:'index', intersect:false } },
        interaction:{ mode:'index', intersect:false },
        scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }
      }
    });
  })();

  // shared helpers
  const barValuePlugin = {
    id: 'barValuePlugin',
    afterDatasetsDraw(chart){
      const {ctx} = chart;
      chart.data.datasets.forEach((dataset)=>{
        const meta = chart.getDatasetMeta(0);
        meta.data.forEach((bar, idx)=>{
          const value = dataset.data[idx];
          if(value == null) return;
          const {x, y} = bar.tooltipPosition();
          ctx.save();
          ctx.fillStyle = '#0b1220';
          ctx.font = '600 12px system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial';
          const isHorizontal = chart.options.indexAxis === 'y';
          const tx = isHorizontal ? bar.x + 6 : x;
          const ty = isHorizontal ? bar.y + 4 : y - 8;
          ctx.textAlign = isHorizontal ? 'left' : 'center';
          ctx.fillText(String(value), tx, ty);
          ctx.restore();
        });
      });
    }
  };
  function makeHGrad(ctx, start='#16a34a', end='#86efac'){
    const g = ctx.createLinearGradient(0,0,ctx.canvas.width,0);
    g.addColorStop(0,start); g.addColorStop(1,end); return g;
  }
  function makeHGradBlue(ctx, start='#0a5cff', end='#93c5fd'){
    const g = ctx.createLinearGradient(0,0,ctx.canvas.width,0);
    g.addColorStop(0,start); g.addColorStop(1,end); return g;
  }

  // ---------- Chart 2: Students by Department ----------
  (function(){
    const labels = (deptStudentLabels || []).map(v => String(v ?? '—'));
    const values = (deptStudentTotals || []).map(v => Number(v || 0));
    const pairs = labels.map((label,i)=>({label, value: values[i] || 0}))
                        .sort((a,b)=>b.value-a.value);

    const empty = !pairs.length || pairs.every(p=>!p.value);
    const emptyEl = document.getElementById('deptStudentsEmpty');
    emptyEl.classList.toggle('d-none', !empty);
    if(empty) return;

    const ctx = document.getElementById('deptStudents').getContext('2d');
    new Chart(ctx, {
      type:'bar',
      data:{
        labels: pairs.map(p=>p.label),
        datasets:[{
          label:'Students',
          data: pairs.map(p=>p.value),
          backgroundColor: makeHGrad(ctx,'#16a34a','#86efac'),
          borderRadius: 8,
          borderSkipped:false
        }]
      },
      options:{
        indexAxis:'y',
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c=>` ${c.raw} student(s)` } } },
        scales:{
          x:{ beginAtZero:true, ticks:{ precision:0 } },
          y:{ ticks:{ autoSkip:false, maxRotation:0, callback:v=>String(v).length>22 ? String(v).slice(0,22)+'…' : v } }
        }
      },
      plugins:[barValuePlugin]
    });
  })();

  // ---------- Chart 3: Courses by Department ----------
  (function(){
    const pairs = (deptLabels || []).map((lbl,i)=>({label:String(lbl ?? '—'), value:Number((deptTotals||[])[i]||0)}))
                  .sort((a,b)=>b.value-a.value);
    const ctx = document.getElementById('deptCourses').getContext('2d');
    new Chart(ctx, {
      type:'bar',
      data:{
        labels: pairs.map(p=>p.label),
        datasets:[{
          label:'Courses',
          data: pairs.map(p=>p.value),
          backgroundColor: makeHGradBlue(ctx,'#0a5cff','#93c5fd'),
          borderRadius: 8,
          borderSkipped:false
        }]
      },
      options:{
        indexAxis:'y',
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c=>` ${c.raw} course(s)` } } },
        scales:{ x:{ beginAtZero:true, ticks:{ precision:0 } }, y:{ ticks:{ autoSkip:false, maxRotation:0 } } }
      },
      plugins:[barValuePlugin]
    });
  })();

  // ---------- Chart 4: Assessments Donut ----------
  (function(){
    const ctx = document.getElementById('aqeDonut').getContext('2d');
    new Chart(ctx, {
      type:'doughnut',
      data:{
        labels:['Assignments','Quizzes','Exams'],
        datasets:[{ data:[totalAssignments,totalQuizzes,totalExams], backgroundColor:['#f59e0b','#7c3aed','#0a5cff'] }]
      },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } }, cutout:'58%' }
    });
  })();

  // ===================== Students table: Live search + Date filters =====================
  const studentSearch = document.getElementById('studentSearch');
  const table  = document.getElementById('studentsTable');
  const rows   = Array.from(table.querySelectorAll('tbody tr'));
  const noRows = document.getElementById('studentsNoRows');

  const filterYear  = document.getElementById('filterYear');
  const filterMonth = document.getElementById('filterMonth');
  const filterFrom  = document.getElementById('filterFrom');
  const filterTo    = document.getElementById('filterTo');
  const applyBtn    = document.getElementById('applyFilters');
  const clearBtn    = document.getElementById('clearFilters');

  function debounce(fn, ms=200){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

  function highlight(text, q){
    if(!q) return text;
    const pattern = new RegExp(`(${q.replace(/[.*+?^${}()|[\\]\\\\]/g,'\\\\$&')})`,'ig');
    return text.replace(pattern,'<mark class="hl">$1</mark>');
  }

  function inDateFilter(isoDate){
    if(!isoDate) return false;
    const d = new Date(isoDate + 'T00:00:00');
    if(Number.isNaN(d.getTime())) return false;

    const y = filterYear.value ? Number(filterYear.value) : null;
    const m = filterMonth.value ? Number(filterMonth.value) : null;
    if(y && d.getFullYear() !== y) return false;
    if(m && (d.getMonth()+1) !== m) return false;

    const from = filterFrom.value ? new Date(filterFrom.value + 'T00:00:00') : null;
    const to   = filterTo.value   ? new Date(filterTo.value   + 'T23:59:59') : null;
    if(from && d < from) return false;
    if(to   && d > to)   return false;

    return true;
  }

  function applyTableFilters(){
    const q = (studentSearch.value || '').trim().toLowerCase();
    let shown = 0;

    rows.forEach(tr=>{
      const td = tr.children;
      const name  = (td[1]?.textContent || '');
      const dept  = (td[2]?.textContent || '');
      const reg   = (td[3]?.textContent || '');
      const email = (td[4]?.textContent || '');
      const hay   = (name + ' ' + dept + ' ' + reg + ' ' + email).toLowerCase();

      const matchText = !q || hay.includes(q);
      const joinedISO = tr.getAttribute('data-joined') || '';
      const matchDate = (!filterYear.value && !filterMonth.value && !filterFrom.value && !filterTo.value) || inDateFilter(joinedISO);

      const visible = matchText && matchDate;
      tr.style.display = visible ? '' : 'none';

      if(visible){
        td[1].innerHTML = highlight(name,  q);
        td[2].innerHTML = highlight(dept,  q);
        td[3].innerHTML = highlight(reg,   q);
        td[4].innerHTML = highlight(email, q);
        shown++;
      }
    });

    noRows.classList.toggle('d-none', shown !== 0);
  }

  const debouncedSearch = debounce(applyTableFilters, 220);
  studentSearch?.addEventListener('input', debouncedSearch);
  applyBtn?.addEventListener('click', applyTableFilters);
  clearBtn?.addEventListener('click', ()=>{
    filterYear.value = '';
    filterMonth.value = '';
    filterFrom.value = '';
    filterTo.value = '';
    applyTableFilters();
  });

  // Initial paint
  applyTableFilters();
})();
</script>
@endsection
