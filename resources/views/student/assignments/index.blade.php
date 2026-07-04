@extends('layouts.student.app')

@section('css')
<style>
  /* (Same design system you used for quizzes) */
  :root{
    --chip-bg:#f8f9fa; --chip-bd:rgba(0,0,0,.08); --accent:#007a33;
    --ok:#16a34a; --ok2:#22c55e; --muted:#6c757d; --warn:#f59e0b; --danger:#ef4444;
  }
  .page-header{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;justify-content:space-between}
  .kpi{display:flex;gap:.5rem;align-items:center;padding:.5rem .75rem;border:1px solid var(--chip-bd);
       background:var(--chip-bg);border-radius:.75rem;font-weight:600}
  .filterbar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;
    padding:.75rem;border:1px solid #e9ecef;border-radius:.75rem;background:#fff}
  .filterbar .form-control,.filterbar .form-select{min-width:200px}
  @media (max-width:575.98px){.filterbar .form-control,.filterbar .form-select{min-width:0;flex:1}}
  .assignment-col{display:flex}
  .card-assignment{height:100%;display:flex;flex-direction:column;background:#fff;
    border:1px solid #e9ecef;border-radius:1rem;overflow:hidden;min-height:340px}
  .card-assignment .card-header{background:#f8fafc;border-bottom:1px solid #e9ecef;padding:.75rem 1rem}
  .card-assignment .card-body{padding:1rem}
  .card-assignment .card-footer{background:#fff;border-top:0;padding:0 1rem 1rem}
  .chip{display:inline-flex;gap:.35rem;align-items:center;padding:.2rem .5rem;border:1px solid var(--chip-bd);
        background:var(--chip-bg);border-radius:.5rem;font-weight:600;font-size:.85rem}
  .badge-soft{font-weight:600;border:1px solid transparent}
  .badge-soft-success{color:#13795b;background:#e6f4ea;border-color:#cfead7}
  .badge-soft-secondary{color:#495057;background:#f1f3f5;border-color:#e9ecef}
  .badge-soft-warning{color:#8a5d00;background:#fff4e5;border-color:#ffe8cc}
  .badge-soft-danger{color:#842029;background:#f8d7da;border-color:#f1aeb5}
  .meta{font-size:.9rem;color:var(--muted)}
  .empty{border:2px dashed #dee2e6;border-radius:1rem;padding:2rem;text-align:center;color:#6c757d}
  .countdown{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}
  .timebox{min-width:64px;padding:.25rem .45rem;border:2px solid var(--accent);border-radius:.5rem;text-align:center;font-variant-numeric:tabular-nums;background:#fff}
  .timebox .num{display:block;font-weight:700;font-size:.95rem;line-height:1.1}
  .timebox .lbl{display:block;font-size:.72rem;color:#007a33;line-height:1.1;margin-top:.1rem}
  .colon{font-weight:700;margin:0 .08rem}
  .btn-pill{border-radius:999px;font-weight:600;letter-spacing:.2px}
  .btn-grad-primary{color:#fff;border:0;background:linear-gradient(135deg,#007a33 0%,#00a651 100%);
    box-shadow:0 6px 18px rgba(0,122,51,.25)}
  .btn-grad-primary:hover{filter:brightness(1.05);box-shadow:0 8px 22px rgba(0,122,51,.32)}
  .btn-grad-success{color:#fff;border:0;background:linear-gradient(135deg,#16a34a 0%,#22c55e 100%);
    box-shadow:0 6px 18px rgba(22,163,74,.25)}
  .btn-grad-success:hover{filter:brightness(1.05);box-shadow:0 8px 22px rgba(22,163,74,.32)}
  .btn-icon{display:inline-flex;gap:.5rem;align-items:center;justify-content:center}
  .btn-icon svg{width:18px;height:18px;flex:0 0 18px}
  .stats-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;width:100%}
  @media (max-width:420px){.stats-row{grid-template-columns:1fr}}
  .stat-tile{display:flex;gap:.75rem;align-items:center;padding:.6rem .75rem;border-radius:.9rem;
    border:1px solid #edf2f7;background:linear-gradient(180deg,#ffffff 0%, #f8fafc 100%);
    box-shadow:0 2px 8px rgba(2,6,23,0.04)}
  .stat-ico{flex:0 0 40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#e9f0ff, #f5f8ff); color:#2563eb; border:1px solid #e6eeff}
  .stat-ico.success{background:linear-gradient(135deg,#e7fff1,#f4fff8);color:#15803d;border-color:#dcfce7}
  .stat-main{display:flex;flex-direction:column;line-height:1.1}
  .stat-label{font-size:.75rem;color:#64748b}
  .stat-value{font-weight:800;font-size:1.05rem}
  .stat-sub{font-size:.8rem;color:#6b7280}
</style>
@endsection

@section('body')
@php
  use Illuminate\Support\Str;
  $prepared = $assignments->map(function($a){
      $due = $a->due_date;
      $submission = $a->student_submission(auth()->guard('student')->id());
      $statusKey = $submission ? 'completed' : 'not-started';
      $statusLabel = $submission ? 'Completed' : 'Not Started';
      $marksObtained = $submission ? $submission->marks_obtained : null;
      $totalMarks = $a->questions->sum('marks');
      $percent = ($submission && $totalMarks>0) ? round(($marksObtained/$totalMarks)*100,1):null;
      $year = $due?->format('Y');
      return [
        'assignment'=>$a,
        'submission'=>$submission,
        'statusKey'=>$statusKey,'statusLabel'=>$statusLabel,
        'marksObtained'=>$marksObtained,'totalMarks'=>$totalMarks,'percent'=>$percent,
        'year'=>$year,
        'due_iso'=>$due->toIso8601String(),
        'searchBlob'=>Str::of(($a->title ?? '').' '.($a->module->course->name ?? '').' '.($a->module->code ?? $a->module->name ?? '').' '.($a->description ?? ''))->lower()->squish()->value(),
      ];
  });
  $years = $prepared->pluck('year')->unique()->sortDesc()->values();
@endphp

<div class="container-fluid">
  <div class="page-header mb-3">
    <div>
      <h4 class="mb-1">My Assignments</h4>
      <div class="text-muted">Professional learning view</div>
    </div>
    <div class="d-flex gap-2">
      <div class="kpi"><span>Total</span><span class="badge text-bg-light">{{ $prepared->count() }}</span></div>
      <div class="kpi"><span>Completed</span><span class="badge text-bg-success">{{ $prepared->where('statusKey','completed')->count() }}</span></div>
      <div class="kpi"><span>Not Started</span><span class="badge text-bg-secondary">{{ $prepared->where('statusKey','not-started')->count() }}</span></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filterbar mb-4">
    <div class="input-group" style="max-width:520px;">
      <span class="input-group-text">Search</span>
      <input id="qSearch" type="search" class="form-control" placeholder="Search by title, course, module, description">
      <button class="btn btn-outline-secondary" id="clearSearch">Clear</button>
    </div>
    <div class="d-flex gap-2">
      <div>
        <label class="form-label m-0 small text-muted">Status</label>
        <select id="statusFilter" class="form-select">
          <option value="">All</option>
          <option value="completed">Completed</option>
          <option value="not-started">Not Started</option>
        </select>
      </div>
      <div>
        <label class="form-label m-0 small text-muted">Year</label>
        <select id="yearFilter" class="form-select">
          <option value="">All years</option>
          @foreach($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
        </select>
      </div>
      <div class="align-self-end"><button type="button" id="resetFilters" class="btn btn-outline-primary">Reset</button></div>
    </div>
  </div>

  @if($prepared->isEmpty())
    <div class="empty">No assignments yet.</div>
  @else
    <div id="assignmentGrid" class="row g-3">
      @foreach($prepared as $row)
        @php $a=$row['assignment']; @endphp
        <div class="col-12 col-sm-6 assignment-col assignment-item"
             data-status="{{ $row['statusKey'] }}"
             data-year="{{ $row['year'] }}"
             data-due="{{ $row['due_iso'] }}"
             data-search="{{ e($row['searchBlob']) }}">
          <div class="card card-assignment shadow-sm w-100">
            <div class="card-header">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <span class="badge badge-soft {{ $row['statusKey']==='completed' ? 'badge-soft-success':'badge-soft-secondary'}}">
                    {{ $row['statusLabel'] }}
                  </span>
                  <span class="chip">{{ $row['year'] }}</span>
                  <h6 class="mb-0">{{ $a->title }}</h6>
                  <div class="meta">{{ $a->module->course->name }} · Module: {{ $a->module->code ?? $a->module->name }}</div>
                </div>
              </div>
            </div>
            <div class="card-body d-flex flex-column gap-2">
              @if(!empty($a->description))
                <div class="text-truncate" title="{{ $a->description }}">{{ $a->description }}</div>
              @endif
              <div class="stats-row">
                <div class="stat-tile">
                  <div class="stat-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 6h11v2H9zM4 5h3v3H4zM9 11h11v2H9zM4 10h3v3H4zM9 16h11v2H9zM4 15h3v3H4z"/></svg></div>
                  <div class="stat-main"><span class="stat-label">Questions</span><span class="stat-value">10</span></div>
                </div>
                <div class="stat-tile">
                  <div class="stat-ico success"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17 3H7v3H4v3a5 5 0 0 0 5 5h.1A5.002 5.002 0 0 0 11 18H8v2h8v-2h-3a5.002 5.002 0 0 0 1.9-4H15a5 5 0 0 0 5-5V6h-3V3Z"/></svg></div>
                  <div class="stat-main">
                    <span class="stat-label">Marks</span>
                    @if($row['submission'])
                      <span class="stat-value">{{ $row['marksObtained'] }} / {{ $row['totalMarks'] }}</span>
                    @else
                      <span class="stat-value">{{ $row['totalMarks'] }} total</span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="meta mt-1">Due: {{ $a->due_date->format('Y-m-d H:i') }}</div>
              <div class="countdown" data-role="countdown">
                <div class="timebox"><span class="num" data-dd>00</span><span class="lbl">Days</span></div><span class="colon">:</span>
                <div class="timebox"><span class="num" data-hh>00</span><span class="lbl">Hours</span></div><span class="colon">:</span>
                <div class="timebox"><span class="num" data-mm>00</span><span class="lbl">Mins</span></div><span class="colon">:</span>
                <div class="timebox"><span class="num" data-ss>00</span><span class="lbl">Seconds</span></div>
              </div>
            </div>
            <div class="card-footer mt-auto">
              @if(!$row['submission'])
                <a href="{{ route('student.assignments.submission',$a->id) }}" class="btn btn-grad-primary btn-pill w-100 btn-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg><span>Start</span>
                </a>
              @else
                <a href="" class="btn btn-grad-success btn-pill w-100 btn-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 5c-7.633 0-11 7-11 7s3.367 7 11 7 11-7 11-7-3.367-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/><circle cx="12" cy="12" r="3"/></svg><span>Preview</span>
                </a>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

@section('js')
<script>
function norm(s){return (s||'').toString().toLowerCase().trim();}
function pad(n){return String(n).padStart(2,'0');}
document.addEventListener('DOMContentLoaded',function(){
  var grid=document.getElementById('assignmentGrid'); if(!grid) return;
  var cards=[].slice.call(grid.querySelectorAll('.assignment-item'));
  var qSearch=document.getElementById('qSearch'),statusFilter=document.getElementById('statusFilter'),yearFilter=document.getElementById('yearFilter');
  function getBlob(el){return norm(el.getAttribute('data-search'));}
  function applyFilters(){
    var q=norm(qSearch.value),st=statusFilter.value,yr=yearFilter.value;var visible=0;
    cards.forEach(function(el){
      var show=(!q||getBlob(el).indexOf(q)!==-1)&&(!st||el.getAttribute('data-status')===st)&&(!yr||el.getAttribute('data-year')===yr);
      el.style.display=show?'':'none'; if(show) visible++;
    });
  }
  qSearch.addEventListener('input',applyFilters);statusFilter.addEventListener('change',applyFilters);yearFilter.addEventListener('change',applyFilters);
  document.getElementById('clearSearch').addEventListener('click',()=>{qSearch.value='';applyFilters();});
  document.getElementById('resetFilters').addEventListener('click',()=>{qSearch.value='';statusFilter.value='';yearFilter.value='';applyFilters();});
  applyFilters();
  function tick(){
    var now=Date.now();
    cards.forEach(function(el){
      var due=new Date(el.getAttribute('data-due')).getTime();var ms=Math.max(0,due-now),sec=Math.floor(ms/1000);
      var d=Math.floor(sec/86400),h=Math.floor((sec%86400)/3600),m=Math.floor((sec%3600)/60),s=sec%60;
      el.querySelector('[data-dd]').textContent=pad(d);el.querySelector('[data-hh]').textContent=pad(h);
      el.querySelector('[data-mm]').textContent=pad(m);el.querySelector('[data-ss]').textContent=pad(s);
    });
    requestAnimationFrame(tick);
  }
  tick();
});
</script>
@endsection
