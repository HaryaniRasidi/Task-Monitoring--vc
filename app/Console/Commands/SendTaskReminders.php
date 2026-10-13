<?php
namespace App\Console\Commands;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Notifications\TaskDueReminder;
use Illuminate\Console\Command;
class SendTaskReminders extends Command {
    protected $signature = 'tasks:send-reminders';
    protected $description = 'Send one reminder per task/deadline, exactly three days before it is due (Malaysia time).';
    public function handle(): int {
        $date = Task::reminderDate();
        $sent = 0; $skipped = 0; $failed = 0;
        Task::with('assignedUser')->whereIn('status', Task::OPEN_STATUSES)->whereDate('due_date', $date)
            ->chunkById(100, function ($tasks) use (&$sent, &$skipped, &$failed) {
                foreach ($tasks as $task) {
                    $task->refresh();
                    $owner = $task->assignedUser;
                    if (!$task->isOpen() || $task->due_date->format('Y-m-d') !== Task::reminderDate()
                        || !$owner || !filter_var($owner->email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
                    // Claim before delivery. A unique key also protects concurrent command runs.
                    $reminder = TaskReminder::firstOrCreate(['task_id' => $task->id, 'due_date' => $task->due_date->format('Y-m-d')], ['status' => 'processing']);
                    if (!$reminder->wasRecentlyCreated) { $skipped++; continue; }
                    try {
                        $owner->notify((new TaskDueReminder($task))->locale('ms'));
                        $reminder->update(['status' => 'sent', 'sent_at' => now()]);
                        $sent++;
                    } catch (\Throwable $exception) {
                        // Retain the claim: never automatically retry an uncertain delivery.
                        $reminder->update(['status' => 'failed']);
                        $this->error('Reminder failed for task '.$task->id.'. Review task_reminders; no automatic retry.');
                        $failed++;
                    }
                }
            });
        $this->info("Reminders processed: $sent; skipped: $skipped; failed: $failed. Mailer: ".config('mail.default'));
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
