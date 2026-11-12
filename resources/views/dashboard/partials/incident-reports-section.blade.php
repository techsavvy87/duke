{{-- Incident reports on Appointment Detail: history (open / resolved) with Edit / Delete, Resolve for Issue appointments,
     and the "Incident Report" modal for In Progress / On Property appointments.
     For completed appointments with a service Report, the history is shown inside that Report instead.
     Expects: $appointment; optional: $incidentReports. --}}
@php
  $incidentReports = $incidentReports ?? $appointment->incident_reports;
  $canResolveIncident = $appointment->status === 'issue' && hasPermission(26, 'can_update');
  $canCreateIncident = $appointment->status === 'in_progress' && hasPermission(26, 'can_create');
  $canEditIncident = hasPermission(26, 'can_update');
  // Issue appointments are settled with Resolve, so their incidents can't be deleted here
  $canDeleteIncident = $appointment->status !== 'issue' && hasPermission(26, 'can_delete');
  $incidentsInReport = $appointment->status === 'completed' && hasServiceReportWorkflow($appointment->service);
  $showIncidentSection = !$incidentsInReport && ($incidentReports->isNotEmpty() || $canCreateIncident);
  // open incidents on an In Progress / On Property appointment are waiting for its Confirm to move it to Issue
  $hasUnconfirmedIncidents = $appointment->status === 'in_progress' && $incidentReports->contains(fn ($incident) => $incident->isOpen());
  // incident form modals on this page: the new-report modal and one edit modal per report
  $incidentModals = [];
  if ($canCreateIncident) {
    $incidentModals[] = ['id' => 'incident_report_modal', 'report' => null];
  }
  if ($showIncidentSection && $canEditIncident) {
    foreach ($incidentReports as $incident) {
      $incidentModals[] = ['id' => 'incident_edit_modal_' . $incident->id, 'report' => $incident];
    }
  }
@endphp
@if ($showIncidentSection)
<div class="card card-border bg-base-100 mt-3">
  <div class="card-body gap-0">
    <div class="bg-base-200 rounded-box collapse collapse-arrow">
      <input aria-label="Collapse trigger" type="checkbox" checked="" name="accordion-multiple" />
      <div class="collapse-title font-medium py-1 flex items-center justify-between gap-2">
        <span>Incident Reports ({{ $incidentReports->count() }})</span>
        @if ($canCreateIncident)
        {{-- above the collapse trigger so clicking it doesn't fold the section --}}
        <button type="button" class="btn btn-xs btn-error btn-outline relative z-10" onclick="openIncidentModal('incident_report_modal')">
          <span class="iconify lucide--plus size-3.5"></span>
          Incident Report
        </button>
        @endif
      </div>
      <div class="collapse-content bg-base-100">
        <div class="mt-4 space-y-3">
          @if ($hasUnconfirmedIncidents)
            <div class="alert alert-soft alert-warning text-sm" role="alert">
              <span class="iconify lucide--info size-4"></span>
              <span>Click Confirm to move this appointment to Issue.</span>
            </div>
          @endif
          @if ($incidentReports->isEmpty())
            <p class="text-sm text-base-content/60">No incidents reported for this appointment.</p>
          @endif
          @foreach ($incidentReports as $incident)
          <div class="rounded-box collapse collapse-arrow">
            <input aria-label="Collapse trigger" type="checkbox" {{ $incident->isResolved() ? '' : 'checked' }} />
            <div class="collapse-title py-2 font-medium flex flex-wrap items-center gap-2">
              <span>Incident #{{ $incident->id }}</span>
              <span class="text-base-content/60 text-sm font-normal">{{ $incident->created_at->format('M j, Y g:i A') }}</span>
              @include('reports.partials.incident-details', ['incidentDetailsPart' => 'badge'])
            </div>
            <div class="collapse-content text-sm">
              @include('reports.partials.incident-details')

              <div class="mt-4 flex justify-end gap-2">
                @if ($canEditIncident)
                <button type="button" class="btn btn-sm btn-ghost" onclick="openIncidentModal('incident_edit_modal_{{ $incident->id }}')">
                  <span class="iconify lucide--pencil size-4"></span>
                  Edit
                </button>
                @endif
                @if ($canDeleteIncident)
                <button type="button" class="btn btn-sm btn-error btn-outline" onclick="confirmDeleteIncident({{ $incident->id }})">
                  <span class="iconify lucide--trash size-4"></span>
                  Delete
                </button>
                @endif
                @if ($canResolveIncident && $incident->isOpen())
                <button type="button" class="btn btn-sm btn-success" onclick="confirmResolveIncident({{ $incident->id }})">
                  <span class="iconify lucide--check size-4"></span>
                  Resolve
                </button>
                @endif
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@if ($showIncidentSection && $canDeleteIncident && $incidentReports->isNotEmpty())
<dialog id="delete_incident_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      Delete Incident
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4" id="delete_incident_message"></p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost btn-sm">No</button>
      </form>
      <form method="POST" action="{{ route('delete-incident-report') }}" onsubmit="this.querySelector('button').disabled = true;">
        @csrf
        <input type="hidden" name="incident_report_id" id="delete_incident_id" value="" />
        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}" />
        <button class="btn btn-error btn-sm">Delete</button>
      </form>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
<script>
  function confirmDeleteIncident(incidentId) {
    document.getElementById('delete_incident_message').textContent =
      `Delete incident #${incidentId}? It will be removed from this appointment and from the incident report list. This can't be undone.`;
    document.getElementById('delete_incident_id').value = incidentId;
    document.getElementById('delete_incident_modal').showModal();
  }
</script>
@endif

@if ($canResolveIncident)
<dialog id="resolve_incident_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      Resolve Incident
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4" id="resolve_incident_message"></p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost btn-sm">No</button>
      </form>
      <form method="POST" action="{{ route('resolve-incident-report') }}" onsubmit="this.querySelector('button').disabled = true;">
        @csrf
        <input type="hidden" name="incident_report_id" id="resolve_incident_id" value="" />
        <button class="btn btn-success btn-sm">Resolve</button>
      </form>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
<script>
  function confirmResolveIncident(incidentId) {
    document.getElementById('resolve_incident_message').textContent =
      `Mark incident #${incidentId} as resolved? The report will be kept for review, and the appointment returns to {{ appointment_status_label('in_progress', $appointment->service) }} once no open incidents remain.`;
    document.getElementById('resolve_incident_id').value = incidentId;
    document.getElementById('resolve_incident_modal').showModal();
  }
</script>
@endif

@if (!empty($incidentModals))
@include('reports.partials.incident-form', ['incidentFormPart' => 'css', 'incidentFormScope' => '.incident-modal-form'])
@foreach ($incidentModals as $incidentModal)
@php $modalReport = $incidentModal['report']; @endphp
<dialog id="{{ $incidentModal['id'] }}" class="modal">
  <div class="modal-box max-w-5xl" style="max-height: 85vh; overflow-y: auto;">
    <div class="flex items-center justify-between text-lg font-medium">
      {{ $modalReport ? 'Edit Incident #' . $modalReport->id : 'Incident Report' }}
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <form class="incident-modal-form" method="POST" enctype="multipart/form-data"
      action="{{ $modalReport ? route('update-incident-report') : route('create-incident-report') }}">
      @csrf
      @if ($modalReport)
        <input type="hidden" name="incident_report_id" value="{{ $modalReport->id }}" />
      @else
        <input type="hidden" name="service_id" value="{{ $appointment->service_id }}" />
        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}" />
      @endif
      @include('reports.partials.incident-form', [
        'incidentFormPart' => 'fields',
        'incidentFieldPrefix' => $incidentModal['id'] . '_',
        'incidentFormReport' => $modalReport,
        'incidentPetsLocked' => true,
        'incidentSelectedPets' => $modalReport ? $modalReport->involved_pets : $appointment->family_pets,
        'incidentSelectedStaffs' => $modalReport ? $modalReport->involved_staffs : collect(),
      ])
    </form>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-sm btn-ghost">Cancel</button>
      </form>
      <button type="button" class="btn btn-sm btn-primary incident-modal-save" onclick="saveIncidentModal('{{ $incidentModal['id'] }}')">
        <span class="loading loading-spinner loading-sm" style="display: none;"></span>
        <span class="iconify lucide--check size-4"></span>
        {{ $modalReport ? 'Update' : 'Save' }}
      </button>
    </div>
  </div>
</dialog>
@endforeach
@include('reports.partials.incident-form', ['incidentFormPart' => 'js'])
<script>
  const initializedIncidentModals = {};

  // create / edit an incident report in a modal; the form is set up the first time it opens
  function openIncidentModal(modalId) {
    const $modal = $('#' + modalId);
    if (!initializedIncidentModals[modalId]) {
      initIncidentForm($modal.find('form.incident-modal-form'), {
        petsUrl: '{{ route("get-incident-eligible-pets", ["serviceId" => $appointment->service_id]) }}',
        dropdownParent: $modal
      });
      initializedIncidentModals[modalId] = true;
    }
    $modal[0].showModal();
  }

  function saveIncidentModal(modalId) {
    const $modal = $('#' + modalId);
    const $form = $modal.find('form.incident-modal-form');
    const $button = $modal.find('.incident-modal-save');
    if ($button.prop('disabled') || !validateIncidentForm($form)) {
      return;
    }

    $button.prop('disabled', true).find('.loading').show();
    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: new FormData($form[0]),
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function () {
        window.location.reload();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        const errors = response.errors ? Object.values(response.errors).flat() : [];
        $('#alert_message').text(errors[0] || response.message || 'Failed to save the incident report. Please try again.');
        alert_modal.showModal();
        $button.prop('disabled', false).find('.loading').hide();
      }
    });
  }
</script>
@endif

<script>
  // In Progress / On Property: Confirm moves the appointment to Issue while it has an open incident report
  const hasUnconfirmedIncidents = {{ $hasUnconfirmedIncidents ? 'true' : 'false' }};

  function confirmIncidentReports() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("confirm-in-progress-appointment", $appointment->id) }}';
    form.style.display = 'none';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
  }
</script>
