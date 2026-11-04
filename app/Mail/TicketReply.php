<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A reply from the Aadi support team to the person who opened a ticket. */
class TicketReply extends Mailable
{
    public function __construct(public SupportTicket $ticket, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[{$this->ticket->reference}] Re: {$this->ticket->subject}");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.ticket-reply');
    }
}
