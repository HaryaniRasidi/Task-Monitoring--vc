<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use App\Notifications\TaskDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
class TaskReminderTest extends TestCase {
    use RefreshDatabase;
    private function task(string $date, string $status = 'to do', bool $assigned = true): Task {
        $task = new Task(['title' => '<b>Fictional task</b>', 'due_date' => $date, 'priority' => 'high', 'status' => $status]);
        $task->description = ''; $task->owner = 'Demo Officer';
        if ($assigned) { $task->user_id = User::factory()->create()->id; }
        $task->save(); return $task;
    }
    public function test_reminder_matches_date_skips_done_and_does_not_duplicate(): void {
        Notification::fake();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-12 08:00', 'Asia/Kuala_Lumpur'));
        $due = $this->task('2026-10-15');
        $this->task('2026-10-15', 'done'); $this->task('2026-10-14'); $this->task('2026-10-16'); $this->task('2026-10-15', 'to do', false);
        $this->artisan('tasks:send-reminders')->assertSuccessful();
        $this->artisan('tasks:send-reminders')->assertSuccessful();
        Notification::assertSentTo($due->assignedUser, TaskDueReminder::class);
        Notification::assertCount(1);
        $this->assertDatabaseCount('task_reminders', 1);
        $this->assertDatabaseHas('task_reminders', ['task_id' => $due->id, 'status' => 'sent']);
        $due->due_date = '2026-10-16'; $due->save();
        $this->travel(1)->days(); $this->artisan('tasks:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($due->assignedUser, TaskDueReminder::class, 2);
        Notification::assertCount(3); $this->assertDatabaseCount('task_reminders', 3);
        $this->travelBack();
    }
    public function test_delivery_failure_is_not_retried(): void {
        $task = $this->task(now('Asia/Kuala_Lumpur')->addDays(3)->toDateString());
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated failure'));
        $this->artisan('tasks:send-reminders')->assertFailed();
        $this->artisan('tasks:send-reminders')->assertSuccessful();
        $this->assertDatabaseHas('task_reminders', ['task_id' => $task->id, 'status' => 'failed']);
    }
}
