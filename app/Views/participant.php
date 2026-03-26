<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6>Participant</h6>
            <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#participantModal" onclick="resetForm()">Add Participant</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="participantTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Birthdate</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Participant Modal -->
<div class="modal fade" id="participantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="participantForm">
                    <input type="hidden" id="participantId" name="participant_id">
                    <input type="hidden" id="instanceGroupId" name="instance_group_id" value="<?= $_GET['instance_group_id'] ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="birthdate" class="form-label">Birthdate</label>
                        <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveParticipant()">Save</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endsection() ?>

<?= $this->section('script') ?>

<script>
    let instance_group_id = "<?= $_GET['instance_group_id'] ?>";
    let fetchRoute = "<?= route_to('api_get_participant') ?>?instance_group_id=" + instance_group_id;
    let saveRoute = "<?= route_to('api_save_participant') ?>";
    let deleteRoute = "<?= route_to('api_delete_participant') ?>";

    $(document).ready(function() {
        $('#participantTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: fetchRoute,
                dataSrc: ""
            },
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'name'
                },
                {
                    data: 'birthdate'
                },
                {
                    data: 'email'
                },
                {
                    data: 'phone'
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <a href="<?= base_url('participant/') ?>${row.id}" class="btn btn-sm btn-secondary text-white me-2">View</a>
                            <button class="btn btn-sm btn-info text-white me-2" onclick="setupFormForEdit(${row.id})" data-bs-toggle="modal" data-bs-target="#participantModal">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteParticipant(${row.id})">Delete</button>
                        `;
                    }
                }
            ]
        });
    });

    function resetForm() {
        $('#participantForm')[0].reset();
        $('#participantId').val('');
        $('#modalTitle').text('Add Participant');
    }

    function setupFormForEdit(participant_id) {
        $.ajax({
            url: fetchRoute,
            type: 'GET',
            success: function(data) {
                const participant = data.find(p => p.id == participant_id);
                if (participant) {
                    $('#participantId').val(participant.id);
                    $('#name').val(participant.name);
                    $('#birthdate').val(participant.birthdate);
                    $('#email').val(participant.email);
                    $('#phone').val(participant.phone);
                    $('#modalTitle').text('Edit Participant');
                }
            }
        });
    }

    function saveParticipant() {
        const formData = {
            participant_id: $('#participantId').val(),
            instance_group_id: instance_group_id,
            name: $('#name').val(),
            birthdate: $('#birthdate').val(),
            email: $('#email').val(),
            phone: $('#phone').val()
        };

        $.ajax({
            url: saveRoute,
            type: 'POST',
            data: formData,
            success: function() {
                $('#participantModal').modal('hide');
                $('#participantTable').DataTable().ajax.reload();
            }
        });
    }

    function deleteParticipant(participant_id) {
        if (confirm('Are you sure?')) {
            $.ajax({
                url: deleteRoute,
                type: 'POST',
                data: {
                    participant_id: participant_id,
                    instance_group_id: instance_group_id
                },
                success: function() {
                    $('#participantTable').DataTable().ajax.reload();
                }
            });
        }
    }
</script>

<?= $this->endsection() ?>