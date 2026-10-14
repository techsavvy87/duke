@extends('layouts.main')
@section('title', 'Demo Accounts')

@section('page-css')
<style>
  th, td {
    text-align: center;
  }
</style>
@endsection

@section('content')
<div class="flex items-center justify-between">
  <h3 class="text-lg font-medium">Demo Accounts</h3>
  <div class="breadcrumbs hidden p-0 text-sm sm:inline">
    <ul>
      <li><a href="{{ route('dashboard') }}">PawPrints</a></li>
      <li>Demo Accounts</li>
    </ul>
  </div>
</div>
<div class="mt-3">
  @include('layouts.alerts')
  <div class="mt-3 grid grid-cols-1 gap-4 xl:grid-cols-5 2xl:grid-cols-10">
    <div class="xl:col-span-2 2xl:col-span-3">
      <div class="card bg-base-100 card-border">
        <div class="card-body">
          <div class="card-title" id="demo_form_title">Create Demo Account</div>
          <input type="hidden" id="demo_account_id" value="">

          <div class="fieldset">
            <p class="text-sm font-medium mt-2">Facility Information</p>
            <div class="space-y-1 mt-2">
              <label class="fieldset-label" for="facility_name">Facility Name*</label>
              <input class="input w-full" id="facility_name" type="text" placeholder="e.g. Happy Paws Resort" />
            </div>

            <p class="text-sm font-medium mt-5">Available Services</p>
            <p class="text-base-content/70 text-xs mt-1">Select the services this facility should see in its demo environment.</p>
            <div class="mt-2 grid grid-cols-2 gap-2" id="demo_services">
              @forelse ($services as $service)
              <label class="flex items-center gap-2 text-sm">
                <input class="checkbox checkbox-sm demo-service" type="checkbox" value="{{ $service->id }}" />
                {{ $service->name }}
              </label>
              @empty
              <p class="text-base-content/60 text-sm col-span-2">No primary services are available.</p>
              @endforelse
            </div>

            <p class="text-sm font-medium mt-5">Demo Login Credentials</p>
            <div class="space-y-1 mt-2">
              <label class="fieldset-label" for="contact_person">Contact Person*</label>
              <input class="input w-full" id="contact_person" type="text" placeholder="" />
            </div>
            <div class="space-y-1 mt-2">
              <label class="fieldset-label" for="login_email">Login Email*</label>
              <input class="input w-full" id="login_email" type="email" placeholder="" autocomplete="off" />
            </div>
            <div class="space-y-1 mt-2">
              <label class="fieldset-label" for="login_password" id="password_label">Password*</label>
              <label class="input w-full focus:outline-0">
                <input class="grow focus:outline-0" id="login_password" type="password" placeholder="" autocomplete="new-password" />
                <label class="swap btn btn-xs btn-ghost btn-circle text-base-content/60">
                  <input type="checkbox" aria-label="Show password" data-password="login_password" id="login_password_toggle" />
                  <span class="iconify lucide--eye swap-off size-4"></span>
                  <span class="iconify lucide--eye-off swap-on size-4"></span>
                </label>
              </label>
              <p class="text-base-content/60 text-xs" id="password_hint" style="display:none;">Leave blank to keep the current password.</p>
            </div>

            <p class="text-sm font-medium mt-5">Account Access</p>
            <div class="mt-2 space-y-2">
              <label class="flex items-center gap-2 text-sm">
                <input class="radio radio-sm" type="radio" name="demo_status" value="active" checked />
                <span><span class="font-medium">Active</span> — Facility can log in</span>
              </label>
              <label class="flex items-center gap-2 text-sm">
                <input class="radio radio-sm" type="radio" name="demo_status" value="inactive" />
                <span><span class="font-medium">Inactive</span> — Login is blocked</span>
              </label>
            </div>
          </div>
          <div class="mt-5 flex justify-end gap-3">
            <button class="btn btn-ghost btn-sm" id="cancel_btn" onclick="resetForm()">Cancel</button>
            <button class="btn btn-sm btn-primary gap-1" onclick="saveDemoAccount()" id="save_btn">
              <span class="loading loading-spinner size-3.5" style="display:none;"></span>
              <span class="btn-text">Save</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="xl:col-span-3 2xl:col-span-7">
      <div class="card bg-base-100 shadow">
        <div class="card-body p-0">
          <div class="flex items-center justify-between px-5 pt-5">
            <div class="inline-flex items-center gap-3">
              <label class="input input-sm">
                <span class="iconify lucide--search text-base-content/80 size-3.5"></span>
                <input class="w-24 sm:w-36" placeholder="Search demo accounts" aria-label="Search demo accounts" type="search" value="{{ $search }}" onkeydown="handleSearch(event)"/>
              </label>
            </div>
          </div>
          <div class="mt-4 overflow-auto">
            <table class="table">
              <thead>
                <tr>
                  <th style="text-align: left">Facility Name</th>
                  <th>Contact Person</th>
                  <th>Login Email</th>
                  <th>Available Services</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($demoAccounts as $demoAccount)
                @php
                  $demoAccountData = [
                    'id' => $demoAccount->id,
                    'facility_name' => $demoAccount->facility_name,
                    'contact_person' => optional($demoAccount->user)->name,
                    'email' => optional($demoAccount->user)->email,
                    'status' => $demoAccount->status,
                    'services' => $demoAccount->enabledServices->pluck('id')->values(),
                  ];
                @endphp
                <tr class="hover:bg-base-200/40 *:text-nowrap">
                  <td style="text-align: left">{{ $demoAccount->facility_name }}</td>
                  <td>{{ optional($demoAccount->user)->name }}</td>
                  <td>{{ optional($demoAccount->user)->email }}</td>
                  <td style="white-space: normal">
                    @forelse ($demoAccount->enabledServices as $service)
                    <span class="badge badge-sm badge-soft badge-primary my-0.5">{{ $service->name }}</span>
                    @empty
                    <span class="text-base-content/60 text-sm">None</span>
                    @endforelse
                  </td>
                  <td>
                    @if ($demoAccount->isActive())
                    <span class="badge badge-sm badge-soft badge-success">Active</span>
                    @else
                    <span class="badge badge-sm badge-soft badge-error">Inactive</span>
                    @endif
                  </td>
                  <td>
                    <div class="inline-flex w-fit">
                      <button class="btn btn-square btn-primary btn-outline btn-xs border-transparent" title="Edit" onclick='editDemoAccount(@json($demoAccountData))'>
                        <span class="iconify lucide--pencil" style="font-size: 0.875rem;"></span>
                      </button>
                      @if ($demoAccount->isActive())
                      <button class="btn btn-square btn-error btn-outline btn-xs border-transparent" title="Deactivate" onclick="openStatusModal({{ $demoAccount->id }}, 'inactive')">
                        <span class="iconify lucide--ban" style="font-size: 0.875rem;"></span>
                      </button>
                      @else
                      <button class="btn btn-square btn-success btn-outline btn-xs border-transparent" title="Activate" onclick="openStatusModal({{ $demoAccount->id }}, 'active')">
                        <span class="iconify lucide--check" style="font-size: 0.875rem;"></span>
                      </button>
                      @endif
                      <button class="btn btn-square btn-error btn-outline btn-xs border-transparent" title="Delete" onclick='openDeleteModal({{ $demoAccount->id }}, @json($demoAccount->facility_name))'>
                        <span class="iconify lucide--trash" style="font-size: 0.875rem;"></span>
                      </button>
                    </div>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="6" class="text-base-content/60">No demo accounts yet.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $demoAccounts->links('layouts.pagination', ['items' => $demoAccounts]) }}
        </div>
      </div>
    </div>
  </div>
</div>
<dialog id="delete_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      Delete Demo Account
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4">
      You are about to permanently delete <span class="font-medium" id="delete_facility_name"></span> and everything in its demo environment:
      its users, customers, pets, appointments, invoices, payments, services and time slots. This cannot be undone. Would you like to proceed?
    </p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost">No</button>
      </form>
      <input type="hidden" id="delete_demo_account_id" value="" />
      <button class="btn btn-error" onclick="deleteDemoAccount()">Delete</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
<dialog id="status_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      <span id="status_modal_title">Deactivate Demo Account</span>
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4" id="status_modal_message"></p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost">No</button>
      </form>
      <input type="hidden" id="status_demo_account_id" value="" />
      <input type="hidden" id="status_value" value="" />
      <button class="btn btn-primary" id="status_confirm_btn" onclick="updateDemoAccountStatus()">Yes</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
@endsection

@section('page-js')
<script src="{{ asset('src/js/components/password-field.js') }}"></script>
<script>
  function errorMessage(xhr) {
    if (xhr.responseJSON && xhr.responseJSON.errors) {
      return Object.values(xhr.responseJSON.errors).flat().join('\n');
    }
    if (xhr.responseJSON && xhr.responseJSON.message) {
      return xhr.responseJSON.message;
    }
    return 'An error occurred. Please try again.';
  }

  function showSuccessAndReload(message) {
    $('#success_message').text(message);
    success_modal.addEventListener('close', () => window.location.reload(), { once: true });
    success_modal.showModal();
  }

  function setSaving(saving) {
    $('#save_btn .loading').css('display', saving ? 'inline-block' : 'none');
    $('#save_btn .btn-text').text(saving ? 'Loading' : 'Save');
    $('#save_btn').prop('disabled', saving);
    $('#cancel_btn').prop('disabled', saving);
  }

  function saveDemoAccount() {
    const demoAccountId = $('#demo_account_id').val();
    const data = {
      demo_account_id: demoAccountId,
      facility_name: $('#facility_name').val().trim(),
      services: $('.demo-service:checked').map(function() { return $(this).val(); }).get(),
      contact_person: $('#contact_person').val().trim(),
      email: $('#login_email').val().trim(),
      password: $('#login_password').val(),
      status: $('input[name="demo_status"]:checked').val(),
    };

    if (!data.facility_name || !data.contact_person || !data.email || (!demoAccountId && !data.password)) {
      $('#alert_message').text('Please fill in all required fields.');
      alert_modal.showModal();
      return;
    }

    setSaving(true);

    $.ajax({
      url: demoAccountId ? '{{ route("update-demo-account") }}' : '{{ route("create-demo-account") }}',
      method: 'POST',
      data: data,
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        setSaving(false);
        showSuccessAndReload(response.message);
      },
      error: function(xhr) {
        setSaving(false);
        $('#alert_message').text(errorMessage(xhr));
        alert_modal.showModal();
      }
    });
  }

  function resetForm() {
    $('#demo_account_id').val('');
    $('#facility_name').val('');
    $('.demo-service').prop('checked', false);
    $('#contact_person').val('');
    $('#login_email').val('');
    $('#login_password').val('').attr('type', 'password');
    $('#login_password_toggle').prop('checked', false);
    $('input[name="demo_status"][value="active"]').prop('checked', true);
    $('#password_label').text('Password*');
    $('#password_hint').hide();
    $('#demo_form_title').text('Create Demo Account');
  }

  function editDemoAccount(demoAccount) {
    resetForm();
    $('#demo_account_id').val(demoAccount.id);
    $('#facility_name').val(demoAccount.facility_name);
    (demoAccount.services || []).forEach(function(serviceId) {
      $('.demo-service[value="' + serviceId + '"]').prop('checked', true);
    });
    $('#contact_person').val(demoAccount.contact_person || '');
    $('#login_email').val(demoAccount.email || '');
    $('input[name="demo_status"][value="' + demoAccount.status + '"]').prop('checked', true);
    $('#password_label').text('Password');
    $('#password_hint').show();
    $('#demo_form_title').text('Edit Demo Account');
  }

  function openStatusModal(id, status) {
    $('#status_demo_account_id').val(id);
    $('#status_value').val(status);
    if (status === 'active') {
      $('#status_modal_title').text('Activate Demo Account');
      $('#status_modal_message').text('Users of this demo account will be able to log in again. Would you like to proceed?');
      $('#status_confirm_btn').removeClass('btn-error').addClass('btn-primary').text('Activate');
    } else {
      $('#status_modal_title').text('Deactivate Demo Account');
      $('#status_modal_message').text('All users of this demo account will be signed out and blocked from logging in. Their data is kept. Would you like to proceed?');
      $('#status_confirm_btn').removeClass('btn-primary').addClass('btn-error').text('Deactivate');
    }
    status_modal.showModal();
  }

  function updateDemoAccountStatus() {
    status_modal.close();

    $.ajax({
      url: '{{ route("update-demo-account-status") }}',
      method: 'POST',
      data: {
        demo_account_id: $('#status_demo_account_id').val(),
        status: $('#status_value').val(),
      },
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        showSuccessAndReload(response.message);
      },
      error: function(xhr) {
        $('#alert_message').text(errorMessage(xhr));
        alert_modal.showModal();
      }
    });
  }

  function openDeleteModal(id, facilityName) {
    $('#delete_demo_account_id').val(id);
    $('#delete_facility_name').text(facilityName);
    delete_modal.showModal();
  }

  function deleteDemoAccount() {
    delete_modal.close();

    const id = $('#delete_demo_account_id').val();
    $.ajax({
      url: '{{ route("delete-demo-account") }}',
      method: 'POST',
      data: { demo_account_id: id },
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        // The form may still hold the deleted account
        if ($('#demo_account_id').val() === id) {
          resetForm();
        }
        showSuccessAndReload(response.message);
      },
      error: function(xhr) {
        $('#alert_message').text(errorMessage(xhr));
        alert_modal.showModal();
      }
    });
  }

  function handleSearch(event) {
    if (event.key === 'Enter') {
      const url = new URL(window.location.href);
      url.searchParams.set('search', event.target.value);
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }
  }
</script>
@endsection
