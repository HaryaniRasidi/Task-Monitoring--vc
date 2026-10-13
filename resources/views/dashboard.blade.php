@extends('layouts.app')
@section('content')
<h1>{{ __('Task dashboard') }}</h1>
@if(!$summary['total'])<p class="muted">{{ __('No tasks yet') }}</p>@endif
<section class="card">
<dl class="dashboard-grid">
@foreach(['open' => 'Open tasks', 'overdue' => 'Overdue tasks', 'due_next_7' => 'Due in the next 7 days', 'done' => 'Completed tasks'] as $key => $label)
<div class="summary-card kpi-{{ $key }}"><dt>{{ __($label) }}</dt><dd>{{ $summary[$key] }}</dd></div>
@endforeach
<div class="summary-card kpi-weekly"><dt>{{ __('Weekly updates') }}</dt><dd @class(['kpi-empty' => !$activeCount])>{{ $activeCount ? $rate.'%' : __('Not applicable') }}</dd><p>{{ $updated }} / {{ $activeCount }} — {{ __('Target') }}: 90%</p></div>
</dl>
<p class="muted">{{ __('Open tasks due today through 7 days from today, inclusive. Reminders still run 3 days before the deadline.') }}</p>
<p class="muted">{{ __('Only status changes count. Active tasks are not done. Week: Monday to Sunday, Malaysia time.') }}</p>
@if(!$activeCount)<p>{{ __('No active tasks to measure.') }}</p>@endif
</section>
<div class="charts-grid">
<section class="card"><h2>{{ __('Workload by owner') }}</h2>
<p class="muted">{{ __('Highest open-task counts first. Counts indicate workload, not confirmed overload; capacity is not recorded.') }}</p>
@if($activeCount)<div class="chart-container"><canvas id="owner-chart" role="img" aria-label="{{ __('Workload by owner') }}">{{ __('Open tasks') }}</canvas></div>@endif
</section>
<section class="card"><h2>{{ __('Tasks by status') }}</h2>
@if($summary['total'])
<div class="chart-container"><canvas id="status-chart" role="img" aria-label="{{ __('Tasks by status') }}">{{ __('Tasks by status') }}</canvas></div>
<dl class="chart-totals">
@foreach(['to_do' => 'to do', 'in_progress' => 'in progress', 'done' => 'done'] as $key => $label)
<div><dt>{{ __($label) }}</dt><dd>{{ $summary[$key] }}</dd></div>
@endforeach
</dl>
@else<p class="muted">{{ __('No tasks yet') }}</p>@endif
</section>
<section class="card"><h2>{{ __('Tasks by priority') }}</h2>
@if($summary['total'])
<div class="chart-container"><canvas id="priority-chart" role="img" aria-label="{{ __('Tasks by priority') }}">{{ __('Tasks by priority') }}</canvas></div>
<dl class="chart-totals">
@foreach($priorityLabels as $index => $label)<div><dt>{{ $label }}</dt><dd>{{ $priorityCounts[$index] }}</dd></div>@endforeach
</dl>
@else<p class="muted">{{ __('No tasks yet') }}</p>@endif
</section>
</div>

<section class="card"><h2>{{ __('10 oldest overdue tasks') }}</h2>
<p class="muted">{{ __('Open tasks due before today, oldest first.') }}</p>
@include('tasks.dashboard-table', ['items' => $overdueTasks])
</section>
@endsection
@push('scripts')
@if($summary['total'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.js" integrity="sha384-sitkSLEO56SJYRloHWybrHos3JVvByQSlOZYJ1HiExytjxUlVOernDbpQcOgwtW8" crossorigin="anonymous"></script>
<script>
const ownerLabels = @json($chartLabels);
const ownerCounts = @json($chartCounts);
const ownerChartTitle = @json($chartTitle);
const statusLabels = @json($statusLabels);
const statusCounts = @json($statusCounts);
const priorityLabels = @json($priorityLabels);
const priorityCounts = @json($priorityCounts);
if (window.Chart) {
    Chart.defaults.font.family = 'Arial, sans-serif';
    new Chart(document.getElementById('priority-chart'), {
        type: 'doughnut',
        data: { labels: priorityLabels, datasets: [{ data: priorityCounts, backgroundColor: ['#16a34a', '#d97706', '#dc2626'], borderWidth: 2, borderColor: '#ffffff' }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('status-chart'), {
        type: 'pie',
        data: { labels: statusLabels, datasets: [{ data: statusCounts, backgroundColor: ['#64748b', '#2563eb', '#16a34a'], borderWidth: 2, borderColor: '#ffffff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
    if (document.getElementById('owner-chart')) {
    new Chart(document.getElementById('owner-chart'), {
        type: 'bar',
        data: { labels: ownerLabels, datasets: [{ label: ownerChartTitle, data: ownerCounts, backgroundColor: '#ec4899' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    }
}
</script>
@endif
@endpush