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

    public function clearAll()
    {
        DeviceAssignment::truncate();
        \App\Models\Device::where('status', 'streaming')->update(['status' => 'online']);
        
        return back()->with('success', 'All assignments have been cleared.');
    }
}
