<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6>Group</h6>
            <a href="javascript:void(0)" class="btn btn-primary" onclick="setupFormForAdd()" data-bs-toggle="modal" data-bs-target="#groupModal">Add Group</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="groupTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="groupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="groupForm">
                    <input type="hidden" id="groupId" name="instance_group_id">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="groupName" name="instance_group_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="groupAddress" name="address" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveGroup()">Save</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endsection() ?>

<?= $this->section('script') ?>

<script>
    let fetchRoute = "<?= route_to('api_get_group') ?>";
    let saveRoute = "<?= route_to('api_save_group') ?>";
    let deleteRoute = "<?= route_to('api_delete_group') ?>";

    $(document).ready(function() {
        $('#groupTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: fetchRoute,
                dataSrc: 'data'
            },
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'instance_group_name'
                },
                {
                    data: 'address'
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <a class="btn btn-sm btn-secondary text-white me-2" href="<?= route_to('participant') ?>?instance_group_id=${row.id}">
                                View Participant
                            </a>
                            <button class="btn btn-sm btn-info text-white me-2" 
                                    onclick="setupFormForEdit(${row.id}, '${row.instance_group_name}', '${row.address}')" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#groupModal">
                                Edit
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deleteGroup(${row.id})">
                                Delete
                            </button>
                        `;
                    }
                }
            ]
        });
    });

    function setupFormForAdd() {
        document.getElementById('groupForm').reset();
        document.getElementById('groupId').value = '';
        document.getElementById('modalTitle').textContent = 'Add Group';
    }

    function setupFormForEdit(id, name, address) {
        document.getElementById('groupId').value = id;
        document.getElementById('groupName').value = name;
        document.getElementById('groupAddress').value = address;
        document.getElementById('modalTitle').textContent = 'Edit Group';
    }

    function saveGroup() {
        const formData = new FormData(document.getElementById('groupForm'));

        $.ajax({
            url: saveRoute,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#groupTable').DataTable().ajax.reload();
                bootstrap.Modal.getInstance(document.getElementById('groupModal')).hide();
            },
            error: function() {
                alert('Error saving group');
            }
        });
    }

    function deleteGroup(id) {
        if (confirm('Are you sure?')) {
            $.ajax({
                url: deleteRoute,
                type: 'POST',
                data: {
                    instance_group_id: id,
                },
                success: function() {
                    $('#groupTable').DataTable().ajax.reload();
                }
            });
        }
    }
</script>

<?= $this->endsection() ?>