@extends('layouts.app')

@section('css')
  @include('layouts.datatable.css-without-bottons')
  <style>
    #smart-progress-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.55);
      z-index: 2000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    #smart-progress-overlay.show { display: flex; }
    .smart-progress-card {
      width: min(480px, 100%);
      background: #fff;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      box-shadow: 0 20px 50px rgba(0,0,0,.2);
    }
    .smart-progress-bar {
      height: 10px;
      background: #e9ecef;
      border-radius: 999px;
      overflow: hidden;
      margin-top: .75rem;
    }
    .smart-progress-bar > span {
      display: block;
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #0d6efd, #20c997);
      border-radius: 999px;
      transition: width .35s ease;
    }
    .smart-progress-step {
      font-size: .9rem;
      color: #495057;
      min-height: 1.25rem;
    }
    .smart-progress-meta {
      font-size: .8rem;
      color: #6c757d;
      margin-top: .35rem;
    }
    #delete-all-code {
      letter-spacing: 0.35rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
  </style>
@endsection

@section('body')
<div class="row">
  <div class="col-md-12">
    <div class="card" id="List">
      <div class="card-header border-bottom-dashed">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Program</label>
            <select id="programSelect" class="form-select">
              <option value="">Select Program</option>
              @foreach($programs as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">School</label>
            <select id="schoolSelect" class="form-select" disabled>
              <option value="">Select School</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Department</label>
            <select id="departmentSelect" class="form-select" disabled>
              <option value="">Select Department</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Degree Level</label>
            <select id="levelSelect" class="form-select" disabled>
              <option value="">Select Degree Level</option>
            </select>
          </div>
        </div>

        <div class="row g-4 align-items-center mt-3">
          <div class="col-sm">
            <h5 class="card-title mb-0">
              Courses List <small id="currentScope" class="text-muted ms-2"></small>
            </h5>
            <div class="text-muted mt-1" style="font-size:.85rem;">
              Select Program → School → Department → Degree Level to manage courses.
              <span id="program-structure-hint" class="d-block mt-1 fw-semibold text-primary"></span>
            </div>
          </div>
            <div class="col-sm-auto">
            <div class="d-flex flex-wrap gap-2 justify-content-sm-end">
              <button type="button" class="btn btn-primary" id="create-btn"
                      data-bs-toggle="modal" data-bs-target="#bulkTextModal" disabled>
                <i class="ri-add-line align-bottom me-1"></i> Add courses (AI)
              </button>
              <button type="button" class="btn btn-outline-danger" id="delete-all-btn"
                      data-bs-toggle="modal" data-bs-target="#deleteAllCoursesModal" disabled>
                <i class="ri-delete-bin-line align-bottom me-1"></i> Delete all in scope
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card-body">
        @if (session('message'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('import_errors'))
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Import details</strong>
            <ul class="mb-0 mt-2">
              @foreach (session('import_errors') as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <table class="table align-middle" id="courseTable" style="width:100%">
          <thead class="table-light text-muted">
            <tr>
              <th style="width: 20px;">#</th>
              <th>Code</th>
              <th>Name</th>
              <th>Level</th>
              <th>Year / Sem</th>
              <th>Credits</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      {{-- Modal partial must contain a <form class="modal-form"> with a hidden #department_id --}}
      @include('courses.partials.modals')
    </div>
  </div>
</div>

<div id="smart-progress-overlay" aria-hidden="true">
  <div class="smart-progress-card" role="status" aria-live="polite">
    <div class="d-flex justify-content-between align-items-center">
      <strong id="smart-progress-title">Working…</strong>
      <span class="badge bg-primary" id="smart-progress-percent">0%</span>
    </div>
    <div class="smart-progress-bar"><span id="smart-progress-fill"></span></div>
    <div class="smart-progress-step mt-2" id="smart-progress-step">Starting…</div>
    <div class="smart-progress-meta" id="smart-progress-meta"></div>
  </div>
</div>
@endsection

@section('js')
  @include('layouts.datatable.js-without-bottons')
  <script>
    // ---------- ROUTES ----------
    const routes = {
      schoolsByProgram: (programId) =>
        `{{ route('courses.schools.byProgram', ['program' => 'PID']) }}`.replace('PID', programId),
      departmentsBySchool: (schoolId) =>
        `{{ route('courses.departments.bySchool', ['school' => 'SID']) }}`.replace('SID', schoolId),
      levelsByDepartment: (deptId) =>
        `{{ route('courses.levels.byDepartment', ['department' => 'DID']) }}`.replace('DID', deptId),
      coursesByDepartment: (deptId, levelId=null) => {
        let url = `{{ route('courses.courses.byDepartment', ['department' => 'DID']) }}`.replace('DID', deptId);
        if (levelId) url += `?degree_level=${levelId}`;
        return url;
      },

      courseDestroy: (id) =>
        `{{ route('settings.courses.destroy', ['course' => 'CID']) }}`.replace('CID', id),
      courseUpdate: (id) =>
        `{{ route('settings.courses.update', ['course' => 'CID']) }}`.replace('CID', id),
      courseStore: `{{ route('settings.courses.store') }}`,
      bulkTextParse: `{{ route('courses.bulkTextParse') }}`,
      bulkTextImport: `{{ route('courses.bulkTextImport') }}`,
      bulkDeleteChallenge: `{{ route('courses.bulkDeleteChallenge') }}`,
      bulkDeleteAll: `{{ route('courses.bulkDeleteAll') }}`
    };

    // ---------- DATATABLE ----------
    let dt = $('#courseTable').DataTable({
      responsive: true,
      pageLength: 10,
      ordering: true,
      searching: true,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'code', defaultContent: '' },
        { data: 'name', defaultContent: '' },
        { data: 'level', defaultContent: '' },
        { data: 'year_sem', defaultContent: '—' },
        { data: 'credits', defaultContent: '' },
        { data: 'status', render: (s) => s === 'active'
            ? '<span class="badge bg-success-subtle text-success">Active</span>'
            : '<span class="badge bg-danger-subtle text-danger">Inactive</span>'
        },
        { data: null, orderable: false, render: (c) => {
            const esc = (v='') => String(v).replaceAll('"','&quot;');
            return `
              <ul class="list-inline hstack gap-2 mb-0">
                <li class="list-inline-item" title="Edit">
                  <a href="#showModal" data-bs-toggle="modal" class="text-primary edit-btn"
                     data-id="${c.id}"
                     data-name="${esc(c.name)}"
                     data-code="${esc(c.code||'')}"
                     data-credits="${c.credits ?? ''}"
                     data-year-index="${c.year_index ?? ''}"
                     data-semester="${c.semester ?? ''}"
                     data-status="${c.status}"
                     data-description="${esc(c.description||'')}"
                     data-action="${routes.courseUpdate(c.id)}">
                    <i class="ri-pencil-fill fs-16"></i>
                  </a>
                </li>
                <li class="list-inline-item" title="Remove">
                  <a class="text-danger remove-item-btn" data-bs-toggle="modal"
                     data-id="${c.id}" href="#deleteRecordModal">
                    <i class="ri-delete-bin-5-fill fs-16"></i>
                  </a>
                </li>
              </ul>`;
          }
        }
      ]
    });

    // ---------- HELPERS ----------
    function resetSelect($el, placeholder) {
      $el.prop('disabled', true).empty().append(`<option value="">${placeholder}</option>`);
    }
    function setAddEnabled(ok){
      $('#create-btn').prop('disabled', !ok);
      $('#delete-all-btn').prop('disabled', !ok);
    }
    function updateBulkModalContext(){
      const deptId = $('#departmentSelect').val() || '';
      const levelId = $('#levelSelect').val() || '';
      $('#bulk_department_id').val(deptId);
      $('#bulk_degree_level_id').val(levelId);
      $('#bulkDeptName').text($('#departmentSelect option:selected').text() || '');
      $('#bulkLevelName').text($('#levelSelect option:selected').text() || '');
    }
    let levelMeta = {};

    function updateProgramStructureHint() {
      const levelId = $('#levelSelect').val();
      const meta = levelMeta[levelId];
      if (meta?.structure_label) {
        $('#program-structure-hint').text(meta.structure_label);
      } else {
        $('#program-structure-hint').text('');
      }
    }
    function updateScope(){
      const deptText = $('#departmentSelect option:selected').text() || '';
      const levelText = $('#levelSelect option:selected').text() || '';
      const parts = [];
      if (deptText) parts.push(`Department: ${deptText}`);
      if (levelText) parts.push(`Level: ${levelText}`);
      $('#currentScope').text(parts.length ? `— ${parts.join(' · ')}` : '');
      updateProgramStructureHint();
    }
    function loadCourses(deptId, levelId){
      dt.clear().draw();
      if (!deptId || !levelId) return;
      $.getJSON(routes.coursesByDepartment(deptId, levelId), function(rows){
        dt.rows.add(rows).draw();
      });
    }

    // Small promise-based loaders to avoid duplicate fetch logic
    function fetchAndFillSchools(programId, preselectId = null){
      return new Promise((resolve) => {
        resetSelect($('#schoolSelect'), 'Select School');
        if (!programId) return resolve([]);
        $.getJSON(routes.schoolsByProgram(programId), function(items){
          const $s = $('#schoolSelect');
          items.forEach(i => $s.append(`<option value="${i.id}">${i.name}</option>`));
          $s.prop('disabled', items.length === 0);
          if (preselectId && items.some(i => String(i.id) === String(preselectId))) {
            $s.val(preselectId);
          }
          resolve(items);
        });
      });
    }
    function fetchAndFillDepartments(schoolId, preselectId = null){
      return new Promise((resolve) => {
        resetSelect($('#departmentSelect'), 'Select Department');
        if (!schoolId) return resolve([]);
        $.getJSON(routes.departmentsBySchool(schoolId), function(items){
          const $d = $('#departmentSelect');
          items.forEach(i => $d.append(`<option value="${i.id}">${i.name}</option>`));
          $d.prop('disabled', items.length === 0);
          if (preselectId && items.some(i => String(i.id) === String(preselectId))) {
            $d.val(preselectId);
          }
          resolve(items);
        });
      });
    }

    function fetchAndFillLevels(deptId, preselectId = null){
      return new Promise((resolve) => {
        resetSelect($('#levelSelect'), 'Select Degree Level');
        if (!deptId) return resolve([]);
        $.getJSON(routes.levelsByDepartment(deptId), function(items){
          const $l = $('#levelSelect');
          levelMeta = {};
          items.forEach(i => {
            $l.append(`<option value="${i.id}">${i.name}</option>`);
            levelMeta[i.id] = i;
          });
          $l.prop('disabled', items.length === 0);
          if (preselectId && items.some(i => String(i.id) === String(preselectId))) {
            $l.val(preselectId);
          }
          resolve(items);
        });
      });
    }

    // ---------- PERSIST SELECTION (NO AUTO-SELECT) ----------
    // Change sessionStorage to localStorage if you want it to persist longer than the tab/session.
    const store = window.sessionStorage; // or window.localStorage
    const STORAGE_KEY = 'courses.filters';

    function saveFilters() {
      store.setItem(STORAGE_KEY, JSON.stringify({
        programId: $('#programSelect').val() || '',
        schoolId: $('#schoolSelect').val() || '',
        departmentId: $('#departmentSelect').val() || '',
        levelId: $('#levelSelect').val() || ''
      }));
    }
    function loadFilters() {
      try { return JSON.parse(store.getItem(STORAGE_KEY) || '{}'); }
      catch { return {}; }
    }

    // Persist on any change
    $('#programSelect, #schoolSelect, #departmentSelect, #levelSelect').on('change', saveFilters);

    // Keep department + level on form submit (add/edit)
    $(document).on('submit', '.modal-form', function () {
      $('#department_id').val($('#departmentSelect').val() || '');
      $('#degree_level_id').val($('#levelSelect').val() || '');
      saveFilters();
    });

    // ---------- CHANGE HANDLERS (USER-DRIVEN ONLY) ----------
    // USER selects Program manually — NO auto-select
    $('#programSelect').on('change', async function(){
      const pid = $(this).val();
      resetSelect($('#schoolSelect'), 'Select School');
      resetSelect($('#departmentSelect'), 'Select Department');
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(false); updateScope(); updateBulkModalContext();
      if (!pid) return;
      await fetchAndFillSchools(pid); // enable only
    });

    // USER selects School manually — NO auto-select of Department
    $('#schoolSelect').on('change', async function(){
      const sid = $(this).val();
      resetSelect($('#departmentSelect'), 'Select Department');
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(false); updateScope(); updateBulkModalContext();
      if (!sid) return;
      await fetchAndFillDepartments(sid);
    });

    // USER selects Department manually
    $('#departmentSelect').on('change', async function(){
      const did = $(this).val();
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(false); updateScope(); updateBulkModalContext();
      if (!did) return;
      await fetchAndFillLevels(did);
    });

    $('#levelSelect').on('change', function(){
      const did = $('#departmentSelect').val();
      const lid = $(this).val();
      setAddEnabled(!!did && !!lid);
      updateScope();
      updateBulkModalContext();
      $('#department_id').val(did);
      $('#degree_level_id').val(lid);
      loadCourses(did, lid);
    });

    // ---------- RESTORE CHAIN ON LOAD (re-select exactly what user had chosen) ----------
    (async function restoreChain(){
      const f = loadFilters();
      if (!f || !f.programId) return;

      // 1) set program, fetch schools, then preselect saved school (no auto unless it matches)
      $('#programSelect').val(f.programId);
      await fetchAndFillSchools(f.programId, f.schoolId);

      // 2) if saved school still exists, fetch depts and preselect saved dept
      if (f.schoolId) {
        await fetchAndFillDepartments(f.schoolId, f.departmentId);
      }

      if (f.departmentId) {
        await fetchAndFillLevels(f.departmentId, f.levelId);
      }

      const did = $('#departmentSelect').val();
      const lid = $('#levelSelect').val();
      if (did && lid) {
        setAddEnabled(true);
        $('#department_id').val(did);
        $('#degree_level_id').val(lid);
        updateScope();
        updateBulkModalContext();
        loadCourses(did, lid);
      } else {
        setAddEnabled(false);
        updateScope();
        updateBulkModalContext();
      }
    })();

    // ---------- MODALS ----------
    $(document).on('click', '#create-btn', function(){
      updateBulkModalContext();
      $('#preview-wrap').addClass('d-none');
      $('#preview-body').empty();
      $('#preview-status').text('');
    });

    $(document).on('click', '.edit-btn', function(){
      $("#exampleModalLabel").text("Edit course");
      $("#add-btn").text("Save Changes");
      $('.modal-form').attr('action', $(this).data('action'));
      if (!$('.modal-form input[name="_method"]').length) {
        $('.modal-form').append('<input type="hidden" name="_method" value="PUT">');
      }

      $('#id').val($(this).data('id'));
      $('#name').val($(this).data('name'));
      $('#code').val($(this).data('code'));
      $('#credits').val($(this).data('credits'));
      $('#year_index').val($(this).data('year-index') || '');
      $('#semester').val($(this).data('semester') || '');
      $('#description').val($(this).data('description'));
      $('#status-field').val($(this).data('status'));

      $('#modalDeptName').text($('#departmentSelect option:selected').text() || '');
      $('#modalLevelName').text($('#levelSelect option:selected').text() || '');
      $('#department_id').val($('#departmentSelect').val() || '');
      $('#degree_level_id').val($('#levelSelect').val() || '');
    });

    $(document).on('click', '.remove-item-btn', function(){
      $('.delete-form').attr('action', routes.courseDestroy($(this).data('id')));
    });

    const deleteAllModal = document.getElementById('deleteAllCoursesModal');
    if (deleteAllModal) {
      deleteAllModal.addEventListener('show.bs.modal', async function () {
        const deptId = $('#departmentSelect').val();
        const levelId = $('#levelSelect').val();
        const $code = $('#delete-all-code');
        const $hint = $('#delete-all-code-hint');
        const $input = $('#confirmation_code');
        const $submit = $('#delete-all-submit');

        $('#delete_all_department_id').val(deptId || '');
        $('#delete_all_degree_level_id').val(levelId || '');
        $('#delete-all-scope').text(
          [$('#departmentSelect option:selected').text(), $('#levelSelect option:selected').text()]
            .filter(Boolean).join(' · ') || '—'
        );

        $code.text('------');
        $hint.text('Generating code…');
        $input.val('').prop('disabled', true);
        $submit.prop('disabled', true);

        if (!deptId || !levelId) {
          $hint.text('Select department and degree level first.');
          return;
        }

        try {
          const response = await fetch(routes.bulkDeleteChallenge, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
              department_id: deptId,
              degree_level_id: levelId
            })
          });

          const payload = await response.json();
          if (!response.ok) {
            throw new Error(payload.message || 'Could not generate confirmation code.');
          }

          $code.text(payload.code);
          $('#delete-all-count').text(payload.course_count ?? 0);
          $hint.text(`Code expires in ${payload.expires_in_minutes || 10} minutes.`);
          $input.prop('disabled', false).trigger('focus');
        } catch (error) {
          $hint.text(error.message || 'Failed to generate code.');
        }
      });

      $('#confirmation_code').on('input', function () {
        const typed = String($(this).val() || '').trim().toUpperCase();
        const expected = String($('#delete-all-code').text() || '').trim().toUpperCase();
        const count = parseInt($('#delete-all-count').text() || '0', 10);
        const valid = typed.length === 6 && typed === expected && count > 0;
        $('#delete-all-submit').prop('disabled', !valid);
      });

      deleteAllModal.addEventListener('hidden.bs.modal', function () {
        $('#confirmation_code').val('').prop('disabled', true);
        $('#delete-all-submit').prop('disabled', true);
        $('#delete-all-code').text('------');
        $('#delete-all-code-hint').text('');
      });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const SmartProgress = {
      overlay: document.getElementById('smart-progress-overlay'),
      titleEl: document.getElementById('smart-progress-title'),
      percentEl: document.getElementById('smart-progress-percent'),
      fillEl: document.getElementById('smart-progress-fill'),
      stepEl: document.getElementById('smart-progress-step'),
      metaEl: document.getElementById('smart-progress-meta'),
      timer: null,
      value: 0,
      show(title) {
        this.value = 0;
        this.titleEl.textContent = title;
        this.percentEl.textContent = '0%';
        this.fillEl.style.width = '0%';
        this.stepEl.textContent = 'Starting…';
        this.metaEl.textContent = '';
        this.overlay.classList.add('show');
        this.overlay.setAttribute('aria-hidden', 'false');
      },
      hide() {
        clearInterval(this.timer);
        this.timer = null;
        this.overlay.classList.remove('show');
        this.overlay.setAttribute('aria-hidden', 'true');
      },
      set(percent, step, meta = '') {
        this.value = Math.max(this.value, Math.min(100, percent));
        this.percentEl.textContent = `${Math.round(this.value)}%`;
        this.fillEl.style.width = `${this.value}%`;
        if (step) this.stepEl.textContent = step;
        if (meta) this.metaEl.textContent = meta;
      },
      animateTo(target, step, meta = '', ms = 900) {
        const start = this.value;
        const delta = target - start;
        const started = performance.now();
        clearInterval(this.timer);
        this.timer = setInterval(() => {
          const elapsed = performance.now() - started;
          const ratio = Math.min(1, elapsed / ms);
          const next = start + (delta * ratio);
          this.set(next, step, meta);
          if (ratio >= 1) {
            clearInterval(this.timer);
            this.timer = null;
          }
        }, 40);
      },
      async runStages(title, stages) {
        this.show(title);
        for (const stage of stages) {
          if (stage.delay) await new Promise(r => setTimeout(r, stage.delay));
          if (stage.animate) {
            this.animateTo(stage.percent, stage.step, stage.meta || '', stage.ms || 900);
            await new Promise(r => setTimeout(r, stage.ms || 900));
          } else {
            this.set(stage.percent, stage.step, stage.meta || '');
          }
        }
      }
    };

    $('#preview-courses-btn').on('click', async function(){
      const deptId = $('#departmentSelect').val();
      const levelId = $('#levelSelect').val();
      const courseText = $('#course_text').val().trim();

      if (!deptId || !levelId || !courseText) {
        $('#preview-status').text('Select department, level, and paste courses first.');
        return;
      }

      $('#preview-status').text('');
      $('#preview-courses-btn').prop('disabled', true);
      $('#import-courses-btn').prop('disabled', true);

      const analysePromise = (async () => {
        const res = await fetch(routes.bulkTextParse, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            department_id: deptId,
            degree_level_id: levelId,
            course_text: courseText,
          }),
        });

        let data = {};
        const raw = await res.text();
        try {
          data = raw ? JSON.parse(raw) : {};
        } catch (parseErr) {
          throw new Error('Could not analyse courses. Please try again.');
        }

        if (!res.ok) {
          throw new Error(data.message || 'Analysis failed');
        }

        return data;
      })();

      try {
        await SmartProgress.runStages('Analysing courses', [
          { percent: 12, step: 'Reading pasted course list…', delay: 120 },
          { percent: 35, step: 'Detecting programme structure…', animate: true, ms: 700 },
          { percent: 58, step: 'Scheduling year and semester with AI…', animate: true, ms: 900 },
          { percent: 78, step: 'Estimating credit units…', animate: true, ms: 700 },
        ]);

        const data = await analysePromise;

        SmartProgress.set(100, 'Analysis complete', `${data.count} course(s) ready`);

        const rows = (data.courses || []).map((course, i) => `
          <tr>
            <td>${i + 1}</td>
            <td><code>${course.code}</code></td>
            <td>${course.name}</td>
            <td>Y${course.year_index}</td>
            <td>S${course.semester}</td>
            <td>${course.credits}</td>
          </tr>
        `).join('');

        $('#preview-body').html(rows);
        $('#preview-wrap').removeClass('d-none');
        $('#preview-status').text(`${data.count} course(s) · ${data.structure_label || (data.program_years + '-year program')}`);
      } catch (err) {
        $('#preview-status').text(err.message);
      } finally {
        setTimeout(() => SmartProgress.hide(), 500);
        $('#preview-courses-btn').prop('disabled', false);
        $('#import-courses-btn').prop('disabled', false);
      }
    });

    $('#bulk-text-form').on('submit', async function(e){
      e.preventDefault();

      const deptId = $('#departmentSelect').val();
      const levelId = $('#levelSelect').val();
      const courseText = $('#course_text').val().trim();

      if (!deptId || !levelId || !courseText) {
        $('#preview-status').text('Select department, level, and paste courses first.');
        return;
      }

      $('#bulk_department_id').val(deptId);
      $('#bulk_degree_level_id').val(levelId);
      $('#import-courses-btn').prop('disabled', true);
      $('#preview-courses-btn').prop('disabled', true);

      try {
        await SmartProgress.runStages('Importing all courses', [
          { percent: 10, step: 'Validating course list…', delay: 120 },
          { percent: 28, step: 'Preparing semester schedule…', animate: true, ms: 700 },
          { percent: 48, step: 'Writing courses to database…', animate: true, ms: 900 },
        ]);

        const importPromise = fetch(routes.bulkTextImport, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            department_id: deptId,
            degree_level_id: levelId,
            course_text: courseText,
          }),
        });

        SmartProgress.animateTo(72, 'Saving course records…', '', 1000);
        const res = await importPromise;
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
          throw new Error(data.message || 'Import failed');
        }

        SmartProgress.set(100, 'Import complete', data.message || 'Courses imported');

        const did = $('#departmentSelect').val();
        const lid = $('#levelSelect').val();
        if (typeof loadCourses === 'function' && did && lid) {
          loadCourses(did, lid);
        }

        setTimeout(() => {
          SmartProgress.hide();
          bootstrap.Modal.getInstance(document.getElementById('bulkTextModal'))?.hide();
          $('#preview-wrap').addClass('d-none');
          $('#preview-body').empty();
          $('#preview-status').text('');
          $('#course_text').val('');

          const alert = document.createElement('div');
          alert.className = 'alert alert-success alert-dismissible fade show';
          alert.innerHTML = `${data.message || 'Courses imported successfully.'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
          document.querySelector('#List .card-body')?.prepend(alert);
        }, 700);
      } catch (err) {
        SmartProgress.hide();
        $('#preview-status').text(err.message);
      } finally {
        $('#import-courses-btn').prop('disabled', false);
        $('#preview-courses-btn').prop('disabled', false);
      }
    });
  </script>
@endsection  