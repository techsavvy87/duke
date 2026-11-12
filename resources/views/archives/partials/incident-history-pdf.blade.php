{{-- PDF version of incident-history: every incident report (resolved and unresolved) for the appointment.
     Uses the .section / .field classes shared by the service report PDFs. Expects: $appointment. --}}
@php
  $pdfIncidents = $appointment->incident_reports;
  $pdfIncidentLabels = [
    'injury_type' => ['bite' => 'Bite', 'laceration' => 'Laceration', 'tear' => 'Tear', 'puncture' => 'Puncture', 'fracture' => 'Fracture', 'other' => 'Other'],
    'yes_no' => ['yes' => 'Yes', 'no' => 'No'],
    'contact_owner' => ['yes' => 'Yes', 'no' => 'No (left message or do not contact)'],
    'treatment_type' => ['in_house' => 'In-house', 'vet' => 'Vet'],
    'vet_results' => ['diagnosis' => 'Diagnosis', 'prognosis' => 'Prognosis', 'treatment' => 'Treatment'],
  ];
  $pdfIncidentStatus = [
    'resolved' => ['Resolved', '#16a34a'],
    'open' => ['Open', '#dc2626'],
  ];
  $pdfStaffName = function ($staff) {
    return $staff && $staff->profile ? trim($staff->profile->first_name . ' ' . $staff->profile->last_name) : optional($staff)->name;
  };
@endphp
@if ($pdfIncidents->isNotEmpty())
<div class="section">
  <h2 class="section-title">Incident Reports</h2>
  @foreach ($pdfIncidents as $incident)
    @php
      [$statusLabel, $statusColor] = $pdfIncidentStatus[$incident->status] ?? $pdfIncidentStatus['open'];
    @endphp
    <div style="margin-bottom: 12px; {{ $loop->first ? '' : 'border-top: 1px solid #e2e8f0; padding-top: 10px;' }}">
      <div style="font-weight: bold; margin-bottom: 6px;">
        Incident #{{ $incident->id }} &middot; {{ $incident->created_at->format('M j, Y g:i A') }}
        <span style="color: {{ $statusColor }}; margin-left: 6px;">{{ $statusLabel }}</span>
      </div>
      <div class="field">
        <span class="field-label">Staffs present:</span>
        <span class="field-value">{{ $incident->involved_staffs->map($pdfStaffName)->filter()->implode(', ') ?: '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Description:</span>
        <span class="field-value" style="white-space: pre-wrap;">{{ $incident->incident_description ?: '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Type of injury:</span>
        <span class="field-value">{{ $pdfIncidentLabels['injury_type'][$incident->injury_type] ?? ($incident->injury_type ?: '-') }}</span>
      </div>
      <div class="field">
        <span class="field-label">Injury location:</span>
        <span class="field-value">{{ $incident->injury_location ?: '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Needs treatment:</span>
        <span class="field-value">{{ $pdfIncidentLabels['yes_no'][$incident->needs_treatment] ?? '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Emergency:</span>
        <span class="field-value">{{ $pdfIncidentLabels['yes_no'][$incident->is_emergency] ?? '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Contact owner:</span>
        <span class="field-value">{{ $pdfIncidentLabels['contact_owner'][$incident->contact_owner] ?? '-' }}</span>
      </div>
      @if ($incident->owner_conversation_notes)
      <div class="field">
        <span class="field-label">Owner notes:</span>
        <span class="field-value" style="white-space: pre-wrap;">{{ $incident->owner_conversation_notes }}</span>
      </div>
      @endif
      <div class="field">
        <span class="field-label">Treatment:</span>
        <span class="field-value">{{ $pdfIncidentLabels['treatment_type'][$incident->treatment_type] ?? '-' }}</span>
      </div>
      @if ($incident->treatment_type === 'vet')
      <div class="field">
        <span class="field-label">Vet:</span>
        <span class="field-value">{{ $incident->vet_name ?: '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Bill:</span>
        <span class="field-value">{{ !is_null($incident->vet_bill) ? '$' . number_format((float) $incident->vet_bill, 2) : '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Payment:</span>
        <span class="field-value">{{ $incident->vet_payment ?: '-' }}</span>
      </div>
      <div class="field">
        <span class="field-label">Vet visit resolution:</span>
        <span class="field-value">{{ $pdfIncidentLabels['vet_results'][$incident->vet_results] ?? '-' }}</span>
      </div>
      @endif
      <div class="field">
        <span class="field-label">Conclusion:</span>
        <span class="field-value" style="white-space: pre-wrap;">{{ $incident->conclusion ?: '-' }}</span>
      </div>
      @if ($incident->isResolved())
      <div class="field">
        <span class="field-label">Resolved:</span>
        <span class="field-value">
          {{ optional($incident->resolved_at)->format('M j, Y g:i A') }}
          @if ($incident->resolvedBy) by {{ $pdfStaffName($incident->resolvedBy) }} @endif
        </span>
      </div>
      @endif
      @if ($incident->pictures)
      <div style="margin-top: 8px;">
        @foreach (array_filter(array_map('trim', explode(',', $incident->pictures))) as $picture)
          @php $pictureSrc = publicPicturePdfSrc('reports', $picture); @endphp
          @if ($pictureSrc !== '')
            <img src="{{ $pictureSrc }}" style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e2e8f0; border-radius: 5px; margin: 0 6px 6px 0;" alt="Incident Picture">
          @endif
        @endforeach
      </div>
      @endif
    </div>
  @endforeach
</div>
@endif
