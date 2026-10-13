<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class MvpTest extends TestCase {
    use RefreshDatabase;
    private function user(string $role = 'officer'): User { $user = User::factory()->create(); $user->role = $role; $user->save(); return $user; }
    private function input(): array { return ['title' => '<b>Demo task</b>', 'due_date' => '2026-10-10', 'priority' => 'high', 'status' => 'to do']; }
    private function task(User $user): Task { $task = new Task($this->input()); $task->user_id = $user->id; $task->owner = $user->name; $task->description = ''; $task->save(); return $task; }
    public function test_login_logout_and_guest_protection(): void {
        $user = User::factory()->create(['email' => 'demo@example.test', 'password' => 'TestPassword123!']);
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('name="_token"', false);
        $this->post('/login', ['email' => 'demo@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'demo@example.test', 'password' => 'TestPassword123!'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_owner_is_derived_and_only_owner_can_update(): void {
        Mail::fake(); $user = $this->user(); $other = $this->user();
        $this->actingAs($user)->post('/tasks', $this->input() + ['user_id' => $other->id, 'owner' => 'Spoof', 'owner_email' => 'fake@example.test'])->assertRedirect('/')->assertSessionHasNoErrors();
        $task = Task::firstOrFail(); $this->assertSame($user->id, $task->user_id); $this->assertSame($user->name, $task->owner); $this->assertNull($task->owner_email);
        $this->get('/')->assertOk()->assertSee('<b>Demo task</b>')->assertDontSee('<b>Demo task</b>', false)->assertSee('Subactivities');
        $this->get('/tasks/'.$task->id)->assertOk();
        $this->actingAs($other)->get('/')->assertDontSee('&lt;b&gt;Demo task', false);
        $this->get('/tasks/'.$task->id)->assertForbidden();
        $this->patch('/tasks/'.$task->id, $this->input())->assertForbidden();
        $supervisor = $this->user('supervisor');
        $this->actingAs($supervisor)->get('/tasks/'.$task->id)->assertOk()->assertDontSee('name="title"', false);
        $this->patch('/tasks/'.$task->id, $this->input())->assertForbidden();
        Mail::assertNothingSent();
    }
    public function test_validation_and_retained_input(): void {
        $user = $this->user();
        $this->actingAs($user)->from('/')->post('/tasks', [])->assertSessionHasErrors(['title','due_date','priority','status']);
        $this->post('/tasks', ['title' => str_repeat('x', 201), 'due_date' => '2026-02-30', 'priority' => 'invalid', 'status' => 'invalid'])->assertSessionHasErrors(['title','due_date','priority','status']);
        $this->assertDatabaseCount('tasks', 0);
        $task = $this->task($user);
        $url = '/tasks/'.$task->id;
        $this->from($url)->patch($url, ['title' => 'Retained title', 'due_date' => '', 'priority' => 'high', 'status' => 'to do'])->assertRedirect($url)->assertSessionHasErrors('due_date');
        $this->get($url)->assertSee('value="Retained title"', false);
        $this->assertNull($task->fresh()->status_updated_at);
    }
    public function test_weekly_measure_counts_only_real_status_changes_and_is_supervisor_only(): void {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-12 12:00', 'Asia/Kuala_Lumpur'));
        $officer = $this->user(); $task = $this->task($officer);
        $this->actingAs($officer)->get('/dashboard')->assertForbidden();
        $input = $this->input(); $input['title'] = 'Title change only';
        $this->patch('/tasks/'.$task->id, $input)->assertRedirect(); $this->assertNull($task->fresh()->status_updated_at);
        $input['status'] = 'in progress';
        $this->patch('/tasks/'.$task->id, $input)->assertRedirect(); $this->assertNotNull($task->fresh()->status_updated_at);
        $this->actingAs($this->user('supervisor'))->get('/dashboard')->assertOk()->assertViewHas('rate', 100.0)->assertViewHas('updated', 1)->assertViewHas('activeCount', 1)->assertViewHas('summary', fn ($s) => $s['overdue'] === 1 && $s['in_progress'] === 1);
        $this->travel(7)->days(); $this->get('/dashboard')->assertViewHas('updated', 0);
        $this->travelBack();
    }
    public function test_empty_dashboard_and_seed_is_fake_and_idempotent(): void {
        $this->actingAs($this->user('supervisor'))->get('/dashboard')->assertOk()->assertViewHas('activeCount', 0);
        config(['demo.password' => 'TestPassword123!']); $this->seed(TaskSeeder::class); $this->seed(TaskSeeder::class);
        $this->assertDatabaseCount('tasks', 30);
        $this->assertSame(15, User::where('email', 'like', 'officer%@example.test')->count());
        $this->assertSame(2, User::where('email', 'like', 'supervisor%@example.test')->count());
        $this->assertTrue(Task::where('due_date', '<', now('Asia/Kuala_Lumpur')->toDateString())->where('status', '!=', 'done')->exists());
    }
    public function test_out_of_scope_routes_are_gone_and_language_still_works(): void {
        $this->actingAs($this->user())->get('/tasks/1/activities')->assertNotFound();
        $this->post('/language', ['locale' => 'ms'])->assertSessionHas('locale','ms');
        $this->get('/')->assertOk()->assertSee('Pemantauan Tugasan');
        $this->post('/language', ['locale' => 'invalid'])->assertSessionHasErrors('locale');
    }
}