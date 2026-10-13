<p class="muted">{{ __('Activity statuses are tracked separately from the main task status.') }}</p>
    <section id="subactivities" class="card" aria-labelledby="activities-heading">
        <h2 id="activities-heading">{{ __('Subactivities') }} ({{ $activities->count() }})</h2>
        <div class="table-scroll" role="region" aria-label="{{ __('Subactivities') }}" tabindex="0">
            <table>
                <caption class="muted">{{ __('Subactivities') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('Subactivity') }}</th>
                        <th scope="col">{{ __('Notes') }}</th>
                        <th scope="col">{{ __('Activity date') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Last updated') }}</th>
                        <th scope="col">{{ __('Update') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <th scope="row">{{ $activity->title }}</th>
                        <td>{{ $activity->notes ?: '-' }}</td>
                        <td>@if($activity->activity_date)<time datetime="{{ $activity->activity_date->format('Y-m-d') }}">{{ $activity->activity_date->format('d/m/Y') }}</time>@else{{ __('Not recorded') }}@endif</td>
                        <td><span class="badge status-{{ str_replace(' ', '-', $activity->status) }}">{{ __($activity->status) }}</span></td>
                        <td><time datetime="{{ $activity->updated_at->toIso8601String() }}">{{ $activity->updated_at->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }} (MYT)</time></td>
                        <td>
                            @can('update', $task)<a href="{{ route('tasks.activities.edit', [$task, $activity]) }}">{{ __('Update') }}</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __('No subactivities yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @can('update', $task)
    <section class="card" aria-labelledby="add-activity-heading">
        <h2 id="add-activity-heading">{{ __('Add subactivity') }}</h2>
        <form method="POST" action="{{ route('tasks.activities.store', $task) }}">
            @csrf
            <div class="grid">
                <div>
                    <label for="activity-title">{{ __('Subactivity') }}</label>
                    <input id="activity-title" name="title" value="{{ old('title') }}" maxlength="200" required @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                    @error('title')<p class="error" id="title-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="activity_date">{{ __('Activity date') }}</label>
                    <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date', now('Asia/Kuala_Lumpur')->toDateString()) }}" required @error('activity_date') aria-invalid="true" aria-describedby="activity-date-error" @enderror>
                    @error('activity_date')<p class="error" id="activity-date-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="activity-status">{{ __('Status') }}</label>
                    <select id="activity-status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                        @foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', 'to do') === $status)>{{ __($status) }}</option>@endforeach
                    </select>
                    @error('status')<p class="error" id="status-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes">{{ __('Notes') }} ({{ __('Optional') }})</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="5000" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes') }}</textarea>
                    @error('notes')<p class="error" id="notes-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit">{{ __('Add subactivity') }}</button>
        </form>
    </section>

@endcan
