<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TempPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tempPassword,
        public readonly string $role,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun Inventory Monitoring — Kata Sandi Sementara',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temp-password',
            with: [
                'tempPassword' => $this->tempPassword,
                'role' => $this->role,
            ],
        );
    }
}
