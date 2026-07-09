{{-- Bulk AI Text Import Modal --}}
<div class="modal fade" id="bulkTextModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <form class="modal-content" method="POST" action="{{ route('courses.bulkTextImport') }}" id="bulk-text-form">
      @csrf
      <input type="hidden" name="department_id" id="bulk_department_id">
      <input type="hidden" name="degree_level_id" id="bulk_degree_level_id">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Add courses with AI</h5>
          <small class="text-muted">(<span id="bulkDeptName"></span> · <span id="bulkLevelName"></span>)</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info small">
          Paste one course per line. Use <code>CODE – Course Name</code> or just the course name.
          <strong>Bachelor's Degree</strong> = 4 years × 2 semesters (8 semesters).
          <strong>Master's Degree</strong> = 2 years × 2 semesters (4 semesters).
          Missing codes are generated automatically. Gemini AI places courses in the correct year and semester.
        </div>

        <div class="mb-3">
          <label class="form-label">Course list</label>
          <textarea class="form-control font-monospace" name="course_text" id="course_text" rows="14"
                    placeholder="ICT1101 – Introduction to Information and Communication Technology&#10;ICT1102 – Computer Applications&#10;Introduction to Accounting"></textarea>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button type="button" class="btn btn-outline-primary btn-sm" id="preview-courses-btn">
            <i class="ri-magic-line"></i> Analyse
          </button>
          <span class="text-muted small align-self-center" id="preview-status"></span>
        </div>

        <div class="table-responsive d-none" id="preview-wrap">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Year</th>
                <th>Sem</th>
                <th>Credits</th>
              </tr>
            </thead>
            <tbody id="preview-body"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="import-courses-btn">
          <i class="ri-save-line align-bottom me-1"></i> Import all courses
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Edit Single Course Modal --}}
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content modal-form" method="POST" action="{{ route('settings.courses.store') }}">
      @csrf
      <input type="hidden" name="id" id="id">
      <input type="hidden" name="department_id" id="department_id">
      <input type="hidden" name="degree_level_id" id="degree_level_id">

      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit course</h5>
        <small class="text-muted ms-2">(<span id="modalDeptName"></span> · <span id="modalLevelName"></span>)</small>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Course Name</label>
          <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Code</label>
          <input type="text" class="form-control" id="code" name="code" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label">Year</label>
            <input type="number" min="1" max="6" class="form-control" id="year_index" name="year_index">
          </div>
          <div class="col-md-4">
            <label class="form-label">Semester</label>
            <select class="form-select" id="semester" name="semester">
              <option value="">—</option>
              <option value="1">1</option>
              <option value="2">2</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Credits</label>
            <input type="number" min="1" max="12" class="form-control" id="credits" name="credits" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" id="status-field" name="status" required>
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="add-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content delete-form" method="POST" action="#">
      @csrf
      @method('DELETE')
      <div class="modal-header">
        <h5 class="modal-title">Delete Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this course?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
