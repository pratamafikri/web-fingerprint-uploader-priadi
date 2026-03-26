<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ParticipantController extends BaseController
{
    public function index()
    {
        return view('participant');
    }

    public function view($id)
    {
        return view('participant_detail.php', ['id' => $id]);
    }
}
