<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <!-- Person Identity Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Participant Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="m-0"><strong>Name:</strong> <p id="participant_name"></p> </p>
                    <p class="m-0"><strong>Email:</strong> <p id="participant_email"></p> </p>
                </div>
                <div class="col-md-6">
                    <p class="m-0"><strong>Birthdate:</strong> <p id="participant_birthdate"></p> </p>
                    <p class="m-0"><strong>Phone:</strong> <p id="participant_phone"></p> </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fingerprint Capture Card -->
    <div class="card">
        <div class="card-header">
            <h5>Fingerprint Capture</h5>
        </div>
        <div class="card-body">
            <div class="container text-center">
                <div class="row row-cols-2 row-cols-lg-5 g-2 g-lg-3">
                    <?php
                    $hands = [
                        'left' => ['thumb', 'index', 'middle', 'ring', 'pinky'],
                        'right' => ['thumb', 'index', 'middle', 'ring', 'pinky']
                    ];
                    ?>
                    <?php foreach ($hands as $hand => $fingers): ?>
                        <?php foreach ($fingers as $finger): ?>
                            <div class="col">
                                <div class="position-relative">
                                    <img id="img_<?= $hand ?>_<?= $finger ?>" 
                                         src="https://placehold.co/500x500" 
                                         alt="<?= ucfirst($hand) ?> <?= ucfirst($finger) ?> fingerprint" 
                                         class="img-fluid cursor-pointer fingerprint-img"
                                         style="cursor: pointer; border-radius: 8px;">
                                    <input type="file" 
                                           id="input_<?= $hand ?>_<?= $finger ?>" 
                                           class="d-none fingerprint-input" 
                                           accept="image/*"
                                           data-hand="<?= $hand ?>"
                                           data-finger="<?= $finger ?>">
                                    <div id="badge_<?= $hand ?>_<?= $finger ?>" class="position-absolute top-0 end-0" style="display: none;">
                                        <span class="badge bg-success">✓</span>
                                    </div>
                                </div>
                                <div class="p-3"><?= ucfirst($hand) ?> <?= ucfirst($finger) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endsection() ?>
<?= $this->section('script') ?>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const participantId = <?= json_encode($id) ?>; // Assuming $participantId is passed from the controller

    $(function() {
        // Map between database fields and image element IDs
        const fingerprintFieldMap = {
            'image_left_thumb': 'img_left_thumb',
            'image_left_forefinger': 'img_left_index',
            'image_left_middlefinger': 'img_left_middle',
            'image_left_thirdfinger': 'img_left_ring',
            'image_left_littlefinger': 'img_left_pinky',
            'image_right_thumb': 'img_right_thumb',
            'image_right_forefinger': 'img_right_index',
            'image_right_middlefinger': 'img_right_middle',
            'image_right_thirdfinger': 'img_right_ring',
            'image_right_littlefinger': 'img_right_pinky'
        };

        // Load participant data
        axios.get("<?= route_to('api_get_participant_by_id', $id) ?>")
            .then(response => {
                const participant = response.data
                document.getElementById('participant_name').innerHTML = participant.name || 'N/A'
                document.getElementById('participant_email').innerHTML = participant.email || 'N/A'
                document.getElementById('participant_birthdate').innerHTML = participant.birthdate || 'N/A'
                document.getElementById('participant_phone').innerHTML = participant.phone || 'N/A'

                // Load fingerprint images
                Object.keys(fingerprintFieldMap).forEach(fieldName => {
                    const imageElementId = fingerprintFieldMap[fieldName];
                    const imageValue = participant[fieldName];
                    
                    if (imageValue) {
                        const imgElement = document.getElementById(imageElementId);
                        if (imgElement) {
                            // If it's a base64 data URI, use it directly
                            if (imageValue.startsWith('data:')) {
                                imgElement.src = imageValue;
                            } else {
                                // Otherwise, treat it as a file path and construct the URL
                                imgElement.src = 'https://restapi.priadi.id/' + imageValue;
                            }
                            
                            // Show success badge for already uploaded images
                            const badgeId = imageElementId.replace('img_', 'badge_');
                            const badge = document.getElementById(badgeId);
                            if (badge) {
                                badge.style.display = 'block';
                                badge.innerHTML = '<span class="badge bg-success">✓</span>';
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching participant data:', error);
            });

        // Fingerprint image click handler
        $(document).on('click', '.fingerprint-img', function() {
            const hand = this.id.split('_')[1];
            const finger = this.id.split('_')[2];
            const inputId = 'input_' + hand + '_' + finger;
            document.getElementById(inputId).click();
        });

        // Fingerprint input change handler
        $(document).on('change', '.fingerprint-input', function() {
            const hand = $(this).data('hand');
            const finger = $(this).data('finger');
            const file = this.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const base64Image = event.target.result;
                    const imgId = 'img_' + hand + '_' + finger;
                    
                    // Update the image preview
                    document.getElementById(imgId).src = base64Image;
                    
                    // Show loading state
                    const badgeId = 'badge_' + hand + '_' + finger;
                    const badge = document.getElementById(badgeId);
                    badge.style.display = 'block';
                    badge.innerHTML = '<span class="badge bg-warning">Uploading...</span>';

                    // Upload to API
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('participant_id', participantId);
                    formData.append('instance_group_id', '<?= $group_id ?? '' ?>');
                    formData.append('hand', hand);
                    formData.append('finger', finger);

                    axios.post("<?= route_to('api_update_finger_participant') ?>", formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                    .then(response => {
                        // Show success badge
                        badge.innerHTML = '<span class="badge bg-success">✓</span>';
                        console.log('Fingerprint uploaded successfully:', response.data);
                    })
                    .catch(error => {
                        // Show error badge
                        badge.innerHTML = '<span class="badge bg-danger">✗</span>';
                        console.error('Error uploading fingerprint:', error);
                        alert('Error uploading fingerprint. Please try again.');
                    });
                };
                reader.readAsDataURL(file);
            }
        });
    })
</script>
<?= $this->endsection() ?>