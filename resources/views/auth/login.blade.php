@extends('layouts.auth')

@section('content')
<div class="card card-outline card-primary shadow-lg">
    <div class="card-header text-center">
        <h4 class="mb-0 font-weight-bold">Private Administration</h4>
        <small class="text-muted">Sign in to manage multi-brand social media</small>
    </div>
    <div class="card-body">
        @include('partials.alerts')

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="input-group mb-3">
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Address" value="{{ old('email', 'admin@socialforge.local') }}" required autofocus>
                <div class="input-group-append">
                    <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                </div>
            </div>

            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                <div class="input-group-append">
                    <div class="input-group-text"><span class="fas fa-lock"></span></div>
                </div>
            </div>

            <div class="row align-items-center mb-3">
                <div class="col-8">
                    <div class="icheck-primary">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Remember Me</label>
                    </div>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>
            </div>
        </form>

        <div class="card-footer bg-light p-2 text-center rounded">
            <small class="text-muted d-block font-italic">Default roles: Super Admin, Admin, Editor, Viewer</small>
        </div>
    </div>
</div>
@endsection
