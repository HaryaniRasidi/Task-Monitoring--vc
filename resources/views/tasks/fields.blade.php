<div class="grid">
<div>
<label for="title">{{ __('Title') }}</label>
<input id="title" name="title" required maxlength="200" value="{{ old('title', $task->title ?? '') }}">
@error('title')<p class="error" role="alert">{{ $message }}</p>@enderror
</div>
<div>
<label for="owner">{{ __('Owner') }}</label>
@if(!isset($task) && auth()->user()->role === 'supervisor')
<select id="owner" name="user_id" required>
<option value="">{{ __('Select officer') }}</option>
@foreach($officers as $officer)<option value="{{ $officer->id }}" @selected((string) old('user_id') === (string) $officer->id)>{{ $officer->name }} ({{ $officer->email }})</option>@endforeach
</select>
@error('user_id')<p class="error" role="alert">{{ $message }}</p>@enderror
@else
<input id="owner" value="{{ isset($task) ? $task->owner : auth()->user()->name }}" readonly>
@endif
</div>
<div>
<label for="due_date">{{ __('Due date') }}</label>
<input type="date" id="due_date" name="due_date" required value="{{ old('due_date', isset($task) ? $task->due_date->format('Y-m-d') : '') }}">
@error('due_date')<p class="error" role="alert">{{ $message }}</p>@enderror
</div>
<div>
<label for="priority">{{ __('Priority') }}</label>
<select id="priority" name="priority" required>
@foreach(\App\Models\Task::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(old('priority', $task->priority ?? 'medium') === $priority)>{{ __($priority) }}</option>@endforeach
</select>
@error('priority')<p class="error" role="alert">{{ $message }}</p>@enderror
</div>
<div>
<label for="status">{{ __('Status') }}</label>
<select id="status" name="status" required>
@foreach(\App\Models\Task::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $task->status ?? 'to do') === $status)>{{ __($status) }}</option>@endforeach
</select>
@error('status')<p class="error" role="alert">{{ $message }}</p>@enderror
</div>
</div>
