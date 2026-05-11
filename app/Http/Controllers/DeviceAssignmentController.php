<?php

namespace App\Http\Controllers;

use App\Models\DeviceAssignment;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class DeviceAssignmentController extends Controller
{
    public function index()
    {
        $assignments = DeviceAssignment::with(['device', 'campaign', 'campaignTrack'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function stats()
    {
        $assignments = DeviceAssignment::with(['campaignTrack'])
            ->whereIn('status', ['playing', 'pending', 'paused'])
            ->get()
            ->map(function ($a) {
                $duration = $a->campaignTrack ? $a->campaignTrack->duration_seconds : 180;
                $elapsed = 0;
                
                if ($a->started_at) {
                    $diff = now()->timestamp - $a->started_at->timestamp;
                    $elapsed = $diff > 0 ? $diff : 0; 
                }
                
                $timeRemaining = max(0, $duration - $elapsed);
                
                return [
                    'id' => $a->id,
                    'status' => $a->status,
                    'remaining' => $timeRemaining,
                    'media_title' => $a->media_title,
                    'media_url' => $a->media_url,
                ];
            });

        return response()->json([
            'success' => true,
            'assignments' => $assignments
        ]);
    }

    public function destroySingle(DeviceAssignment $assignment)
    {
        $device = $assignment->device;
        
        // Send stop command if it was active
        if ($assignment->isActive() && $device && $device->fcm_token) {
            try {
                $messaging = $this->getMessaging();
                if ($messaging) {
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'       => 'stop',
                            'action'        => 'stop',
                            'assignment_id' => (string) $assignment->id,
                            'timestamp'     => (string) now()->timestamp,
                            'command_id'    => \Illuminate\Support\Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);
                    $messaging->send($message);
                }
            } catch (\Exception $e) {}
        }

        $assignment->delete();
        
        if ($device && $device->assignments()->active()->count() === 0) {
            $device->update(['status' => 'online']);
        }

        return back()->with('success', 'Assignment deleted successfully.');
    }

    public function clearAll()
    {
        $activeAssignments = DeviceAssignment::with('device')->whereIn('status', ['playing', 'pending', 'paused'])->get();
        $messaging = $this->getMessaging();
        
        if ($messaging) {
            foreach ($activeAssignments as $assignment) {
                $device = $assignment->device;
                if ($device && $device->fcm_token) {
                    try {
                        $message = CloudMessage::withTarget('token', $device->fcm_token)
                            ->withData([
                                'command'       => 'stop',
                                'action'        => 'stop',
                                'assignment_id' => (string) $assignment->id,
                                'timestamp'     => (string) now()->timestamp,
                                'command_id'    => Str::uuid()->toString(),
                            ])
                            ->withAndroidConfig(['priority' => 'high']);
                        $messaging->send($message);
                    } catch (\Exception $e) {
                        // ignore errors on clear
                    }
                }
            }
        }

        DeviceAssignment::truncate();
        \App\Models\Device::where('status', 'streaming')->update(['status' => 'online']);
        
        return back()->with('success', 'All assignments cleared and stop commands sent to devices.');
    }

    public function runWorker()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('campaigns:execute');
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Worker executed successfully.',
                'output'  => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Worker failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getMessaging()
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        if (!file_exists($credentialsPath)) {
            $storagePath = storage_path('app');
            $files = glob($storagePath . '/*.json');
            foreach ($files as $file) {
                if (str_contains($file, 'firebase-adminsdk')) {
                    $credentialsPath = $file;
                    break;
                }
            }
        }

        if (!file_exists($credentialsPath)) return null;

        try {
            return (new Factory)->withServiceAccount($credentialsPath)->createMessaging();
        } catch (\Exception $e) {
            return null;
        }
    }
}
