@extends('layouts.app')
@section('content')
<h1>{{ __('Login') }}</h1>
<section class="card">
<form method="POST" action="{{ route('login.store') }}">
@csrf
<label for="email">{{ __('Email') }}</label>
<input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="username">
@error('email')<p class="error" role="alert">{{ $message }}</p>@enderror
<label for="password">{{ __('Password') }}</label>
<input type="password" id="password" name="password" required maxlength="200" autocomplete="current-password">
@error('password')<p class="error" role="alert">{{ $message }}</p>@enderror
<button>{{ __('Login') }}</button>
</form>
</section>
@endsection
