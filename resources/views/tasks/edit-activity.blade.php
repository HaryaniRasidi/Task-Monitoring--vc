@extends('layouts.app')
@section('title', __('Update activity'))
@section('content')
    <a href="{{ route('tasks.show', $task) }}">&larr; {{ __('Subactivities') }}</a>
    <h1>{{ __('Update activity') }}</h1>
    <p class="muted">{{ $task->title }}</p>
    @if($errors->any())
        <div class="errors" role="alert"><strong>{{ __('Please correct the following fields:') }}</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <section class="card">
<form method="POST" action="{{ route('tasks.activities.update', [$task, $activity]) }}">
            @csrf
            @method('PATCH')
            <div class="grid">
                <div>
                    <label for="title">{{ __('Subactivity') }}</label>
                    <input id="title" name="title" value="{{ old('title', $activity->title) }}" maxlength="200" required @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                    @error('title')<p class="error" id="title-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="activity_date">{{ __('Activity date') }}</label>
                    <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date', $activity->activity_date?->format('Y-m-d')) }}" required @error('activity_date') aria-invalid="true" aria-describedby="activity-date-error" @enderror>
                    @error('activity_date')<p class="error" id="activity-date-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status">{{ __('Status') }}</label>
                    <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                        @foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', $activity->status) === $status)>{{ __($status) }}</option>@endforeach
                    </select>
                    @error('status')<p class="error" id="status-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes">{{ __('Notes') }} ({{ __('Optional') }})</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="5000" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes', $activity->notes) }}</textarea>
                    @error('notes')<p class="error" id="notes-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit">{{ __('Save changes') }}</button>
        </form>
        <p><a href="{{ route('tasks.show', $task) }}">{{ __('Cancel') }}</a></p>
    </section>
@endsection