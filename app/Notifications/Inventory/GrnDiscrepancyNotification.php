<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GrnDiscrepancyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public GoodsReceivedNote $grn;
    public string $discrepancyType;
    public string $description;

    public function __construct(GoodsReceivedNote $grn, string $discrepancyType, string $description)
    {
        $this->grn = $grn;
        $this->discrepancyType = $discrepancyType;
        $this->description = $description;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('inventory.grn.show', $this->grn);

        return (new MailMessage)
            ->subject("GRN Discrepancy: {$this->grn->grn_number}")
            ->line("A discrepancy has been detected in GRN **{$this->grn->grn_number}**.")
            ->line("**Type:** {$this->discrepancyType}")
            ->line("**Description:** {$this->description}")
            ->line("**Supplier:** " . ($this->grn->supplier->name ?? 'N/A'))
            ->line("**Received Date:** " . ($this->grn->received_date?->format('Y-m-d') ?? 'N/A'))
            ->action('View GRN', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'grn_id' => $this->grn->id,
            'grn_number' => $this->grn->grn_number,
            'discrepancy_type' => $this->discrepancyType,
            'description' => $this->description,
        ];
    }
}
