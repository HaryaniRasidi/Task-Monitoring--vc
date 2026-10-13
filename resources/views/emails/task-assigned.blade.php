<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><title>{{ __('New task assigned') }}</title></head>
<body>
    <h1>{{ __('New task assigned') }}</h1>
    <p>{{ $task->owner }}, {{ __('A task has been assigned to you.') }}</p>
    <dl>
        <dt>{{ __('Title') }}</dt><dd>{{ $task->title }}</dd>
        <dt>{{ __('Description') }}</dt><dd>{{ $task->description }}</dd>
        <dt>{{ __('Due date') }}</dt><dd>{{ $task->due_date->format('d/m/Y') }}</dd>
        <dt>{{ __('Priority') }}</dt><dd>{{ __($task->priority) }}</dd>
        <dt>{{ __('Status') }}</dt><dd>{{ __($task->status) }}</dd>
    </dl>
    <p><a href="{{ route('tasks.activities.index', $task) }}">{{ __('View task') }}</a></p>
</body>
</html>
