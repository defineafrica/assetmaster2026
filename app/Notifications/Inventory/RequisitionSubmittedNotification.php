<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequisitionSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PurchaseRequisition $requisition;
    public string $approverName;

    public function __construct(PurchaseRequisition $requisition, string $approverName = '')
    {
        $this->requisition = $requisition;
        $this->approverName = $approverName;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('inventory.requisitions.show', $this->requisition);

        return (new MailMessage)
            ->subject("New Purchase Requisition Submitted: {$this->requisition->pr_number}")
            ->line("A new purchase requisition has been submitted and requires your approval.")
            ->line("**PR Number:** {$this->requisition->pr_number}")
            ->line("**Requested By:** " . ($this->requisition->requestingUser->display_name ?? 'N/A'))
            ->line("**Department:** {$this->requisition->department->name ?? 'N/A'}")
            ->line("**Estimated Total:** " . number_format($this->requisition->estimated_total ?? 0, 2))
            ->action('Review Requisition', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'pr_number' => $this->requisition->pr_number,
            'requested_by' => $this->requisition->requestingUser->display_name ?? 'N/A',
            'estimated_total' => $this->requisition->estimated_total,
        ];
    }
}
