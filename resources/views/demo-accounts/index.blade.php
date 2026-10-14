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
  <div class="card bg-base-100 shadow mt-3">
    <div class="card-body p-0">
      <div class="flex items-center justify-between px-5 pt-5">
        <div class="inline-flex items-center gap-3">
          <label class="input input-sm">
            <span class="iconify lucide--search text-base-content/80 size-3.5"></span>
            <input class="w-24 sm:w-36" placeholder="Search demo accounts" aria-label="Search demo accounts" type="search" value="{{ $search }}" onkeydown="handleSearch(event)"/>
          </label>
        </div>
        <a aria-label="Create demo account" class="btn btn-primary btn-sm max-sm:btn-square" href="{{ route('add-demo-account') }}">
          <span class="iconify lucide--plus size-4"></span>
          <span class="hidden sm:inline">New Demo Account</span>
        </a>
      </div>
      <div class="mt-4 overflow-auto">
        <table class="table">
          <thead>
            <tr>
              <th style="text-align: left">Facility Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Available Services</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($demoAccounts as $demoAccount)
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
                  <a class="btn btn-square btn-primary btn-outline btn-xs border-transparent" title="Edit" href="{{ route('edit-demo-account', ['id' => $demoAccount->id]) }}">
                    <span class="iconify lucide--pencil" style="font-size: 0.875rem;"></span>
                  </a>
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
