@extends('layouts.app')
@section('content')
<h1>{{ __('Manage users') }}</h1>
@if(session('success'))<p class="success" role="status">{{ session('success') }}</p>@endif
<section class="card">
<h2>{{ __('Officers') }} ({{ $officers->count() }})</h2>
<div class="table-scroll" role="region" aria-label="{{ __('Officers') }}" tabindex="0">
<table><thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Email') }}</th></tr></thead>
<tbody>@forelse($officers as $officer)<tr><td>{{ $officer->name }}</td><td>{{ $officer->email }}</td></tr>@empty<tr><td colspan="2">{{ __('No officers yet.') }}</td></tr>@endforelse</tbody></table>
</div></section>
<section class="card"><h2>{{ __('Add officer') }}</h2>
<p class="muted">{{ __('Password must have at least 12 characters, letters and numbers.') }}</p>
<form method="POST" action="{{ route('users.store') }}">@csrf
<div class="grid">
<div><label for="name">{{ __('Name') }}</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="100">@error('name')<p class="error" role="alert">{{ $message }}</p>@enderror</div>
<div><label for="email">{{ __('Email') }}</label><input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="254">@error('email')<p class="error" role="alert">{{ $message }}</p>@enderror</div>
<div><label for="password">{{ __('Password') }}</label><input type="password" id="password" name="password" autocomplete="new-password" required minlength="12" maxlength="200">@error('password')<p class="error" role="alert">{{ $message }}</p>@enderror</div>
<div><label for="password_confirmation">{{ __('Confirm password') }}</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="12" maxlength="200">@error('password_confirmation')<p class="error" role="alert">{{ $message }}</p>@enderror</div>
</div><button>{{ __('Add officer') }}</button>
</form></section>
@endsection