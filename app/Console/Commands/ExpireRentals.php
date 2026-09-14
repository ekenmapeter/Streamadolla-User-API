<?php

namespace App\Console\Commands;

use App\Models\DeviceRental;
use App\Services\RentalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireRentals extends Command
{
    protected $signature = 'rentals:expire';

    protected $description = 'Terminate rentals whose device stopped heartbeating or was unlinked.';

    public function handle(RentalService $rentals): int
    {
        $expired = 0;

        $activeRentals = DeviceRental::active()->with('device')->get();

        foreach ($activeRentals as $rental) {
            if ($rentals->isStale($rental)) {
                $rentals->terminateRental($rental);
                $expired++;
                $this->line("  ✗ Expired rental #{$rental->id} (user #{$rental->user_id})");
            }
        }

        $this->info("[{$this->getName()}] Expired {$expired} stale rental(s).");
        Log::info('rentals:expire complete', ['expired' => $expired]);

        return 0;
    }
}