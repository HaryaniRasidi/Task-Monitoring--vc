<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TaskAssigned extends Mailable
{
    public function __construct(public Task $task) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('New task assigned'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.task-assigned');
    }
}
