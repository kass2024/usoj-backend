@extends('layouts.app')

@section('css')
  @include('layouts.datatable.css-without-bottons')
@endsection

@section('body')
<div class="row">
  <div class="col-md-12">
    <div class="card" id="List">
      <div class="card-header border-bottom-dashed">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Program</label>
            <select id="programSelect" class="form-select">
              <option value="">Select Program</option>
              @foreach($programs as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">School</label>
            <select id="schoolSelect" class="form-select" disabled>
              <option value="">Select School</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Department</label>
            <select id="departmentSelect" class="form-select" disabled>
              <option value="">Select Department</option>
            </select>
          </div>
        </div>

        <div class="row g-4 align-items-center mt-3">
          <div class="col-sm">
            <h5 class="card-title mb-0">
              Courses List <small id="currentScope" class="text-muted ms-2"></small>
            </h5>
          </div>
          <div class="col-sm-auto">
            <button type="button" class="btn btn-primary" id="create-btn"
                    data-bs-toggle="modal" data-bs-target="#showModal" disabled>
              <i class="ri-add-line align-bottom me-1"></i> Add course
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <table class="table align-middle" id="courseTable" style="width:100%">
          <thead class="table-light text-muted">
            <tr>
              <th style="width: 20px;">#</th>
              <th>Code</th>
              <th>Name</th>
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
      coursesByDepartment: (deptId) =>
        `{{ route('courses.courses.byDepartment', ['department' => 'DID']) }}`.replace('DID', deptId),

      courseDestroy: (id) =>
        `{{ route('settings.courses.destroy', ['course' => 'CID']) }}`.replace('CID', id),
      courseUpdate: (id) =>
        `{{ route('settings.courses.update', ['course' => 'CID']) }}`.replace('CID', id),
      courseStore: `{{ route('settings.courses.store') }}`
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
    function setAddEnabled(ok){ $('#create-btn').prop('disabled', !ok); }
    function updateScope(){
      const deptText = $('#departmentSelect option:selected').text() || '';
      $('#currentScope').text(deptText ? `— Department: ${deptText}` : '');
    }
    function loadCourses(deptId){
      dt.clear().draw();
      if (!deptId) return;
      $.getJSON(routes.coursesByDepartment(deptId), function(rows){
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

    // ---------- PERSIST SELECTION (NO AUTO-SELECT) ----------
    // Change sessionStorage to localStorage if you want it to persist longer than the tab/session.
    const store = window.sessionStorage; // or window.localStorage
    const STORAGE_KEY = 'courses.filters';

    function saveFilters() {
      store.setItem(STORAGE_KEY, JSON.stringify({
        programId: $('#programSelect').val() || '',
        schoolId: $('#schoolSelect').val() || '',
        departmentId: $('#departmentSelect').val() || ''
      }));
    }
    function loadFilters() {
      try { return JSON.parse(store.getItem(STORAGE_KEY) || '{}'); }
      catch { return {}; }
    }

    // Persist on any change
    $('#programSelect, #schoolSelect, #departmentSelect').on('change', saveFilters);

    // Keep department on form submit (add/edit)
    $(document).on('submit', '.modal-form', function () {
      // ensure hidden dept id is set for backend
      $('#department_id').val($('#departmentSelect').val() || '');
      saveFilters();
      // Normal form submit will reload; filters will be restored below
    });

    // ---------- CHANGE HANDLERS (USER-DRIVEN ONLY) ----------
    // USER selects Program manually — NO auto-select
    $('#programSelect').on('change', async function(){
      const pid = $(this).val();
      resetSelect($('#schoolSelect'), 'Select School');
      resetSelect($('#departmentSelect'), 'Select Department');
      dt.clear().draw(); setAddEnabled(false); updateScope();
      if (!pid) return;
      await fetchAndFillSchools(pid); // enable only
    });

    // USER selects School manually — NO auto-select of Department
    $('#schoolSelect').on('change', async function(){
      const sid = $(this).val();
      resetSelect($('#departmentSelect'), 'Select Department');
      dt.clear().draw(); setAddEnabled(false); updateScope();
      if (!sid) return;
      await fetchAndFillDepartments(sid); // enable only
    });

    // USER selects Department manually
    $('#departmentSelect').on('change', function(){
      const did = $(this).val();
      setAddEnabled(!!did); updateScope();
      $('#department_id').val(did);
      loadCourses(did);
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

      // 3) if saved dept still exists, enable add button & load courses
      const did = $('#departmentSelect').val();
      if (did) {
        setAddEnabled(true);
        $('#department_id').val(did);
        updateScope();
        loadCourses(did);
      } else {
        setAddEnabled(false);
        updateScope();
      }
    })();

    // ---------- MODALS ----------
    $(document).on('click', '#create-btn', function(){
      $("#exampleModalLabel").text("Add course");
      $("#add-btn").text("Save");
      $('.modal-form').attr('action', routes.courseStore);
      $('.modal-form input[name="_method"]').remove();

      $('#id').val('');
      $('#name').val('');
      $('#code').val('');
      $('#credits').val('');
      $('#description').val('');
      $('#status-field').val('active');

      $('#modalDeptName').text($('#departmentSelect option:selected').text() || '');
      // make sure the current department is sent
      $('#department_id').val($('#departmentSelect').val() || '');
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
      $('#description').val($(this).data('description'));
      $('#status-field').val($(this).data('status'));

      $('#modalDeptName').text($('#departmentSelect option:selected').text() || '');
      $('#department_id').val($('#departmentSelect').val() || '');
    });

    $(document).on('click', '.remove-item-btn', function(){
      $('.delete-form').attr('action', routes.courseDestroy($(this).data('id')));
    });
  </script>
@endsection  