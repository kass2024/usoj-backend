@extends('layouts.app')

@section('css')
  @include('layouts.datatable.css-without-bottons')
  <style>
    .muted-note{font-size:.85rem;color:#6b7280}
  </style>
@endsection

@section('body')
<div class="row">
  <div class="col-md-12">
    <div class="card" id="List">

      {{-- Header + Actions --}}
      <div class="card-header border-bottom-dashed">
        <div class="row g-4 align-items-center">
          <div class="col-sm">
            <h5 class="card-title mb-0">
              Students List <small id="currentScope" class="text-muted ms-2"></small>
            </h5>
            <div class="muted-note mt-1">Select Program → School → Department (→ Degree Level optional) to load students.</div>
          </div>
          <div class="col-sm-auto">
            <button type="button" class="btn btn-primary add-btn"
                    data-action="{{ route('students.store') }}"
                    data-bs-toggle="modal" id="create-btn"
                    data-bs-target="#showModal" disabled>
              <i class="ri-add-line align-bottom me-1"></i> Add Student
            </button>
          </div>
        </div>

        {{-- Cascade selects --}}
        <div class="row g-3 align-items-end mt-3">
          <div class="col-md-3">
            <label class="form-label">Program</label>
            <select id="programSelect" class="form-select">
              <option value="">Select Program</option>
              @isset($programs)
                @foreach($programs as $p)
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              @endisset
            </select>
            <div class="muted-note" id="programHelp"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">School</label>
            <select id="schoolSelect" class="form-select" disabled>
              <option value="">Select School</option>
            </select>
            <div class="muted-note" id="schoolHelp"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Department</label>
            <select id="departmentSelect" class="form-select" disabled>
              <option value="">Select Department</option>
            </select>
            <div class="muted-note" id="deptHelp"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Degree Level (optional)</label>
            <select id="levelSelect" class="form-select" disabled>
              <option value="">Select Degree Level</option>
            </select>
            <div class="muted-note" id="levelHelp"></div>
          </div>
        </div>
      </div>

      {{-- Table --}}
      <div class="card-body">
        <table class="table align-middle" id="studentTable" style="width:100%">
          <thead class="table-light text-muted">
          <tr>
            <th style="width:20px;">#</th>
            <th class="sort" data-sort="reg_no">Reg N<sup>0</sup></th>
            <th class="sort" data-sort="name">Name</th>
            <th class="sort" data-sort="email">Email</th>
            <th class="sort" data-sort="phone">Phone</th>
            <th class="sort" data-sort="level">Level</th>
            <th class="sort" data-sort="status">Status</th>
            <th class="sort" data-sort="action">Action</th>
          </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      {{-- Create/Edit Modal --}}
      <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-light p-3">
              <h5 class="modal-title" id="exampleModalLabel"></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>

            <form class="tablelist-form modal-form" method="POST" autocomplete="off" enctype="multipart/form-data">
              @csrf
              <div class="modal-body">
                <input type="hidden" value="{{ old('id') }}" name="id" id="id" />
                <input type="hidden" value="" name="department_id" id="department_id" />
                <input type="hidden" value="" name="degree_level_id" id="degree_level_id" />

                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="fname" class="form-label">First Name</label>
                    <input type="text" name="fname" value="{{ old('fname') }}" id="fname" class="form-control" placeholder="Enter first name"/>
                    @error('fname') <span class="text-danger">{{ $message }}</span> @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="lname" class="form-label">Last Name</label>
                    <input type="text" name="lname" value="{{ old('lname') }}" id="lname" class="form-control" placeholder="Enter last name"/>
                    @error('lname') <span class="text-danger">{{ $message }}</span> @enderror
                  </div>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" name="email" value="{{ old('email') }}" id="email" class="form-control" placeholder="Enter email"/>
                  @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                  <label for="date_created" class="form-label">Date To Set On Admission Letter</label>
                  <input type="date" name="date_created" value="{{ old('date_created') }}" id="date_created" class="form-control"/>
                  @error('date_created') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" name="phone" value="{{ old('phone') }}" id="phone" class="form-control" placeholder="Enter phone"/>
                  @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                  <label for="status-field" class="form-label">Status</label>
                  <select class="form-select" name="status" id="status-field">
                    <option value="" selected disabled>Select</option>
                    <option {{ old('status') == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ old('status') == 'inactive' ? 'selected' : '' }} value="inactive">Inactive</option>
                  </select>
                  @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                  <label for="profile_img" class="form-label">Student Photo</label>
                  <div class="text-center mb-2">
                    <img id="profile-img-preview"
                         src="{{ asset('images/profile.jpg') }}"
                         alt="Student photo preview"
                         class="rounded border"
                         style="width:110px;height:130px;object-fit:cover;">
                  </div>
                  <input type="file"
                         name="profile_img"
                         id="profile_img"
                         class="form-control"
                         accept="image/jpeg,image/png,image/jpg">
                  <div class="form-text">Optional. Used on transcript and degree certificates.</div>
                  @error('profile_img') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="small text-muted">
                  <span id="modalDeptName"></span>
                  <span id="modalLevelName" class="ms-2"></span>
                </div>
              </div>

              <div class="modal-footer">
                <div class="hstack gap-2 justify-content-end">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary" id="add-btn">Submit</button>
                </div>
              </div>
            </form>

          </div>
        </div>
      </div>

      {{-- Delete Modal --}}
      <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form class="delete-form" method="post">
                @csrf
                @method('DELETE')
                <div class="mt-2 text-center">
                  <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                             colors="primary:#f7b84b,secondary:#f06548"
                             style="width:100px;height:100px"></lord-icon>
                  <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                    <h4>Are you sure ?</h4>
                    <p class="text-muted mx-4 mb-0">Are you sure you want to remove this record ?</p>
                  </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                  <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn w-sm btn-danger" id="delete-record">Yes, Delete It!</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div> {{-- /card --}}
  </div>
</div>
@endsection

@section('js')
  @include('layouts.datatable.js-without-bottons')
  <script>
    // ---------- ROUTES ----------
    const routes = {
      schoolsByProgram: (programId) =>
        `{{ route('students.schools.byProgram', ['program' => 'PID']) }}`.replace('PID', programId),
      departmentsBySchool: (schoolId) =>
        `{{ route('students.departments.bySchool', ['school' => 'SID']) }}`.replace('SID', schoolId),
      levelsByDepartment: (deptId) =>
        `{{ route('students.levels.byDepartment', ['department' => 'DID']) }}`.replace('DID', deptId),
      studentsByDepartment: (deptId, levelId=null) => {
        let url = `{{ route('students.byDepartment', ['department' => 'DID']) }}`.replace('DID', deptId);
        if (levelId) url += `?degree_level=${levelId}`;
        return url;
      },
      studentDestroy: (id) =>
        `{{ route('students.destroy', ['student' => 'SID']) }}`.replace('SID', id),
      studentUpdate: (id) =>
        `{{ route('students.update', ['student' => 'SID']) }}`.replace('SID', id),
      studentStore: `{{ route('students.store') }}`
    };

    // ---------- DATATABLE ----------
    let dt = $('#studentTable').DataTable({
      responsive: true,
      pageLength: 10,
      ordering: true,
      searching: true,
      columns: [
        { data: null, render: (d,t,r,m) => m.row + 1 },
        { data: 'reg_number', defaultContent: '' },
        { data: 'name', defaultContent: '' },
        { data: 'email', defaultContent: '' },
        { data: 'phone', defaultContent: '' },
        { data: 'level', defaultContent: '' },
        { data: 'status', render: (s) => s === 'active'
            ? '<span class="badge bg-success-subtle text-success">Active</span>'
            : '<span class="badge bg-danger-subtle text-danger">Inactive</span>'
        },
        { data: null, orderable: false, render: (c) => {
            const esc = (v='') => String(v).replaceAll('"','&quot;');
            const names = (c.name || '').trim().split(/\s+/);
            const fname = esc(names[0] || '');
            const lname = esc(names.slice(1).join(' ') || '');
            return `
              <ul class="list-inline hstack gap-2 mb-0">
                <li class="list-inline-item" title="Edit">
                  <a href="#showModal" data-bs-toggle="modal" class="text-primary edit-btn"
                     data-id="${c.id}"
                     data-fname="${fname}"
                     data-lname="${lname}"
                     data-status="${c.status || ''}"
                     data-email="${esc(c.email || '')}"
                     data-phone="${esc(c.phone || '')}"
                     data-profile-img="${esc(c.profile_img_url || '{{ asset('images/profile.jpg') }}')}"
                     data-action="${routes.studentUpdate(c.id)}">
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
    const note = (id, msg) => document.getElementById(id).textContent = msg || '';
    function resetSelect($el, placeholder) {
      $el.prop('disabled', true).empty().append(`<option value="">${placeholder}</option>`);
    }
    function setAddEnabled(ok){ $('#create-btn').prop('disabled', !ok); }
    function updateScope(){
      const deptText  = $('#departmentSelect option:selected').text() || '';
      const levelText = $('#levelSelect option:selected').text() || '';
      const parts = [];
      if (deptText) parts.push(`Dept: ${deptText}`);
      if (levelText) parts.push(`Level: ${levelText}`);
      $('#currentScope').text(parts.length ? '— ' + parts.join(' · ') : '');
    }

    // VERBOSE AJAX (shows real URL/status/message if it fails)
    function ajaxJSON(url, onOk, helpId){
      console.log('[ajaxJSON:url]', url);
      note(helpId, 'Loading...');
      $.ajax({
        url, method: 'GET', dataType: 'json', cache: false,
        success: function (data) { note(helpId, ''); onOk(data); },
        error: function (xhr) {
          let msg = `Failed (${xhr.status})`;
          if (xhr.status === 0)   msg += ' — network/blocked.';
          if (xhr.status === 404) msg += ' — route not found or bad parameter.';
          if (xhr.status === 419) msg += ' — login/session expired (redirect).';
          if (xhr.status === 500) msg += ' — server error (see logs).';
          try { const json = JSON.parse(xhr.responseText); if (json?.message) msg += ` ${json.message}`; } catch {}
          note(helpId, msg);
          console.error('[ajaxJSON:error]', { url, status: xhr.status, response: xhr.responseText });
        }
      });
    }

    function loadStudents(deptId, levelId=null){
      dt.clear().draw();
      if (!deptId) return;
      ajaxJSON(routes.studentsByDepartment(deptId, levelId), (rows)=>{
        dt.rows.add(rows).draw();
      }, 'deptHelp');
    }

    function fetchAndFillSchools(programId, pre=null){
      return new Promise((resolve) => {
        resetSelect($('#schoolSelect'), 'Select School');
        if (!programId){ note('programHelp','Please choose a program'); return resolve([]); }
        ajaxJSON(routes.schoolsByProgram(programId), (items)=>{
          const $s = $('#schoolSelect');
          items.forEach(i => $s.append(`<option value="${i.id}">${i.name}</option>`));
          $s.prop('disabled', items.length === 0);
          if (items.length === 0) note('schoolHelp','No schools found for this program');
          if (pre && items.some(i => String(i.id) === String(pre))) $s.val(pre);
          resolve(items);
        }, 'schoolHelp');
      });
    }

    function fetchAndFillDepartments(schoolId, pre=null){
      return new Promise((resolve) => {
        resetSelect($('#departmentSelect'), 'Select Department');
        if (!schoolId){ note('schoolHelp','Please choose a school'); return resolve([]); }
        ajaxJSON(routes.departmentsBySchool(schoolId), (items)=>{
          const $d = $('#departmentSelect');
          items.forEach(i => $d.append(`<option value="${i.id}">${i.name}</option>`));
          $d.prop('disabled', items.length === 0);
          if (items.length === 0) note('deptHelp','No departments found for this school');
          if (pre && items.some(i => String(i.id) === String(pre))) $d.val(pre);
          resolve(items);
        }, 'deptHelp');
      });
    }

    function fetchAndFillLevels(deptId, pre=null){
      return new Promise((resolve) => {
        resetSelect($('#levelSelect'), 'Select Degree Level');
        if (!deptId){ note('deptHelp','Please choose a department'); return resolve([]); }
        ajaxJSON(routes.levelsByDepartment(deptId), (items)=>{
          const $l = $('#levelSelect');
          items.forEach(i => $l.append(`<option value="${i.id}">${i.name}</option>`));
          $l.prop('disabled', items.length === 0);
          if (items.length === 0) note('levelHelp','No degree levels for this department’s program');
          if (pre && items.some(i => String(i.id) === String(pre))) $l.val(pre);
          resolve(items);
        }, 'levelHelp');
      });
    }

    // ---------- PERSIST SELECTION ----------
    const store = window.sessionStorage;
    const STORAGE_KEY = 'students.filters';
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
    $('#programSelect, #schoolSelect, #departmentSelect, #levelSelect').on('change', saveFilters);

    // Hidden fields for modal submit
    $(document).on('submit', '.modal-form', function () {
      $('#department_id').val($('#departmentSelect').val() || '');
      $('#degree_level_id').val($('#levelSelect').val() || '');
      saveFilters();
    });

    // ---------- CHANGE HANDLERS ----------
    $('#programSelect').on('change', async function(){
      const pid = $(this).val();
      resetSelect($('#schoolSelect'), 'Select School');
      resetSelect($('#departmentSelect'), 'Select Department');
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(false); updateScope();
      if (!pid) return;
      await fetchAndFillSchools(pid);
    });

    $('#schoolSelect').on('change', async function(){
      const sid = $(this).val();
      resetSelect($('#departmentSelect'), 'Select Department');
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(false); updateScope();
      if (!sid) return;
      await fetchAndFillDepartments(sid);
    });

    $('#departmentSelect').on('change', async function(){
      const did = $(this).val();
      resetSelect($('#levelSelect'), 'Select Degree Level');
      dt.clear().draw(); setAddEnabled(!!did); updateScope();
      if (!did) return;
      await fetchAndFillLevels(did);
      loadStudents(did, null);          // show all students in dept
      $('#department_id').val(did);
    });

    $('#levelSelect').on('change', function(){
      const did = $('#departmentSelect').val();
      const lid = $(this).val();
      setAddEnabled(!!did);
      updateScope();
      $('#department_id').val(did);
      $('#degree_level_id').val(lid || '');
      loadStudents(did, lid || null);
    });

    // ---------- RESTORE CHAIN ----------
    (async function restoreChain(){
      const f = loadFilters();
      if (!f || !f.programId) return;

      $('#programSelect').val(f.programId);
      await fetchAndFillSchools(f.programId, f.schoolId);

      if (f.schoolId) await fetchAndFillDepartments(f.schoolId, f.departmentId);
      if (f.departmentId) {
        await fetchAndFillLevels(f.departmentId, f.levelId);
        setAddEnabled(true);
        $('#department_id').val(f.departmentId);
        $('#degree_level_id').val(f.levelId || '');
        updateScope();
        loadStudents(f.departmentId, f.levelId || null);
      } else {
        setAddEnabled(false); updateScope();
      }
    })();
  </script>

  <!-- ---------- MODALS (create/edit/delete) using your preferred handlers ---------- -->
  <script>
    $(document).ready(function() {
      const defaultPhoto = "{{ asset('images/profile.jpg') }}";

      function setPhotoPreview(url) {
        $('#profile-img-preview').attr('src', url || defaultPhoto);
      }

      $('#profile_img').on('change', function () {
        const file = this.files && this.files[0];
        if (!file) {
          return;
        }
        setPhotoPreview(URL.createObjectURL(file));
      });

      // CREATE
      $(document).on('click', '#create-btn', function() {
        document.getElementById("exampleModalLabel").innerHTML = "Add Student";
        document.getElementById("add-btn").innerHTML = "Submit";

        var form_action = $(this).data('action') || (typeof routes !== 'undefined' ? routes.studentStore : '');
        $('.tablelist-form').attr('action', form_action);

        // remove stray PUT from previous edit
        $('.tablelist-form input[name="_method"]').remove();

        // reset fields
        $('#id').val('');
        $('#fname').val('');
        $('#lname').val('');
        $('#email').val('');
        $('#phone').val('');
        $('#status-field').val('active');
        $('#profile_img').val('');
        setPhotoPreview(defaultPhoto);

        // modal scope + hidden fields
        $('#modalDeptName').text($('#departmentSelect option:selected').text() || '');
        $('#modalLevelName').text($('#levelSelect option:selected').text() || '');
        $('#department_id').val($('#departmentSelect').val() || '');
        $('#degree_level_id').val($('#levelSelect').val() || '');
      });

      // EDIT
      $(document).on('click', '.edit-btn', function() {
        document.getElementById("exampleModalLabel").innerHTML = "Edit Student";
        document.getElementById("add-btn").innerHTML = "Save Changes";
        var $form = $('.tablelist-form');

        $form.attr('action', $(this).data('action'));
        if (!$form.find('input[name="_method"]').length) {
          $form.append('<input type="hidden" name="_method" value="PUT">');
        }

        $('#id').val($(this).data('id'));
        $('#fname').val($(this).data('fname'));
        $('#lname').val($(this).data('lname'));
        $('#email').val($(this).data('email'));
        $('#phone').val($(this).data('phone'));
        $('#status-field').val($(this).data('status'));
        $('#profile_img').val('');
        setPhotoPreview($(this).data('profile-img'));

        $('#modalDeptName').text($('#departmentSelect option:selected').text() || '');
        $('#modalLevelName').text($('#levelSelect option:selected').text() || '');
        $('#department_id').val($('#departmentSelect').val() || '');
        $('#degree_level_id').val($('#levelSelect').val() || '');
      });

      // DELETE
      $(document).on('click', '.remove-item-btn', function() {
        var route = "{{ route('students.destroy', ['student' => ':id']) }}";
        route = route.replace(':id', $(this).data('id'));
        $('.delete-form').attr('action', route);
      });

      // keep scope hidden fields on submit
      $(document).on('submit', '.modal-form', function () {
        $('#department_id').val($('#departmentSelect').val() || '');
        $('#degree_level_id').val($('#levelSelect').val() || '');
      });
    });
  </script>

  <!-- ---------- VALIDATION RE-OPEN ---------- -->
  @if ($errors->any())
    @if (old('id'))
      <script>
        document.getElementById("add-btn").innerHTML = "Save Changes";
        document.getElementById("exampleModalLabel").innerHTML = "Edit Student";

        var myModal = new bootstrap.Modal(document.getElementById('showModal'), { keyboard: false });
        myModal.show();

        var id = "{{ old('id') }}";
        var route = "{{ route('students.update', ['student' => ':id']) }}";
        route = route.replace(':id', id);

        var $form = $('.tablelist-form');
        $form.attr('action', route);
        if (!$form.find('input[name="_method"]').length) {
          $form.append('<input type="hidden" name="_method" value="PUT">');
        }

        // ensure hidden scope fields
        $('#department_id').val($('#departmentSelect').val() || '');
        $('#degree_level_id').val($('#levelSelect').val() || '');
      </script>
    @else
      <script>
        document.getElementById("exampleModalLabel").innerHTML = "Add Student";
        document.getElementById("add-btn").innerHTML = "Submit";

        var myModal = new bootstrap.Modal(document.getElementById('showModal'), { keyboard: false });
        myModal.show();

        // clean stray PUT
        $('.tablelist-form input[name="_method"]').remove();

        // ensure hidden scope fields
        $('#department_id').val($('#departmentSelect').val() || '');
        $('#degree_level_id').val($('#levelSelect').val() || '');
      </script>
    @endif
  @endif
@endsection
