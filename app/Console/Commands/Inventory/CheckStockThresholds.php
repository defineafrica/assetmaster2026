<?php

namespace App\Console\Commands\Inventory;

use App\Models\Accessory;
use App\Models\Consumable;
use App\Models\Inventory\StockThreshold\StockThreshold;
use App\Models\User;
use App\Notifications\Inventory\LowStockAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckStockThresholds extends Command
{
    protected $signature = 'snipeit:check-stock-thresholds 
                            {--dry-run : Run without sending notifications}';

    protected $description = 'Check stock levels against configured thresholds and send alerts';

    public function handle(): int
    {
        $this->info('Checking stock thresholds...');
        
        $thresholds = StockThreshold::getAllThresholds();
        $alertsSent = 0;

        foreach ($thresholds as $threshold) {
            $currentQuantity = $this->getCurrentQuantity($threshold->item_type, $threshold->item_id);
            $itemName = $this->getItemName($threshold->item_type, $threshold->item_id);

            if ($currentQuantity !== null && $currentQuantity <= $threshold->min_quantity) {
                $this->warn("Low stock detected: {$itemName} ({$currentQuantity} / min: {$threshold->min_quantity})");

                if (!$this->option('dry-run')) {
                    $this->sendAlert($threshold, $currentQuantity, $itemName);
                    $alertsSent++;
                }
            }
        }

        $this->info("Stock check complete. Alerts sent: {$alertsSent}");
        
        if ($this->option('dry-run')) {
            $this->warn('(Dry run - no notifications sent)');
        }

        Log::info("Stock threshold check completed", [
            'thresholds_checked' => $thresholds->count(),
            'alerts_sent' => $alertsSent,
            'dry_run' => $this->option('dry-run'),
        ]);

        return Command::SUCCESS;
    }

    protected function getCurrentQuantity(string $itemType, int $itemId): ?int
    {
        if ($itemType === 'consumables') {
            $item = Consumable::find($itemId);
            return $item?->qty;
        }

        if ($itemType === 'accessories') {
            $item = Accessory::find($itemId);
            return $item?->qty;
        }

        return null;
    }

    protected function getItemName(string $itemType, int $itemId): string
    {
        $item = null;

        if ($itemType === 'consumables') {
            $item = Consumable::find($itemId);
        } elseif ($itemType === 'accessories') {
            $item = Accessory::find($itemId);
        }

        return $item?->name ?? "Unknown {$itemType} #{$itemId}";
    }

    protected function sendAlert(StockThreshold $threshold, int $currentQuantity, string $itemName): void
    {
        if ($threshold->alert_email) {
            $recipients = $this->getNotificationRecipients($threshold->alert_email);
            
            foreach ($recipients as $recipient) {
                $recipient->notify(new LowStockAlertNotification(
                    $threshold,
                    $currentQuantity,
                    $itemName,
                    $threshold->item_type
                ));
            }

            $this->info("Alert sent to: {$threshold->alert_email}");
        }
    }

    protected function getNotificationRecipients(string $alertEmail): array
    {
        $emails = array_map('trim', explode(',', $alertEmail));
        $recipients = [];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $recipients[] = $user;
            }
        }

        return $recipients;
    }
}
