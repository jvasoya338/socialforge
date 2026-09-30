@extends('layouts.admin')

@section('title', 'Users Management')
@section('page_title', 'System Users & Roles')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Administrative & Editorial Users</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td><strong>{{ $u->name }}</strong></td>
                                    <td>{{ $u->email }}</td>
                                    <td>
                                        <span class="badge {{ match($u->role) {
                                            'super_admin' => 'badge-danger',
                                            'admin' => 'badge-primary',
                                            'editor' => 'badge-warning',
                                            default => 'badge-secondary'
                                        } }} text-uppercase">
                                            {{ str_replace('_', ' ', $u->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $u->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $u->is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($users->hasPages())
                <div class="card-footer bg-light">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Add User Column -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Create User</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.system.users.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="u_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="u_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="u_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="u_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="u_role">Role <span class="text-danger">*</span></label>
                        <select name="role" id="u_role" class="form-control" required>
                            <option value="editor">Editor (Draft & Content)</option>
                            <option value="admin">Admin (Approve, Schedule & Publish)</option>
                            <option value="super_admin">Super Admin (Full System & Security)</option>
                            <option value="viewer">Viewer (Read-Only Analytics)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="u_password">Temporary Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="u_password" class="form-control" minlength="8" required>
                    </div>

                    <input type="hidden" name="is_active" value="1">

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus mr-1"></i> Create User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
