<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('shop.mail_order_subject', ['number' => $this->order->number], $this->order->locale),
        );
    }

    public function content(): Content
    {
        // Sablon markdown mail bilesenleri (x-mail::message) kullaniyor;
        // "view:" ile verilirse "No hint path defined for [mail]" hatasi olur.
        return new Content(
            markdown: 'mail.order-placed',
            with: ['order' => $this->order->load('items', 'shippingMethod')],
        );
    }
}
