@php($inlineFailed = (string) old('inline_task_id') === (string) $parentTask->id && $errors->inlineActivity->any())
<details id="task-activities-{{ $parentTask->id }}" @if($inlineFailed) open @endif>
<summary>{{ __('Subactivities') }} ({{ $parentTask->activities->count() }}) @can('update', $parentTask) — {{ __('Add subactivity') }} @endcan</summary>
<div class="inline-activities">
<table><thead><tr>@foreach(['Subactivity','Notes','Activity date','Status','Update'] as $label)<th scope="col">{{ __($label) }}</th>@endforeach</tr></thead>
<tbody>@forelse($parentTask->activities as $activity)
<tr><td>{{ $activity->title }}</td><td>{{ $activity->notes }}</td><td>{{ $activity->activity_date?->format('d/m/Y') ?? __('Not recorded') }}</td><td><span class="badge status-{{ str_replace(' ', '-', $activity->status) }}">{{ __($activity->status) }}</span></td><td>@can('update', $parentTask)<a href="{{ route('tasks.activities.edit', [$parentTask,$activity]) }}">{{ __('Update') }}</a>@endcan</td></tr>
@empty<tr><td colspan="5">{{ __('No subactivities yet.') }}</td></tr>@endforelse</tbody></table>
@can('update', $parentTask)
<h3>{{ __('Add subactivity') }}</h3>
<form method="POST" action="{{ route('tasks.activities.store', $parentTask) }}">
@csrf
<input type="hidden" name="inline_task_id" value="{{ $parentTask->id }}">
<input type="hidden" name="page" value="{{ $tasks->currentPage() }}">
<div class="grid">
@foreach(['title' => 'Subactivity', 'activity_date' => 'Activity date', 'status' => 'Status', 'notes' => 'Notes'] as $field => $label)
<div><label for="inline-{{ $parentTask->id }}-{{ $field }}">{{ __($label) }}</label>
@if($field === 'status')
<select id="inline-{{ $parentTask->id }}-{{ $field }}" name="status" required>
@foreach(\App\Models\Task::STATUSES as $status)<option value="{{ $status }}" @selected(($inlineFailed ? old('status') : 'to do') === $status)>{{ __($status) }}</option>@endforeach
</select>
@elseif($field === 'notes')
<textarea id="inline-{{ $parentTask->id }}-{{ $field }}" name="notes" rows="2" maxlength="5000">{{ $inlineFailed ? old('notes') : '' }}</textarea>
@else
<input id="inline-{{ $parentTask->id }}-{{ $field }}" type="{{ $field === 'activity_date' ? 'date' : 'text' }}" name="{{ $field }}" value="{{ $inlineFailed ? old($field) : ($field === 'activity_date' ? \App\Models\Task::today() : '') }}" @if($field === 'title') maxlength="200" @endif required>
@endif
@if($inlineFailed && $errors->inlineActivity->has($field))<p class="error" role="alert">{{ $errors->inlineActivity->first($field) }}</p>@endif
</div>
@endforeach
</div><button>{{ __('Add subactivity') }}</button>
</form>
@endcan
</div></details>