@extends('layouts.app')
@section('content')
<a href="{{ route('tasks.index') }}">&larr; {{ __('Task list') }}</a>
<h1>{{ $task->title }}</h1>
@if(session('success'))<p class="success" role="status">{{ session('success') }}</p>@endif
<section class="card">
@can('update', $task)
<form method="POST" action="{{ route('tasks.update', $task) }}">
@csrf
@method('PATCH')
@include('tasks.fields')
<button>{{ __('Save changes') }}</button>
</form>
@else
<dl>
<dt>{{ __('Owner') }}</dt><dd>{{ $task->owner }}</dd>
<dt>{{ __('Due date') }}</dt><dd>{{ $task->due_date->format('d/m/Y') }}</dd>
<dt>{{ __('Priority') }}</dt><dd>{{ __($task->priority) }}</dd>
<dt>{{ __('Status') }}</dt><dd>{{ __($task->status) }}</dd>
</dl>
@endcan
<p class="muted">{{ __('Last status change') }}: {{ $task->status_updated_at?->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') ?? __('Not recorded') }}</p>
</section>
@include('tasks.activities')
@endsection
