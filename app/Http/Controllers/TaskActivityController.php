<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskActivityController extends Controller
{
    public function index(Task $task): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $task);
        return redirect()->route('tasks.show', $task)->withFragment('subactivities');
    }
    public function store(Request $request, Task $task): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $task);
        $validated = $request->validateWithBag($request->has('inline_task_id') ? 'inlineActivity' : 'default', [
            'inline_task_id' => ['sometimes', 'required', 'integer', Rule::in([$task->id])],
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000'],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'activity_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
        ], [
            'activity_date.required' => __('Please enter an activity date.'),
            'activity_date.date_format' => __('Please enter a valid activity date in YYYY-MM-DD format.'),
            'title.required' => __('Please enter :attribute.', ['attribute' => __('Title')]),
            'string' => __('The :attribute must be text.'),
            'max' => __('The :attribute must be no more than :max characters.'),
            'status.required' => __('Please select a status.'),
            'status.in' => __('Please select to do, in progress or done.'),
        ], ['title' => __('Title'), 'notes' => __('Notes'), 'status' => __('Status')]);

        $inline = isset($validated['inline_task_id']);
        $page = $validated['page'] ?? 1;
        unset($validated['inline_task_id'], $validated['page']);
        $task->activities()->create($validated);
        if ($inline) {
            return redirect()->route('tasks.index', ['page' => $page])->withFragment('task-activities-'.$task->id)->with('success', __('Activity added successfully.'));
        }

        return redirect()->route('tasks.show', $task)->withFragment('subactivities')->with('success', __('Activity added successfully.'));
    }

    public function edit(Task $task, TaskActivity $activity): View
    {
        abort_unless($activity->task_id === $task->id, 404);
        \Illuminate\Support\Facades\Gate::authorize('update', $task);

        return view('tasks.edit-activity', ['task' => $task, 'activity' => $activity, 'statuses' => Task::STATUSES]);
    }
    public function update(Request $request, Task $task, TaskActivity $activity): RedirectResponse
    {
        abort_unless($activity->task_id === $task->id, 404);
        \Illuminate\Support\Facades\Gate::authorize('update', $task);
        $validated = $request->validateWithBag($request->has('inline_task_id') ? 'inlineActivity' : 'default', [
            'inline_task_id' => ['sometimes', 'required', 'integer', Rule::in([$task->id])],
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000'],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'activity_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
        ], [
            'activity_date.required' => __('Please enter an activity date.'),
            'activity_date.date_format' => __('Please enter a valid activity date in YYYY-MM-DD format.'),
            'title.required' => __('Please enter :attribute.', ['attribute' => __('Title')]),
            'string' => __('The :attribute must be text.'),
            'max' => __('The :attribute must be no more than :max characters.'),
            'status.required' => __('Please select a status.'),
            'status.in' => __('Please select to do, in progress or done.'),
        ], ['title' => __('Title'), 'notes' => __('Notes'), 'status' => __('Status')]);

        $activity->update($validated);

        return redirect()->route('tasks.show', $task)->withFragment('subactivities')->with('success', __('Activity updated successfully.'));
    }
}
