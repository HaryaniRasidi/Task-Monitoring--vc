<div class="table-scroll" role="region" aria-label="{{ __('Task list') }}" tabindex="0">
<table><thead><tr>@foreach(['Title', 'Owner', 'Due date', 'Priority', 'Status'] as $label)<th scope="col">{{ __($label) }}</th>@endforeach</tr></thead>
<tbody>@forelse($items as $item)
<tr @class(['overdue' => $item->isOverdue()])>
<td>@can('view', $item)<a href="{{ route('tasks.show', $item) }}">{{ $item->title }}</a>@else{{ $item->title }}@endcan</td>
<td>{{ $item->assignedUser?->name ?? __('Unassigned') }}</td>
<td>{{ $item->due_date->format('d/m/Y') }}</td>
<td><span class="badge priority-{{ $item->priority }}">{{ __($item->priority) }}</span></td>
<td><span class="badge status-{{ str_replace(' ', '-', $item->status) }}">{{ __($item->status) }}</span></td>
</tr>@empty<tr><td colspan="5">{{ __('No matching tasks.') }}</td></tr>@endforelse</tbody></table>
</div>