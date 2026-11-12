@extends('layouts.main')
@section('title', 'Create Incident Report')

@section('page-css')
<link rel="stylesheet" href="{{ asset('src/libs/select2/select2.min.css') }}" />
@include('reports.partials.incident-form', ['incidentFormPart' => 'css', 'incidentFormScope' => '#create_form'])
@endsection

@section('content')
<div class="flex items-center justify-between">
  <h3 class="text-lg font-medium">Create Incident Report</h3>
  <div class="breadcrumbs hidden p-0 text-sm sm:inline">
    <ul>
      <li><a href="{{ route('dashboard') }}">PawPrints</a></li>
      <li><a href="{{ route('list-incident-reports', ['serviceId' => $serviceId]) }}">Incident Reports</a></li>
      <li class="opacity-80">Create</li>
    </ul>
  </div>
</div>
<div class="mt-3">
  @include('layouts.alerts')
  <form action="{{ route('create-incident-report') }}" method="POST" enctype="multipart/form-data" id="create_form">
    @csrf
    <input type="hidden" name="service_id" value="{{ $serviceId }}" />
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        @include('reports.partials.incident-form')
      </div>
    </div>
    <div class="mt-6 flex justify-end gap-3">
      <div class="flex gap-3">
        <a class="btn btn-sm btn-ghost" href="{{ url()->previous() }}">
          <span class="iconify lucide--x size-4"></span>
          Cancel
        </a>
        <button type="button" class="btn btn-sm btn-primary" onclick="createReport()">
          <span class="iconify lucide--check size-4"></span>
          Create
        </button>
      </div>
    </div>
  </form>
</div>
@endsection

@section('page-js')
<script src="{{ asset('src/libs/select2/select2.min.js') }}"></script>
@include('reports.partials.incident-form', ['incidentFormPart' => 'js'])
<script>
  $(document).ready(function() {
    initIncidentForm($('#create_form'), {
      petsUrl: '{{ route("get-incident-eligible-pets", ["serviceId" => $serviceId]) }}'
    });
  });

  function createReport() {
    if (!validateIncidentForm($('#create_form'))) {
      return;
    }

    $('#create_form').submit();
  }
</script>
@endsection
