@extends('layouts.student.app')

@section('css')
<style>
  :root{
    --chip-bg:#f8f9fa; --chip-bd:rgba(0,0,0,.08); --accent:#007a33;
    --ok:#16a34a; --ok2:#22c55e; --muted:#6c757d; --warn:#f59e0b; --danger:#ef4444;
  }

  .page-header{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;justify-content:space-between}
  .kpi{display:flex;gap:.5rem;align-items:center;padding:.5rem .75rem;border:1px solid var(--chip-bd);
       background:var(--chip-bg);border-radius:.75rem;font-weight:600}

  /* Filter toolbar */
  .filterbar{
    display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;
    padding:.75rem;border:1px solid #e9ecef;border-radius:.75rem;background:#fff
  }
  .filterbar .form-control, .filterbar .form-select{min-width:200px}
  @media (max-width: 575.98px){ .filterbar .form-control, .filterbar .form-select{min-width:0;flex:1} }

  /* Cards — compact, equal-height */
  .quiz-col{display:flex}
  .card-quiz{
    height:100%;display:flex;flex-direction:column;background:#fff;
    border:1px solid #e9ecef;border-radius:1rem;overflow:hidden;
    min-height:340px; /* compact */
  }
  .card-quiz .card-header{background:#f8fafc;border-bottom:1px solid #e9ecef;padding:.75rem 1rem}
  .card-quiz .card-body{padding:1rem}
  .card-quiz .card-footer{background:#fff;border-top:0;padding:0 1rem 1rem}

  .chip{display:inline-flex;gap:.35rem;align-items:center;padding:.2rem .5rem;border:1px solid var(--chip-bd);
        background:var(--chip-bg);border-radius:.5rem;font-weight:600;font-size:.85rem}
  .badge-soft{font-weight:600;border:1px solid transparent}
  .badge-soft-success{color:#13795b;background:#e6f4ea;border-color:#cfead7}
  .badge-soft-secondary{color:#495057;background:#f1f3f5;border-color:#e9ecef}
  .badge-soft-warning{color:#8a5d00;background:#fff4e5;border-color:#ffe8cc}
  .badge-soft-danger{color:#842029;background:#f8d7da;border-color:#f1aeb5}
  .meta{font-size:.9rem;color:var(--muted)}
  .empty{border:2px dashed #dee2e6;border-radius:1rem;padding:2rem;text-align:center;color:#6c757d}

  /* Countdown — compact */
  .countdown{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}
  .timebox{min-width:64px;padding:.25rem .45rem;border:2px solid var(--accent);border-radius:.5rem;text-align:center;font-variant-numeric:tabular-nums;background:#fff}
  .timebox .num{display:block;font-weight:700;font-size:.95rem;line-height:1.1}
  .timebox .lbl{display:block;font-size:.72rem;color:#007a33;line-height:1.1;margin-top:.1rem}
  .colon{font-weight:700;margin:0 .08rem}

  /* Premium buttons */
  .btn-pill{border-radius:999px;font-weight:600;letter-spacing:.2px}
  .btn-grad-primary{
    color:#fff;border:0;background:linear-gradient(135deg,#007a33 0%,#00a651 100%);
    box-shadow:0 6px 18px rgba(0,122,51,.25);
  }
  .btn-grad-primary:hover{filter:brightness(1.05);box-shadow:0 8px 22px rgba(0,122,51,.32)}
  .btn-grad-primary:active{transform:translateY(1px)}
  .btn-grad-success{
    color:#fff;border:0;background:linear-gradient(135deg,#16a34a 0%,#22c55e 100%);
    box-shadow:0 6px 18px rgba(22,163,74,.25);
  }
  .btn-grad-success:hover{filter:brightness(1.05);box-shadow:0 8px 22px rgba(22,163,74,.32)}
  .btn-grad-success:active{transform:translateY(1px)}
  .btn-icon{display:inline-flex;gap:.5rem;align-items:center;justify-content:center}
  .btn-icon svg{width:18px;height:18px;flex:0 0 18px}

  /* ----- NEW: Beautiful stat tiles for Questions & Marks ----- */
  .stats-row{
    display:grid;grid-template-columns:1fr 1fr;gap:.75rem;width:100%;
  }
  @media (max-width: 420px){ .stats-row{grid-template-columns:1fr} }
  .stat-tile{
    display:flex;gap:.75rem;align-items:center;padding:.6rem .75rem;border-radius:.9rem;
    border:1px solid #edf2f7;background:linear-gradient(180deg,#ffffff 0%, #f8fafc 100%);
    box-shadow:0 2px 8px rgba(2,6,23,0.04);
  }
  .stat-ico{
    flex:0 0 40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#e9f0ff, #f5f8ff); color:#2563eb;
    border:1px solid #e6eeff;
  }
  .stat-ico.success{ background:linear-gradient(135deg,#e7fff1,#f4fff8); color:#15803d; border-color:#dcfce7; }
  .stat-main{display:flex;flex-direction:column;line-height:1.1}
  .stat-label{font-size:.75rem;color:#64748b}
  .stat-value{font-weight:800;font-size:1.05rem}
  .stat-sub{font-size:.8rem;color:#6b7280}
  .pill{
    margin-left:auto;align-self:flex-start;padding:.25rem .5rem;border-radius:999px;font-weight:700;font-size:.75rem;
    border:1px solid #e5e7eb;background:#f9fafb;color:#374151
  }
  .pill.good{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
  .pill.warn{background:#fff7ed;color:#9a3412;border-color:#fed7aa}
  .pill.bad{background:#fef2f2;color:#7f1d1d;border-color:#fecaca}
</style>
@endsection

@section('body')
@php
  use Illuminate\Support\Str;

  $prepared = $quizzes->map(function($quiz){
      $start = $quiz->start_date;
      $end   = $quiz->end_date;

      $submission  = $quiz->student_submission(auth()->guard('student')->id());
      $statusKey   = $submission ? 'completed' : 'not-started';
      $statusLabel = $submission ? 'Completed' : 'Not Started';

      $totalMarks     = $quiz->questions->sum('marks');
      $marksObtained  = $submission ? (float)$submission->marks_obtained : null;
      $percent        = ($submission && $totalMarks > 0) ? round(($marksObtained / $totalMarks) * 100, 1) : null;

      $year         = $start?->format('Y');
      $ayStartYear  = ((int)$start->format('n') >= 9) ? (int)$start->format('Y') : (int)$start->format('Y') - 1;
      $academicYear = $ayStartYear . '/' . ($ayStartYear + 1);

      $now = now();
      $availabilityKey   = $now->lt($start) ? 'upcoming' : ($now->between($start, $end) ? 'open' : 'closed');
      $availabilityLabel = ucfirst($availabilityKey);

      $searchBlob = Str::of(
        ($quiz->title ?? '') . ' ' .
        ($quiz->module->course->name ?? '') . ' ' .
        ($quiz->module->code ?? $quiz->module->name ?? '') . ' ' .
        ($quiz->description ?? '')
      )->lower()->squish()->value();

      return [
        'quiz' => $quiz,
        'start' => $start, 'end' => $end,
        'start_iso' => $start->toIso8601String(), 'end_iso' => $end->toIso8601String(),
        'submission' => $submission,
        'statusKey' => $statusKey, 'statusLabel' => $statusLabel,
        'availabilityKey' => $availabilityKey, 'availabilityLabel' => $availabilityLabel,
        'totalMarks' => $totalMarks, 'marksObtained' => $marksObtained, 'percent' => $percent,
        'year' => $year, 'academicYear' => $academicYear,
        'searchBlob' => $searchBlob,
      ];
  });

  $years = $prepared->pluck('year')->unique()->sortDesc()->values();
@endphp

<div class="container-fluid">
  <div class="page-header mb-3">
    <div>
      <h4 class="mb-1">My Quizzes</h4>
      <div class="text-muted">Professional learning view</div>
    </div>
    <div class="d-flex gap-2">
      <div class="kpi"><span>Total</span><span class="badge text-bg-light">{{ $prepared->count() }}</span></div>
      <div class="kpi"><span>Completed</span><span class="badge text-bg-success">{{ $prepared->where('statusKey','completed')->count() }}</span></div>
      <div class="kpi"><span>Not Started</span><span class="badge text-bg-secondary">{{ $prepared->where('statusKey','not-started')->count() }}</span></div>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="filterbar mb-4" id="filters">
    <div class="input-group" style="max-width:520px;">
      <span class="input-group-text" id="lblSearch">Search</span>
      <input id="qSearch" type="search" class="form-control" placeholder="Search by title, course, module, description" aria-labelledby="lblSearch">
      <button class="btn btn-outline-secondary" type="button" id="clearSearch">Clear</button>
    </div>

    <div class="d-flex gap-2">
      <div>
        <label for="statusFilter" class="form-label m-0 small text-muted">Status</label>
        <select id="statusFilter" class="form-select">
          <option value="">All</option>
          <option value="completed">Completed</option>
          <option value="not-started">Not Started</option>
        </select>
      </div>

      <div>
        <label for="yearFilter" class="form-label m-0 small text-muted">Year</label>
        <select id="yearFilter" class="form-select">
          <option value="">All years</option>
          @foreach($years as $y)
            <option value="{{ $y }}">{{ $y }}</option>
          @endforeach
        </select>
      </div>

      <div class="align-self-end">
        <button type="button" id="resetFilters" class="btn btn-outline-primary">Reset filters</button>
      </div>
    </div>
  </div>

  @if($prepared->isEmpty())
    <div class="empty">No quizzes yet.</div>
  @else
    <div id="quizGrid" class="row g-3">
      @foreach($prepared as $row)
        @php $q = $row['quiz']; @endphp

        <!-- 1 on phones, 2 from sm+ -->
        <div class="col-12 col-sm-6 quiz-col quiz-item"
             data-status="{{ $row['statusKey'] }}"
             data-year="{{ $row['year'] }}"
             data-start="{{ $row['start_iso'] }}"
             data-end="{{ $row['end_iso'] }}"
             data-search="{{ e($row['searchBlob']) }}">
          <div class="card card-quiz shadow-sm w-100">
            <div class="card-header">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge badge-soft {{ $row['statusKey']==='completed' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                      {{ $row['statusLabel'] }}
                    </span>
                    <span class="badge badge-soft
                      @if($row['availabilityKey']==='open') badge-soft-success
                      @elseif($row['availabilityKey']==='upcoming') badge-soft-warning
                      @else badge-soft-danger @endif availability-badge">
                      {{ $row['availabilityLabel'] }}
                    </span>
                    <span class="chip" title="Year">{{ $row['year'] }}</span>
                    <span class="chip" title="Academic Year">{{ $row['academicYear'] }}</span>
                  </div>
                  <h6 class="mb-0" data-title>{{ $q->title }}</h6>
                  <div class="meta" data-meta>
                    {{ $q->module->course->name }} · Module: {{ $q->module->code ?? $q->module->name }}
                  </div>
                </div>
              </div>
            </div>

            <div class="card-body d-flex flex-column gap-2">
              @if(!empty($q->description))
                <div class="text-truncate" data-desc title="{{ $q->description }}">{{ $q->description }}</div>
              @endif

              <!-- ===== NEW: STAT TILES ===== -->
              <div class="stats-row">
                <!-- Total Questions -->
                <div class="stat-tile">
                  <div class="stat-ico" aria-hidden="true">
                    <!-- list icon -->
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 6h11v2H9zM4 5h3v3H4zM9 11h11v2H9zM4 10h3v3H4zM9 16h11v2H9zM4 15h3v3H4z"/></svg>
                  </div>
                  <div class="stat-main">
                    <span class="stat-label">Total Questions</span>
                    <span class="stat-value">10</span>
                    <span class="stat-sub">in this quiz</span>
                  </div>
                </div>

                <!-- Marks -->
                <div class="stat-tile">
                  <div class="stat-ico success" aria-hidden="true">
                    <!-- trophy icon -->
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17 3H7v3H4v3a5 5 0 0 0 5 5h.1A5.002 5.002 0 0 0 11 18H8v2h8v-2h-3a5.002 5.002 0 0 0 1.9-4H15a5 5 0 0 0 5-5V6h-3V3Z"/></svg>
                  </div>
                  <div class="stat-main">
                    <span class="stat-label">Marks</span>
                    @if($row['submission'])
                      <span class="stat-value">{{ rtrim(rtrim(number_format($row['marksObtained'], 2, '.', ''), '0'), '.') }} / {{ $row['totalMarks'] }}</span>
                      <span class="stat-sub">Your score</span>
                    @else
                      <span class="stat-value">{{ $row['totalMarks'] }} total</span>
                      <span class="stat-sub">Available marks</span>
                    @endif
                  </div>
                  @if($row['submission'] && $row['totalMarks']>0)
                    @php
                      $pct = $row['percent'];
                      $pillClass = $pct>=70 ? 'good' : ($pct>=40 ? 'warn' : 'bad');
                    @endphp
                    <span class="pill {{ $pillClass }}">{{ $pct }}%</span>
                  @endif
                </div>
              </div>

              <!-- Time / countdown -->
              <div class="meta mt-1">
                {{ $row['start']->format('Y-m-d H:i') }} – {{ $row['end']->format('H:i') }}
                <span class="text-muted"> · {{ $row['start']->diffForHumans() }}</span>
              </div>
              <div class="countdown" data-role="countdown">
                <div class="timebox"><span class="num" data-dd>00</span><span class="lbl">Days</span></div>
                <span class="colon">:</span>
                <div class="timebox"><span class="num" data-hh>00</span><span class="lbl">Hours</span></div>
                <span class="colon">:</span>
                <div class="timebox"><span class="num" data-mm>00</span><span class="lbl">Mins</span></div>
                <span class="colon">:</span>
                <div class="timebox"><span class="num" data-ss>00</span><span class="lbl">Seconds</span></div>
              </div>

              @if($row['submission'])
                <div>
                  <div class="progress" style="height:6px" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $row['percent'] }}">
                    <div class="progress-bar" style="width: {{ $row['percent'] }}%"></div>
                  </div>
                  <div class="meta mt-1">{{ $row['percent'] }}%</div>
                </div>
              @endif
            </div>

            <div class="card-footer mt-auto">
              @if(!$row['submission'])
                <a href="{{ route('student.quizzes.submission', $q->id) }}" class="btn btn-grad-primary btn-pill w-100 btn-icon start-btn" title="Start this quiz">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                  <span>Start</span>
                </a>
              @else
                <a href="#" class="btn btn-grad-success btn-pill w-100 btn-icon" title="Preview your submission">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 5c-7.633 0-11 7-11 7s3.367 7 11 7 11-7 11-7-3.367-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/><circle cx="12" cy="12" r="3"/></svg>
                  <span>Preview</span>
                </a>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

<noscript>
  <div class="alert alert-warning mt-3">Search & filters require JavaScript. Please enable JavaScript to use instant filtering.</div>
</noscript>
@endsection

@section('js')
<script>
// ==== Utilities ====
function norm(s){ return (s || '').toString().toLowerCase().trim(); }
function pad(n){ return String(n).padStart(2,'0'); }

document.addEventListener('DOMContentLoaded', function(){
  var grid = document.getElementById('quizGrid'); if (!grid) return;
  var cards = Array.prototype.slice.call(grid.querySelectorAll('.quiz-item'));
  var qSearch = document.getElementById('qSearch');
  var statusFilter = document.getElementById('statusFilter');
  var yearFilter = document.getElementById('yearFilter');
  var clearSearch = document.getElementById('clearSearch');
  var resetFilters = document.getElementById('resetFilters');

  function getBlob(el){
    var v = el.getAttribute('data-search');
    if (v) return norm(v);
    var t='', tEl=el.querySelector('[data-title]'), mEl=el.querySelector('[data-meta]'), dEl=el.querySelector('[data-desc]');
    if (tEl) t += ' ' + tEl.textContent;
    if (mEl) t += ' ' + mEl.textContent;
    if (dEl) t += ' ' + dEl.textContent;
    return norm(t);
  }

  function applyFilters(){
    var q  = norm(qSearch ? qSearch.value : '');
    var st = statusFilter ? statusFilter.value : '';
    var yr = yearFilter ? yearFilter.value : '';

    var visible = 0;
    for (var i=0;i<cards.length;i++){
      var el = cards[i];
      var okText = !q || getBlob(el).indexOf(q) !== -1;
      var okSt   = !st || el.getAttribute('data-status') === st;
      var okYr   = !yr || el.getAttribute('data-year') === yr;
      var show = okText && okSt && okYr;
      el.style.display = show ? '' : 'none';
      if (show) visible++;
    }

    var empty = document.getElementById('noResults');
    if (!empty){
      empty = document.createElement('div');
      empty.id = 'noResults';
      empty.className = 'mt-3 text-center text-muted';
      empty.textContent = 'No quizzes match your filters.';
      grid.parentNode.insertBefore(empty, grid.nextSibling);
    }
    empty.style.display = visible ? 'none' : '';
  }

  var t;
  if (qSearch){ qSearch.addEventListener('input', function(){ clearTimeout(t); t=setTimeout(applyFilters, 120); }); }
  if (clearSearch){ clearSearch.addEventListener('click', function(){ if (qSearch){ qSearch.value=''; qSearch.focus(); } applyFilters(); }); }
  if (resetFilters){
    resetFilters.addEventListener('click', function(){
      if (qSearch) qSearch.value='';
      if (statusFilter) statusFilter.value='';
      if (yearFilter) yearFilter.value='';
      applyFilters();
    });
  }
  if (statusFilter) statusFilter.addEventListener('change', applyFilters);
  if (yearFilter) yearFilter.addEventListener('change', applyFilters);

  // Initial render
  applyFilters();

  // Countdown updater
  function updateCard(el){
    var start = new Date(el.getAttribute('data-start')).getTime();
    var end   = new Date(el.getAttribute('data-end')).getTime();
    var now   = Date.now();
    var badge = el.querySelector('.availability-badge');
    var startBtn = el.querySelector('.start-btn');

    var target, state;
    if (now < start) { state='Upcoming'; target = start; }
    else if (now <= end) { state='Open'; target = end; }
    else { state='Closed'; target = now; }

    if (badge){
      badge.classList.remove('badge-soft-success','badge-soft-warning','badge-soft-danger');
      if (state==='Open') badge.classList.add('badge-soft-success');
      else if (state==='Upcoming') badge.classList.add('badge-soft-warning');
      else badge.classList.add('badge-soft-danger');
      badge.textContent = state;
    }
    if (startBtn){
      var enabled = (state==='Open');
      if (enabled) startBtn.removeAttribute('disabled'); else startBtn.setAttribute('disabled','disabled');
      startBtn.textContent = enabled ? 'Start' : 'Start (Unavailable)';
    }

    var ms=Math.max(0,target-now), sec=Math.floor(ms/1000);
    var d=Math.floor(sec/86400), h=Math.floor((sec%86400)/3600), m=Math.floor((sec%3600)/60), s=sec%60;
    var dd=el.querySelector('[data-dd]'), hh=el.querySelector('[data-hh]'), mm=el.querySelector('[data-mm]'), ss=el.querySelector('[data-ss]');
    if (dd) dd.textContent=pad(d); if (hh) hh.textContent=pad(h); if (mm) mm.textContent=pad(m); if (ss) ss.textContent=pad(s);
  }
  function tick(){ for (var i=0;i<cards.length;i++){ updateCard(cards[i]); } requestAnimationFrame(tick); }
  tick();
});
</script>
@endsection
