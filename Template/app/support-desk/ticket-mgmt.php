<?php
require '../../includes/header.php';
?>
<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Ticket Mgmt</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage ticket categories and statuses from one place.</p>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Ticket Category Add</h6>
      </div>
      <div class="card-body border-bottom border-200">
        <form class="row g-2" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="new-category-name">Category Name</label>
            <input class="form-control" id="new-category-name" name="new_category_name" type="text" placeholder="Enter ticket category" required />
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" type="submit">Add Category</button>
          </div>
        </form>
      </div>
      <div class="card-body">
        <h6 class="mb-3">Ticket Category Lists with Edit</h6>
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Category</th>
                <th class="text-800 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Billing" /></td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Technical Issue" /></td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Service Interruption" /></td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Installation" /></td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Ticket Status Add</h6>
      </div>
      <div class="card-body border-bottom border-200">
        <form class="row g-2" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="new-status-name">Status Name</label>
            <input class="form-control" id="new-status-name" name="new_status_name" type="text" placeholder="Enter ticket status" required />
          </div>
          <div class="col-12">
            <label class="form-label" for="new-status-color">Status Color</label>
            <select class="form-select" id="new-status-color" name="new_status_color">
              <option value="success">Green (Success)</option>
              <option value="primary">Blue (Primary)</option>
              <option value="warning">Yellow (Warning)</option>
              <option value="danger">Red (Danger)</option>
              <option value="info">Teal (Info)</option>
              <option value="secondary">Gray (Secondary)</option>
              <option value="dark">Dark</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" type="submit">Add Status</button>
          </div>
        </form>
      </div>
      <div class="card-body">
        <h6 class="mb-3">Ticket Statuses List with Edit</h6>
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Status</th>
                <th class="text-800">Color</th>
                <th class="text-800 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Opened" /></td>
                <td>
                  <select class="form-select form-select-sm status-color-select" style="width:auto;">
                    <option value="success" selected>Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-success status-badge">Green</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="In Progress" /></td>
                <td>
                  <select class="form-select form-select-sm status-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary" selected>Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-primary status-badge">Blue</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Pending" /></td>
                <td>
                  <select class="form-select form-select-sm status-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning" selected>Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-warning status-badge">Yellow</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Closed" /></td>
                <td>
                  <select class="form-select form-select-sm status-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary" selected>Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-secondary status-badge">Gray</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Ticket Priority Add</h6>
      </div>
      <div class="card-body border-bottom border-200">
        <form class="row g-2" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="new-priority-name">Priority Name</label>
            <input class="form-control" id="new-priority-name" name="new_priority_name" type="text" placeholder="Enter ticket priority" required />
          </div>
          <div class="col-12">
            <label class="form-label" for="new-priority-color">Priority Color</label>
            <select class="form-select" id="new-priority-color" name="new_priority_color">
              <option value="success">Green (Success)</option>
              <option value="primary">Blue (Primary)</option>
              <option value="warning">Yellow (Warning)</option>
              <option value="danger">Red (Danger)</option>
              <option value="info">Teal (Info)</option>
              <option value="secondary">Gray (Secondary)</option>
              <option value="dark">Dark</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" type="submit">Add Priority</button>
          </div>
        </form>
      </div>
      <div class="card-body">
        <h6 class="mb-3">Ticket Priority List with Edit</h6>
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Priority</th>
                <th class="text-800">Color</th>
                <th class="text-800 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Low" /></td>
                <td>
                  <select class="form-select form-select-sm priority-color-select" style="width:auto;">
                    <option value="success" selected>Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-success priority-badge">Green</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Medium" /></td>
                <td>
                  <select class="form-select form-select-sm priority-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning" selected>Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-warning priority-badge">Yellow</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="High" /></td>
                <td>
                  <select class="form-select form-select-sm priority-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger" selected>Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark">Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-danger priority-badge">Red</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
              <tr>
                <td><input class="form-control form-control-sm" type="text" value="Critical" /></td>
                <td>
                  <select class="form-select form-select-sm priority-color-select" style="width:auto;">
                    <option value="success">Green</option>
                    <option value="primary">Blue</option>
                    <option value="warning">Yellow</option>
                    <option value="danger">Red</option>
                    <option value="info">Teal</option>
                    <option value="secondary">Gray</option>
                    <option value="dark" selected>Dark</option>
                  </select>
                  <span class="badge ms-1 badge-subtle-dark priority-badge">Dark</span>
                </td>
                <td class="text-end"><button class="btn btn-falcon-default btn-sm" type="button">Edit</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  const colorLabels = {
    success: 'Green', primary: 'Blue', warning: 'Yellow',
    danger: 'Red', info: 'Teal', secondary: 'Gray', dark: 'Dark'
  };

  document.querySelectorAll('.status-color-select, .priority-color-select').forEach(function (sel) {
    sel.addEventListener('change', function () {
      const badge = this.nextElementSibling;
      const val = this.value;
      badge.className = badge.className.replace(/badge-subtle-\S+/, '');
      badge.classList.add('badge-subtle-' + val);
      badge.textContent = colorLabels[val] || val;
    });
  });
})();
</script>
<?php
require '../../includes/footer.php';
?>
