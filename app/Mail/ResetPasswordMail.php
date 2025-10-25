<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable {
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $userName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $email, string $token, string $userName = '') {
        $frontendUrl = env('FRONTEND_URL', config('app.url', 'http://localhost:8000'));
        $this->resetUrl = "{$frontendUrl}/reset-password/{$token}?email={$email}";
        $this->userName = $userName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        return new Envelope(
            subject: 'Restablecer contraseña - Sistema de Control de Asistencia',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'resetUrl' => $this->resetUrl,
                'userName' => $this->userName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array {
        return [];
    }
}
