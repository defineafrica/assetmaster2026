<?php

namespace App\Mail;

use App\Helpers\Helper;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class CheckinInventoryMail extends BaseMailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(Inventory $inventory, $checkedOutTo, User $checkedInBy, $note)
    {
        $this->target = $checkedOutTo;
        $this->item = $inventory;
        $this->admin = $checkedInBy;
        $this->note = $note;

        $this->settings = Setting::getSettings();
        $this->expected_checkin = '';

        if ($this->item->expected_checkin) {
            $this->expected_checkin = Helper::getFormattedDateObject($this->item->expected_checkin, 'date',
                false);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $from = new Address(config('mail.from.address'), config('mail.from.name'));

        return new Envelope(
            from: $from,
            subject: trans('mail.Inventory_Checkin_Notification', ['tag' => $this->item->inventory_tag]),
        );
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return Content
     */
    public function content(): Content
    {
        $this->item->load('assetstatus');
        $fields = [];

        // Check if the item has custom fields associated with it
        if (($this->item->model) && ($this->item->model->fieldset)) {
            $fields = $this->item->model->fieldset->fields;
        }

        return new Content(
            markdown: 'mail.markdown.checkin-inventory',
            with:  [
                'item'          => $this->item,
                'status'        => $this->item->assetstatus?->name,
                'admin'         => $this->admin,
                'note'          => $this->note,
                'target'        => $this->target,
                'fields'        => $fields,
                'expected_checkin'  => $this->expected_checkin,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
