@extends('layouts.student.app')

@section('css')
<style>
  /* ========== Design tokens ========== */
  :root{
    --bg:#0b1220; --fg:#0f172a; --muted:#6b7280;
    --card:#ffffff; --card-2:#fbfdff; --card-bd:#e6e9ef;
    --brand:#007a33; --brand2:#005a26;
    --ok:#10b981; --warn:#f59e0b; --bad:#ef4444;
    --shadow-1: 0 10px 30px rgba(2,6,23,.08);
    --shadow-2: 0 22px 60px rgba(2,6,23,.14);
    --radius: 16px;
  }
  [data-theme="dark"]{
    --bg:#060b15; --fg:#e5e7eb; --muted:#94a3b8;
    --card:#0f172a; --card-2:#0b1324; --card-bd:#1f2a44;
    --brand:#60a5fa; --brand2:#3b82f6;
    --shadow-1: 0 10px 28px rgba(0,0,0,.38);
    --shadow-2: 0 22px 70px rgba(0,0,0,.55);
  }
  html,body{
    background:radial-gradient(1200px 600px at 20% -10%, rgba(0,122,51,.08), transparent 60%),
               radial-gradient(1200px 600px at 100% 0%, rgba(29,78,216,.06), transparent 60%),
               var(--bg);
    color:var(--fg);
  }

  /* ========== Header ========== */
  .page-header{display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;justify-content:space-between}
  .title{font-weight:900;letter-spacing:.2px}
  .chip{
    display:inline-flex;align-items:center;gap:.45rem;
    padding:.38rem .7rem;border-radius:999px;font-weight:800;font-size:.82rem;
    background:linear-gradient(180deg,var(--card-2),var(--card));border:1px solid var(--card-bd);
    box-shadow:var(--shadow-1)
  }
  .chip i{opacity:.9}
  .chip .label{color:var(--muted);text-transform:uppercase;letter-spacing:.03em}

  /* ========== Sticky filter bar ========== */
  .filter-wrap{position:sticky;top:0;z-index:20;margin-bottom:1rem}
  .filterbar{
    display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;
    padding:.85rem .95rem;border-radius:14px;border:1px solid var(--card-bd);
    background:linear-gradient(180deg, rgba(248,250,252,.82), rgba(255,255,255,.9));
    box-shadow:var(--shadow-1);backdrop-filter:blur(6px)
  }
  [data-theme="dark"] .filterbar{
    background:linear-gradient(180deg, rgba(15,23,42,.72), rgba(11,19,36,.8));
  }
  .filterbar .form-select{min-width:260px;border-radius:12px;border:1px solid var(--card-bd)}
  .hint{color:var(--muted);font-weight:700}
  .divider{width:1px;height:28px;background:var(--card-bd);margin:0 .25rem}

  /* ========== Empty state ========== */
  .empty{
    border:1px dashed var(--card-bd);border-radius:var(--radius);
    background:linear-gradient(180deg,var(--card-2),var(--card));
    padding:2rem;text-align:center;color:var(--muted);box-shadow:var(--shadow-1)
  }
  .empty svg{opacity:.9;margin-bottom:.75rem}

  /* ========== Grid & Cards ========== */
  .grid{display:grid;gap:1.1rem}
  @media(min-width:576px){ .grid{grid-template-columns:repeat(2,minmax(0,1fr))} }
  @media(min-width:1200px){ .grid{grid-template-columns:repeat(3,minmax(0,1fr))} }

  .card{
    display:flex;flex-direction:column;height:100%;
    border:1px solid var(--card-bd);border-radius:var(--radius);
    background:linear-gradient(180deg,var(--card-2),var(--card));
    box-shadow:var(--shadow-1);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .card:hover{transform:translateY(-2px);box-shadow:var(--shadow-2);border-color:rgba(0,122,51,.35)}

  .card-header{
    padding:1rem 1.1rem;border-bottom:1px solid var(--card-bd);
    display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem
  }
  .title-wrap{min-width:0}
  .module-title{
    margin:0;font-weight:900;line-height:1.25;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  }
  .code-pill{
    white-space:nowrap;border:1px solid var(--card-bd);border-radius:999px;
    padding:.2rem .55rem;font-weight:800;color:var(--brand);background:rgba(0,122,51,.08)
  }
  [data-theme="dark"] .code-pill{background:rgba(96,165,250,.18)}

  .year-pill{
    border:0;border-radius:999px;padding:.28rem .6rem;font-weight:900;color:#fff;
    background:linear-gradient(90deg,var(--brand),var(--brand2));box-shadow:0 8px 18px rgba(0,122,51,.25)
  }

  .card-body{padding:1rem 1.1rem}
  .meta{
    display:flex;gap:.85rem;flex-wrap:wrap;margin-bottom:.85rem;color:var(--muted);font-weight:700
  }
  .meta i{opacity:.9}
  .divider-h{height:1px;background:var(--card-bd);margin:.6rem 0}

  .actions{display:flex;flex-wrap:wrap;gap:.55rem}
  .btn-primary{
    display:inline-flex;align-items:center;gap:.45rem;
    padding:.55rem .8rem;border-radius:12px;font-weight:900;border:1px solid transparent;
    color:#fff;background:linear-gradient(90deg,var(--brand),var(--brand2));
    box-shadow:0 10px 24px rgba(0,122,51,.25)
  }
  .btn-primary:hover{filter:saturate(1.08) brightness(1.05)}
  .btn-ghost{
    display:inline-flex;align-items:center;gap:.45rem;
    padding:.55rem .8rem;border-radius:12px;font-weight:900;
    border:1px solid var(--card-bd);background:transparent;color:var(--fg);text-decoration:none
  }
  .btn-ghost:hover{border-color:var(--brand);color:var(--brand)}

  .card-footer{
    margin-top:auto;padding:.8rem 1.1rem;border-top:1px solid var(--card-bd);
    color:var(--muted);font-weight:700;font-size:.9rem
  }
</style>

<script>
  (()=>{ 
    const k='studentTheme', saved=localStorage.getItem(k);
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
      <h4 class="title mb-2">Modules by Year & Semester</h4>
      <div class="d-flex flex-wrap gap-2">
        <span class="chip"><i class="ri-bank-line"></i><span class="label">Department</span><span>{{ $departmentName ?? '—' }}</span></span>
      </div>
    </div>
  </div>

  <!-- Sticky Filters -->
  <div class="filter-wrap">
    <form method="GET" class="filterbar" role="search" aria-label="Filter modules by year and semester">
      <i class="ri-filter-3-line" aria-hidden="true"></i>

      <label for="year_id" class="fw-bold text-muted mb-0" style="min-width:86px">Year</label>
      <select name="year_id" id="year_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Select Year">
        <option value="">Choose Year…</option>
        @foreach($years as $y)
          <option value="{{ $y->id }}" {{ (int)$selectedYearId === (int)$y->id ? 'selected' : '' }}>
            {{ $y->year_name }}
          </option>
        @endforeach
      </select>

      <span class="divider" aria-hidden="true"></span>

      <label for="semester" class="fw-bold text-muted mb-0" style="min-width:86px">Semester</label>
      <select name="semester" id="semester" class="form-select form-select-sm" {{ $selectedYearId ? '' : 'disabled' }} onchange="this.form.submit()" aria-label="Select Semester">
        <option value="">Choose Semester…</option>
        @foreach($availableSemesters as $sem)
          <option value="{{ $sem }}" {{ (string)$selectedSemester === (string)$sem ? 'selected' : '' }}>Semester {{ $sem }}</option>
        @endforeach
      </select>

      @if(!$filtersReady)
        <span class="hint ms-1"><i class="ri-arrow-right-line"></i> Select <strong>Year</strong> and <strong>Semester</strong> to load modules.</span>
      @endif
    </form>
  </div>

  <!-- Content -->
  @if(!$filtersReady)
    <div class="empty mt-3">
      <svg width="88" height="88" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <rect x="3" y="4" width="18" height="14" rx="2" stroke-width="1.5"></rect>
        <path d="M3 8h18M7 12h10M7 16h6" stroke-width="1.5"></path>
      </svg>
      <div class="fw-bold">Waiting for your selection</div>
      <div>Pick a <strong>Year</strong> and a <strong>Semester</strong> above.</div>
    </div>
  @else
    @if($moduleCards->isEmpty())
      <div class="empty mt-2">
        <svg width="88" height="88" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="12" cy="12" r="9" stroke-width="1.5"></circle>
          <path d="M8 12h8M12 8v8" stroke-width="1.5"></path>
        </svg>
        <div class="fw-bold">No modules found</div>
        <div>Try a different semester or year.</div>
      </div>
    @else
      <div class="grid mt-2">
        @foreach($moduleCards as $m)
          <article class="card" aria-label="Module">
            <header class="card-header">
              <div class="title-wrap">
                <!-- Use course name as main title (or swap to module title if you have it) -->
                <h6 class="module-title">
                  {{ $m->course ?? 'Module' }}
                  @if($m->course_code)
                    <span class="code-pill ms-1">({{ $m->course_code }})</span>
                  @endif
                </h6>
              </div>
              @if($m->year_name)
                <!-- FIX: show only the year name (no 'Year: Year 1' duplication) -->
                <span class="year-pill">{{ $m->year_name }}</span>
              @endif
            </header>

            <div class="card-body">
              <div class="meta">
                <span><i class="ri-calendar-2-line"></i> Semester <strong>{{ $m->semester ?? '—' }}</strong></span>
                <span><i class="ri-sticky-note-line"></i> Lessons <strong>{{ $m->lessons_count }}</strong></span>
              </div>

              <div class="divider-h"></div>

              <div class="actions" aria-label="Module actions">
                <a href="{{ route('student.module', $m->id) }}" class="btn-primary">
                  <i class="ri-arrow-right-circle-line"></i> Open Module
                </a>
                <a href="{{ route('student.module', $m->id) }}#notes" class="btn-ghost">
                  <i class="ri-sticky-note-line"></i> Notes
                </a>
                <a href="{{ route('student.module', $m->id) }}#resources" class="btn-ghost">
                  <i class="ri-archive-2-line"></i> Resources
                </a>
              </div>
            </div>

            <footer class="card-footer">
              Added: {{ optional($m->created_at)->format('d M Y') }}
            </footer>
          </article>
        @endforeach
      </div>
    @endif
  @endif
</div>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
@endsection
