<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\StockThreshold\StockThreshold;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public StockThreshold $threshold;
    public int $currentQuantity;
    public string $itemName;
    public string $itemType;

    public function __construct(
        StockThreshold $threshold,
        int $currentQuantity,
        string $itemName,
        string $itemType
    ) {
        $this->threshold = $threshold;
        $this->currentQuantity = $currentQuantity;
        $this->itemName = $itemName;
        $this->itemType = $itemType;
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        
        if ($this->threshold->send_sms && method_exists($notifiable, 'getPhoneNumber')) {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemLabel = $this->itemType === 'consumables' ? 'Consumable' : 'Accessory';
        
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->itemName}")
            ->line("{$itemLabel} '{$this->itemName}' has fallen below the minimum stock threshold.")
            ->line("**Current Quantity:** {$this->currentQuantity}")
            ->line("**Minimum Threshold:** {$this->threshold->min_quantity}")
            ->line("**Reorder Quantity:** {$this->threshold->reorder_quantity}")
            ->action('View Item', $this->getItemUrl())
            ->line('Please take action to replenish stock.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'threshold_id' => $this->threshold->id,
            'item_type' => $this->itemType,
            'item_id' => $this->threshold->item_id,
            'item_name' => $this->itemName,
            'current_quantity' => $this->currentQuantity,
            'min_quantity' => $this->threshold->min_quantity,
            'reorder_quantity' => $this->threshold->reorder_quantity,
        ];
    }

    protected function getItemUrl(): string
    {
        $baseUrl = config('app.url');
        
        if ($this->itemType === 'consumables') {
            return "{$baseUrl}/consumables/{$this->threshold->item_id}";
        }
        
        if ($this->itemType === 'accessories') {
            return "{$baseUrl}/accessories/{$this->threshold->item_id}";
        }

        return "{$baseUrl}/";
    }
}
