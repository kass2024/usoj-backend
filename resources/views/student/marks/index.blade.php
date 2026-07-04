@extends('layouts.student.app')

@section('css')
<style>
  :root{
    --chip-bg:#f8f9fa; --chip-bd:#e9ecef; --muted:#6c757d; --accent:#007a33;
    --ok:#16a34a; --warn:#f59e0b; --danger:#ef4444;
  }

  /* Header / KPIs */
  .page-header{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;justify-content:space-between}
  .kpi{display:flex;gap:.6rem;align-items:center;padding:.65rem .9rem;border:1px solid var(--chip-bd);
       background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);border-radius:.9rem;font-weight:800;box-shadow:0 2px 10px rgba(2,6,23,.04)}
  .kpi .val{padding:.1rem .55rem;border:1px solid var(--chip-bd);background:#fff;border-radius:.5rem;min-width:3ch;text-align:center}

  /* Filters */
  .filterbar{
    display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;
    padding:.85rem;border:1px solid var(--chip-bd);border-radius:.9rem;background:#fff;
    box-shadow:0 2px 10px rgba(2,6,23,.04)
  }
  .filterbar .form-control,.filterbar .form-select{min-width:200px}
  @media (max-width:575.98px){ .filterbar .form-control,.filterbar .form-select{min-width:0;flex:1} }

  /* Table */
  .card{border:1px solid #edf2f7;border-radius:1rem;overflow:hidden;box-shadow:0 6px 18px rgba(2,6,23,.06)}
  .table thead th{white-space:nowrap}
  .row-title{font-weight:700}
  .row-sub{font-size:.85rem;color:var(--muted)}
  .badge-soft{font-weight:700;border:1px solid transparent}
  .badge-soft-success{color:#13795b;background:#e6f4ea;border-color:#cfead7}
  .badge-soft-secondary{color:#495057;background:#f1f3f5;border-color:#e9ecef}
  .badge-soft-warning{color:#8a5d00;background:#fff4e5;border-color:#ffe8cc}
  .badge-soft-danger{color:#842029;background:#f8d7da;border-color:#f1aeb5}

  /* Progress / Pills */
  .progress{height:8px;border-radius:999px;--bs-progress-bg:#f1f3f5}
  .pill{
    display:inline-block;font-size:.75rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;border:1px solid #e5e7eb;
    background:#f9fafb;color:#374151
  }
  .pill.good{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
  .pill.warn{background:#fff7ed;color:#9a3412;border-color:#fed7aa}
  .pill.bad{background:#fef2f2;color:#7f1d1d;border-color:#fecaca}

  .empty{border:2px dashed #dee2e6;border-radius:1rem;padding:2rem;text-align:center;color:#6c757d}

  /* Hover highlight */
  #marksTable tbody tr{transition:background-color .15s ease}
  #marksTable tbody tr:hover{background:#f8fafc}
</style>
@endsection

@section('body')
@php
  use Illuminate\Support\Str;

  // KPIs
  $total = $submissions->count();
  $scored = $submissions->whereNotNull('marks_obtained');

  $avgPct = null;
  if ($scored->count() > 0) {
      $avgPct = round($scored->map(function($s){
          $totalMarks =
              ($s->exam?->questions?->sum('marks')) ??
              ($s->exam->total_marks ?? null) ??
              ($s->quiz?->questions?->sum('marks')) ??
              ($s->assignment?->questions?->sum('marks')) ??
              0;
          return $totalMarks > 0 ? ($s->marks_obtained / $totalMarks) * 100 : null;
      })->filter()->avg(), 1);
  }

  $years = $submissions->pluck('created_at')->map(fn($d)=>$d?->format('Y'))->unique()->sortDesc()->values();

  // Helpers (same logic used in each row)
  function subType($s){ return $s->exam ? 'Exam' : ($s->quiz ? 'Quiz' : ($s->assignment ? 'Assignment' : 'Submission')); }
  function subTitle($s){ return $s->exam->title ?? $s->quiz->title ?? $s->assignment->title ?? '—'; }
  function totalMarksOf($s){
      return
        ($s->exam?->questions?->sum('marks')) ??
        ($s->exam->total_marks ?? null) ??
        ($s->quiz?->questions?->sum('marks')) ??
        ($s->assignment?->questions?->sum('marks')) ??
        0;
  }
  function courseNameOf($s){
      return $s->exam?->module?->course?->name
          ?? $s->quiz?->module?->course?->name
          ?? $s->assignment?->module?->course?->name
          ?? null;
  }
@endphp

<div class="container-fluid py-4">
  <div class="page-header mb-3">
    <div>
      <h4 class="mb-1">My Marks</h4>
      <div class="text-muted">Live search & filters • professional university view</div>
    </div>
    <div class="d-flex gap-2">
      <div class="kpi">Total <span class="val">{{ $total }}</span></div>
      <div class="kpi">With Score <span class="val">{{ $scored->count() }}</span></div>
      <div class="kpi">Avg % <span class="val">{{ $avgPct !== null ? $avgPct.'%' : '—' }}</span></div>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="filterbar mb-4" id="filters">
    <div class="input-group" style="max-width:560px;">
      <span class="input-group-text">Search</span>
      <input id="qSearch" type="search" class="form-control" placeholder="Search by title, course, type…">
      <button class="btn btn-outline-secondary" id="clearSearch" type="button">Clear</button>
    </div>

    <div class="d-flex gap-2 ms-auto">
      <div>
        <label class="form-label m-0 small text-muted">Type</label>
        <select id="typeFilter" class="form-select">
          <option value="">All</option>
          <option>Exam</option>
          <option>Quiz</option>
          <option>Assignment</option>
        </select>
      </div>
      <div>
        <label class="form-label m-0 small text-muted">Year</label>
        <select id="yearFilter" class="form-select">
          <option value="">All years</option>
          @foreach($years as $y)
            <option value="{{ $y }}">{{ $y }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label m-0 small text-muted">Status</label>
        <select id="statusFilter" class="form-select">
          <option value="">All</option>
          <option value="pass">Pass (≥ 50%)</option>
          <option value="fail">Fail (&lt; 50%)</option>
          <option value="pending">Pending (no score)</option>
        </select>
      </div>
      <div class="align-self-end">
        <button class="btn btn-outline-primary" id="resetFilters" type="button">Reset</button>
      </div>
    </div>
  </div>

  @if($submissions->isEmpty())
    <div class="empty">No marks available yet.</div>
  @else
  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0" id="marksTable">
        <thead class="table-light text-muted">
          <tr>
            <th style="width: 20px;">#</th>
            <th>Assessment</th>
            <th>Course</th>
            <th class="text-center">Score</th>
            <th style="width:220px;">Progress</th>
            <th>Date</th>
            {{-- No "View" column per request --}}
          </tr>
        </thead>
        <tbody>
          @foreach($submissions as $i => $s)
            @php
              $type = subType($s);
              $title = subTitle($s);
              $course = courseNameOf($s);
              $totalMarks = totalMarksOf($s);
              $score = $s->marks_obtained;
              $pct = ($score !== null && $totalMarks > 0) ? round(($score/$totalMarks)*100, 1) : null;
              $year = optional($s->created_at)->format('Y');
              $passfail = $pct===null ? 'pending' : ($pct >= 50 ? 'pass' : 'fail');
              $pillClass = $pct===null ? '' : ($pct>=70?'good':($pct>=40?'warn':'bad'));
            @endphp
            <tr class="mark-row"
                data-title="{{ Str::lower($title.' '.($course??'').' '.$type) }}"
                data-type="{{ $type }}"
                data-year="{{ $year }}"
                data-status="{{ $passfail }}">
              <td>{{ $i+1 }}</td>
              <td>
                <div class="row-title">{{ $title }}</div>
                <div class="row-sub">
                  <span class="badge {{ $type==='Exam'?'badge-soft-danger':($type==='Quiz'?'badge-soft-success':'badge-soft-secondary') }}">{{ $type }}</span>
                  <span class="ms-2 pill {{ $pillClass }}">{{ $pct!==null ? $pct.'%' : 'Pending' }}</span>
                </div>
              </td>
              <td class="text-muted">{{ $course ?? '—' }}</td>
              <td class="text-center">
                @if($score !== null)
                  <span class="fw-bold">{{ rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.') }}</span>
                  <span class="text-muted">/ {{ $totalMarks }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                @if($pct !== null)
                  <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                    <div class="progress-bar" style="width: {{ $pct }}%"></div>
                  </div>
                  <div class="small text-muted mt-1">{{ $pct }}%</div>
                @else
                  <span class="small text-muted">Not graded yet</span>
                @endif
              </td>
              <td class="text-muted">{{ optional($s->created_at)->format('d M Y, H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>
@endsection

@section('js')
<script>
(function(){
  const q = document.getElementById('qSearch');
  const clearBtn = document.getElementById('clearSearch');
  const resetBtn = document.getElementById('resetFilters');
  const typeSel = document.getElementById('typeFilter');
  const yearSel = document.getElementById('yearFilter');
  const statusSel = document.getElementById('statusFilter');
  const rows = Array.from(document.querySelectorAll('#marksTable .mark-row'));

  function norm(s){ return (s||'').toString().toLowerCase().trim(); }

  function applyFilters(){
    const term = norm(q?.value);
    const type = typeSel?.value || '';
    const year = yearSel?.value || '';
    const st   = statusSel?.value || '';

    let visible = 0;
    rows.forEach(tr=>{
      const text = tr.getAttribute('data-title') || '';
      const okText = !term || text.indexOf(term) !== -1;
      const okType = !type || tr.getAttribute('data-type') === type;
      const okYear = !year || tr.getAttribute('data-year') === year;
      const okSt   = !st || tr.getAttribute('data-status') === st;
      const show = okText && okType && okYear && okSt;
      tr.style.display = show ? '' : 'none';
      if(show) visible++;
    });

    // "No results" row
    let empty = document.getElementById('noRows');
    if(!empty){
      empty = document.createElement('tr');
      empty.id = 'noRows';
      empty.innerHTML = `<td colspan="6" class="text-center text-muted py-4">No results match your filters.</td>`;
      const tbody = document.querySelector('#marksTable tbody');
      tbody.appendChild(empty);
    }
    empty.style.display = visible ? 'none' : '';
  }

  let t;
  q?.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(applyFilters, 120); });
  clearBtn?.addEventListener('click', ()=>{ if(q){ q.value=''; q.focus(); } applyFilters(); });
  typeSel?.addEventListener('change', applyFilters);
  yearSel?.addEventListener('change', applyFilters);
  statusSel?.addEventListener('change', applyFilters);
  resetBtn?.addEventListener('click', ()=>{
    if(q) q.value='';
    if(typeSel) typeSel.value='';
    if(yearSel) yearSel.value='';
    if(statusSel) statusSel.value='';
    applyFilters();
  });

  applyFilters();
})();
</script>
@endsection
