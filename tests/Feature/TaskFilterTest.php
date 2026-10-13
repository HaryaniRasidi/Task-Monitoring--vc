<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Just after midnight in Malaysia: the UTC date is still yesterday.
        $this->travelTo(Carbon::parse('2026-10-13 00:30:00', Task::TIMEZONE));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function user(string $name = 'Demo Amina', string $role = 'officer'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function task(User $owner, string $title, string $status = 'Not started', string $priority = 'Medium', int $dueDays = 0): Task
    {
        // The query-string vocabulary follows the spec; fixtures use the existing SQLite values.
        $statuses = ['Not started' => 'to do', 'In progress' => 'in progress', 'Complete' => 'done'];
        $task = new Task([
            'title' => $title,
            'status' => $statuses[$status],
            'priority' => strtolower($priority),
            'due_date' => now(Task::TIMEZONE)->addDays($dueDays)->toDateString(),
        ]);
        $task->user_id = $owner->id;
        $task->owner = $owner->name;
        $task->description = '';
        $task->save();

        return $task;
    }

    private function listing(array $filters = []): TestResponse
    {
        return $this->get(route('tasks.index', $filters));
    }

    private function assertTasks(TestResponse $response, array $expected): void
    {
        $ids = array_map(fn (Task $task) => $task->id, $expected);
        sort($ids);
        $response->assertOk()->assertViewHas('tasks', function ($tasks) use ($ids) {
            $actual = $tasks->pluck('id')->all();
            sort($actual);

            return $actual === $ids && $tasks->total() === count($ids);
        });
    }

    public function test_status_filter_returns_only_the_two_complete_tasks(): void
    {
        $amina = $this->user();
        foreach (range(1, 3) as $number) {
            $this->task($amina, 'Not started '.$number);
        }
        $first = $this->task($amina, 'Complete one', 'Complete');
        $second = $this->task($amina, 'Complete two', 'Complete');
        $this->actingAs($amina);

        $response = $this->listing(['status' => 'Complete']);
        $this->assertTasks($response, [$first, $second]);
        $response->assertSee('(filtered)')->assertDontSee('Not started 1');
    }

    public function test_priority_filter_returns_only_matching_tasks(): void
    {
        $amina = $this->user();
        $high = $this->task($amina, 'High priority task', 'Not started', 'High');
        $this->task($amina, 'Low priority task', 'Not started', 'Low');
        $this->task($amina, 'Medium priority task');
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['priority' => 'High']), [$high]);
    }

    public function test_all_four_filters_are_combined_with_and(): void
    {
        $amina = $this->user();
        $match = $this->task($amina, 'Budget target', 'In progress', 'High', -1);
        $this->task($amina, 'Budget wrong status', 'Not started', 'High', -1);
        $this->task($amina, 'Budget wrong priority', 'In progress', 'Low', -1);
        $this->task($amina, 'Budget due today', 'In progress', 'High', 0);
        $this->task($amina, 'Different title', 'In progress', 'High', -1);
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['status' => 'In progress', 'priority' => 'High', 'overdue' => '1', 'q' => 'Budget']), [$match]);
    }

    public function test_overdue_excludes_complete_tasks_due_yesterday(): void
    {
        $amina = $this->user();
        $open = $this->task($amina, 'Unfinished yesterday', 'Not started', 'Medium', -1);
        $this->task($amina, 'Completed yesterday', 'Complete', 'Medium', -1);
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['overdue' => '1']), [$open]);
        $this->assertTasks($this->listing(['status' => 'Complete', 'overdue' => '1']), []);
    }

    public function test_task_due_today_is_not_overdue(): void
    {
        $amina = $this->user();
        $yesterday = $this->task($amina, 'Yesterday', 'In progress', 'Medium', -1);
        $this->task($amina, 'Today', 'In progress');
        $this->task($amina, 'Tomorrow', 'Not started', 'Medium', 1);
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['overdue' => '1']), [$yesterday]);
    }

    public function test_overdue_filter_agrees_with_model_for_each_status_and_date_boundary(): void
    {
        $amina = $this->user();
        $tasks = [];
        foreach (['Not started', 'In progress', 'Complete'] as $status) {
            foreach ([-1, 0, 1] as $days) {
                $tasks[] = $this->task($amina, $status.' deadline '.$days, $status, 'Medium', $days);
            }
        }
        $expected = array_values(array_filter($tasks, fn (Task $task) => $task->isOverdue()));
        $this->assertCount(2, $expected);
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['overdue' => '1']), $expected);
    }

    public function test_title_search_is_a_trimmed_case_insensitive_substring(): void
    {
        $amina = $this->user();
        $match = $this->task($amina, 'Prepare BUDGET report');
        $this->task($amina, 'Prepare meeting minutes');
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['q' => '  budget  ']), [$match]);
    }

    public function test_percent_in_search_is_literal_not_a_wildcard(): void
    {
        $amina = $this->user();
        $literal = $this->task($amina, 'Review 50% allocation');
        $this->task($amina, 'Review 50 percent allocation');
        $this->task($amina, 'Unrelated allocation');
        $this->actingAs($amina);

        $this->assertTasks($this->listing(['q' => '%']), [$literal]);
        $this->assertTasks($this->listing(['q' => '50%']), [$literal]);
    }

    public function test_another_users_tasks_never_appear_under_any_filter_even_for_supervisors(): void
    {
        $daniel = $this->user('Demo Daniel');
        $private = $this->task($daniel, 'Budget private Daniel', 'Not started', 'High', -1);
        $private->activities()->create(['title' => 'Private activity', 'notes' => 'Private notes', 'activity_date' => Task::today(), 'status' => 'to do']);
        foreach (['officer', 'supervisor'] as $role) {
            $amina = $this->user('Demo Amina '.$role, $role);
            $own = $this->task($amina, 'Budget own '.$role, 'Not started', 'High', -1);
            $this->actingAs($amina);
            foreach ([[], ['status' => 'Not started'], ['priority' => 'High'], ['overdue' => '1'], ['q' => 'Budget'], ['status' => 'Not started', 'priority' => 'High', 'overdue' => '1', 'q' => 'Budget'], ['status' => 'unknown'], ['priority' => 'unknown']] as $filters) {
                $response = $this->listing($filters);
                $this->assertTasks($response, [$own]);
                $response->assertDontSee($private->title)->assertDontSee('Private activity')->assertDontSee('Private notes');
            }
        }
    }

    public function test_unknown_status_or_priority_is_ignored_without_discarding_valid_filters(): void
    {
        $amina = $this->user();
        $high = $this->task($amina, 'High pending', 'Not started', 'High');
        $done = $this->task($amina, 'Low completed', 'Complete', 'Low');
        $this->actingAs($amina);

        foreach ([['status' => 'unknown'], ['priority' => 'unknown'], ['status' => 'unknown', 'priority' => 'unknown']] as $filters) {
            $this->assertTasks($this->listing($filters), [$high, $done]);
        }
        $this->assertTasks($this->listing(['status' => 'unknown', 'priority' => 'High']), [$high]);
        $this->assertTasks($this->listing(['status' => 'Complete', 'priority' => 'unknown']), [$done]);
    }

    public function test_array_filter_values_are_ignored_and_do_not_break_the_page(): void
    {
        $amina = $this->user();
        $own = $this->task($amina, 'Own sample task');
        $this->task($this->user('Demo Daniel'), 'Other sample task');
        $this->actingAs($amina);

        foreach (['status', 'priority', 'overdue', 'q'] as $field) {
            $this->assertTasks($this->listing([$field => ['unexpected']]), [$own]);
        }
        $this->assertTasks($this->listing(['status' => ['Complete'], 'priority' => ['High'], 'q' => ['Budget'], 'overdue' => ['1']]), [$own]);
    }

    public function test_filter_form_keeps_all_chosen_values(): void
    {
        $amina = $this->user();
        $this->actingAs($amina);
        $response = $this->listing(['status' => 'In progress', 'priority' => 'High', 'overdue' => '1', 'q' => 'Budget']);
        $response->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $form = '//form[translate(@method,"get","GET")="GET"]';
        $this->assertSame(1, $xpath->query($form.'//select[@name="status"]/option[@value="In progress" and @selected]')->length);
        $this->assertSame(1, $xpath->query($form.'//select[@name="priority"]/option[@value="High" and @selected]')->length);
        $this->assertSame(1, $xpath->query($form.'//input[@name="overdue" and @value="1" and @checked]')->length);
        $this->assertSame(1, $xpath->query($form.'//input[@name="q" and @value="Budget"]')->length);
    }

    public function test_search_input_is_escaped_when_redisplayed(): void
    {
        $amina = $this->user();
        $this->actingAs($amina);
        $query = '"><script>alert("demo")</script>';
        $response = $this->listing(['q' => $query])->assertOk();
        $response->assertSee(e($query), false)->assertDontSee($query, false);
    }

    public function test_no_matches_displays_filtered_count_and_clear_message(): void
    {
        $amina = $this->user();
        $this->task($amina, 'Meeting minutes');
        $this->actingAs($amina);
        $response = $this->listing(['q' => 'Nonexistent title']);

        $this->assertTasks($response, []);
        $response->assertSee('(filtered)')->assertSee('No tasks match these filters.');
    }
}
