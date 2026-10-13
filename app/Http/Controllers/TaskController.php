<?php
namespace App\Http\Controllers;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
class TaskController extends Controller {
    private function validated(Request $request): array {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'priority' => ['required', 'string', Rule::in(Task::PRIORITIES)],
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
        ]);
    }
    public function index(Request $request) {
        $query = Task::query();
        if ($request->user()->role !== 'supervisor') { $query->where('user_id', $request->user()->id); }
        return view('tasks.index', ['tasks' => $query->with(['activities' => fn ($query) => $query->latest('id')])->orderBy('due_date')->orderBy('id')->paginate(10), 'officers' => $request->user()->role === 'supervisor' ? \App\Models\User::where('role', 'officer')->orderBy('name')->get() : collect()]);
    }
    public function store(Request $request) {
        $data = $this->validated($request);
        $owner = $request->user();
        if ($owner->role === 'supervisor') {
            $assignment = $request->validate(['user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'officer')]]);
            $owner = \App\Models\User::findOrFail($assignment['user_id']);
        }
        $task = new Task($data);
        $task->user_id = $owner->id;
        $task->owner = $owner->name;
        $task->description = '';
        $task->save();
        return redirect()->route('tasks.index')->with('success', __('Task added successfully.'));
    }
    public function show(Task $task) { Gate::authorize('view', $task); return view('tasks.show', ['task' => $task, 'activities' => $task->activities()->latest('id')->get(), 'statuses' => Task::STATUSES]); }
    public function update(Request $request, Task $task) {
        Gate::authorize('update', $task);
        $task->fill($this->validated($request));
        if ($task->isDirty('status')) { $task->status_updated_at = now(); }
        $task->save();
        return redirect()->route('tasks.show', $task)->with('success', __('Task updated successfully.'));
    }
    public function dashboard(Request $request) {
        $activeCount = Task::open()->count();
        $summary = [
            'total' => Task::count(),
            'open' => $activeCount,
            'overdue' => Task::overdue()->count(),
            'due_soon' => Task::open()->whereDate('due_date', '>=', Task::today())->whereDate('due_date', '<=', Task::reminderDate())->count(),
            'due_next_7' => Task::open()->whereDate('due_date', '>=', Task::today())->whereDate('due_date', '<=', now(Task::TIMEZONE)->addDays(7)->toDateString())->count(),
            'done' => Task::where('status', 'done')->count(),
            'in_progress' => Task::where('status', 'in progress')->count(),
            'to_do' => Task::where('status', 'to do')->count(),
        ];
        $updated = Task::open()->whereBetween('status_updated_at', [now(Task::TIMEZONE)->startOfWeek()->utc(), now()->utc()])->count();
        $rate = $activeCount ? round($updated / $activeCount * 100) : 0;
        $overdueTasks = Task::overdue()->with('assignedUser')->orderBy('due_date')->orderBy('id')->limit(10)->get();
        $counts = Task::open()->select('user_id')->selectRaw('COUNT(*) AS open_count')->groupBy('user_id')->get();
        $owners = \App\Models\User::where('role', 'officer')->orWhereIn('id', $counts->pluck('user_id')->filter())->get()->keyBy('id');
        $workload = $owners->map(fn ($owner) => ['name' => $owner->name, 'open' => (int) ($counts->firstWhere('user_id', $owner->id)?->open_count ?? 0)])->values();
        $unassigned = $counts->filter(fn ($row) => !$row->user_id || !$owners->has($row->user_id))->sum('open_count');
        if ($unassigned) { $workload->push(['name' => __('Unassigned'), 'open' => (int) $unassigned]); }
        $workload = $workload->sortByDesc('open')->values();
        $chartLabels = $workload->pluck('name')->all();
        $chartCounts = $workload->pluck('open')->all();
        $chartTitle = __('Open tasks');
        $statusLabels = array_map(fn ($status) => __($status), Task::STATUSES);
        $statusCounts = [$summary['to_do'], $summary['in_progress'], $summary['done']];
        $priorityTotals = Task::select('priority')->selectRaw('COUNT(*) AS task_count')->groupBy('priority')->pluck('task_count', 'priority');
        $priorityLabels = array_map(fn ($priority) => __($priority), Task::PRIORITIES);
        $priorityCounts = array_map(fn ($priority) => (int) ($priorityTotals[$priority] ?? 0), Task::PRIORITIES);
        return view('dashboard', compact('summary', 'activeCount', 'updated', 'rate', 'overdueTasks', 'workload', 'chartLabels', 'chartCounts', 'chartTitle', 'statusLabels', 'statusCounts', 'priorityLabels', 'priorityCounts'));
    }
}
