{{-- Read-only service report for a completed appointment. The body is the archive report (archive-report-fragment)
     and Export PDF uses the same per-service report PDFs as the Archived Appointment page. Expects: $appointment. --}}
@php
  $serviceReportPdfRoute = null;
  if (isGroomingService($appointment->service)) {
    $serviceReportPdfRoute = 'export-grooming-report-pdf';
  } elseif (isDaycareService($appointment->service)) {
    $serviceReportPdfRoute = 'export-daycare-report-pdf';
  } elseif (isPrivateTrainingService($appointment->service)) {
    $serviceReportPdfRoute = 'export-training-report-pdf';
  } elseif (isGroupClassService($appointment->service)) {
    $serviceReportPdfRoute = 'export-group-class-report-pdf';
  } elseif (isAlaCarteService($appointment->service)) {
    $serviceReportPdfRoute = 'export-ala-carte-report-pdf';
  } elseif (isPackageService($appointment->service)) {
    $serviceReportPdfRoute = 'export-package-report-pdf';
  }
@endphp
<div class="card card-border bg-base-100 mt-3">
  <div class="card-body gap-0">
    <div class="bg-base-200 rounded-box collapse collapse-arrow">
      <input aria-label="Collapse trigger" type="checkbox" name="accordion-multiple" />
      <div class="collapse-title font-medium py-1">{{ $appointment->service->name }} Report</div>
      <div class="collapse-content bg-base-100">
        <div id="service_report_content" class="mt-4 text-sm" data-fetch-url="{{ route('archive-report-fragment', $appointment->id) }}">
          <div class="flex justify-center py-8"><span class="loading loading-spinner loading-md"></span></div>
        </div>
        @if ($serviceReportPdfRoute)
        <div class="mt-4 flex justify-end">
          <a href="{{ route($serviceReportPdfRoute, $appointment->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-download-icon lucide-cloud-download"><path d="M12 13v8l-4-4"/><path d="m12 21 4-4"/><path d="M4.393 15.269A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.436 8.284"/></svg>
            Export PDF
          </a>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
