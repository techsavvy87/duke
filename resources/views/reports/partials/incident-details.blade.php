{{-- One incident report, shown on Appointment Detail and in the service Report. Expects: $incident.
     $incidentDetailsPart: 'details' (default) - read-only details; 'badge' - status badge. --}}
@php
  $incidentDetailsPart = $incidentDetailsPart ?? 'details';
@endphp
@if ($incidentDetailsPart === 'badge')
@if ($incident->isResolved())
  <span class="badge badge-soft badge-success badge-sm">Resolved</span>
@else
  <span class="badge badge-soft badge-error badge-sm">Open</span>
@endif
@else
@php
  $incidentLabels = [
    'injury_type' => ['bite' => 'Bite', 'laceration' => 'Laceration', 'tear' => 'Tear', 'puncture' => 'Puncture', 'fracture' => 'Fracture', 'other' => 'Other'],
    'yes_no' => ['yes' => 'Yes', 'no' => 'No'],
    'contact_owner' => ['yes' => 'Yes', 'no' => 'No (left message or do not contact)'],
    'treatment_type' => ['in_house' => 'In-house', 'vet' => 'Vet'],
    'vet_results' => ['diagnosis' => 'Diagnosis', 'prognosis' => 'Prognosis', 'treatment' => 'Treatment'],
  ];
  $incidentStaffName = function ($staff) {
    return $staff && $staff->profile ? trim($staff->profile->first_name . ' ' . $staff->profile->last_name) : optional($staff)->name;
  };
@endphp
<div class="grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-2">
  <div>
    <p class="font-medium">Staffs present</p>
    <p class="text-base-content/70">{{ $incident->involved_staffs->map($incidentStaffName)->filter()->implode(', ') ?: '-' }}</p>
  </div>
  <div class="md:col-span-2">
    <p class="font-medium">Description of incident</p>
    <p class="text-base-content/70 whitespace-pre-line">{{ $incident->incident_description ?: '-' }}</p>
  </div>
  @if ($incident->pictures)
  <div class="md:col-span-2">
    <p class="font-medium">Pictures</p>
    <div class="mt-1 flex flex-wrap gap-2">
      @foreach (array_filter(array_map('trim', explode(',', $incident->pictures))) as $picture)
        <a href="{{ asset('storage/reports/' . $picture) }}" target="_blank" rel="noopener">
          <img src="{{ asset('storage/reports/' . $picture) }}" alt="Incident Picture" class="w-20 h-20 object-cover rounded-lg border" />
        </a>
      @endforeach
    </div>
  </div>
  @endif
</div>

<p class="mt-4 font-semibold">Triage/Assessment</p>
<div class="mt-1 grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-4">
  <div>
    <p class="font-medium">Type of injury</p>
    <p class="text-base-content/70">{{ $incidentLabels['injury_type'][$incident->injury_type] ?? ($incident->injury_type ?: '-') }}</p>
  </div>
  <div>
    <p class="font-medium">Injury location</p>
    <p class="text-base-content/70">{{ $incident->injury_location ?: '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Needs treatment</p>
    <p class="text-base-content/70">{{ $incidentLabels['yes_no'][$incident->needs_treatment] ?? '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Emergency</p>
    <p class="text-base-content/70">{{ $incidentLabels['yes_no'][$incident->is_emergency] ?? '-' }}</p>
  </div>
</div>

<p class="mt-4 font-semibold">Actions taken</p>
<div class="mt-1 grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-2">
  <div>
    <p class="font-medium">Contact owner</p>
    <p class="text-base-content/70">{{ $incidentLabels['contact_owner'][$incident->contact_owner] ?? '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Treatment</p>
    <p class="text-base-content/70">{{ $incidentLabels['treatment_type'][$incident->treatment_type] ?? '-' }}</p>
  </div>
  @if ($incident->owner_conversation_notes)
  <div class="md:col-span-2">
    <p class="font-medium">Notes from owner conversation</p>
    <p class="text-base-content/70 whitespace-pre-line">{{ $incident->owner_conversation_notes }}</p>
  </div>
  @endif
  @if ($incident->treatment_type === 'vet')
  <div>
    <p class="font-medium">Vet</p>
    <p class="text-base-content/70">{{ $incident->vet_name ?: '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Bill</p>
    <p class="text-base-content/70">{{ !is_null($incident->vet_bill) ? '$' . number_format((float) $incident->vet_bill, 2) : '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Payment</p>
    <p class="text-base-content/70">{{ $incident->vet_payment ?: '-' }}</p>
  </div>
  <div>
    <p class="font-medium">Vet visit resolution</p>
    <p class="text-base-content/70">{{ $incidentLabels['vet_results'][$incident->vet_results] ?? '-' }}</p>
  </div>
  @endif
</div>

<div class="mt-4">
  <p class="font-semibold">Conclusion</p>
  <p class="text-base-content/70 whitespace-pre-line">{{ $incident->conclusion ?: '-' }}</p>
</div>

@if ($incident->isResolved())
  <p class="mt-4 text-base-content/60">
    Resolved {{ optional($incident->resolved_at)->format('M j, Y g:i A') }}
    @if ($incident->resolvedBy) by {{ $incidentStaffName($incident->resolvedBy) }} @endif
  </p>
@endif
@endif
