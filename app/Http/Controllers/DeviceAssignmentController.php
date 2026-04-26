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
