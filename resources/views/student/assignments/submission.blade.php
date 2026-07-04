@extends('layouts.student.app')

@section('css')
<style>
  :root { --bg:#0f172a; --card:#111827; --text:#e5e7eb; --muted:#9ca3af; --accent:#3b82f6; --danger:#ef4444; --ok:#22c55e; --border:#1f2937; }
  body { background:var(--bg); color:var(--text); }
  .card { background:var(--card); border:1px solid var(--border); border-radius:.9rem; }
  .card-header { border-bottom:1px solid rgba(255,255,255,.08); }
  .badge { font-weight:600; }
  .pager-nums .btn{width:42px;height:42px;font-weight:700}
  .option-card{cursor:pointer} .option-card input{margin-top:.3rem}
  .option-active{outline:2px solid rgba(59,130,246,.8)}
  .kbd{background:#111827;color:#e5e7eb;border:1px solid #1f2937;border-radius:.35rem;padding:.08rem .35rem}
  .hidden{display:none !important}
</style>
@endsection

@section('body')
<div class="container-xxl py-4">
  <div class="card shadow-lg border-0">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-1">{{ $assignment->title ?? 'Assignment' }}</h5>
        <div class="small text-muted">
          Time Remaining: <span class="fw-bold" id="quizTimer">--:--</span>
        </div>
      </div>
      <div><span class="badge rounded-pill text-bg-info" id="status">Ready</span></div>
    </div>

    <!-- START SCREEN -->
    <div class="card-body" id="startScreen">
      <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Important Instructions</div>
        <ul class="mb-0 ps-3">
          <li>Complete all questions before submitting.</li>
          <li>Answer honestly; no plagiarism.</li>
        </ul>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="readyChk">
        <label class="form-check-label" for="readyChk">
          I confirm I am ready to start this assignment.
        </label>
      </div>

      <button class="btn btn-primary" id="startBtn">Start</button>
    </div>

    <!-- ASSIGNMENT SCREEN -->
    <form class="card-body hidden" id="examForm"
          method="POST"
          action="{{ route('student.assignments.save_submission', $assignment->id) }}"
          enctype="multipart/form-data"
          autocomplete="off">
      @csrf

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm">
            <div class="card-body" id="qHost"></div>
          </div>

          <div class="d-flex align-items-center gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" id="prevBtn">← Prev</button>
            <button type="button" class="btn btn-primary" id="nextBtn">Next →</button>
            <div class="ms-auto" style="min-width:240px">
              <div class="progress" style="height:10px;">
                <div class="progress-bar" id="progBar" role="progressbar" style="width:0%"></div>
              </div>
              <div class="small text-muted mt-1" id="progressTxt">0/0 answered</div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header">
              <div class="fw-semibold">Question Navigator</div>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap gap-2 pager-nums" id="numRail"></div>
            </div>
          </div>

          <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
              <div class="d-grid">
                <button type="button" class="btn btn-danger" id="finishBtn">Submit Now</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden payload -->
      <div id="hiddenInputs" class="hidden"></div>
    </form>

    <!-- RESULT SCREEN -->
    <div class="card-body hidden" id="resultScreen">
      <div class="text-center py-4">
        <h4 id="resultTitle" class="mb-2">Assignment submitted</h4>
        <p id="resultMsg" class="text-muted">Your answers have been recorded.</p>
      </div>
    </div>
  </div>
</div>

@php
  $ASSIGNMENT_DURATION = (int) ($assignment->duration_minutes ?? 60) * 60;
  $ASSIGNMENT_QUESTIONS = $questions->map(function($q){
      return [
          'id'      => $q->id,
          'type'    => $q->type, // 'radio' | 'checkbox' | 'open' | 'file'
          'marks'   => $q->marks,
          'title'   => $q->title,
          'choices' => collect($q->choices ?? [])->map(fn($c)=>['id'=>$c['id'] ?? null, 'title'=>$c['title'] ?? null])->values()->all(),
      ];
  })->values()->all();
@endphp

<script>
  window.EXAM = {
    durationSeconds: {{ $ASSIGNMENT_DURATION }},
    questions: @json($ASSIGNMENT_QUESTIONS)
  };
</script>
@endsection

@section('js')
<script>
(function(){
  const $  = (s,r=document)=>r.querySelector(s);
  const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));

  const startBtn=$('#startBtn'), readyChk=$('#readyChk');
  const startScreen=$('#startScreen'), examForm=$('#examForm'), resultScreen=$('#resultScreen');
  const qHost=$('#qHost'), numRail=$('#numRail'), prevBtn=$('#prevBtn'), nextBtn=$('#nextBtn');
  const progBar=$('#progBar'), progressTxt=$('#progressTxt'), finishBtn=$('#finishBtn');
  const hiddenInputs=$('#hiddenInputs'), timerEl=$('#quizTimer'), statusEl=$('#status');

  const Q = window.EXAM?.questions || [];
  const DURATION = window.EXAM?.durationSeconds || 3600;

  let currentIndex=0, submitted=false;
  // answers: radio/open -> string, checkbox -> string[], file -> File|null
  const answers = Q.map(q => q.type==='checkbox' ? [] : (q.type==='file' ? null : ''));
  const filesStore = {}; // by question id

  function startTimer(secTotal){
    let remain=secTotal;
    const tick=()=>{
      if(submitted) return;
      const m=Math.floor(remain/60), s=remain%60;
      timerEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      if(remain<=0){ submitExam(true,'Time up'); return; }
      remain--; setTimeout(tick,1000);
    };
    tick();
  }

  function syncHiddenInputs(){
    hiddenInputs.innerHTML='';
    Q.forEach((q,idx)=>{
      const wrap=document.createElement('div');
      // Always include question_id
      const qid=document.createElement('input');
      qid.type='hidden'; qid.name=`questions[${q.id}][question_id]`; qid.value=q.id;
      wrap.appendChild(qid);

      if(q.type==='checkbox'){
        const arr = Array.isArray(answers[idx]) ? answers[idx] : [];
        arr.forEach(val=>{
          const i=document.createElement('input');
          i.type='hidden'; i.name=`questions[${q.id}][answer][]`; i.value=val;
          wrap.appendChild(i);
        });
      } else if(q.type==='file'){
        // Ensure there is an actual file input in the rendered question with this name:
        const fileInput = qHost.querySelector(`input[type="file"][data-q="${q.id}"]`);
        if(fileInput){ fileInput.name = `questions[${q.id}][file]`; }
      } else {
        const i=document.createElement('input');
        i.type='hidden'; i.name=`questions[${q.id}][answer]`; i.value=answers[idx] ?? '';
        wrap.appendChild(i);
      }

      hiddenInputs.appendChild(wrap);
    });
  }

  function initPager(){
    numRail.innerHTML='';
    Q.forEach((_,i)=>{
      const b=document.createElement('button'); b.type='button'; b.className='btn btn-outline-secondary'; b.textContent=String(i+1);
      b.addEventListener('click', ()=>{ currentIndex=i; renderQuestion(Q[currentIndex]); });
      numRail.appendChild(b);
    });
    updatePagerUI();
  }

  function updatePagerUI(){
    const buttons = $$('.btn', numRail);
    buttons.forEach((b,i)=>{
      b.classList.toggle('btn-primary', i===currentIndex);
      b.classList.toggle('btn-outline-secondary', i!==currentIndex);
      const q=Q[i], v=answers[i];
      const isAnswered = q.type==='checkbox' ? (Array.isArray(v) && v.length>0)
                      : q.type==='file' ? !!filesStore[q.id]
                      : !!(v && String(v).length);
      b.classList.toggle('active', isAnswered);
      b.setAttribute('aria-label', `Question ${i+1}${isAnswered?' (answered)':''}`);
    });
    const answered = answers.reduce((n,v,i)=>{
      const q=Q[i];
      const ok = q.type==='checkbox' ? (Array.isArray(v) && v.length>0)
               : q.type==='file' ? !!filesStore[q.id]
               : !!(v && String(v).length);
      return n + (ok?1:0);
    },0);
    const pct=(answered/Math.max(Q.length,1))*100;
    progBar.style.width = `${pct}%`;
    progressTxt.textContent = `${answered}/${Q.length} answered`;
  }

  function renderQuestion(q){
    if(!q){ qHost.innerHTML='<p class="text-muted">No questions.</p>'; return; }
    qHost.innerHTML='';
    const head=document.createElement('div');
    head.className='d-flex align-items-start justify-content-between mb-2';
    head.innerHTML = `<h6 class="mb-0">Question ${currentIndex+1}: ${q.title}</h6>
                      <span class="badge text-bg-light">${q.marks ?? 1} mark(s)</span>`;
    qHost.appendChild(head);
    qHost.appendChild(Object.assign(document.createElement('hr'),{className:'mt-2 mb-3'}));

    if(q.type==='radio' || (q.choices?.length && q.type!=='checkbox')){
      (q.choices||[]).forEach(ch=>{
        const id=`q${q.id}_${ch.id}`;
        const wrap=document.createElement('label'); wrap.className='d-flex align-items-start gap-2 border rounded p-2 mb-2 option-card';
        const input=document.createElement('input');
        input.type='radio'; input.className='form-check-input mt-1';
        input.name=`q_${q.id}`; input.value=ch.id; input.id=id;
        input.checked = (answers[currentIndex]===ch.id);
        input.addEventListener('change', ()=>{
          answers[currentIndex]=ch.id;
          $$('.option-card', qHost).forEach(e=>e.classList.remove('option-active'));
          wrap.classList.add('option-active');
          syncHiddenInputs(); updatePagerUI();
        });
        const text=document.createElement('div'); text.className='form-check-label'; text.textContent=ch.title;
        wrap.appendChild(input); wrap.appendChild(text);
        if(input.checked) wrap.classList.add('option-active');
        qHost.appendChild(wrap);
      });
    } else if(q.type==='checkbox'){
      (q.choices||[]).forEach(ch=>{
        const id=`q${q.id}_${ch.id}`;
        const wrap=document.createElement('label'); wrap.className='d-flex align-items-start gap-2 border rounded p-2 mb-2 option-card';
        const input=document.createElement('input');
        input.type='checkbox'; input.className='form-check-input mt-1';
        input.name=`q_${q.id}[]`; input.value=ch.id; input.id=id;
        const arr = Array.isArray(answers[currentIndex]) ? answers[currentIndex] : [];
        input.checked = arr.includes(ch.id);
        input.addEventListener('change', ()=>{
          const a = Array.isArray(answers[currentIndex]) ? answers[currentIndex] : [];
          if(input.checked){ if(!a.includes(ch.id)) a.push(ch.id); }
          else{ const ix=a.indexOf(ch.id); if(ix>-1) a.splice(ix,1); }
          answers[currentIndex]=a;
          wrap.classList.toggle('option-active', input.checked);
          syncHiddenInputs(); updatePagerUI();
        });
        const text=document.createElement('div'); text.className='form-check-label'; text.textContent=ch.title;
        wrap.appendChild(input); wrap.appendChild(text);
        if(input.checked) wrap.classList.add('option-active');
        qHost.appendChild(wrap);
      });
    } else if(q.type==='open'){
      const ta=document.createElement('textarea');
      ta.className='form-control'; ta.rows=6; ta.placeholder='Write your answer here...';
      ta.value = answers[currentIndex] || '';
      ta.addEventListener('input', ()=>{ answers[currentIndex]=ta.value; syncHiddenInputs(); updatePagerUI(); });
      qHost.appendChild(ta);
    } else if(q.type==='file'){
      const inp=document.createElement('input'); inp.type='file'; inp.className='form-control'; inp.setAttribute('data-q', q.id);
      inp.addEventListener('change', ()=>{
        filesStore[q.id] = inp.files?.[0] || null;
        answers[currentIndex] = filesStore[q.id] ? '[file]' : '';
        // Name gets set in syncHiddenInputs() to questions[ID][file]
        syncHiddenInputs(); updatePagerUI();
      });
      qHost.appendChild(inp);
    } else {
      // Fallback to open text
      const ta=document.createElement('textarea');
      ta.className='form-control'; ta.rows=6; ta.value = answers[currentIndex] || '';
      ta.addEventListener('input', ()=>{ answers[currentIndex]=ta.value; syncHiddenInputs(); updatePagerUI(); });
      qHost.appendChild(ta);
    }

    prevBtn.disabled = currentIndex===0;
    nextBtn.disabled = currentIndex===Q.length-1;
    syncHiddenInputs(); updatePagerUI();
  }

  function goTo(i){ currentIndex=Math.max(0, Math.min(Q.length-1, i)); renderQuestion(Q[currentIndex]); }
  prevBtn.addEventListener('click', ()=>{ if(currentIndex>0) goTo(currentIndex-1); });
  nextBtn.addEventListener('click', ()=>{ if(currentIndex<Q.length-1) goTo(currentIndex+1); });

  function submitExam(auto=false, reason=''){
    if(submitted) return;
    submitted=true;
    statusEl.textContent='Submitting…';
    syncHiddenInputs();
    examForm.submit();
  }

  finishBtn.addEventListener('click', ()=>submitExam(false));

  // Start flow
  startBtn.addEventListener('click', ()=>{
    if(!readyChk.checked){ alert('Please confirm you are ready.'); return; }
    startScreen.classList.add('hidden');
    examForm.classList.remove('hidden');
    initPager(); renderQuestion(Q[0]); startTimer(DURATION);
  });

  // Keyboard shortcuts
  window.addEventListener('keydown', (e)=>{
    const k=(e.key||'').toLowerCase();
    if(submitted) return;
    if(k==='arrowright'){ e.preventDefault(); if(currentIndex<Q.length-1) goTo(currentIndex+1); }
    if(k==='arrowleft'){ e.preventDefault(); if(currentIndex>0) goTo(currentIndex-1); }
    if(!e.ctrlKey && !e.metaKey && /^[1-9]$/.test(k)){
      const idx=parseInt(k,10)-1; if(idx>=0 && idx<Q.length) goTo(idx);
    }
  }, {capture:true});

})();
</script>
@endsection
