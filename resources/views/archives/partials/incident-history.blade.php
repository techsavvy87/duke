{{-- Incident history (resolved and unresolved) inside the service Report. Expects: $appointment. --}}
@php
  $reportIncidents = $appointment->incident_reports;
@endphp
@if ($reportIncidents->isNotEmpty())
<div class="mt-4 text-sm">
  <p class="font-medium text-sm text-base-content/80 mb-2">Incident Reports ({{ $reportIncidents->count() }}):</p>
  <div class="space-y-4 ms-2">
    @foreach ($reportIncidents as $incident)
    <div class="border-base-300 border-t pt-3 first:border-t-0 first:pt-0">
      <div class="flex flex-wrap items-center gap-2 mb-2">
        <span class="font-medium">Incident #{{ $incident->id }}</span>
        <span class="text-base-content/60">{{ $incident->created_at->format('M j, Y g:i A') }}</span>
        @include('reports.partials.incident-details', ['incidentDetailsPart' => 'badge'])
      </div>
      @include('reports.partials.incident-details')
    </div>
    @endforeach
  </div>
</div>
@endif
