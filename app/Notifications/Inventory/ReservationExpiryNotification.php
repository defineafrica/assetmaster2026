<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\Reservation\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Reservation $reservation;
    public string $itemName;

    public function __construct(Reservation $reservation, string $itemName)
    {
        $this->reservation = $reservation;
        $this->itemName = $itemName;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('inventory.reservations.show', $this->reservation);

        return (new MailMessage)
            ->subject("Reservation Expiring: {$this->reservation->reservation_number}")
            ->line("Your reservation for **{$this->itemName}** is expiring soon.")
            ->line("**Reservation Number:** {$this->reservation->reservation_number}")
            ->line("**Quantity:** {$this->reservation->quantity}")
            ->line("**Expires At:** {$this->reservation->expires_at->format('Y-m-d H:i')}")
            ->action('View Reservation', $url)
            ->line('Please fulfill or cancel this reservation before it expires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'reservation_number' => $this->reservation->reservation_number,
            'item_name' => $this->itemName,
            'quantity' => $this->reservation->quantity,
            'expires_at' => $this->reservation->expires_at->toIso8601String(),
        ];
    }
}
