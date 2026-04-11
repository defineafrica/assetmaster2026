<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequisitionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PurchaseRequisition $requisition;

    public function __construct(PurchaseRequisition $requisition)
    {
        $this->requisition = $requisition;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('inventory.requisitions.show', $this->requisition);

        return (new MailMessage)
            ->subject("Purchase Requisition Approved: {$this->requisition->pr_number}")
            ->line("Your purchase requisition has been **approved**.")
            ->line("**PR Number:** {$this->requisition->pr_number}")
            ->line("**Department:** {$this->requisition->department->name ?? 'N/A'}")
            ->line("**Estimated Total:** " . number_format($this->requisition->estimated_total ?? 0, 2))
            ->line("**Approved By:** " . ($this->requisition->approver->display_name ?? 'N/A'))
            ->action('View Requisition', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'pr_number' => $this->requisition->pr_number,
            'status' => $this->requisition->status,
            'estimated_total' => $this->requisition->estimated_total,
        ];
    }
}
