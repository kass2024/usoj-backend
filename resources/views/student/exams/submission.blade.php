@extends('layouts.student.app')

@section('css')
<style>
:root {
    --bg:#0f172a; --card:#111827; --text:#e5e7eb; --muted:#9ca3af;
    --accent:#3b82f6; --danger:#ef4444; --ok:#22c55e; --border:#1f2937;
}
body { background:var(--bg); color:var(--text); }
.card { background:var(--card); border:1px solid var(--border); border-radius:.9rem; }
.card-header { border-bottom:1px solid rgba(255,255,255,.08); }
.badge { font-weight:600; }
.kbd { background:#111827;color:#e5e7eb;border:1px solid #1f2937;border-radius:.35rem;padding:.08rem .35rem; }
.hidden { display:none !important; }
.text-muted-2 { color:#9ca3af; }
.pager-nums .btn{width:42px;height:42px;font-weight:700}
.option-card{cursor:pointer}
.option-card input{margin-top:.3rem}
.option-active{outline:2px solid rgba(59,130,246,.8)}
</style>
@endsection

@section('body')
@php
  $exam = $exam ?? ($quiz ?? null);
@endphp

<div class="container-xxl py-4">
    <div class="card shadow-lg border-0">

        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">{{ $exam->title ?? 'Exam' }}</h5>
                <div class="small text-muted">
                    Runs in <strong>Fullscreen</strong>. Auto-submit on exit/minimize/tab-switch.
                    <span class="ms-2">Time Remaining: <span class="fw-bold" id="quizTimer">--:--</span></span>
                </div>
            </div>
        </div>

        <!-- START SCREEN -->
        <div class="card-body" id="startScreen">
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">Important Exam Instructions</div>
                <ul class="mb-0 ps-3">
                    <li>The exam runs in <strong>Fullscreen</strong>. If you minimize, switch tab, or exit fullscreen, it will auto-submit.</li>
                </ul>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="readyChk">
                <label class="form-check-label" for="readyChk">
                    I confirm I am ready to start this exam.
                </label>
            </div>

            <button class="btn btn-primary" id="startBtn">Start Exam</button>
        </div>

        <!-- EXAM SCREEN -->
        <form class="card-body hidden" id="examForm"
              method="POST"
              action="{{ route('student.exams.save_submission', $exam->id) }}"
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
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold">Question Navigator</span>
                                <span class="ms-2 small text-muted">(click a number)</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2 pager-nums" id="numRail"></div>
                            <div class="alert alert-secondary mt-3 mb-0 small">
                                <div class="fw-semibold mb-1">Shortcuts</div>
                                <div><span class="kbd">←</span>/<span class="kbd">→</span> previous/next • <span class="kbd">1…9</span> jump</div>
                            </div>
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
                <h4 id="resultTitle" class="mb-2">Exam submitted</h4>
                <p id="resultMsg" class="text-muted">Your answers have been recorded.</p>
                <p id="resultReason" class="small text-muted"></p>
            </div>
        </div>

    </div>
</div>

@php
    $EXAM_DURATION = (int) ($exam->duration_minutes ?? 60) * 60;
    $EXAM_QUESTIONS = $questions->map(function($q){
        return [
            'id' => $q->id,
            'type' => $q->type,   // 'radio' | 'checkbox' | 'open' | 'file'
            'marks' => $q->marks,
            'title' => $q->title,
            'choices' => collect($q->choices ?? [])->map(fn($c)=>['id'=>$c['id'] ?? null, 'title'=>$c['title'] ?? null])->values()->all(),
        ];
    })->values()->all();
@endphp

<script>
window.EXAM = {
    durationSeconds: {{ $EXAM_DURATION }},
    questions: @json($EXAM_QUESTIONS)
};
</script>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const $ = (s, r=document)=>r.querySelector(s);
  const $$= (s, r=document)=>Array.from(r.querySelectorAll(s));

  const startBtn = $('#startBtn');
  const readyChk = $('#readyChk');
  const startScreen = $('#startScreen');
  const examForm = $('#examForm');
  const resultScreen = $('#resultScreen');
  const qHost = $('#qHost');
  const numRail = $('#numRail');
  const prevBtn = $('#prevBtn');
  const nextBtn = $('#nextBtn');
  const finishBtn = $('#finishBtn');
  const progBar = $('#progBar');
  const progressTxt = $('#progressTxt');
  const hiddenInputs = $('#hiddenInputs');
  const timerEl = $('#quizTimer');

  const Q = window.EXAM?.questions ?? [];
  let currentIndex = 0;
  let submitted = false;

  // answers: radio/open -> value (string)
  //          checkbox    -> array of values (string[])
  //          file        -> File object
  const answers = Q.map(q => (q.type==='checkbox'?[]: (q.type==='file'?null:null)));
  const filesStore = {};

  function renderQuestion(q){
    if(!q){ qHost.innerHTML='<p class="text-muted">No questions.</p>'; return; }
    qHost.innerHTML='';
    const head=document.createElement('div');
    head.className='d-flex align-items-start justify-content-between mb-2';
    head.innerHTML = `<h6 class="mb-0">Question ${currentIndex+1}: ${q.title}</h6>
                      <span class="badge text-bg-light">${q.marks ?? 1} mark(s)</span>`;
    qHost.appendChild(head);
    qHost.appendChild(Object.assign(document.createElement('hr'),{className:'mt-2 mb-3'}));

    if(q.type==='radio' || (q.type!=='checkbox' && q.choices?.length)){
      // treat as single-choice if choices present and not checkbox
      (q.choices||[]).forEach(ch=>{
        const id = `q${q.id}_${ch.id}`;
        const wrapper=document.createElement('label');
        wrapper.className='d-flex align-items-start gap-2 border rounded p-2 mb-2 option-card';
        const input=document.createElement('input');
        input.type='radio'; input.className='form-check-input mt-1';
        input.name=`q_${q.id}`; input.value=ch.id; input.id=id;
        input.checked = (answers[currentIndex]===ch.id);
        input.addEventListener('change', ()=>{
          answers[currentIndex]=ch.id;
          $$('.option-card', qHost).forEach(el=>el.classList.remove('option-active'));
          wrapper.classList.add('option-active');
          syncHiddenInputs(); updatePagerUI();
        });
        const text=document.createElement('div'); text.className='form-check-label'; text.textContent = ch.title;
        wrapper.appendChild(input); wrapper.appendChild(text);
        if(input.checked) wrapper.classList.add('option-active');
        qHost.appendChild(wrapper);
      });
    } else if(q.type==='checkbox'){
      (q.choices||[]).forEach(ch=>{
        const id=`q${q.id}_${ch.id}`;
        const wrapper=document.createElement('label');
        wrapper.className='d-flex align-items-start gap-2 border rounded p-2 mb-2 option-card';
        const input=document.createElement('input');
        input.type='checkbox'; input.className='form-check-input mt-1';
        input.name=`q_${q.id}[]`; input.value=ch.id; input.id=id;
        const arr = Array.isArray(answers[currentIndex]) ? answers[currentIndex] : [];
        input.checked = arr.includes(ch.id);
        input.addEventListener('change', ()=>{
          const a = Array.isArray(answers[currentIndex]) ? answers[currentIndex] : [];
          if(input.checked){ if(!a.includes(ch.id)) a.push(ch.id); }
          else{ const i=a.indexOf(ch.id); if(i>-1) a.splice(i,1); }
          answers[currentIndex]=a;
          wrapper.classList.toggle('option-active', input.checked);
          syncHiddenInputs(); updatePagerUI();
        });
        const text=document.createElement('div'); text.className='form-check-label'; text.textContent = ch.title;
        wrapper.appendChild(input); wrapper.appendChild(text);
        if(input.checked) wrapper.classList.add('option-active');
        qHost.appendChild(wrapper);
      });
    } else if(q.type==='open'){
      const ta=document.createElement('textarea');
      ta.className='form-control'; ta.rows=6; ta.placeholder='Write your answer here...';
      ta.value = answers[currentIndex] || '';
      ta.addEventListener('input', ()=>{ answers[currentIndex]=ta.value; syncHiddenInputs(); updatePagerUI(); });
      qHost.appendChild(ta);
    } else if(q.type==='file'){
      const inp=document.createElement('input'); inp.type='file'; inp.className='form-control';
      inp.addEventListener('change', ()=>{ filesStore[q.id] = inp.files?.[0] || null; answers[currentIndex] = filesStore[q.id] ? '[file]' : null; updatePagerUI(); });
      qHost.appendChild(inp);
    } else {
      // Fallback to open text if type missing and no choices
      const ta=document.createElement('textarea');
      ta.className='form-control'; ta.rows=6; ta.value = answers[currentIndex] || '';
      ta.addEventListener('input', ()=>{ answers[currentIndex]=ta.value; syncHiddenInputs(); updatePagerUI(); });
      qHost.appendChild(ta);
    }

    prevBtn.disabled = currentIndex===0;
    nextBtn.disabled = currentIndex===Q.length-1;
    syncHiddenInputs(); updatePagerUI();
  }

  function initPager(){
    numRail.innerHTML='';
    Q.forEach((_,i)=>{
      const b=document.createElement('button');
      b.type='button'; b.className='btn btn-outline-secondary'; b.textContent=String(i+1);
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
      const q=Q[i]; const v=answers[i];
      const isAnswered = q.type==='checkbox' ? (Array.isArray(v) && v.length>0)
                      : q.type==='file' ? !!filesStore[q.id]
                      : !!v;
      b.classList.toggle('active', isAnswered);
      b.setAttribute('aria-label', `Question ${i+1}${isAnswered?' (answered)':''}`);
    });
    const answered = answers.reduce((n,v,i)=>{
      const q=Q[i];
      const ok = q.type==='checkbox' ? (Array.isArray(v) && v.length>0)
               : q.type==='file' ? !!filesStore[q.id]
               : !!v;
      return n + (ok?1:0);
    },0);
    const pct = (answered/Math.max(Q.length,1))*100;
    progBar.style.width = `${pct}%`;
    progressTxt.textContent = `${answered}/${Q.length} answered`;
  }

  function goPrev(){ if(currentIndex>0){ currentIndex--; renderQuestion(Q[currentIndex]); } }
  function goNext(){ if(currentIndex<Q.length-1){ currentIndex++; renderQuestion(Q[currentIndex]); } }

  prevBtn.addEventListener('click', goPrev);
  nextBtn.addEventListener('click', goNext);

  // Timer
  function startTimer(total){
    let remain = total;
    const tick=()=>{
      if(submitted) return;
      const m = Math.floor(remain/60), s = remain%60;
      timerEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      if(remain<=0){ finishBtn.click(); return; }
      remain--; setTimeout(tick, 1000);
    };
    tick();
  }

  // Hidden inputs sync (Laravel-friendly nested structure)
  function syncHiddenInputs(){
    hiddenInputs.innerHTML='';
    Q.forEach((q,idx)=>{
      const wrap=document.createElement('div');
      wrap.innerHTML += `<input type="hidden" name="questions[${q.id}][question_id]" value="${q.id}">`;
      const v = answers[idx];

      if(q.type==='checkbox'){
        const arr = Array.isArray(v)? v : [];
        arr.forEach(val=>{
          const i=document.createElement('input');
          i.type='hidden'; i.name=`questions[${q.id}][answer][]`; i.value=val;
          wrap.appendChild(i);
        });
      } else if(q.type==='file'){
        // file field must be a real <input type="file" name="..."> in form.
        // We add a placeholder field name so backend expects it:
        // ensure the visible input has name `questions[ID][file]`.
        // We'll re-name the rendered file input here if present.
        const fileInput = qHost.querySelector('input[type="file"]');
        if(fileInput){ fileInput.name = `questions[${q.id}][file]`; }
      } else {
        const i=document.createElement('input');
        i.type='hidden'; i.name=`questions[${q.id}][answer]`; i.value=v??'';
        wrap.appendChild(i);
      }
      hiddenInputs.appendChild(wrap);
    });
  }

  // Submit
  finishBtn.addEventListener('click', ()=>{
    syncHiddenInputs();
    submitted=true;
    try{ if(document.fullscreenElement) document.exitFullscreen(); }catch(_){}
    examForm.submit();
  });

  // Start
  startBtn.addEventListener('click', async ()=>{
    if(!readyChk.checked){
      alert('Please confirm that you are ready to start the exam.');
      return;
    }
    // Fullscreen
    try{
      const el=document.documentElement;
      if(el.requestFullscreen) await el.requestFullscreen();
      else if(el.webkitRequestFullscreen) await el.webkitRequestFullscreen();
    }catch(_){}
    if(!document.fullscreenElement && !document.webkitFullscreenElement){
      alert('Fullscreen is required to start the exam.');
      return;
    }

    startScreen.classList.add('hidden');
    examForm.classList.remove('hidden');

    initPager();
    renderQuestion(Q[0]);
    startTimer(window.EXAM?.durationSeconds ?? 3600);
  });

  // Auto-submit on losing focus / leaving fullscreen / tab switch
  document.addEventListener('fullscreenchange', ()=>{
    if(!submitted && !document.fullscreenElement){ submitted=true; $('#resultReason').textContent=''; examForm.submit(); }
  });
  document.addEventListener('visibilitychange', ()=>{
    if(!submitted && document.visibilityState!=='visible'){ submitted=true; $('#resultReason').textContent=''; examForm.submit(); }
  });
  window.addEventListener('blur', ()=>{
    if(!submitted){ submitted=true; $('#resultReason').textContent=''; examForm.submit(); }
  });
  window.addEventListener('beforeunload', ()=>{
    if(!submitted){ submitted=true; $('#resultReason').textContent=''; }
  });

  // Keyboard shortcuts
  window.addEventListener('keydown', (e)=>{
    const k=(e.key||'').toLowerCase();
    if(submitted) return;
    if(k==='arrowright'){ e.preventDefault(); goNext(); }
    if(k==='arrowleft'){ e.preventDefault(); goPrev(); }
    if(!e.ctrlKey && !e.metaKey && /^[1-9]$/.test(k)){
      const idx=parseInt(k,10)-1; if(idx>=0 && idx<Q.length){ currentIndex=idx; renderQuestion(Q[currentIndex]); }
    }
  }, {capture:true});

});
</script>
@endsection
