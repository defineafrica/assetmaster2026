<?php

namespace App\Notifications\Inventory;

use App\Models\Consumable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpiryAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Consumable $consumable;
    public string $expiryDate;
    public int $daysUntilExpiry;

    public function __construct(Consumable $consumable, string $expiryDate, int $daysUntilExpiry)
    {
        $this->consumable = $consumable;
        $this->expiryDate = $expiryDate;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('consumables.show', $this->consumable);

        return (new MailMessage)
            ->subject("Expiry Alert: {$this->consumable->name}")
            ->line("The consumable **{$this->consumable->name}** is expiring soon.")
            ->line("**Expiry Date:** {$this->expiryDate}")
            ->line("**Days Until Expiry:** {$this->daysUntilExpiry}")
            ->line("**Current Quantity:** {$this->consumable->qty}")
            ->action('View Item', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'consumable_id' => $this->consumable->id,
            'consumable_name' => $this->consumable->name,
            'expiry_date' => $this->expiryDate,
            'days_until_expiry' => $this->daysUntilExpiry,
        ];
    }
}
