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
  .pager-nums .btn{width:42px;height:42px;font-weight:700}
  .option-card{cursor:pointer}
  .option-card input{margin-top:.3rem}
  .option-active{outline:2px solid rgba(59,130,246,.8)}
  .kbd{background:#111827;color:#e5e7eb;border:1px solid #1f2937;border-radius:.35rem;padding:.08rem .35rem}
  .hidden{display:none !important}
  .text-muted-2{color:#9ca3af}
</style>
@endsection

@section('body')
<div class="container-xxl py-4">
  <div class="card shadow-lg border-0">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-1">{{ $quiz->title ?? 'Exam' }}</h5>
        <div class="small text-muted">
          Runs in <strong>Fullscreen</strong>. Auto-submit on exit/minimize/tab-switch.
          <span class="ms-2">Time Remaining: <span class="fw-bold" id="quizTimer">--:--</span></span>
        </div>
      </div>
      <div>
        <span class="badge rounded-pill text-bg-info" id="status">Ready</span>
      </div>
    </div>

    <!-- START SCREEN -->
    <div class="card-body" id="startScreen">
      <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Important Exam Instructions</div>
        <ul class="mb-0 ps-3">
          <li>The exam runs in <strong>Fullscreen</strong>. If you minimize, switch tab, or exit fullscreen, it will <strong>auto-submit</strong>.</li>
          <li>Copy/paste and large text selections are tracked (plagiarism protection).</li>
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
    <form class="card-body hidden" id="examForm" method="POST" action="{{ route('student.quizzes.save_submission', $quiz->id) }}" enctype="multipart/form-data" autocomplete="off">
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
              <span class="fw-semibold">Question Navigator</span>
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
      <div id="hiddenInputs" class="hidden"></div>
    </form>

    <!-- RESULT SCREEN -->
    <div class="card-body hidden" id="resultScreen">
      <div class="text-center py-4">
        <h4 id="resultTitle" class="mb-2">Exam submitted</h4>
        <p id="resultMsg" class="text-muted">Your answers have been recorded.</p>
        <p id="resultReason" class="small text-muted"></p>
        <p id="resultPassFail" class="fw-semibold"></p>
      </div>
    </div>
  </div>
</div>

@php
    $EXAM_DURATION = (int) ($quiz->duration_minutes ?? 60) * 60;
    $EXAM_QUESTIONS = $questions->map(function($q){
        return [
            'id'      => $q->id,
            'type'    => $q->type,
            'marks'   => $q->marks,
            'title'   => $q->title,
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
@verbatim
<script>
(function(){
  const $  = (s, r=document)=>r.querySelector(s);
  const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));
  const startBtn=$('#startBtn'), readyChk=$('#readyChk');
  const startScreen=$('#startScreen'), examForm=$('#examForm'), resultScreen=$('#resultScreen');
  const qHost=$('#qHost'), numRail=$('#numRail'), prevBtn=$('#prevBtn'), nextBtn=$('#nextBtn');
  const progBar=$('#progBar'), progressTxt=$('#progressTxt'), finishBtn=$('#finishBtn'), quizTimer=$('#quizTimer');

  let started=false, submitted=false, currentIndex=0, userAnswers={};
  const Q = window.EXAM.questions;
  const DURATION = window.EXAM.durationSeconds;

  function startTimer(secTotal){
    let remain=secTotal;
    const tick=()=>{
      if(submitted) return;
      quizTimer.textContent = `${String(Math.floor(remain/60)).padStart(2,'0')}:${String(remain%60).padStart(2,'0')}`;
      if(remain<=0){ submitExam(true,'Time up'); return; }
      remain--; setTimeout(tick,1000);
    }; tick();
  }

  function syncHiddenInputs(){
    const hidden=$('#hiddenInputs'); hidden.innerHTML='';
    Q.forEach(q=>{
      const group=document.createElement('div');
      const hid=document.createElement('input'); hid.type='hidden'; hid.name=`questions[${q.id}][question_id]`; hid.value=q.id;
      group.appendChild(hid);
      if(q.type==='radio'){ const val=userAnswers[q.id]??''; group.innerHTML+=`<input type="hidden" name="questions[${q.id}][answer]" value="${val}">`; }
      else if(q.type==='checkbox'){ (userAnswers[q.id]||[]).forEach(v=>group.innerHTML+=`<input type="hidden" name="questions[${q.id}][answer][]" value="${v}">`); }
      else if(q.type==='open'){ group.innerHTML+=`<input type="hidden" name="questions[${q.id}][answer]" value="${userAnswers[q.id]||''}">`; }
      hidden.appendChild(group);
    });
  }

  function initPager(){
    numRail.innerHTML='';
    Q.forEach((_,i)=>{
      const b=document.createElement('button');
      b.type='button'; b.className='btn btn-outline-secondary'; b.textContent=String(i+1);
      b.addEventListener('click',()=>goTo(i)); numRail.appendChild(b);
    });
    updatePagerUI();
  }
  function updatePagerUI(){
    const buttons=$$('.btn',numRail);
    buttons.forEach((b,i)=>b.classList.toggle('btn-primary',i===currentIndex));
    const answered=Q.reduce((n,q)=>n+((q.type==='checkbox'?(userAnswers[q.id]||[]).length:(userAnswers[q.id]?1:0))),0);
    const pct=(answered/Q.length)*100;
    progBar.style.width=`${pct}%`; progressTxt.textContent=`${answered}/${Q.length} answered`;
  }
  function goTo(i){ currentIndex=Math.max(0,Math.min(Q.length-1,i)); renderQuestion(Q[currentIndex]); }

  function renderQuestion(q){
    qHost.innerHTML=`<h6>Question ${currentIndex+1}: ${q.title}</h6><hr>`;
    if(q.type==='radio'||q.type==='checkbox'){ q.choices.forEach(ch=>{
      const id=`q${q.id}_${ch.id}`;
      const wrapper=document.createElement('label');
      wrapper.className='d-block border rounded p-2 mb-2 option-card';
      wrapper.innerHTML=`<input type="${q.type==='radio'?'radio':'checkbox'}" name="q_${q.id}${q.type==='checkbox'?'[]':''}" value="${ch.id}" id="${id}"> ${ch.title}`;
      const input=wrapper.querySelector('input'); 
      if(q.type==='radio'){ input.checked=(userAnswers[q.id]===ch.id); }
      else{ input.checked=(userAnswers[q.id]||[]).includes(ch.id); }
      input.addEventListener('change',()=>{
        if(q.type==='radio'){ userAnswers[q.id]=ch.id; }
        else{ const arr=userAnswers[q.id]||[]; input.checked?arr.push(ch.id):arr.splice(arr.indexOf(ch.id),1); userAnswers[q.id]=arr; }
        syncHiddenInputs(); updatePagerUI();
      });
      qHost.appendChild(wrapper);
    }); }
    else if(q.type==='open'){ const ta=document.createElement('textarea'); ta.className='form-control'; ta.rows=6; ta.value=userAnswers[q.id]||''; ta.addEventListener('input',()=>{userAnswers[q.id]=ta.value; syncHiddenInputs(); updatePagerUI();}); qHost.appendChild(ta);}
    prevBtn.disabled=currentIndex===0; nextBtn.disabled=currentIndex===Q.length-1;
    syncHiddenInputs(); updatePagerUI();
  }

  startBtn.addEventListener('click',async()=>{
    if(!readyChk.checked) return alert('Please confirm you are ready.');
    try{await document.documentElement.requestFullscreen();}catch(_){}
    if(!document.fullscreenElement) return alert('Fullscreen required');
    started=true; hide(startScreen); show(examForm); initPager(); renderQuestion(Q[0]); startTimer(DURATION);
  });
  prevBtn.addEventListener('click',()=>{ if(currentIndex>0) goTo(currentIndex-1); });
  nextBtn.addEventListener('click',()=>{ if(currentIndex<Q.length-1) goTo(currentIndex+1); });
  finishBtn.addEventListener('click',()=>submitExam(false));

  async function submitExam(auto=false,reason=''){
    if(submitted) return; submitted=true;
    try{if(document.fullscreenElement) await document.exitFullscreen();}catch(_){}
    $('#resultTitle').textContent=auto?'Exam auto-submitted':'Exam submitted';
    $('#resultReason').textContent=reason?`Reason: ${reason}`:'';
    examForm.submit();
  }

  // Security
  document.addEventListener('fullscreenchange',()=>{ if(started && !submitted && !document.fullscreenElement){ submitExam(true,'Exited fullscreen'); }});
  document.addEventListener('visibilitychange',()=>{ if(started && !submitted && document.visibilityState!=='visible'){ submitExam(true,'Tab switched/minimized'); }});
  window.addEventListener('beforeunload',()=>{ if(started && !submitted) submitExam(true,'Page closed/reloaded'); });

  function show(el){el.classList.remove('hidden');} function hide(el){el.classList.add('hidden');}
})();
</script>
@endverbatim
@endsection
