<?php

namespace App\Http\Controllers;

use App\Models\DeviceAssignment;
use Illuminate\Http\Request;

class DeviceAssignmentController extends Controller
{
    public function index()
    {
        $assignments = DeviceAssignment::with(['device', 'campaign', 'campaignTrack'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('assignments.index', compact('assignments'));
    }
}
