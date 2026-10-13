<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DemoDatasetTest extends TestCase {
    use RefreshDatabase;
    public function test_seed_covers_measures_without_duplicates_and_refreshes_relative_dates(): void {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 12:00', Task::TIMEZONE));
        config(['demo.password' => 'TestPassword123!']);
        $this->seed(TaskSeeder::class); $ids = Task::orderBy('id')->pluck('id')->all();
        $this->seed(TaskSeeder::class);
        $this->assertSame($ids, Task::orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('task_activities', 90);
        $this->assertDemoActivityDates();
        $this->assertDatabaseCount('tasks', 30); $this->assertDatabaseCount('users', 17);
        $this->assertSame(10, Task::whereDate('due_date', '<', Task::today())->count());
        $this->assertSame(10, Task::whereBetween('due_date', [Task::today(),now(Task::TIMEZONE)->addDays(7)->endOfDay()])->count());
        $this->assertSame(10, Task::whereDate('due_date', '>', now(Task::TIMEZONE)->addDays(7)->toDateString())->count());
        foreach (['low','medium','high'] as $priority) { $this->assertSame(10, Task::where('priority',$priority)->count()); }
        $this->assertSame(12, Task::where('status','to do')->count()); $this->assertSame(12, Task::where('status','in progress')->count());
        $this->assertSame(6, Task::where('status','done')->count());
        $this->assertSame(0, User::where('email','not like','%@example.test')->count());
        $supervisor = User::where('role','supervisor')->firstOrFail();
        $this->actingAs($supervisor)->get('/dashboard')->assertOk()
            ->assertViewHas('summary',fn ($s) => $s['total'] === 30 && $s['overdue'] === 10 && $s['due_soon'] === 5 && $s['due_next_7'] === 8)
            ->assertViewHas('priorityCounts', [10,10,10])->assertSee("type: 'doughnut'", false)->assertSeeInOrder(['id="owner-chart"', 'id="status-chart"', 'id="priority-chart"'], false)
            ->assertViewHas('updated',12)->assertViewHas('activeCount',24)->assertViewHas('rate',50.0)
            ->assertViewHas('workload',fn ($rows) => $rows->first()['open'] === 16 && $rows->where('open',2)->count() === 4 && $rows->where('open',0)->count() === 10);
        $firstDate = Task::first()->due_date->format('Y-m-d');
        $this->travel(8)->days(); $this->seed(TaskSeeder::class);
        $this->assertDatabaseCount('tasks',30);
        $this->assertDatabaseCount('task_activities',90);
        $this->assertDemoActivityDates();
        $this->assertSame(\Illuminate\Support\Carbon::parse($firstDate)->addDays(8)->toDateString(),Task::first()->due_date->format('Y-m-d'));
        $this->travelBack();
    }
    private function assertDemoActivityDates(): void {
        foreach (Task::with('activities')->get() as $task) {
            $this->assertCount(3, $task->activities);
            foreach ($task->activities as $activity) {
                $this->assertLessThanOrEqual($task->due_date->format('Y-m-d'), $activity->activity_date->format('Y-m-d'));
                $this->assertLessThanOrEqual(Task::today(), $activity->activity_date->format('Y-m-d'));
                if ($task->status === 'done') { $this->assertSame('done', $activity->status); }
                if ($task->status === 'to do') { $this->assertSame('to do', $activity->status); }
            }
        }
    }}