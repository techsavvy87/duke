{{-- Service notes and behavior recorded with the service report (checkout flows). Expects: $appointment, $checkout. --}}
@php
  $extraFlows = (isset($checkout) && $checkout && is_array($checkout->flows)) ? $checkout->flows : [];
  $extraServiceNotes = $extraFlows['service_notes'] ?? '';
  $extraBehaviors = getBehaviorDisplayItems($extraFlows['behavior_ids'] ?? []);
@endphp
@if (!isBoardingService($appointment->service) && (filled($extraServiceNotes) || !empty($extraBehaviors)))
<div class="mt-4 space-y-3 text-sm">
  @if (filled($extraServiceNotes))
  <div>
    <p class="font-medium text-sm text-base-content/80 mb-1">Notes for {{ $appointment->service->name }}:</p>
    <p class="text-sm text-base-content/70 whitespace-pre-wrap ms-2">{{ $extraServiceNotes }}</p>
  </div>
  @endif
  @if (!empty($extraBehaviors))
  <div>
    <p class="font-medium text-sm text-base-content/80 mb-1">Behavior:</p>
    <div class="space-y-1 ms-2">
      @foreach ($extraBehaviors as $behavior)
        <div class="flex items-center gap-2">
          @if (!empty($behavior['icon_markup']))
            <span class="service-report-behavior-icon inline-flex items-center justify-center shrink-0" style="width: 24px; height: 24px;">{!! $behavior['icon_markup'] !!}</span>
          @endif
          <span class="text-sm text-base-content/70">{{ $behavior['label'] ?: 'N/A' }}</span>
        </div>
      @endforeach
    </div>
  </div>
  @endif
</div>
@once
<style>
  .service-report-behavior-icon svg { width: 24px; height: 24px; }
</style>
@endonce
@endif
