<?php

require 'vendor/autoload.php';

use Priadi\ApiClient\ApiClient;

// Inisialisasi client dengan kredensial mitra
$client = new ApiClient('mitra-username', 'mitra-password');

// Bila menggunakan OAuth, tambahkan parameter false
// $client = new ApiClient('oauth_client_id', 'oauth_client_secret', false);

try {
    // Mendapatkan semua instance group
    // Jika token expired, client akan otomatis mencoba refresh token
    $instanceGroups = $client->get('instance-group');
    if (isset($instanceGroups['error'])) {
        // Jika refresh token gagal, minta mitra untuk login ulang
        echo "Error: {$instanceGroups['error']}\n";
        echo "Silakan login ulang untuk mendapatkan token baru.\n";
    } else {
        echo "Instance Groups:\n";
        print_r($instanceGroups);
    }

    // Membuat instance group baru
    $newGroup = $client->post('instance-group', ['name' => 'Mitra Group', 'description' => 'Group for Mitra']);
    if (isset($newGroup['error'])) {
        echo "Error: {$newGroup['error']}\n";
        echo "Silakan login ulang untuk mendapatkan token baru.\n";
    } else {
        echo "New Group Created:\n";
        print_r($newGroup);
    }

    // Mengupdate instance group
    $updatedGroup = $client->put('instance-group/1', ['name' => 'Updated Group']);
    if (isset($updatedGroup['error'])) {
        echo "Error: {$updatedGroup['error']}\n";
        echo "Silakan login ulang untuk mendapatkan token baru.\n";
    } else {
        echo "Updated Group:\n";
        print_r($updatedGroup);
    }

    // Menghapus instance group
    $deleteResult = $client->delete('instance-group/1');
    if (isset($deleteResult['error'])) {
        echo "Error: {$deleteResult['error']}\n";
        echo "Silakan login ulang untuk mendapatkan token baru.\n";
    } else {
        echo "Delete Result:\n";
        print_r($deleteResult);
    }
} catch (\Exception $e) {
    echo "Unexpected Error: {$e->getMessage()}\n";
}
