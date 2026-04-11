<?php

namespace App\Console\Commands\Inventory;

use App\Services\Inventory\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'snipeit:release-expired-reservations 
                            {--dry-run : Show what would be released without releasing}';

    protected $description = 'Release reservations that have passed their expiry date';

    public function handle(ReservationService $service): int
    {
        $this->info('Checking for expired reservations...');

        $expiredReservations = \App\Models\Inventory\Reservation\Reservation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->with(['reserver', 'item'])
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('No expired reservations found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredReservations->count()} expired reservations:");
        
        $this->table(
            ['Reservation #', 'Item', 'Reserved By', 'Expires At', 'Status'],
            $expiredReservations->map(function ($reservation) {
                return [
                    $reservation->reservation_number,
                    $reservation->item ? $reservation->item->name : "{$reservation->item_type} #{$reservation->item_id}",
                    $reservation->reserver ? $reservation->reserver->fullName() : 'Unknown',
                    $reservation->expires_at->toDateTimeString(),
                    $reservation->status,
                ];
            })->toArray()
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run - no reservations will be released.');
            return Command::SUCCESS;
        }

        $released = $service->releaseExpiredReservations();
        
        $this->info("Released {$released} expired reservations.");

        Log::info('Expired reservations release completed', [
            'found' => $expiredReservations->count(),
            'released' => $released,
        ]);

        return Command::SUCCESS;
    }
}
