@extends('layouts.main')
@section('title', 'Create Demo Account')

@section('page-css')
@endsection

@section('content')
<div class="flex items-center justify-between">
  <h3 class="text-lg font-medium">Create Demo Account</h3>
  <div class="breadcrumbs hidden p-0 text-sm sm:inline">
    <ul>
      <li><a href="{{ route('dashboard') }}">PawPrints</a></li>
      <li><a href="{{ route('demo-accounts') }}">Demo Accounts</a></li>
      <li class="opacity-80">Create</li>
    </ul>
  </div>
</div>
<div class="mt-3">
  @include('layouts.alerts')
  <form action="{{ route('create-demo-account') }}" method="POST" id="create_form">
    @csrf
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2 mt-3">
      <div class="card bg-base-100 shadow">
        <div class="card-body">
          <div class="card-title">Facility Information</div>
          <div class="fieldset mt-2">
            <div class="space-y-2">
              <label class="fieldset-label" for="facility_name">Facility Name*</label>
              <input class="input w-full" id="facility_name" name="facility_name" type="text" placeholder="e.g. Happy Paws Resort" value="{{ old('facility_name') }}" />
            </div>
          </div>

          <div class="card-title mt-5">Available Services</div>
          <p class="text-base-content/70 text-xs">Select the services this facility should see in its demo environment.</p>
          <div class="mt-2 grid grid-cols-2 gap-2">
            @forelse ($services as $service)
            <label class="flex items-center gap-2 text-sm">
              <input class="checkbox checkbox-sm" type="checkbox" name="services[]" value="{{ $service->id }}" {{ in_array($service->id, old('services', [])) ? 'checked' : '' }} />
              {{ $service->name }}
            </label>
            @empty
            <p class="text-base-content/60 text-sm col-span-2">No primary services are available.</p>
            @endforelse
          </div>
        </div>
      </div>
      <div class="card bg-base-100 shadow">
        <div class="card-body">
          <div class="card-title">Demo Login Credentials</div>
          <div class="fieldset mt-2 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="space-y-2">
              <label class="fieldset-label" for="username">Username*</label>
              <label class="input w-full focus:outline-0">
                <span class="iconify lucide--user text-base-content/60 size-4"></span>
                <input class="grow focus:outline-0" placeholder="Username" id="username" name="username" type="text" value="{{ old('username') }}" />
              </label>
            </div>
            <div class="space-y-2">
              <label class="fieldset-label" for="email">Email*</label>
              <label class="input w-full focus:outline-0">
                <span class="iconify lucide--mail text-base-content/60 size-4"></span>
                <input class="grow focus:outline-0" placeholder="Email" id="email" name="email" type="email" autocomplete="off" value="{{ old('email') }}" />
              </label>
            </div>
            <div class="space-y-2">
              <label class="fieldset-label" for="password">Password*</label>
              <label class="input w-full focus:outline-0">
                <span class="iconify lucide--key-round text-base-content/60 size-4"></span>
                <input class="grow focus:outline-0" placeholder="Password" id="password" name="password" type="password" autocomplete="new-password" />
                <label class="swap btn btn-xs btn-ghost btn-circle text-base-content/60">
                  <input type="checkbox" aria-label="Show password" data-password="password" />
                  <span class="iconify lucide--eye swap-off size-4"></span>
                  <span class="iconify lucide--eye-off swap-on size-4"></span>
                </label>
              </label>
            </div>
            <div class="space-y-2">
              <label class="fieldset-label" for="confirm_password">Confirm Password*</label>
              <label class="input w-full focus:outline-0">
                <span class="iconify lucide--key-round text-base-content/60 size-4"></span>
                <input class="grow focus:outline-0" placeholder="Confirm Password" id="confirm_password" name="password_confirmation" type="password" autocomplete="new-password" />
                <label class="swap btn btn-xs btn-ghost btn-circle text-base-content/60">
                  <input type="checkbox" aria-label="Show password" data-password="confirm_password" />
                  <span class="iconify lucide--eye swap-off size-4"></span>
                  <span class="iconify lucide--eye-off swap-on size-4"></span>
                </label>
              </label>
            </div>
          </div>

          <div class="card-title mt-5">Account Access</div>
          <div class="mt-2 space-y-2">
            <label class="flex items-center gap-2 text-sm">
              <input class="radio radio-sm" type="radio" name="status" value="active" {{ old('status', 'active') === 'active' ? 'checked' : '' }} />
              <span><span class="font-medium">Active</span> — Facility can log in</span>
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input class="radio radio-sm" type="radio" name="status" value="inactive" {{ old('status') === 'inactive' ? 'checked' : '' }} />
              <span><span class="font-medium">Inactive</span> — Login is blocked</span>
            </label>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6 flex justify-end gap-3">
      <a class="btn btn-sm btn-ghost" href="{{ route('demo-accounts') }}">
        <span class="iconify lucide--x size-4"></span>
        Cancel
      </a>
      <button class="btn btn-sm btn-primary" type="button" onclick="saveDemoAccount()">
        <span class="iconify lucide--check size-4"></span>
        Save
      </button>
    </div>
  </form>
</div>
@endsection

@section('page-js')
  <script src="{{ asset('src/js/components/password-field.js') }}"></script>

  <script>
    function saveDemoAccount() {
      const facilityName = $('#facility_name').val().trim();
      const username = $('#username').val().trim();
      const email = $('#email').val().trim();
      const password = $('#password').val();
      const confirmPassword = $('#confirm_password').val();

      if (!facilityName || !username || !email || !password || !confirmPassword) {
        $('#alert_message').text('Please fill in all required fields.');
        alert_modal.showModal();
        return;
      }

      if (password !== confirmPassword) {
        $('#alert_message').text('Passwords do not match.');
        alert_modal.showModal();
        return;
      }

      $('#create_form').submit();
    }
  </script>
@endsection
