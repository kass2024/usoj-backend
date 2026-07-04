{{-- Create / Edit Modal --}}
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content modal-form" method="POST" action="{{ route('settings.courses.store') }}">
      @csrf
      <input type="hidden" name="id" id="id">
      <input type="hidden" name="department_id" id="department_id">

      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add course</h5>
        <small class="text-muted ms-2">(<span id="modalDeptName"></span>)</small>
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

        <div class="mb-3">
          <label class="form-label">Credits</label>
          <input type="number" min="0" class="form-control" id="credits" name="credits" required>
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
        <button type="submit" class="btn btn-primary" id="add-btn">Save</button>
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
