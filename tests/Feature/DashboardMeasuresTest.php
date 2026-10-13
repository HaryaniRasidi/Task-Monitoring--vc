<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DashboardMeasuresTest extends TestCase {
    use RefreshDatabase;
    public function test_date_boundaries_workload_and_done_exclusion(): void {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 00:30', Task::TIMEZONE));
        $officer = User::factory()->create(['name' => 'Demo Officer']);
        $idle = User::factory()->create(['name' => 'Idle Officer']);
        $supervisor = User::factory()->create(); $supervisor->role = 'supervisor'; $supervisor->save();
        $records = [];
        foreach ([['2026-10-12','to do'], ['2026-10-13','in progress'], ['2026-10-16','to do'], ['2026-10-17','to do'], ['2026-10-12','done'], ['2026-10-16','done']] as $i => [$date,$status]) {
            $task = new Task(['title' => 'Task '.$i, 'due_date' => $date, 'priority' => 'medium', 'status' => $status]);
            $task->owner = $officer->name; $task->user_id = $officer->id; $task->description = ''; $task->save(); $records[] = $task;
        }
        $unassigned = new Task(['title' => 'Legacy task', 'due_date' => '2026-10-12', 'priority' => 'low', 'status' => 'to do']);
        $unassigned->owner = 'Legacy'; $unassigned->description = ''; $unassigned->save();
        $this->assertTrue($records[0]->isOverdue()); $this->assertFalse($records[1]->isOverdue());
        $this->assertTrue($records[1]->isDueSoon()); $this->assertTrue($records[2]->isDueSoon());
        $this->assertFalse($records[3]->isDueSoon()); $this->assertFalse($records[4]->isOverdue()); $this->assertFalse($records[5]->isDueSoon());
        $this->assertSame('2026-10-16', Task::reminderDate());
        $this->actingAs($supervisor)->get('/dashboard')->assertOk()
            ->assertViewHas('summary', fn ($s) => $s['overdue'] === 2 && $s['due_soon'] === 2)
            ->assertViewHas('overdueTasks', fn ($tasks) => $tasks->count() === 2)
            ->assertViewHas('workload', fn ($rows) => $rows->first()['open'] === 4 && $rows->firstWhere('name','Unassigned')['open'] === 1 && $rows->firstWhere('name','Idle Officer')['open'] === 0)
            ->assertSee('Workload by owner')->assertSee('Unassigned');
        $this->actingAs($officer)->get('/')->assertOk()->assertSee('Due soon');
        $this->get('/dashboard')->assertForbidden();
        $this->travelBack();
    }
    public function test_aggregate_queries_limit_overdue_rows_and_guard_links(): void {
        $owner = User::factory()->create(['name' => '</script><script>alert(1)</script>']);
        $supervisor = User::factory()->create(); $supervisor->role = 'supervisor'; $supervisor->save();
        for ($i = 0; $i < 12; $i++) {
            $task = new Task(['title' => 'Overdue '.$i, 'due_date' => now(Task::TIMEZONE)->subDays($i + 1)->toDateString(), 'priority' => 'high', 'status' => 'to do']);
            $task->user_id = $owner->id; $task->owner = $owner->name; $task->description = ''; $task->save();
        }
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $response = $this->actingAs($supervisor)->get('/dashboard')->assertOk()
            ->assertViewHas('overdueTasks', fn ($tasks) => $tasks->count() === 10 && $tasks->first()->title === 'Overdue 11' && $tasks->last()->title === 'Overdue 2')
            ->assertViewHas('summary', fn ($s) => $s['overdue'] === 12 && $s['open'] === 12)
            ->assertSee('integrity="sha384-', false)->assertDontSee($owner->name, false);
        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query');
        $this->assertTrue($queries->contains(fn ($sql) => str_contains(strtolower($sql), 'group by')));
        $this->assertFalse($queries->contains(fn ($sql) => preg_match('/select \* from "tasks"(?!.*limit)/i', $sql)));
        \Illuminate\Support\Facades\DB::disableQueryLog();
        $item = Task::firstOrFail();
        $this->actingAs(User::factory()->create());
        $this->view('tasks.dashboard-table', ['items' => collect([$item])])->assertSee($item->title)->assertDontSee(route('tasks.show', $item));
    }
    public function test_empty_dashboard_is_usable_without_chart(): void {
        $supervisor = User::factory()->create(); $supervisor->role = 'supervisor'; $supervisor->save();
        $this->actingAs($supervisor)->get('/dashboard')->assertOk()->assertSee('No tasks yet')->assertDontSee('cdn.jsdelivr.net', false);
    }    public function test_status_pie_chart_renders_when_all_tasks_are_done(): void {
        $user = User::factory()->create(); $user->role = 'supervisor'; $user->save();
        $task = new Task(['title' => 'Demo completed', 'due_date' => Task::today(), 'priority' => 'low', 'status' => 'done']);
        $task->owner = $user->name; $task->user_id = $user->id; $task->description = ''; $task->save();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewHas('statusCounts', [0,0,1])
            ->assertSee('id="status-chart"', false)->assertSee("type: 'pie'", false)->assertSee('cdn.jsdelivr.net', false)->assertDontSee('id="owner-chart"', false);
        $this->withSession(['locale' => 'ms'])->get('/dashboard')->assertViewHas('statusLabels', ['Belum mula','Sedang dilaksanakan','Selesai']);
    }    public function test_canvas_roles_and_navigation_use_dashboard_permission(): void {
        $this->get('/dashboard')->assertRedirect('/login');
        foreach (['officer' => 403, 'supervisor' => 200] as $role => $status) {
            $user = User::factory()->create(); $user->role = $role; $user->save();
            $this->actingAs($user)->get('/dashboard')->assertStatus($status);
            $page = $this->get('/')->assertOk();
            if ($role === 'supervisor') { $page->assertSee('href="'.route('dashboard').'"', false); }
            else { $page->assertDontSee('href="'.route('dashboard').'"', false); }
        }
    }
    public function test_roles_land_on_main_screen_and_permitted_intended_page_is_preserved(): void {
        foreach (['officer' => '/', 'supervisor' => '/dashboard'] as $role => $destination) {
            $user = User::factory()->create(['password' => 'DemoPassword123!']); $user->role = $role; $user->save();
            $this->post('/login', ['email' => $user->email, 'password' => 'DemoPassword123!'])->assertRedirect($destination);
            $this->post('/logout')->assertRedirect('/login');
        }
        $officer = User::factory()->create(['password' => 'DemoPassword123!']);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => $officer->email, 'password' => 'DemoPassword123!'])->assertRedirect('/');
        $this->post('/logout');
        $supervisor = User::factory()->create(['password' => 'DemoPassword123!']); $supervisor->role = 'supervisor'; $supervisor->save();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => $supervisor->email, 'password' => 'DemoPassword123!'])->assertRedirect('/dashboard');
    }
    public function test_small_dataset_has_exact_dashboard_figures(): void {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 12:00', Task::TIMEZONE));
        $supervisor = User::factory()->create(); $supervisor->role = 'supervisor'; $supervisor->save();
        foreach ([[-1,'to do'],[0,'in progress'],[7,'to do'],[8,'in progress'],[-2,'done']] as $i => [$days,$status]) {
            $task = new Task(['title' => 'Demo '.$i, 'due_date' => now(Task::TIMEZONE)->addDays($days)->toDateString(), 'priority' => 'medium', 'status' => $status]);
            $task->user_id = $supervisor->id; $task->owner = $supervisor->name; $task->description = '';
            if ($i < 2) { $task->status_updated_at = now(); }
            $task->save();
        }
        $this->actingAs($supervisor)->get('/dashboard')->assertOk()
            ->assertViewHas('summary', ['total' => 5,'open' => 4,'overdue' => 1,'due_soon' => 1,'due_next_7' => 2,'done' => 1,'in_progress' => 2,'to_do' => 2])
            ->assertViewHas('updated',2)->assertViewHas('rate',50.0)->assertViewHas('statusCounts',[2,2,1])->assertViewHas('priorityCounts',[0,5,0]);
        $this->travelBack();
    }}