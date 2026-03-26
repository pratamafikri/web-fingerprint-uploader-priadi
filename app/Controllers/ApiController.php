<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Priadi\ApiClient\ApiClient;

class ApiController extends BaseController
{
    protected $apiKey, $apiSecret, $isJwt;
    protected ApiClient $apiClient;

    function __construct()
    {
        $this->apiKey = env('P2F_API_KEY', '');
        $this->apiSecret = env('P2F_API_SECRET', '');
        $this->isJwt = env('P2F_API_JWT', FALSE);
        $this->apiClient = new ApiClient($this->apiKey, $this->apiSecret, $this->isJwt);
    }

    public function getGroups()
    {
        $cacheKey = 'instance_groups';
        $cache = cache();

        if ($cachedGroups = $cache->get($cacheKey)) {
            return $this->response->setJSON($cachedGroups);
        }

        $groups = $this->apiClient->get('instance-group');
        $cache->save($cacheKey, $groups, 3600); // Cache for 1 hour

        return $this->response->setJSON($groups);
    }

    public function saveGroup()
    {
        $instanceGroupId = $this->request->getPost('instance_group_id');
        $instanceGroupName = $this->request->getPost('instance_group_name');
        $address = $this->request->getPost('address');

        $data = [
            'instance_group_name' => $instanceGroupName,
            'address' => $address
        ];

        if ($instanceGroupId) {
            $result = $this->apiClient->put('instance-group/' . $instanceGroupId, $data);
        } else {
            $result = $this->apiClient->post('instance-group', $data);
        }

        if ($result) {
            cache()->delete('instance_groups');
        }

        return $this->response->setJSON($result);
    }

    public function deleteGroup()
    {
        $instanceGroupId = $this->request->getPost('instance_group_id');

        $result = $this->apiClient->delete('instance-group/' . $instanceGroupId);

        if ($result) {
            cache()->delete('instance_groups');
            cache()->delete('participant_' . $instanceGroupId);
        }

        return $this->response->setJSON($result);
    }

    public function getParticipantByGroup()
    {
        $instanceGroupId = $_GET['instance_group_id'];

        $cacheKey = 'participant_' . $instanceGroupId;
        $cache = cache();

        if ($cachedParticipants = $cache->get($cacheKey)) {
            return $this->response->setJSON($cachedParticipants);
        }

        $participants = $this->apiClient->get('person?instance_group_id=' . $instanceGroupId);
        $cache->save($cacheKey, $participants, 3600); // Cache for 1 hour

        return $this->response->setJSON($participants);
    }

    public function getParticipantById($participantId)
    {
        $cacheKey = 'participant_' . $participantId;
        $cache = cache();

        if ($cachedParticipant = $cache->get($cacheKey)) {
            return $this->response->setJSON($cachedParticipant);
        }

        $participant = $this->apiClient->get('person/' . $participantId);
        $cache->save($cacheKey, $participant, 3600);

        return $this->response->setJSON($participant);
    }

    public function saveParticipant()
    {
        $participantId = $this->request->getPost('participant_id');
        $instanceGroupId = $this->request->getPost('instance_group_id');
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $birthdate = $this->request->getPost('birthdate');
        $phone = $this->request->getPost('phone');


        $data = [
            'id' => $participantId,
            'name' => $name,
            'email' => $email,
            'birthdate' => $birthdate,
            'phone' => $phone,
            'instance_group_id' => $instanceGroupId
        ];


        if ($participantId) {
            $result = $this->apiClient->put('person/' . $participantId, $data);
        } else {
            $result = $this->apiClient->post('person', $data);
        }

        if ($result) {
            cache()->delete('participant_' . $instanceGroupId);
        }

        return $this->response->setJSON($result);
    }

    private function mapFingerName($hand, $finger)
    {
        $fingerMap = [
            'thumb' => 'thumb',
            'index' => 'forefinger',
            'middle' => 'middlefinger',
            'ring' => 'thirdfinger',
            'pinky' => 'littlefinger'
        ];

        $handPrefix = $hand === 'left' ? 'left' : 'right';
        $fingerName = $fingerMap[$finger] ?? $finger;
        return 'image_' . $handPrefix . '_' . $fingerName;
    }

    public function saveFingerParticipant()
    {
        $participantId = $this->request->getPost('participant_id');
        $instanceGroupId = $this->request->getPost('instance_group_id');
        $image = $this->request->getFile('image');
        $hand = $this->request->getPost('hand');
        $finger = $this->request->getPost('finger');

        if (!$image || !$hand || !$finger) {
            return $this->response->setJSON([
                'error' => 'Missing required fields: image, hand, or finger'
            ], 400);
        }

        if (!$participantId) {
            return $this->response->setJSON([
                'error' => 'Missing participant_id'
            ], 400);
        }

        try {
            // Validate image file type
            $mimeType = $image->getMimeType();
            if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                return $this->response->setJSON([
                    'error' => 'Invalid image type. Only JPG and PNG are allowed.'
                ], 400);
            }

            if ($image->getSizeByUnit('mb') > 2) {
                return $this->response->setJSON([
                    'error' => 'Image size exceeds 2MB limit'
                ], 400);
            }

            // Convert to base64 with data URI
            $imageContent = file_get_contents($image->getTempName());
            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);

            // Map finger name to database field
            $fieldName = $this->mapFingerName($hand, $finger);

            $data = [
                'id' => $participantId,
                $fieldName => $base64Image,
            ];

            $result = $this->apiClient->put('person/finger/' . $participantId, $data);

            if ($result) {
                cache()->delete('participant_' . $participantId);
                if ($instanceGroupId) {
                    cache()->delete('participant_' . $instanceGroupId);
                }
            }

            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => 'Failed to process image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteParticipant()
    {
        $participantId = $this->request->getPost('participant_id');
        $instanceGroupId = $this->request->getPost('instance_group_id');

        $result = $this->apiClient->delete('person/' . $participantId);

        if ($result) {
            cache()->delete('participant_' . $instanceGroupId);
        }

        return $this->response->setJSON($result);
    }
}
