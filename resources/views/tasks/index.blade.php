@extends('layouts.app')
@section('content')
<h1>{{ auth()->user()->role === 'officer' ? __('My Tasks') : __('Task Monitoring') }}</h1>
@if(session('success'))<p class="success" role="status">{{ session('success') }}</p>@endif
<section id="task-list" class="card">
<h2>{{ __('Tasks') }} ({{ $tasks->total() }})</h2>
<div class="table-scroll" role="region" aria-label="{{ __('Task list') }}" tabindex="0">
<table>
<thead><tr>@foreach(['Title', 'Owner', 'Due date', 'Priority', 'Status'] as $label)<th scope="col">{{ __($label) }}</th>@endforeach</tr></thead>
<tbody>
@forelse($tasks as $listedTask)
@php($overdue = $listedTask->isOverdue())
<tr @class(['overdue' => $overdue])>
<td><a href="{{ route('tasks.show', $listedTask) }}">{{ $listedTask->title }}</a></td>
<td>{{ $listedTask->owner }}</td>
<td>{{ $listedTask->due_date->format('d/m/Y') }} @if($overdue)<span class="overdue-label">{{ __('Overdue') }}</span>@elseif($listedTask->isDueSoon())<span class="badge">{{ __('Due soon') }}</span>@endif</td>
<td><span class="badge priority-{{ $listedTask->priority }}">{{ __($listedTask->priority) }}</span></td>
<td><span class="badge status-{{ str_replace(' ', '-', $listedTask->status) }}">{{ __($listedTask->status) }}</span></td>
</tr>
<tr><td colspan="5">@include('tasks.inline-activities', ['parentTask' => $listedTask])</td></tr>
@empty<tr><td colspan="5">{{ __('No tasks yet. Add your first task below.') }}</td></tr>@endforelse
</tbody></table></div>
@if($tasks->hasPages())
<nav class="pagination" aria-label="{{ __('Pages') }}">
@if(!$tasks->onFirstPage())<a href="{{ $tasks->previousPageUrl() }}">&larr; {{ __('Previous') }}</a>@endif
<span>{{ __('Page') }} {{ $tasks->currentPage() }} / {{ $tasks->lastPage() }} ({{ $tasks->total() }} {{ __('Tasks') }})</span>
@if($tasks->hasMorePages())<a href="{{ $tasks->nextPageUrl() }}">{{ __('Next') }} &rarr;</a>@endif
</nav>
@endif
</section>
<section id="add-task" class="card">
<h2>{{ __('Add a task') }}</h2>
<form method="POST" action="{{ route('tasks.store') }}">
@csrf
@include('tasks.fields')
<button>{{ __('Add task') }}</button>
</form></section>
@endsection
