{{-- Incident report form shared by the Create page and the Appointment Detail incident modals (create and edit).
     Include once per part with $incidentFormPart:
       'css'    - Select2 styling. Expects: $incidentFormScope (CSS selector of the form).
       'fields' - the form fields (default). Optional: $incidentFieldPrefix (element id prefix, so the fields can
                  live on a page with other forms), $incidentSelectedPets / $incidentSelectedStaffs (pre-selected
                  options), $incidentPetsLocked (show pets read-only), $incidentFormReport (an existing report whose values fill the fields, for editing).
       'js'     - behaviour. Fields are looked up by name inside the given form, so element ids don't matter. --}}
@php
  $incidentFormPart = $incidentFormPart ?? 'fields';
@endphp
@if ($incidentFormPart === 'css')
<style>
  {{ $incidentFormScope }} .select2-container--default .select2-selection--multiple {
    min-height: 40px;
    height: 40px;
    overflow-y: auto;
    overflow-x: hidden !important;
    white-space: normal !important;
  }

  {{ $incidentFormScope }} .select2-container--default .select2-selection--multiple .select2-selection__choice {
    margin-top: 10px !important;
    margin-left: 10px !important;
  }

  {{ $incidentFormScope }} .select2-container .select2-search--inline .select2-search__field {
    margin-top: 10px !important;
    margin-left: 10px !important;
  }
  /* A locked (disabled) picker only shows its selected tags, without the empty search box */
  {{ $incidentFormScope }} .select2-container--disabled .select2-search--inline {
    display: none !important;
  }

  {{ $incidentFormScope }} .select2-container--disabled .select2-selection,
  {{ $incidentFormScope }} .select2-container--disabled .select2-selection * {
    cursor: not-allowed !important;
  }

  /* Also ensure the dropdown fits the parent */
  {{ $incidentFormScope }} .select2-container {
    width: 100% !important;
    min-width: 0 !important;
  }
  /* The open dropdown is appended to <body> (or the modal); don't let the global 100% width stretch it past the viewport */
  body > .select2-container,
  dialog > .select2-container {
    width: auto !important;
  }
</style>
@elseif ($incidentFormPart === 'js')
@once
<script>
  /**
   * Set up the incident form: pets/staff pickers and the dependent owner-notes / vet fields.
   * options.petsUrl: eligible pets endpoint; options.dropdownParent: element to render dropdowns in (needed inside a modal).
   */
  function initIncidentForm($form, options) {
    const dropdownParent = options.dropdownParent ? { dropdownParent: options.dropdownParent } : {};

    $form.find('select[name="pets[]"]').select2(Object.assign({
      placeholder: "Choose pets",
      ajax: {
        url: options.petsUrl,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term // Send the search term as 'q'
          };
        },
        processResults: function (data) {
          return {
            results: data.map(function (pet) {
              return {
                id: pet.id,
                text: pet.name
              };
            })
          };
        }
      }
    }, dropdownParent));

    $form.find('[name="staffs[]"]').select2(Object.assign({
      placeholder: "Choose a staff",
      ajax: {
        url: '{{ route("get-appointment-staffs") }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term // Send the search term as 'q'
          };
        },
        processResults: function (data) {
          return {
            results: data.map(function (staff) {
              return {
                id: staff.id,
                text: staff.profile.first_name + " " + staff.profile.last_name,
                first_name: staff.profile.first_name,
                last_name: staff.profile.last_name,
                email: staff.email,
                phone_number: staff.profile.phone_number_1
              };
            })
          };
        }
      },
      templateResult: function (staff) {
        if (!staff.id) {
          return staff.text;
        }
        var $container = $(`
          <div class="flex items-center gap-2">
            <span class="font-medium">${staff.first_name} ${staff.last_name}</span>
            <span class="text-sm text-base-content/70">(${staff.email} | ${staff.phone_number})</span>
          </div>
        `);
        return $container;
      },
    }, dropdownParent));

    $form.find('input[name=contact_owner]').on('change', function() {
      const $notes = $form.find('[name=owner_conversation_notes]');
      if ($(this).val() === 'yes') {
        $notes.prop('disabled', false);
      } else {
        $notes.prop('disabled', true).val('');
      }
    });

    $form.find('input[name=treatment_type]').on('change', function() {
      const $vetFields = $form.find('[name=vet_name], [name=vet_bill], [name=vet_payment], [name=vet_results]');
      if ($(this).val() === 'vet') {
        $vetFields.prop('disabled', false);
      } else {
        $vetFields.prop('disabled', true).val('');
      }
    });
  }

  /** Edit form: drop a current picture (the server deletes pictures missing from current_pictures). */
  function removeIncidentPicture(button, pictureName) {
    const $input = $(button).closest('form').find('[name=current_pictures]');
    $input.val($input.val().split(',').filter(function (name) { return name && name !== pictureName; }).join(','));
    $(button).closest('.incident-picture-item').remove();
  }

  /** Required-field check; shows the alert modal and returns false when something is missing. */
  function validateIncidentForm($form) {
    const pets = $form.find('select[name="pets[]"]').val() || [];
    const staffs = $form.find('[name="staffs[]"]').val() || [];
    const injuryType = $form.find('[name=injury_type]').val();
    const injuryLocation = $form.find('[name=injury_location]').val();
    const needsTreatment = $form.find('input[name="needs_treatment"]:checked').val();
    const isEmergency = $form.find('input[name="is_emergency"]:checked').val();
    const contactOwner = $form.find('input[name="contact_owner"]:checked').val();
    const treatmentType = $form.find('input[name="treatment_type"]:checked').val();
    const conclusion = ($form.find('[name=conclusion]').val() || '').trim();

    if (pets.length === 0 || staffs.length === 0 || !injuryType || !injuryLocation || !needsTreatment || !isEmergency || !contactOwner || !treatmentType || !conclusion) {
      $('#alert_message').text('Please fill in all required fields.');
      alert_modal.showModal();
      return false;
    }

    return true;
  }
</script>
@endonce
@else
@php
  $p = $incidentFieldPrefix ?? '';
  $incidentSelectedPets = $incidentSelectedPets ?? collect();
  $incidentSelectedStaffs = $incidentSelectedStaffs ?? collect();
  // Appointment Detail modals: the pets are the appointment's, so "Pets involved" is shown but can't be changed
  $incidentPetsLocked = $incidentPetsLocked ?? false;
  // an existing report to edit (Appointment Detail edit modal); null for a new report
  $incidentFormReport = $incidentFormReport ?? null;
  $v = fn ($field) => $incidentFormReport ? $incidentFormReport->$field : null;
  $checked = fn ($field, $value) => $v($field) === $value ? 'checked' : '';
  $selected = fn ($field, $value) => $v($field) === $value ? 'selected' : '';
  // when editing, dependent fields start disabled unless their answer enables them (as on the Update page)
  $notesDisabled = $incidentFormReport && $v('contact_owner') !== 'yes' ? 'disabled' : '';
  $vetDisabled = $incidentFormReport && $v('treatment_type') !== 'vet' ? 'disabled' : '';
  $currentPictures = $incidentFormReport ? array_values(array_filter(array_map('trim', explode(',', $incidentFormReport->pictures ?? '')))) : [];
@endphp
<div class="fieldset mt-2 grid grid-cols-1 gap-4 lg:grid-cols-2">
  <div class="space-y-2">
    <label class="fieldset-label" for="{{ $p }}pets">Pets involved *</label>
    <select class="select w-full" name="pets[]" id="{{ $p }}pets" multiple {{ $incidentPetsLocked ? 'disabled' : '' }}>
      @foreach ($incidentSelectedPets as $pet)
        <option value="{{ $pet->id }}" selected>{{ $pet->name }}</option>
      @endforeach
    </select>
    @if ($incidentPetsLocked)
      {{-- a disabled select isn't submitted; send the pets as hidden inputs --}}
      @foreach ($incidentSelectedPets as $pet)
        <input type="hidden" name="pets[]" value="{{ $pet->id }}" />
      @endforeach
    @endif
  </div>
  <div class="space-y-2">
    <label class="fieldset-label" for="{{ $p }}staffs">Staffs present *</label>
    <select class="select w-full" name="staffs[]" id="{{ $p }}staffs" multiple>
      @foreach ($incidentSelectedStaffs as $staff)
        <option value="{{ $staff->id }}" selected>{{ optional($staff->profile)->first_name }} {{ optional($staff->profile)->last_name }}</option>
      @endforeach
    </select>
  </div>
</div>
<div class="fieldset mt-4">
  <label class="fieldset-label" for="{{ $p }}description">Description of incident</label>
  <textarea class="textarea w-full" name="description" id="{{ $p }}description" rows="3" placeholder="Describe the incident...">{{ $v('incident_description') }}</textarea>
</div>
<div class="fieldset mt-1">
  @if ($incidentFormReport)
    <input type="hidden" name="current_pictures" value="{{ implode(',', $currentPictures) }}" />
    @if (!empty($currentPictures))
    <div class="mb-4">
      <label class="fieldset-label text-sm">Current Pictures:</label>
      <div class="mt-2 flex flex-wrap gap-2">
        @foreach ($currentPictures as $picture)
        <div class="relative incident-picture-item">
          <a href="{{ asset('storage/reports/' . $picture) }}" target="_blank" rel="noopener">
            <img src="{{ asset('storage/reports/' . $picture) }}" alt="Incident Picture" class="w-24 h-24 object-cover rounded-lg border" />
          </a>
          <button type="button" class="absolute -top-2 -right-2 btn btn-xs btn-error btn-circle" aria-label="Remove picture" onclick="removeIncidentPicture(this, '{{ $picture }}')">
            <span class="iconify lucide--x size-2"></span>
          </button>
        </div>
        @endforeach
      </div>
    </div>
    @endif
  @endif
  <fieldset class="fieldset gap-1">
    <legend class="fieldset-legend">Pictures</legend>
    <input aria-label="File" class="file-input w-full" type="file" name="pictures[]" multiple />
    <span class="fieldset-label" style="margin-left: 4px; font-size: 13px;">Upload multiple photos to record an incident</span>
  </fieldset>
</div>
<div class="fieldset mt-5 space-y-2">
  <label class="fieldset-label font-semibold">Triage/Assessment</label>
  <div class="grid grid-cols-1 gap-6 xl:grid-cols-4 px-1">
    <div class="space-y-1">
      <label class="fieldset-label" for="{{ $p }}injury_type">What type of injury? *</label>
      <select class="select w-full" name="injury_type" id="{{ $p }}injury_type">
        <option value="" hidden>Choose a injury type</option>
        <option value="bite" {{ $selected('injury_type', 'bite') }}>Bite</option>
        <option value="laceration" {{ $selected('injury_type', 'laceration') }}>Laceration</option>
        <option value="tear" {{ $selected('injury_type', 'tear') }}>Tear</option>
        <option value="puncture" {{ $selected('injury_type', 'puncture') }}>Puncture</option>
        <option value="fracture" {{ $selected('injury_type', 'fracture') }}>Fracture</option>
        <option value="other" {{ $selected('injury_type', 'other') }}>Other</option>
      </select>
    </div>
    <div class="space-y-1">
      <label class="fieldset-label" for="{{ $p }}injury_location">Where is the injury? *</label>
      <input type="text" class="input w-full" name="injury_location" id="{{ $p }}injury_location" placeholder="E.g., Left front leg" value="{{ $v('injury_location') }}" />
    </div>
    <div class="space-y-3">
      <label class="fieldset-label">Injury need treatment? *</label>
      <div class="flex gap-6">
        <label class="label cursor-pointer">
          <input type="radio" name="needs_treatment" value="yes" class="radio radio-primary radio-sm" {{ $checked('needs_treatment', 'yes') }}>
          <span class="label-text ml-2">Yes</span>
        </label>
        <label class="label cursor-pointer">
          <input type="radio" name="needs_treatment" value="no" class="radio radio-primary radio-sm" {{ $checked('needs_treatment', 'no') }}>
          <span class="label-text ml-2">No</span>
        </label>
      </div>
    </div>
    <div class="space-y-3">
      <label class="fieldset-label">Is this an emergency? *</label>
      <div class="flex gap-6">
        <label class="label cursor-pointer">
          <input type="radio" name="is_emergency" value="yes" class="radio radio-primary radio-sm" {{ $checked('is_emergency', 'yes') }}>
          <span class="label-text ml-2">Yes</span>
        </label>
        <label class="label cursor-pointer">
          <input type="radio" name="is_emergency" value="no" class="radio radio-primary radio-sm" {{ $checked('is_emergency', 'no') }}>
          <span class="label-text ml-2">No</span>
        </label>
      </div>
    </div>
  </div>
</div>
<div class="fieldset mt-5 space-y-2">
  <label class="fieldset-label font-semibold">Actions taken</label>
  <div class="grid grid-cols-1 gap-4 xl:grid-cols-2 px-1">
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="fieldset-label">Contact owner? *</label>
        <div class="flex gap-6 px-4">
          <label class="label cursor-pointer">
            <input type="radio" name="contact_owner" value="yes" class="radio radio-primary radio-sm" {{ $checked('contact_owner', 'yes') }}>
            <span class="label-text ml-2">Yes</span>
          </label>
          <label class="label cursor-pointer">
            <input type="radio" name="contact_owner" value="no" class="radio radio-primary radio-sm" {{ $checked('contact_owner', 'no') }}>
            <span class="label-text ml-2">No (left message or do not contact)</span>
          </label>
        </div>
      </div>
      <div class="space-y-1 px-4">
        <label class="fieldset-label" for="{{ $p }}owner_conversation_notes">If yes, notes from conversation</label>
        <textarea class="textarea w-full" name="owner_conversation_notes" id="{{ $p }}owner_conversation_notes" rows="3" placeholder="Enter notes..." {{ $notesDisabled }}>{{ $v('owner_conversation_notes') }}</textarea>
      </div>
    </div>
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="fieldset-label">Treatment *</label>
        <div class="flex gap-6 px-4">
          <label class="label cursor-pointer">
            <input type="radio" name="treatment_type" value="in_house" class="radio radio-primary radio-sm" {{ $checked('treatment_type', 'in_house') }}>
            <span class="label-text ml-2">In-house</span>
          </label>
          <label class="label cursor-pointer">
            <input type="radio" name="treatment_type" value="vet" class="radio radio-primary radio-sm" {{ $checked('treatment_type', 'vet') }}>
            <span class="label-text ml-2">Vet</span>
          </label>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-2 xl:grid-cols-2 px-4">
        <div class="space-y-1">
          <label class="fieldset-label" for="{{ $p }}vet_name">Which vet</label>
          <input class="input w-full input-sm" name="vet_name" id="{{ $p }}vet_name" placeholder="Enter vet name..." value="{{ $v('vet_name') }}" {{ $vetDisabled }}/>
        </div>
        <div class="space-y-1">
          <label class="fieldset-label" for="{{ $p }}vet_bill">Bill</label>
          <label class="input w-full focus:outline-0 input-sm">
            <input class="grow focus:outline-0" placeholder="e.g. 100.00" id="{{ $p }}vet_bill" name="vet_bill" type="text" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="{{ $v('vet_bill') }}" {{ $vetDisabled }}/>
            <span class="badge badge-ghost badge-sm">USD</span>
          </label>
        </div>
        <div class="space-y-1">
          <label class="fieldset-label" for="{{ $p }}vet_payment">Payment</label>
          <input class="input w-full input-sm" name="vet_payment" id="{{ $p }}vet_payment" placeholder="Enter vet payment..." value="{{ $v('vet_payment') }}" {{ $vetDisabled }}/>
        </div>
        <div class="space-y-1">
          <label class="fieldset-label" for="{{ $p }}vet_results">Vet visit resolution</label>
          <select class="select w-full select-sm" name="vet_results" id="{{ $p }}vet_results" {{ $vetDisabled }}>
            <option value="" hidden>Choose a resolution</option>
            <option value="diagnosis" {{ $selected('vet_results', 'diagnosis') }}>Diagnosis</option>
            <option value="prognosis" {{ $selected('vet_results', 'prognosis') }}>Prognosis</option>
            <option value="treatment" {{ $selected('vet_results', 'treatment') }}>Treatment</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="fieldset mt-2 space-y-1">
  <label class="fieldset-label" for="{{ $p }}conclusion">Conclusion *</label>
  <textarea class="textarea w-full" name="conclusion" id="{{ $p }}conclusion" rows="3" placeholder="How do we proceed after conversation with owner">{{ $v('conclusion') }}</textarea>
</div>
@endif
