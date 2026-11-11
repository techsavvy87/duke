{{-- PDF version of service-report-extras: service notes and behavior (icon + name) from the checkout flows.
     Expects: $appointment, $checkout; optional: $skipServiceNotes when the report already prints them. --}}
@php
  $pdfExtraFlows = (isset($checkout) && $checkout && is_array($checkout->flows)) ? $checkout->flows : [];
  $pdfExtraServiceNotes = empty($skipServiceNotes) ? ($pdfExtraFlows['service_notes'] ?? '') : '';
  $pdfExtraBehaviors = getBehaviorDisplayItems($pdfExtraFlows['behavior_ids'] ?? []);
@endphp
@if (filled($pdfExtraServiceNotes) || !empty($pdfExtraBehaviors))
<div class="section">
  <h2 class="section-title">Service Notes &amp; Behavior</h2>
  @if (filled($pdfExtraServiceNotes))
  <div style="margin-bottom: 8px;">
    <div style="font-weight: bold; margin-bottom: 4px;">Notes for {{ $appointment->service->name }}:</div>
    <div style="white-space: pre-wrap;">{{ $pdfExtraServiceNotes }}</div>
  </div>
  @endif
  @if (!empty($pdfExtraBehaviors))
  <div>
    <div style="font-weight: bold; margin-bottom: 4px;">Behavior:</div>
    @foreach ($pdfExtraBehaviors as $behavior)
      @php
        $behaviorIconDataUri = behaviorIconPdfDataUri($behavior['icon_markup'] ?? '');
      @endphp
      <div style="margin-bottom: 5px;">
        @if ($behaviorIconDataUri !== '')
          <img src="{{ $behaviorIconDataUri }}" alt="" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 4px;" />
        @endif
        <span style="vertical-align: middle;">{{ $behavior['label'] ?: 'N/A' }}</span>
      </div>
    @endforeach
  </div>
  @endif
</div>
@endif
