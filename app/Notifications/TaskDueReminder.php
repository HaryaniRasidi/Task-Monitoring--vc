<?php
namespace App\Notifications;
use App\Models\Task;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class TaskDueReminder extends Notification {
    public function __construct(public Task $task) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)
            ->subject(__('Task due in 3 days'))
            ->greeting(__('Hello').', '.$notifiable->name)
            ->line(__('Your task is due in 3 days.'))
            ->line(__('Title').': '.$this->task->title)
            ->line(__('Due date').': '.$this->task->due_date->format('d/m/Y'))
            ->line(__('Status').': '.__($this->task->status))
            ->action(__('View task'), route('tasks.show', $this->task));
    }
}
