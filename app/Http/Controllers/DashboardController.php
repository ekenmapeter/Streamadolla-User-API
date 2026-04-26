<?php
namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\DeviceAssignment;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\AssignmentController;

class DashboardController extends Controller
{
    public function index()
    {
        $this->cleanupStaleDevices();

        // Get all devices with their current assignment
        $devices = Device::with('currentAssignment')
                         ->orderBy('status', 'desc')
                         ->orderBy('last_seen', 'desc')
                         ->get();

        // Calculate counts
        $onlineCount = Device::whereIn('status', ['online', 'streaming'])->count();
        $streamingCount = Device::where('status', 'streaming')->count();
        $offlineCount = Device::where('status', 'offline')->count();
        $totalCount = $devices->count();

        // Recent logs (last 24h)
        $recentLogs = DeviceLog::with('device')
            ->orderBy('created_at', 'desc')
            ->where('created_at', '>=', now()->subHours(24))
            ->limit(100)
            ->get();

        // Error count (last 24h)
        $errorCount = DeviceLog::errors()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Active assignments
        $activeAssignments = DeviceAssignment::with('device')
            ->active()
            ->orderBy('assigned_at', 'desc')
            ->get();

        // Assignment stats
        $totalAssignments = DeviceAssignment::count();
        $activeAssignmentCount = $activeAssignments->count();

        // Campaigns
        $campaigns = Campaign::with('tracks')->withCount('assignments')->get();

        // Pass data to the view
        return view('dashboard', [
            'devices'               => $devices,
            'onlineCount'           => $onlineCount,
            'streamingCount'        => $streamingCount,
            'offlineCount'          => $offlineCount,
            'totalCount'            => $totalCount,
            'recentLogs'            => $recentLogs,
            'errorCount'            => $errorCount,
            'activeAssignments'     => $activeAssignments,
            'activeAssignmentCount' => $activeAssignmentCount,
            'campaigns'             => $campaigns,
        ]);
    }

    public function sendCommand(Request $request)
    {
        // Simple validation
        $request->validate([
            'action' => 'required|in:play,pause,stop,open',
            'platform' => 'nullable|in:spotify,youtube',
            'spotify_uri' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'media_url' => 'nullable|string'
        ]);

        // Use CommandController to send the message
        $commandController = app()->make(CommandController::class);
        $response = $commandController->sendToAll($request);

        if ($response->getStatusCode() === 200) {
            return back()->with('success', 'Command broadcasted to all devices!');
        } else {
            return back()->with('error', 'Failed to send command: ' . (json_decode($response->getContent())->message ?? 'Unknown error'));
        }
    }

    public function assignTask(Request $request)
    {
        $request->validate([
            'device_ids'   => 'required|array|min:1',
            'device_ids.*' => 'exists:devices,id',
            'platform'     => 'required|in:spotify,youtube',
            'media_url'    => 'required|string',
            'media_title'  => 'nullable|string|max:255',
        ]);

        $assignmentController = app()->make(AssignmentController::class);
        $response = $assignmentController->store($request);

        $result = json_decode($response->getContent());

        if ($response->getStatusCode() === 200 && $result->success) {
            $count = count($request->device_ids);
            return back()->with('success', "Task assigned to {$count} device(s) successfully!");
        } else {
            return back()->with('error', 'Failed to assign task: ' . ($result->message ?? 'Unknown error'));
        }
    }

    public function stats()
    {
        $this->cleanupStaleDevices();

        $devices = Device::with('currentAssignment')->get();
        $activeAssignments = DeviceAssignment::with('device')
            ->active()
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'devices' => $devices,
            'activeAssignments' => $activeAssignments,
            'counts' => [
                'online' => Device::whereIn('status', ['online', 'streaming'])->count(),
                'streaming' => Device::where('status', 'streaming')->count(),
                'offline' => Device::where('status', 'offline')->count(),
                'total' => Device::count(),
                'activeTasks' => $activeAssignments->count(),
            ]
        ]);
    }

    private function cleanupStaleDevices()
    {
        // Auto-cleanup: Mark devices offline if no heartbeat/action in the last 15 minutes
        $offlineCutoff = now()->subMinutes(15);
        $staleDevices = Device::where('last_seen', '<', $offlineCutoff)
                              ->where('status', '!=', 'offline')
                              ->get();
        
        if ($staleDevices->count() > 0) {
            $staleDeviceIds = $staleDevices->pluck('id');
            // Mark devices offline
            Device::whereIn('id', $staleDeviceIds)->update(['status' => 'offline']);
            // Stop any active assignments for these devices
            DeviceAssignment::whereIn('device_id', $staleDeviceIds)
                            ->whereIn('status', ['pending', 'playing', 'paused'])
                            ->update(['status' => 'stopped']);
        }
    }

    public function clearLogs()
    {
        \App\Models\DeviceLog::truncate();
        return redirect()->route('dashboard')->with('success', 'All activity logs have been cleared successfully.');
    }
}
