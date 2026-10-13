<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class OfficerAssignmentTest extends TestCase {
    use RefreshDatabase;
    private function supervisor(): User { $user = User::factory()->create(); $user->role = 'supervisor'; $user->save(); return $user; }
    private function input(): array { return ['title' => 'Demo assignment', 'due_date' => '2026-11-01', 'priority' => 'medium', 'status' => 'to do']; }
    public function test_supervisor_creates_officer_and_assigns_task(): void {
        $supervisor = $this->supervisor();
        $this->actingAs($supervisor)->get('/users')->assertOk()->assertSee('Add officer');
        $this->post('/users', ['name' => 'Demo New Officer', 'email' => 'new@example.test', 'password' => 'SecureDemo12345', 'password_confirmation' => 'SecureDemo12345', 'role' => 'supervisor'])->assertRedirect('/users')->assertSessionHasNoErrors();
        $officer = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('officer', $officer->role); $this->assertTrue(Hash::check('SecureDemo12345', $officer->password));
        $this->get('/')->assertOk()->assertSee('Demo New Officer')->assertSee('name="user_id"', false);
        $this->post('/tasks', $this->input() + ['user_id' => $officer->id])->assertRedirect('/')->assertSessionHasNoErrors();
        $task = Task::firstOrFail(); $this->assertSame($officer->id, $task->user_id); $this->assertSame($officer->name, $task->owner);
        $this->actingAs($officer)->get('/')->assertSee('Demo assignment');
        $this->get('/tasks/'.$task->id)->assertOk()->assertSee('Save changes');
        $this->get('/users')->assertForbidden(); $this->post('/users', [])->assertForbidden();
    }
    public function test_invalid_account_and_assignment_are_rejected(): void {
        $supervisor = $this->supervisor();
        $this->actingAs($supervisor)->post('/users', [])->assertSessionHasErrors(['name', 'email', 'password', 'password_confirmation']);
        $this->post('/users', ['name' => 'Demo', 'email' => $supervisor->email, 'password' => 'short', 'password_confirmation' => 'different'])->assertSessionHasErrors(['email','password']);
        foreach ([null, 99999, $supervisor->id] as $id) { $this->post('/tasks', $this->input() + ['user_id' => $id])->assertSessionHasErrors('user_id'); }
        $this->assertDatabaseCount('tasks', 0);
    }
}