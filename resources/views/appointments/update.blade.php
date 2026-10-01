@extends('layouts.main')
@section('title', 'Update Appointment')

@section('page-css')
  <link rel="stylesheet" href="{{ asset('src/libs/select2/select2.min.css') }}" />
  <style>
    .select2-container--default .select2-selection--multiple {
      min-height: 40px;
      height: 40px;
      overflow-y: auto;
      overflow-x: hidden !important;
      white-space: normal !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      margin-top: 10px !important;
      margin-left: 10px !important;
    }

    .select2-container .select2-search--inline .select2-search__field {
      margin-top: 10px !important;
      margin-left: 10px !important;
    }
    /* Also ensure the dropdown fits the parent */
    .select2-container {
      width: 100% !important;
      min-width: 0 !important;
    }
    .select2-container--default.select2-container--disabled .select2-selection--multiple {
      background-color: unset !important;
    }

    /*
      Prevent the browser from jumping back to the top when dynamic Select2
      fields are rebuilt/shown/hidden while the user is scrolling.
    */
    html,
    body {
      overflow-anchor: none;
      scroll-behavior: auto !important;
      overflow-x: hidden !important;
    }

    /*
      Select2 appends its dropdown to the page body by default.
      On this layout it can increase document width and create a horizontal
      scrollbar while the dropdown is open. Keep the dropdown inside the
      visible viewport and prevent long option text from stretching the page.
    */
    .select2-container--open,
    .select2-dropdown {
      max-width: calc(100vw - 32px) !important;
      box-sizing: border-box !important;
    }

    .select2-dropdown {
      overflow-x: hidden !important;
    }

    .select2-search--dropdown .select2-search__field {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }

    .select2-results__option,
    .select2-selection__rendered {
      max-width: 100% !important;
      overflow-x: hidden !important;
      text-overflow: ellipsis;
    }

    .select2-results__option {
      white-space: normal !important;
      word-break: break-word !important;
    }

    /*
      Boarding uses sunshine-laravel's form: one four column grid, in sunshine's field order.
      The other services keep the rows below as they are.
    */
    #service_fields.boarding-form-layout {
      display: grid;
      grid-template-columns: repeat(1, minmax(0, 1fr));
      gap: 1.5rem;
      margin-top: 0.5rem;
      padding-block: 0.25rem;
    }

    @media (min-width: 1280px) {
      #service_fields.boarding-form-layout {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }
    }

    #service_fields.boarding-form-layout > .fieldset:not(.hidden) {
      display: contents;
    }

    #service_fields.boarding-form-layout #service_group { order: 1; }
    #service_fields.boarding-form-layout #boarding_start_group { order: 2; }
    #service_fields.boarding-form-layout #boarding_end_group { order: 3; }
    #service_fields.boarding-form-layout #room_group { order: 4; }
    #service_fields.boarding-form-layout #kennel_group { order: 5; }
    #service_fields.boarding-form-layout #family_kennel_assignments_group { order: 6; grid-column: 1 / -1; }
    #service_fields.boarding-form-layout #additional_services_group { order: 7; grid-column: auto; }
    #service_fields.boarding-form-layout #time_slot_group { order: 8; }
    #service_fields.boarding-form-layout #staff_group,
    #service_fields.boarding-form-layout #staff_group > div { order: 9; }
    #service_fields.boarding-form-layout #appointment_status_group { order: 10; }
    #service_fields.boarding-form-layout #appointment_notes_group { order: 11; grid-column: 1 / -1; }
    #service_fields.boarding-form-layout #wait_listed_group { order: 12; grid-column: 1 / -1; }
  </style>
@endsection

@section('content')
<div class="flex items-center justify-between">
  <h3 class="text-lg font-medium">Update Appointment</h3>
  <div class="breadcrumbs hidden p-0 text-sm sm:inline">
    <ul>
      <li><a href="{{ route('dashboard') }}">PawPrints</a></li>
      <li><a href="{{ route('appointments') }}">Appointments</a></li>
      <li class="opacity-80">Update</li>
    </ul>
  </div>
</div>
<div class="mt-3">
  @include('layouts.alerts')
  <form action="{{ route('update-appointment') }}" method="POST" id="update_form">
    @csrf
    <input type="hidden" name="appointment_id" id="appointment_id" value="{{ $appointment->id }}" />
    <input type="hidden" name="status" id="form_status" value="" />
    <input type="hidden" name="allow_assignment_conflict" id="allow_assignment_conflict" value="0" />
    <input type="hidden" name="assignment_conflict_info" id="assignment_conflict_info" value="" />
    @if(isPackageService($appointment->service))
      <input type="hidden" name="customer" value="{{ $appointment->customer_id }}" />
      <input type="hidden" name="service" value="{{ $appointment->service_id }}" />
      @php
        $selectedPackageId = $appointment->metadata && isset($appointment->metadata['package_id']) ? $appointment->metadata['package_id'] : null;
      @endphp
      @if($selectedPackageId)
        <input type="hidden" name="package_id" value="{{ $selectedPackageId }}" />
      @endif
    @endif
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        <div class="fieldset mt-2 grid grid-cols-1 gap-6 xl:grid-cols-2">
          <div class="space-y-2">
            <label class="fieldset-label" for="customer">Customer*</label>
            <select class="select w-full" name="customer" id="customer" {{ isPackageService($appointment->service) ? 'disabled' : '' }}>
              <option value="" hidden selected>Choose a customer</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="fieldset-label" for="pet">Pet*</label>
            <select class="select w-full" name="pet" id="pet">
              <option value="" hidden selected>Choose a pet</option>
            </select>
          </div>
        </div>
        <div id="service_fields">
        <div class="fieldset mt-2 grid grid-cols-1 gap-6 xl:grid-cols-3">
          <div class="space-y-2" id="service_group">
            <label class="fieldset-label" for="service">Service*</label>
            <select class="select w-full" name="service" id="service" onchange="changeService(this)" value="{{ $appointment->service_id }}" {{ isPackageService($appointment->service) ? 'disabled' : '' }}>
              <option value="" hidden selected>Choose a service</option>
              @foreach($services as $service)
                <option value="{{ $service->id }}" {{ $service->id == $appointment->service_id ? 'selected' : '' }}>{{ $service->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="xl:col-span-2" id="additional_services_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="additional_services">Additional Services</label>
              <div id="additional_services_single_wrapper">
                <select class="select w-full" name="additional_services[]" id="additional_services" multiple data-selected="{{ $appointment->additional_service_ids }}">
                  @foreach($additionalServices as $service)
                    @php
                      $selectedAdditionalServices = $appointment->additional_service_ids ? explode(',', $appointment->additional_service_ids) : [];
                    @endphp
                    <option value="{{ $service->id }}" {{ in_array($service->id, $selectedAdditionalServices) ? 'selected' : '' }}>{{ $service->name }}</option>
                  @endforeach
                </select>
              </div>
              <div id="additional_services_by_pet_container" class="hidden space-y-3"></div>
            </div>
          </div>
          <div class="xl:col-span-2 hidden" id="secondary_services_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="secondary_services">Grooming Services*</label>
              <select class="select w-full" name="secondary_services[]" id="secondary_services" multiple>
                @php
                  $selectedSecondaryServices = $appointment->metadata && isset($appointment->metadata['secondary_service_ids']) ? explode(',', $appointment->metadata['secondary_service_ids']) : [];
                @endphp
                @foreach($secondaryServices as $service)
                  <option value="{{ $service->id }}" {{ in_array((string)$service->id, $selectedSecondaryServices) ? 'selected' : '' }}>{{ $service->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="xl:col-span-2 hidden" id="group_classes_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="group_classes">Group Classes*</label>
              @php
                $selectedGroupClasses = $appointment->metadata && isset($appointment->metadata['group_class_ids']) ? explode(',', $appointment->metadata['group_class_ids']) : [];
              @endphp
              <select class="select w-full" name="group_class_ids[]" id="group_classes" multiple disabled>
                @isset($groupClasses)
                  @foreach($groupClasses as $cls)
                    <option value="{{ $cls->id }}" {{ in_array((string)$cls->id, $selectedGroupClasses) ? 'selected' : '' }}>{{ $cls->name }}</option>
                  @endforeach
                @endisset
              </select>
              <div id="group_classes_details" class="mt-3 space-y-2"></div>
            </div>
          </div>
          <div class="xl:col-span-2 {{ isPackageService($appointment->service) ? '' : 'hidden' }}" id="packages_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="packages">Packages*</label>
              <select class="select w-full" name="package_id" id="packages" {{ isPackageService($appointment->service) ? 'disabled' : '' }}>
                @isset($packages)
                  @php
                    $selectedPackageId = $appointment->metadata && isset($appointment->metadata['package_id']) ? $appointment->metadata['package_id'] : null;
                  @endphp
                  @if(!$selectedPackageId)
                    <option value="" hidden selected>Choose a package</option>
                  @endif
                  @foreach($packages as $package)
                    <option value="{{ $package->id }}" data-package='@json($package)' {{ $selectedPackageId == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                  @endforeach
                @endisset
              </select>
              <div id="packages_details" class="mt-3 space-y-2"></div>
            </div>
          </div>
        </div>
        <div class="fieldset mt-3 grid grid-cols-1 gap-6 xl:grid-cols-3">
          <input type="hidden" id="date" name="date" />
          <div class="space-y-2" id="date_group">
            <label class="fieldset-label" for="date">Date*</label>
            <div class="dropdown w-full">
              <div role="button" class="btn btn-outline border-base-300 flex items-center gap-2" tabindex="0">
                <span class="iconify lucide--calendar text-base-content/60 size-4"></span>
                <p class="text-start" id="button_cally_target">{{ $appointment->date ? Carbon\Carbon::parse($appointment->date)->format('Y-m-d') : '-' }}</p>
                <span class="iconify lucide--chevron-down text-base-content/70 size-4"></span>
              </div>
              <div class="dropdown-content mt-2" tabindex="0">
                <calendar-date class="cally bg-base-100 rounded-box shadow-md transition-all hover:shadow-lg" id="button_cally_element" value="{{ $appointment->date ? Carbon\Carbon::parse($appointment->date)->format('Y-m-d') : '-' }}" >
                  <span class="iconify lucide--chevron-left" slot="previous"></span>
                  <span class="iconify lucide--chevron-right" slot="next"></span>
                  <calendar-month></calendar-month>
                </calendar-date>
              </div>
            </div>
          </div>
          <div class="space-y-2 {{ $appointment->service && str_contains(strtolower($appointment->service->category->name), 'daycare') ? '' : 'hidden' }}" id="daycare_duration_group">
            <label class="fieldset-label" for="daycare_duration">Duration*</label>
            <select class="select w-full" name="daycare_duration" id="daycare_duration">
              <option value="" hidden selected>Choose duration</option>
              <option value="half" {{ $appointment->metadata && isset($appointment->metadata['daycare_duration']) && $appointment->metadata['daycare_duration'] === 'half_day' ? 'selected' : '' }}>Half Day</option>
              <option value="full" {{ $appointment->metadata && isset($appointment->metadata['daycare_duration']) && $appointment->metadata['daycare_duration'] === 'full_day' ? 'selected' : '' }}>Full Day</option>
            </select>
          </div>
          <div class="space-y-2 {{ $appointment->service && str_contains(strtolower($appointment->service->category->name), 'training') ? '' : 'hidden' }}" id="private_training_duration_group">
            <label class="fieldset-label" for="private_training_duration">Duration*</label>
            <select class="select w-full" name="private_training_duration" id="private_training_duration">
              <option value="" hidden selected>Choose duration</option>
              <option value="half" {{ $appointment->metadata && isset($appointment->metadata['private_training_duration']) && $appointment->metadata['private_training_duration'] === 'half_hour' ? 'selected' : '' }}>Half Hour</option>
              <option value="one" {{ $appointment->metadata && isset($appointment->metadata['private_training_duration']) && $appointment->metadata['private_training_duration'] === 'one_hour' ? 'selected' : '' }}>One Hour</option>
            </select>
          </div>
          <div class="space-y-2" id="time_slot_group">
            <label class="fieldset-label" for="time_slot">Start Time - End Time*</label>
            @if (isAlaCarteService($appointment->service))
            <input type="text" value="{{ $appointment->start_time }} - {{ $appointment->end_time}}" disabled class="input w-full bg-base-200" />
            @else
            @php
              $selectedTimeSlotId = $appointment->metadata['additional_service_time_slot_id'] ?? null;
              $selectedTimeSlotStart = $appointment->metadata['additional_service_time_slot_start_time'] ?? $appointment->start_time;
            @endphp
            <div id="single_time_slot_wrapper">
              <select class="select w-full" name="time_slot" id="time_slot">
                <option value="" hidden selected>Choose a time slot</option>
                @foreach ($timeSlots as $slot)
                  @if (isBoardingService($appointment->service))
                    <option value="{{ $slot->id }}" {{ (string) $slot->id === (string) $selectedTimeSlotId || $slot->start_time == $selectedTimeSlotStart ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</option>
                  @else
                    <option value="{{ $slot->id }}" {{ $slot->start_time == $appointment->start_time ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</option>
                  @endif
                @endforeach
              </select>
              <input type="hidden" name="time_slot_data" id="time_slot_data" />
            </div>
            @endif
            <div id="additional_service_time_slots_container" class="hidden space-y-3"></div>
          </div>
          <div class="space-y-2 hidden" id="boarding_start_group">
            <label class="fieldset-label">Drop Off Date/Time*</label>
            <input
              type="datetime-local"
              class="input w-full"
              id="boarding_start_datetime"
              name="boarding_start_datetime"
              format="YYYY-MM-DD HH:mm" placeholder="Select drop off date/time"
              value="{{ $appointment->date ? $appointment->date . 'T' . \Carbon\Carbon::parse($appointment->start_time)->format('H:i') : '' }}"
            />
          </div>
          <div class="space-y-2 hidden" id="boarding_end_group">
            <label class="fieldset-label">Pick Up Date/Time*</label>
            <input
              type="datetime-local"
              class="input w-full"
              id="boarding_end_datetime"
              name="boarding_end_datetime"
              format="YYYY-MM-DD HH:mm" placeholder="Select drop off date/time"
              value="{{ $appointment->end_date ? $appointment->end_date . 'T' . \Carbon\Carbon::parse($appointment->end_time)->format('H:i') : '' }}"
            />
          </div>
          <div class="space-y-2 hidden" id="room_group">
            <label class="fieldset-label" for="room">Assignment*</label>
            <select class="select w-full" name="room" id="room">
              <option value="" hidden selected>Choose a room</option>
              @foreach($rooms as $room)
                <option
                  value="{{ $room->id }}"
                  data-room-type="{{ implode(',', $room->room_type_array) }}"
                  data-kennel-ids="{{ implode(',', $room->kennel_id_array) }}"
                  data-restrict-count="{{ $room->restrict_count }}"
                  {{ (string)($selectedAssignmentRoomId ?? '') === (string)$room->id ? 'selected' : '' }}
                >{{ $room->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="space-y-2 hidden" id="kennel_group">
            <label class="fieldset-label" for="kennel">Kennel*</label>
            <select class="select w-full" name="kennel" id="kennel">
              <option value="" hidden selected>Choose a kennel</option>
              @foreach($kennels as $kennel)
                <option value="{{ $kennel->id }}" data-kennel-type="{{ $kennel->kennel_type ?? 'Canine' }}" {{ (string)($appointment->kennel_id ?? '') === (string)$kennel->id ? 'selected' : '' }}>{{ $kennel->name }} ({{ $kennel->kennel_type ?? 'Canine' }})</option>
              @endforeach
            </select>
          </div>
          <div class="space-y-2 hidden xl:col-span-4" id="family_kennel_assignments_group">
            <label class="fieldset-label">Kennel Assignments*</label>
            <div id="family_kennel_assignments_container" class="grid grid-cols-1 gap-3 xl:grid-cols-2"></div>
          </div>
        </div>
          <div class="fieldset mt-3 grid grid-cols-1 gap-6 xl:grid-cols-2">
          <div class="space-y-2 {{ isPackageService($appointment->service) ? 'hidden' : '' }}" id="staff_group">
            <label class="fieldset-label" for="staff">Staff</label>
            <select class="select w-full" name="staff" id="staff">
              <option value="" hidden selected>Choose a staff</option>
            </select>
          </div>
          <div class="space-y-2" id="appointment_status_group">
            <label class="fieldset-label" for="appointment_status">Appointment Status</label>
            <select class="select w-full" name="appointment_status" id="appointment_status">
              <option value="">-- Select Status --</option>
              <option value="cancelled" {{ $appointment->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              <option value="no_show" {{ $appointment->status === 'no_show' ? 'selected' : '' }}>No Show</option>
            </select>
          </div>
        </div>
        <div class="fieldset mt-3 grid grid-cols-1 gap-6">
          <div class="space-y-2" id="appointment_notes_group">
            <label class="fieldset-label" for="appointment_notes">Appointment Notes</label>
            <textarea class="textarea w-full" id="appointment_notes" name="appointment_notes" rows="3">{{ old('appointment_notes', $appointment->metadata['appointment_notes'] ?? '') }}</textarea>
          </div>
          <div class="space-y-2" id="wait_listed_group">
            <label class="label cursor-pointer justify-start gap-3 px-0">
              <input type="checkbox" name="is_wait_listed" id="is_wait_listed" class="checkbox checkbox-sm" value="1" {{ $appointment->status === 'wait listed' ? 'checked' : '' }} />
              <span class="fieldset-label mb-0">Wait Listed</span>
            </label>
          </div>
        </div>
        </div>
      </div>
    </div>
    <div class="mt-6 flex justify-end gap-3">
      <a class="btn btn-sm btn-ghost" href="{{ url()->previous() }}">
        <span class="iconify lucide--x size-4"></span>
        Cancel
      </a>
      <button class="btn btn-sm btn-primary" type="button" onclick="saveAppointment()">
        <span class="iconify lucide--check size-4"></span>
        Save
      </button>
    </div>
  </form>
</div>
<dialog id="confirm_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      Confirm
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4" id="confirm_message"></p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost btn-sm">No</button>
      </form>
      <button class="btn btn-primary btn-sm btn-soft" id="confirm_status_button" onclick="confirmAction()">Yes</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<dialog id="assignment_modal" class="modal">
  <div class="modal-box">
    <div class="flex items-center justify-between text-lg font-medium">
      Assignment Warning
      <form method="dialog">
        <button class="btn btn-sm btn-ghost btn-circle" aria-label="Close modal">
          <span class="iconify lucide--x size-4"></span>
        </button>
      </form>
    </div>
    <p class="py-4" id="assignment_message"></p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-ghost btn-sm" onclick="changeAssignmentRoom()">Change Room</button>
      </form>
      <button id="continue_anyway_btn" class="btn btn-primary btn-sm btn-soft" onclick="continueWithAssignmentConflict()">Continue Anyway</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
@endsection

@section('page-js')
  <script src="{{ asset('src/libs/select2/select2.min.js') }}"></script>
  <script src="{{ asset('src/assets/ui-components-calendar.js') }}"></script>
  <script type="module" src="https://unpkg.com/cally"></script>

  <script>
    const confirm_modal = document.getElementById('confirm_modal');
    const assignment_modal = document.getElementById('assignment_modal');
    const alert_modal = document.getElementById('alert_modal') || null;
    const appointmentDate = "{{ $appointment->date ? \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') : '' }}";
    const appointmentStartTime = "{{ $appointment->start_time ? \Carbon\Carbon::parse($appointment->start_time)->format('H:i:s') : '' }}";
    const LATE_CANCELLATION_MESSAGE = 'This cancellation is within 24 hours of the appointment check-in time. Late cancellations may incur an additional fee. Do you want to continue?';

    document.getElementById("button_cally_element")?.addEventListener("change", (e) => {
      document.getElementById("button_cally_target").innerText = e.target.value

      const serviceId = $('#service').val();
      const petId = $('#pet').val();
      const daycareDuration = $('#daycare_duration').val();
      const privateTrainingDuration = $('#private_training_duration').val();
      const secondaryServiceIds = $('#secondary_services').val() || [];

      populateTimeSlots(serviceId, e.target.value, petId, daycareDuration, privateTrainingDuration, secondaryServiceIds);
    })

    $(document).ready(function() {
      window.initialKennels = [
        @foreach($kennels as $kennel)
          { id: '{{ $kennel->id }}', name: '{{ addslashes($kennel->name) }}' },
        @endforeach
      ];
      window.initialRooms = [
        @foreach($rooms as $room)
          {
            id: '{{ $room->id }}',
            name: '{{ addslashes($room->name) }}',
            room_types: '{{ addslashes(implode(',', $room->room_type_array)) }}',
            kennel_ids: '{{ addslashes(implode(',', $room->kennel_id_array)) }}',
            restrict_count: '{{ $room->restrict_count }}'
          },
        @endforeach
      ];
      window.initialFamilyPetAssignments = @json($appointment->family_pet_assignments ?? []);

      // customer select2 with ajax
      $('#customer').select2({
        placeholder: "Choose a customer",
        ajax: {
          url: '{{ route("get-appointment-customers") }}',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              q: params.term // Send the search term as 'q'
            };
          },
          processResults: function (data) {
            return {
              results: data.map(function (customer) {
                return {
                  id: customer.id,
                  first_name: customer.profile.first_name,
                  last_name: customer.profile.last_name,
                  email: customer.email,
                  phone_number: customer.profile.phone_number_1
                };
              })
            };
          }
        },
        templateResult: function (customer) {
          if (!customer.id) {
            return customer.text;
          }
          if (!customer.first_name) {
            return customer.text;
          }
          var $container = $(`
            <div class="flex items-center gap-2">
              <span class="font-medium">${customer.first_name} ${customer.last_name}</span>
              <span class="text-sm text-base-content/70">(${customer.email} | ${customer.phone_number})</span>
            </div>
          `);
          return $container;
        },
        templateSelection: function (customer) {
          if (!customer.id) {
            return customer.text;
          }
          if (!customer.first_name) {
            return customer.text;
          }
          var $container = $(`
            <div class="flex items-center gap-2">
              <span class="font-medium">${customer.first_name} ${customer.last_name}</span>
              <span class="text-sm text-base-content/70">(${customer.email} | ${customer.phone_number})</span>
            </div>
          `);
          return $container;
        }
      });

      // Add the customer option if not present
      var customerText = "{{ $appointment->customer->profile->first_name ?? '' }} {{ $appointment->customer->profile->last_name ?? '' }} ({{ $appointment->customer->email ?? '' }} | {{ $appointment->customer->profile->phone_number_1 ?? '' }})";
      var customerOption = new Option(customerText, "{{ $appointment->customer_id }}", true, true);
      $('#customer').append(customerOption).trigger('change');

      $('#customer').on('select2:select', function (e) {
        const customerId = e.params.data.id;
        loadPets(customerId);
      });

      const currentCustomerId = "{{ $appointment->customer_id }}";
      const currentPetIds = @json(isBoardingService($appointment->service) ? $appointment->family_pet_ids : [$appointment->pet_id]);
      loadPets(currentCustomerId, currentPetIds);

      @if(!isPackageService($appointment->service))
      $('#staff').select2({
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
          if (!staff.first_name) {
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
        templateSelection: function (staff) {
          if (!staff.id) {
            return staff.text;
          }
          if (!staff.first_name) {
            return staff.text;
          }
          var $container = $(`
            <div class="flex items-center gap-2">
              <span class="font-medium">${staff.first_name} ${staff.last_name}</span>
              <span class="text-sm text-base-content/70">(${staff.email} | ${staff.phone_number})</span>
            </div>
          `);
          return $container;
        }
      });
      // Add the staff option if not present
      var staffText = "{{ $appointment->staff->profile->first_name ?? '' }} {{ $appointment->staff->profile->last_name ?? '' }} ({{ $appointment->staff->email ?? '' }} | {{ $appointment->staff->profile->phone_number_1 ?? '' }})";
      var staffOption = new Option(staffText, "{{ $appointment->staff_id }}", true, true);
      $('#staff').append(staffOption).trigger('change');
      @endif

      $('#kennel').select2({
        placeholder: "Choose a kennel",
        width: '100%',
        allowClear: true
      });

      $('#room').select2({
        placeholder: "Choose a room",
        width: '100%',
        allowClear: true
      });

      window.originalAdditionalOptions = $('#additional_services').html();

      // Define servicesData globally so it's accessible to all functions
      window.servicesData = [];
      @foreach($services as $s)
        window.servicesData.push({
          id: {{ $s->id }},
          name: '{{ addslashes($s->name) }}',
          category_name: '{{ $s->category ? addslashes($s->category->name) : '' }}',
          price_small: {{ $s->price_small !== null ? $s->price_small : 'null' }},
        });
      @endforeach

      // Define additionalServicesData with category and level info
      window.additionalServicesData = [];
      @foreach($additionalServices as $s)
        window.additionalServicesData.push({
          id: {{ $s->id }},
          name: '{{ addslashes($s->name) }}',
          category_name: '{{ $s->category ? addslashes($s->category->name) : '' }}',
          level: '{{ $s->level }}',
        });
      @endforeach

      window.initialAdditionalServicesByPet = @json($appointmentAdditionalServicesByPet ?? []);
      window.initialAdditionalServiceTimeSlotsByPet = @json($appointment->metadata['additional_service_time_slots_by_pet'] ?? []);
      window.initialAdditionalServiceTimeSlots = @json($appointment->metadata['additional_service_time_slots'] ?? []);
      window.additionalServicesByPetState = {};
      window.selectedAdditionalServiceTimeslotsByPair = {};
      window.initialAdditionalServiceTimeslotDetailsByPair = {};

      if (window.initialAdditionalServicesByPet && typeof window.initialAdditionalServicesByPet === 'object') {
        Object.keys(window.initialAdditionalServicesByPet).forEach(function(petId) {
          window.initialAdditionalServicesByPet[String(petId)] = normalizeServiceIdList(window.initialAdditionalServicesByPet[petId]);
          window.additionalServicesByPetState[String(petId)] = normalizeServiceIdList(window.initialAdditionalServicesByPet[petId]);
        });
      }

      if ((!window.initialAdditionalServiceTimeSlots || Object.keys(window.initialAdditionalServiceTimeSlots).length === 0)
          && "{{ $appointment->metadata['additional_service_time_slot_id'] ?? '' }}"
          && "{{ $appointment->metadata['additional_service_time_slot_service_id'] ?? '' }}") {
        window.initialAdditionalServiceTimeSlots = {
          "{{ $appointment->metadata['additional_service_time_slot_service_id'] ?? '' }}": {
            time_slot_id: "{{ $appointment->metadata['additional_service_time_slot_id'] ?? '' }}"
          }
        };
      }

      if (window.initialAdditionalServiceTimeSlots && typeof window.initialAdditionalServiceTimeSlots === 'object') {
        const normalizedTimeSlotMap = {};

        Object.keys(window.initialAdditionalServiceTimeSlots).forEach(function(serviceId) {
          const rawDetails = window.initialAdditionalServiceTimeSlots[serviceId];
          const normalizedSlotId = (rawDetails && typeof rawDetails === 'object')
            ? String(rawDetails.time_slot_id || '')
            : String(rawDetails || '');

          if (!normalizedSlotId) {
            return;
          }

          normalizedTimeSlotMap[String(serviceId)] = {
            time_slot_id: normalizedSlotId,
            service_id: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.service_id || '') : '',
            date: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.date || '') : '',
            start_time: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.start_time || '') : '',
            end_time: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.end_time || '') : ''
          };
        });

        window.initialAdditionalServiceTimeSlots = normalizedTimeSlotMap;
      }

      if (window.initialAdditionalServiceTimeSlotsByPet && typeof window.initialAdditionalServiceTimeSlotsByPet === 'object') {
        Object.keys(window.initialAdditionalServiceTimeSlotsByPet).forEach(function(petId) {
          const serviceSlots = window.initialAdditionalServiceTimeSlotsByPet[petId];
          if (!serviceSlots || typeof serviceSlots !== 'object') {
            return;
          }

          Object.keys(serviceSlots).forEach(function(serviceId) {
            const rawDetails = serviceSlots[serviceId];
            const normalizedSlotId = (rawDetails && typeof rawDetails === 'object')
              ? String(rawDetails.time_slot_id || '')
              : String(rawDetails || '');

            if (!normalizedSlotId) {
              return;
            }

            const pairKey = String(petId) + '_' + String(serviceId);
            window.initialAdditionalServiceTimeslotDetailsByPair[pairKey] = {
              time_slot_id: normalizedSlotId,
              service_id: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.service_id || serviceId || '') : String(serviceId),
              date: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.date || '') : '',
              start_time: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.start_time || '') : '',
              end_time: rawDetails && typeof rawDetails === 'object' ? String(rawDetails.end_time || '') : ''
            };
            window.selectedAdditionalServiceTimeslotsByPair[pairKey] = normalizedSlotId;
          });
        });
      }

      if (Object.keys(window.initialAdditionalServiceTimeslotDetailsByPair).length === 0 && window.initialAdditionalServiceTimeSlots && typeof window.initialAdditionalServiceTimeSlots === 'object') {
        Object.keys(window.initialAdditionalServicesByPet || {}).forEach(function(petId) {
          const serviceIds = normalizeServiceIdList(window.initialAdditionalServicesByPet[petId] || []);
          serviceIds.forEach(function(serviceId) {
            const rawDetails = window.initialAdditionalServiceTimeSlots[String(serviceId)];
            if (!rawDetails) {
              return;
            }

            const normalizedSlotId = String(rawDetails.time_slot_id || '');
            if (!normalizedSlotId) {
              return;
            }

            const pairKey = String(petId) + '_' + String(serviceId);
            window.initialAdditionalServiceTimeslotDetailsByPair[pairKey] = {
              time_slot_id: normalizedSlotId,
              service_id: String(rawDetails.service_id || serviceId || ''),
              date: String(rawDetails.date || ''),
              start_time: String(rawDetails.start_time || ''),
              end_time: String(rawDetails.end_time || '')
            };
            window.selectedAdditionalServiceTimeslotsByPair[pairKey] = normalizedSlotId;
          });
        });
      }


      window.packagesData = [];
      @isset($packages)
        @foreach($packages as $package)
          window.packagesData.push({
            id: {{ $package->id }},
            name: '{{ addslashes($package->name) }}',
            price: {{ $package->price }},
            days: {{ $package->days ?? 0 }},
            service_ids: '{{ $package->service_ids }}',
            description: `{!! addslashes($package->description ?? '') !!}`
          });
        @endforeach
      @endisset

      $('#additional_services').select2({
        placeholder: "Choose additional services (optional)",
        allowClear: true,
        multiple: true,
        width: '100%',
        closeOnSelect: false
      }).on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        handleAdditionalServiceTimeSlotState();
      });

      $('#boarding_end_datetime').on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        handleAdditionalServiceTimeSlotState();
        refreshAvailableKennels();
      });

      $('#boarding_start_datetime').on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        refreshAvailableKennels();
      });

      $('#room').on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        refreshAvailableKennels();
      });

      $('#secondary_services').select2({
        placeholder: "Choose secondary services (required)",
        allowClear: false,
        multiple: true,
        width: '100%',
        closeOnSelect: false
      }).on('change', function() {
        const serviceId = $('#service').val();
        const date = $('#button_cally_target').text();
        const petId = $('#pet').val();
        const secondaryServiceIds = $(this).val() || [];

        if (serviceId && date !== '-' && petId && secondaryServiceIds.length > 0) {
          populateTimeSlots(serviceId, date, petId, '', '', secondaryServiceIds);
        } else {
          $('#time_slot').empty();
          $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');
        }
      });

      $('#group_classes').select2({
        placeholder: "Select group classes",
        multiple: true,
        width: '100%',
        closeOnSelect: false
      }).on('change', function() {
        renderGroupClassDetails();
      });
      renderGroupClassDetails();

      $('#packages').select2({
        placeholder: "Choose a package",
        width: '100%',
        disabled: {{ isPackageService($appointment->service) ? 'true' : 'false' }}
      }).on('change', function() {
        renderPackageDetails();
      });
      
      @php
        $isPackage = isPackageService($appointment->service);
        $selectedPackageId = null;
        if ($isPackage && $appointment->metadata) {
          $metadata = is_array($appointment->metadata) ? $appointment->metadata : json_decode($appointment->metadata, true);
          if ($metadata && isset($metadata['package_id'])) {
            $selectedPackageId = $metadata['package_id'];
          }
        }
      @endphp
      
      @if($isPackage && $appointment->metadata)
      const metadataFromPage = @json($appointment->metadata);
      let packageIdFromMetadata = null;
      if (metadataFromPage && metadataFromPage.package_id) {
        packageIdFromMetadata = metadataFromPage.package_id;
      }
      
      @if($selectedPackageId)
      setTimeout(function() {
        const packageId = {{ $selectedPackageId }};
        $('#packages').val(packageId).trigger('change.select2');
        setTimeout(function() {
          renderPackageDetails();
        }, 50);
      }, 200);
      @elseif(isset($appointment->metadata['package_id']))
      setTimeout(function() {
        if (packageIdFromMetadata) {
          $('#packages').val(packageIdFromMetadata).trigger('change.select2');
          setTimeout(function() {
            renderPackageDetails();
          }, 50);
        }
      }, 200);
      @endif
      @else
      console.log('Package initialization skipped - isPackage:', {{ $isPackage ? 'true' : 'false' }}, 'metadata exists:', {{ $appointment->metadata ? 'true' : 'false' }});
      @endif

      $('#pet').on('change', function() {
        if (isBoardingSelectedService($('#service').val())) {
          renderAdditionalServicesByPetSelectors();
          updateBoardingLocationField();
          handleAdditionalServiceTimeSlotState();
          refreshAvailableKennels();
          return;
        }

        const serviceId = $('#service').val();
        const date = $('#button_cally_target').text();
        const petId = $(this).val();
        const daycareDuration = $('#daycare_duration').val();
        const privateTrainingDuration = $('#private_training_duration').val();

        if (serviceId && date !== '-' && petId) {
          const secondaryServiceIds = $('#secondary_services').val() || [];
          populateTimeSlots(serviceId, date, petId, daycareDuration, privateTrainingDuration, secondaryServiceIds);
        }
      });

      $('#time_slot').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const slotDataAttr = selectedOption.attr('data-slot-data');
        if (slotDataAttr) {
          const slotData = JSON.parse(decodeURIComponent(slotDataAttr));
          $('#time_slot_data').val(JSON.stringify(slotData));
        } else {
          $('#time_slot_data').val('');
        }
      });

      var selectedServiceId = $('#service').val();
      if (selectedServiceId) {
        checkServiceType(selectedServiceId);
        // Keep persisted appointment selections only on initial page load.
        updateAdditionalServices(selectedServiceId, true);

        if (isBoardingSelectedService(selectedServiceId)) {
          renderAdditionalServicesByPetSelectors();
          updateBoardingLocationField();
          handleAdditionalServiceTimeSlotState();
          refreshAvailableKennels();
        }

        const isAlaCarte = $('#secondary_services_group').is(':visible');
        if (isAlaCarte) {
          const date = $('#button_cally_target').text();
          const petId = $('#pet').val();
          const secondaryServiceIds = $('#secondary_services').val() || [];

          if (date !== '-' && petId && secondaryServiceIds.length > 0) {
            populateTimeSlots(selectedServiceId, date, petId, '', '', secondaryServiceIds);
          }
        }
      }

      $('#daycare_duration').on('change', function() {
        const daycareDuration = $(this).val();
        const serviceId = $('#service').val();
        const date = $('#button_cally_target').text();
        const petId = $('#pet').val();
        const secondaryServiceIds = $('#secondary_services').val() || [];

        populateTimeSlots(serviceId, date, petId, daycareDuration, '', secondaryServiceIds);
      });

      $('#private_training_duration').on('change', function() {
        const privateTrainingDuration = $(this).val();
        const serviceId = $('#service').val();
        const date = $('#button_cally_target').text();
        const petId = $('#pet').val();
        const secondaryServiceIds = $('#secondary_services').val() || [];

        populateTimeSlots(serviceId, date, petId, '', privateTrainingDuration, secondaryServiceIds);
      });
    });

    function changeService(ele) {
      applyBoardingFormMode(isBoardingSelectedService($(ele).val()));

      if (isBoardingSelectedService($(ele).val())) {
        checkServiceType($(ele).val());

        const serviceId = $(ele).val();
        const petId = getPrimaryPetId();

        $('#time_slot').empty();
        $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');
        $('#time_slot_data').val('');

        updateAdditionalServices(serviceId, false);
        updateBoardingLocationField();

        if (!isBoardingSelectedService(serviceId) && appointmentDate && petId) {
          populateTimeSlots(serviceId, appointmentDate, petId);
        } else {
          handleAdditionalServiceTimeSlotState();
        }

        refreshAvailableKennels();

        return;
      }

      const serviceId = $(ele).val();
      const date = $('#button_cally_target').text();
      const petId = $('#pet').val();
      const daycareDuration = $('#daycare_duration').val();
      const privateTrainingDuration = $('#private_training_duration').val();
      const service = window.servicesData.find(function(s) { return s.id == serviceId; });
      const categoryName = service && service.category_name ? service.category_name.toLowerCase() : '';

      // Clear stale selections when moving away from Group Class / Package services.
      if (!categoryName.includes('group')) {
        $('#group_classes').val(null).trigger('change');
        $('#group_classes_details').empty();
      }

      if (!categoryName.includes('package')) {
        $('#packages').val(null).trigger('change');
        $('#packages_details').empty();
        $('#customer_package_id').val('');
      }

      checkServiceType(serviceId);

      const secondaryServiceIds = $('#secondary_services').val() || [];
      populateTimeSlots(serviceId, date, petId, daycareDuration, privateTrainingDuration, secondaryServiceIds);
      // On manual service changes, do not keep stale additional-service selections.
      updateAdditionalServices(serviceId, false);
    }

    function checkServiceType(serviceId) {
      $('#daycare_duration').val('');
      $('#private_training_duration').val('');

      // Reset all conditional groups first to avoid stale UI state across service switches.
      $('#daycare_duration_group').addClass('hidden');
      $('#private_training_duration_group').addClass('hidden');
      $('#group_classes_group').addClass('hidden');
      $('#packages_group').addClass('hidden');
      $('#additional_services_group').addClass('hidden');
      $('#secondary_services_group').addClass('hidden');
      $('#date_group').addClass('hidden');
      $('#time_slot_group').addClass('hidden');
      $('#boarding_start_group').addClass('hidden');
      $('#boarding_end_group').addClass('hidden');
      $('#staff_group').addClass('hidden');

      applyBoardingFormMode(isBoardingSelectedService(serviceId));

      if (!serviceId) {
        return;
      }

      const service = window.servicesData.find(function(s) { return s.id == serviceId; });

      if (service && service.category_name && service.category_name.toLowerCase().includes('daycare')) {
        $('#daycare_duration_group').removeClass('hidden');
        $('#additional_services_group').removeClass('hidden');
        $('#date_group').removeClass('hidden');
        $('#time_slot_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      } else if (service && service.category_name && service.category_name.toLowerCase().includes('group')) {
        $('#additional_services_group').removeClass('hidden');
        $('#group_classes_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      } else if (service && service.category_name && service.category_name.toLowerCase().includes('training')) {
        $('#private_training_duration_group').removeClass('hidden');
        $('#additional_services_group').removeClass('hidden');
        $('#date_group').removeClass('hidden');
        $('#time_slot_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      } else if (service && service.category_name && service.category_name.toLowerCase().includes('carte')) {
        $('#additional_services_group').removeClass('hidden');
        $('#secondary_services_group').removeClass('hidden');
        $('#date_group').removeClass('hidden');
        $('#time_slot_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      } else if (service && service.category_name && service.category_name.toLowerCase().includes('boarding')) {
        $('#additional_services_group').removeClass('hidden');
        $('#boarding_start_group').removeClass('hidden');
        $('#boarding_end_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      } else if (service && service.category_name && service.category_name.toLowerCase().includes('package')) {
        $('#additional_services_group').removeClass('hidden');
        $('#packages_group').removeClass('hidden');
        $('#date_group').removeClass('hidden');

        const customerId = $('#customer').val();
        if (customerId) {
          loadCustomerPackages(customerId);
        }
      } else {
        $('#additional_services_group').removeClass('hidden');
        $('#date_group').removeClass('hidden');
        $('#time_slot_group').removeClass('hidden');
        $('#staff_group').removeClass('hidden');
      }
    }

    function updateAdditionalServices(selectedServiceId, preserveSelection = true) {
      if (isBoardingSelectedService(selectedServiceId)) {
        let currentValues = $('#additional_services').val() || [];

        try {
          $('#additional_services').select2('destroy');
        } catch (e) {
          console.error('Failed to destroy select2 for additional services.');
        }

        $('#additional_services').html(window.originalAdditionalOptions);

        // Admin adaptation: admin lists every service here, sunshine only grooming ones.
        $('#additional_services option').each(function() {
          var optionVal = $(this).val();
          var additionalService = window.additionalServicesData.find(function(s) {
            return String(s.id) === String(optionVal);
          });

          if (!isBoardingAdditionalService(additionalService)) {
            $(this).remove();
          }
        });

        if (selectedServiceId) {
          $('#additional_services option[value="' + selectedServiceId + '"]').remove();
        }

        $('#additional_services').select2({
          placeholder: "Choose additional services (optional)",
          allowClear: true,
          multiple: true,
          width: '100%',
          closeOnSelect: false
        }).off('change.boarding').on('change.boarding', function() {
          if (!isBoardingSelectedService($('#service').val())) {
            return;
          }

          handleAdditionalServiceTimeSlotState();
        });

        if (preserveSelection && currentValues.length > 0) {
          const validValues = currentValues.filter(function(value) {
            return $('#additional_services option[value="' + value + '"]').length > 0;
          });
          $('#additional_services').val(validValues).trigger('change');
        }

        renderAdditionalServicesByPetSelectors();

        return;
      }

      var currentValues = $('#additional_services').val() || [];

      if (currentValues.includes(selectedServiceId)) {
        var newValues = currentValues.filter(function(value) {
          return value !== selectedServiceId;
        });
        currentValues = newValues;
      }

      try {
        $('#additional_services').select2('destroy');
      } catch (e) {
        console.error('Failed to destroy select2 for additional services.');
      }

      $('#additional_services').html(window.originalAdditionalOptions);

      // Get the selected service to determine category
      var service = window.servicesData.find(function(s) { return s.id == selectedServiceId; });
      const categoryName = service ? (service.category_name || '').toLowerCase() : '';

      // Filter additional services based on service category
      if (categoryName.includes('daycare') || categoryName.includes('boarding')) {
        // For daycare and boarding: show grooming (secondary level) and training services
        $('#additional_services option').each(function() {
          var optionVal = $(this).val();
          var additionalService = window.additionalServicesData.find(function(s) { return String(s.id) === String(optionVal); });
          if (additionalService) {
            const catName = (additionalService.category_name || '').toLowerCase();
            const isGroomingSecondary = (catName.includes('groom') || catName.includes('chauffeur')) && additionalService.level === 'secondary';
            const isTraining = catName.includes('training');
            if (!isGroomingSecondary && !isTraining) {
              $(this).remove();
            }
          }
        });
      } else if (categoryName.includes('training')) {
        // For private training: show only grooming services (secondary level)
        $('#additional_services option').each(function() {
          var optionVal = $(this).val();
          var additionalService = window.additionalServicesData.find(function(s) { return String(s.id) === String(optionVal); });
          if (additionalService) {
            const catName = (additionalService.category_name || '').toLowerCase();
            const isGroomingSecondary = catName.includes('groom') && additionalService.level === 'secondary';
            if (!isGroomingSecondary) {
              $(this).remove();
            }
          }
        });
      } else if (categoryName.includes('grooming') || categoryName.includes('groom')) {
        // For grooming: only allow secondary grooming services
        $('#additional_services option').each(function() {
          var optionVal = $(this).val();
          var additionalService = window.additionalServicesData.find(function(s) { return String(s.id) === String(optionVal); });
          if (additionalService) {
            const catName = (additionalService.category_name || '').toLowerCase();
            const isGroomingSecondary = (catName.includes('groom') || catName.includes('chauffeur')) && additionalService.level === 'secondary';
            if (!isGroomingSecondary) {
              $(this).remove();
            }
          }
        });
      } else if (categoryName.includes('carte') || categoryName.includes('package') || categoryName.includes('group')) {
        $('#additional_services option').each(function() {
          var optionVal = $(this).val();
          var additionalService = window.additionalServicesData.find(function(s) { return String(s.id) === String(optionVal); });
          if (additionalService) {
            const catName = (additionalService.category_name || '').toLowerCase();
            const isGroomingSecondary = catName.includes('chauffeur') && additionalService.level === 'secondary';
            if (!isGroomingSecondary) {
              $(this).remove();
            }
          }
        });
      }

      // Always remove the selected service from the list
      if (selectedServiceId) {
        var removedOption = $('#additional_services option[value="' + selectedServiceId + '"]');
        removedOption.remove();
      }

      $('#additional_services').select2({
        placeholder: "Choose additional services (optional)",
        allowClear: true,
        multiple: true,
        width: '100%',
        closeOnSelect: false
      });

      if (preserveSelection && currentValues.length > 0) {
        const validValues = currentValues.filter(function(value) {
          return $('#additional_services option[value="' + value + '"]').length > 0;
        });
        $('#additional_services').val(validValues).trigger('change');
      } else {
        $('#additional_services').val(null).trigger('change');
      }
    }

    /*
      Shared by both forms. Boarding calls it the sunshine-laravel way,
      populateTimeSlots(serviceId, date, petId, pickupTime = '', isBoardingAdditionalService = false);
      the other services pass (..., daycareDuration, privateTrainingDuration, secondaryServiceIds).
    */
    function populateTimeSlots(serviceId, date, petId, ...args) {
      if (isBoardingSelectedService($('#service').val())) {
        const [pickupTime = '', isBoardingAdditionalService = false] = args;

        if (!serviceId || !date || date === '-' || !petId) {
          $('#time_slot').empty();
          $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');
          return;
        }

        $.ajax({
          url: '{{ route("get-appointment-timeslots") }}',
          method: 'POST',
          data: {
            service_id: serviceId,
            date: date,
            pet_id: petId,
            pickup_time: pickupTime,
            is_boarding_additional_service: isBoardingAdditionalService ? 1 : 0
          },
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          dataType: 'json',
          success: function(timeSlots) {
            $('#time_slot').empty();
            $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');

            @php
              $selectedAdditionalSlotId = $appointment->metadata['additional_service_time_slot_id'] ?? null;
              $selectedAdditionalSlotStart = $appointment->metadata['additional_service_time_slot_start_time'] ?? null;
              $selectedAdditionalSlotEnd = $appointment->metadata['additional_service_time_slot_end_time'] ?? null;
            @endphp
            const selectedAdditionalSlotId = "{{ $selectedAdditionalSlotId ?? '' }}";
            const selectedAdditionalSlotStart = "{{ $selectedAdditionalSlotStart ?? '' }}";
            const selectedAdditionalSlotEnd = "{{ $selectedAdditionalSlotEnd ?? '' }}";
            const appointmentStartTime = "{{ $appointment->start_time ?? '' }}";
            let selectedOptionExists = false;

            if (timeSlots.length === 0) {
              if (selectedAdditionalSlotId && selectedAdditionalSlotStart && selectedAdditionalSlotEnd) {
                const selectedLabel = formatTimeToAMPM(selectedAdditionalSlotStart) + ' - ' + formatTimeToAMPM(selectedAdditionalSlotEnd);
                $('#time_slot').append('<option value="' + selectedAdditionalSlotId + '" selected data-slot-data="">' + selectedLabel + '</option>');
              } else {
                $('#time_slot').append('<option value="" disabled>No available time slots</option>');
              }
              return;
            }

            $.each(timeSlots, function(index, slot) {
              const start = formatTimeToAMPM(slot.start_time);
              const end = formatTimeToAMPM(slot.end_time);
              const displayText = start + ' - ' + end;
              const disabled = slot.status !== 'available' ? 'disabled' : '';
              const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);

              const isSelected = selectedAdditionalSlotId
                ? String(slot.id || '') === String(selectedAdditionalSlotId) || slot.start_time === selectedAdditionalSlotStart
                : slot.start_time === appointmentStartTime;

              if (isSelected) {
                selectedOptionExists = true;
              }

              $('#time_slot').append('<option value="' + slotValue + '" ' + disabled + (isSelected ? ' selected' : '') + ' data-slot-data="' + encodeURIComponent(JSON.stringify(slot)) + '">' + displayText + '</option>');

              if (isSelected) {
                $('#time_slot_data').val(JSON.stringify(slot));
              }
            });

            if (!selectedOptionExists && selectedAdditionalSlotId && selectedAdditionalSlotStart && selectedAdditionalSlotEnd) {
              const selectedLabel = formatTimeToAMPM(selectedAdditionalSlotStart) + ' - ' + formatTimeToAMPM(selectedAdditionalSlotEnd);
              $('#time_slot').append('<option value="' + selectedAdditionalSlotId + '" selected data-slot-data="">' + selectedLabel + '</option>');
            }
          },
          error: function() {
            console.error('Failed to fetch time slots for the selected service and date.');
          }
        });

        return;
      }

      let [daycareDuration = '', privateTrainingDuration = '', secondaryServiceIds = []] = args;

      if (!serviceId || date === '-' || !petId) {
        $('#time_slot').empty();
        $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');
        return;
      }

      const isAlaCarte = $('#secondary_services_group').is(':visible');
      if (isAlaCarte && (!secondaryServiceIds || secondaryServiceIds.length === 0)) {
        secondaryServiceIds = $('#secondary_services').val() || [];
      }

      $.ajax({
        url: '{{ route("get-appointment-timeslots") }}',
        method: 'POST',
        data: {
          service_id: serviceId,
          date: date,
          pet_id: petId,
          daycare_duration: daycareDuration,
          private_training_duration: privateTrainingDuration,
          secondary_service_ids: secondaryServiceIds
        },
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        dataType: 'json',
        success: function(timeSlots) {
          $('#time_slot').empty();
          $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');

          if (timeSlots.length === 0) {
            $('#time_slot').append('<option value="" disabled>No available time slots</option>');
          } else {
            $.each(timeSlots, function(index, slot) {
              let displayText = '';
              if (slot.is_virtual && slot.optimized_service_order) {
                // For ala carte, show the optimized service order
                const services = slot.optimized_service_order.map(function(s) {
                  return s.service_name + ' (' + formatTimeToAMPM(s.start_time) + ' - ' + formatTimeToAMPM(s.end_time) + ')';
                }).join(', ');
                displayText = formatTimeToAMPM(slot.start_time) + ' - ' + formatTimeToAMPM(slot.end_time) + ' (' + services + ')';
              } else {
                const start = formatTimeToAMPM(slot.start_time);
                const end = formatTimeToAMPM(slot.end_time);
                displayText = start + ' - ' + end;
              }
              const disabled = slot.status !== 'available' ? 'disabled' : '';
              const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);

              let isSelected = false;

              @if($appointment->start_time)
                const appointmentStartTime = "{{ $appointment->start_time }}";

                if (slot.is_virtual) {
                  @if($appointment->metadata && isset($appointment->metadata['used_slot_ids']))
                    const appointmentUsedSlots = @json(explode(',', $appointment->metadata['used_slot_ids']));
                    if (slot.start_time === appointmentStartTime && slot.used_slot_ids && slot.used_slot_ids.length > 0) {
                      const slotIdsMatch = slot.used_slot_ids.every(function(id) {
                        return appointmentUsedSlots.includes(String(id));
                      }) && slot.used_slot_ids.length === appointmentUsedSlots.length;
                      if (slotIdsMatch) {
                        isSelected = true;
                      }
                    }
                  @else
                    isSelected = slot.start_time === appointmentStartTime;
                  @endif
                } else {
                  isSelected = (slot.id && slot.id == "{{ $timeSlots->firstWhere('start_time', $appointment->start_time)->id ?? '' }}") ||
                              slot.start_time === appointmentStartTime;
                }
              @endif

              $('#time_slot').append('<option value="' + slotValue + '" ' + disabled + (isSelected ? ' selected' : '') + ' data-slot-data="' + encodeURIComponent(JSON.stringify(slot)) + '">' + displayText + '</option>');

              if (isSelected && slot.is_virtual) {
                $('#time_slot_data').val(JSON.stringify(slot));
              }
            });
          }
        },
        error: function() {
          console.error('Failed to fetch time slots for the selected service and date.');
        }
      });
    }

    function formatTimeToAMPM(timeStr) {
      // timeStr is '09:00:00'
      const [hours, minutes, seconds] = timeStr.split(':');
      const date = new Date();
      date.setHours(hours, minutes, seconds || 0);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function saveAppointment() {
      if (isBoardingSelectedService($('#service').val())) {
        const selectedStatus = $('#appointment_status').val();
        const currentStatus = '{{ $appointment->status }}';
        const isWaitListed = $('#is_wait_listed').is(':checked');

        if (isWaitListed) {
          $('#form_status').val('wait listed');
          proceedWithFormSubmission();
          return;
        }
      
        if (selectedStatus === 'cancelled') {
          const requiresModal = requiresLateCancellationModal(appointmentDate, appointmentStartTime);

          if (!requiresModal) {
            $('#form_status').val(selectedStatus);
            proceedWithFormSubmission();
            return;
          }

          $('#confirm_message').text(LATE_CANCELLATION_MESSAGE);

          $('#confirm_modal .btn-primary').off('click').on('click', function() {
            confirm_modal.close();
            $('#form_status').val(selectedStatus);
            proceedWithFormSubmission();
          });

          confirm_modal.showModal();
          return;
        }

        if (selectedStatus === 'no_show') {
          $('#confirm_message').text('Are you sure you want to mark as no show this appointment?');

          $('#confirm_modal .btn-primary').off('click').on('click', function() {
            confirm_modal.close();
            $('#form_status').val(selectedStatus);
            proceedWithFormSubmission();
          });

          confirm_modal.showModal();
          return;
        } else if (selectedStatus === '' && (currentStatus === 'cancelled' || currentStatus === 'no_show' || currentStatus === 'wait listed')) {
          $('#form_status').val('checked_in');
          proceedWithFormSubmission();
          return;
        }

        if (selectedStatus) {
          $('#form_status').val(selectedStatus === '' ? 'checked_in' : selectedStatus);
        }
        proceedWithFormSubmission();

        return;
      }

      const selectedStatus = $('#appointment_status').val();
      const currentStatus = '{{ $appointment->status }}';
      const isWaitListed = $('#is_wait_listed').is(':checked');

      if (isWaitListed) {
        $('#form_status').val('wait listed');
        proceedWithFormSubmission();
        return;
      }
      
      if (selectedStatus === 'cancelled' || selectedStatus === 'no_show') {
        const statusText = selectedStatus === 'cancelled' ? 'cancel' : 'mark as no show';
        $('#confirm_message').text(`Are you sure you want to ${statusText} this appointment?`);
        
        $('#confirm_modal .btn-primary').off('click').on('click', function() {
          confirm_modal.close();
          $('#form_status').val(selectedStatus);
          proceedWithFormSubmission();
        });
        
        confirm_modal.showModal();
        return;
      } else if (selectedStatus === '' && (currentStatus === 'cancelled' || currentStatus === 'no_show' || currentStatus === 'wait listed')) {
        $('#form_status').val('checked_in');
        proceedWithFormSubmission();
        return;
      }

      if (selectedStatus) {
        $('#form_status').val(selectedStatus === '' ? 'checked_in' : selectedStatus);
      }
      proceedWithFormSubmission();
    }

    function proceedWithFormSubmission() {
      if (isBoardingSelectedService($('#service').val())) {
        const customer = $('#customer').val();
        const pet = getSelectedPetIds();
        const primaryPetId = getPrimaryPetId();
        const service = $('#service').val();
        const timeSlot = $('#time_slot').val();
        const selectedAdditionalServices = collectSelectedAdditionalServiceIds();
        const additionalServicesByPet = getPerPetAdditionalServicesPayload();
        const chauffeurSelected = hasSelectedChauffeurAdditionalService(selectedAdditionalServices);
        const isBoarding = $('#boarding_start_group').is(':visible');
        const boardingStart = $('#boarding_start_datetime').val();
        const boardingEnd = $('#boarding_end_datetime').val();
        const kennel = $('#kennel').val();
        const room = $('#room').val();
        const selectedRoomType = getSelectedRoomType();
        const familyKennelMode = getSelectedPetAssignmentMode();
        const familyPetAssignments = familyKennelMode === 'individual' ? getSelectedFamilyPetAssignments() : {};
        const isWaitListed = $('#is_wait_listed').is(':checked');

        if (!customer || pet.length === 0 || !service) {
          $('#alert_message').text('Please fill in all required fields.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding && !isWaitListed && familyKennelMode !== 'individual' && !room) {
          $('#alert_message').text('Please select a room for the boarding appointment.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding && !isWaitListed && familyKennelMode === 'individual' && hasMissingFamilyPetAssignments()) {
          $('#alert_message').text('Please assign a room and kennel (for standard rooms) to each selected pet.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding && !isWaitListed && selectedRoomType === 'standard' && familyKennelMode === 'shared' && !kennel) {
          $('#alert_message').text('Please select a kennel for the boarding appointment.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding && !isWaitListed && familyKennelMode !== 'individual' && selectedRoomType === 'standard') {
          const roomKennelIds = getSelectedRoomKennelIds();
          if (kennel && roomKennelIds.length > 0 && !roomKennelIds.includes(String(kennel))) {
            $('#alert_message').text('The selected kennel does not belong to the selected room.');
            alert_modal.showModal();
            return;
          }
        }

        if (isBoarding && selectedAdditionalServices.length > 0 && hasMissingAdditionalServiceTimeSlots()) {
          $('#alert_message').text('Please select a valid time slot for each additional service.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding) {
          if (!boardingStart || !boardingEnd) {
            $('#alert_message').text('Please select both drop off and pick up date/time for boarding service.');
            alert_modal.showModal();
            return;
          }

          if (new Date(boardingStart) >= new Date(boardingEnd)) {
            $('#alert_message').text('Pick up date/time must be after drop off date/time for boarding service.');
            alert_modal.showModal();
            return;
          }

          const boardingStartMinutes = getTotalMinutesFromDateTimeValue(boardingStart);
          const isEarlyDropOff = boardingStartMinutes !== null && boardingStartMinutes < ((7 * 60) + 30);
          const isLateDropOff = boardingStartMinutes !== null && boardingStartMinutes > ((17 * 60) + 30);
          if (
            boardingStartMinutes === null
            || (isEarlyDropOff && !canCreateEarlyBoardingDropoff)
            || isLateDropOff
          ) {
            $('#alert_message').text('Drop-off time must be between 7:30 AM and 5:30 PM.');
            alert_modal.showModal();
            return;
          }

          if (!isWithinBusinessHours(boardingEnd)) {
            $('#alert_message').text('Pick-up time must be between 7:30 AM and 5:30 PM.');
            alert_modal.showModal();
            return;
          }

          if (!isWaitListed && $('#allow_assignment_conflict').val() !== '1') {
            $.ajax({
              url: '{{ route("validate-assignment") }}',
              method: 'POST',
              dataType: 'json',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              data: {
                room_id: familyKennelMode === 'shared' ? room : null,
                kennel_id: selectedRoomType === 'standard' && familyKennelMode === 'shared' ? kennel : null,
                pet_ids: pet,
                family_pet_assignments: familyPetAssignments,
                boarding_start_datetime: boardingStart,
                boarding_end_datetime: boardingEnd,
                appointment_id: '{{ $appointment->id }}'
              },
              success: function(response) {
                if (response.conflict) {
                  var messageText = response.message || 'The selected assignment is already in use during this time period.';
                  var isBlocking = response.conflict_type === 'kennel_blocked';
                  $('#assignment_conflict_info').val(JSON.stringify(response));

                  if (isBlocking) {
                    messageText += '<br><br>Please choose another kennel.';
                    $('#continue_anyway_btn').hide();
                  } else {
                    messageText += '<br><br>Do you want to continue anyway?';
                    $('#continue_anyway_btn').show();
                  }

                  $('#assignment_message').html(messageText);
                  assignment_modal.showModal();
                  return;
                }

                submitAppointmentDetails(customer, pet, primaryPetId, service, timeSlot, selectedAdditionalServices, additionalServicesByPet, chauffeurSelected, isBoarding, boardingStart, boardingEnd, kennel, room, selectedRoomType);
              },
              error: function() {
                console.error('Failed to validate assignment.');
                $('#alert_message').text('An error occurred while validating the assignment. Please try again.');
                alert_modal.showModal();
              }
            });

            return;
          }
        }

        submitAppointmentDetails(customer, pet, primaryPetId, service, timeSlot, selectedAdditionalServices, additionalServicesByPet, chauffeurSelected, isBoarding, boardingStart, boardingEnd, kennel, room, selectedRoomType);

        return;
      }

      const customer = $('#customer').val();
      const pet = $('#pet').val();
      const service = $('#service').val();
      const date = $('#button_cally_target').text();
      const timeSlot = $('#time_slot').val();
      const isDaycare = $('#daycare_duration_group').is(':visible');
      const daycareDuration = $('#daycare_duration').val();
      const isPrivateTraining = $('#private_training_duration_group').is(':visible');
      const privateTrainingDuration = $('#private_training_duration').val();
      const isGroupClasses = $('#group_classes_group').is(':visible');
      const groupClassesSelected = $('#group_classes').val() || [];
      const isPackage = $('#packages_group').is(':visible');
      const packageSelected = $('#packages').val();
      const isAlaCarte = $('#secondary_services_group').is(':visible');
      const secondaryServicesSelected = $('#secondary_services').val() || [];

      const isBoarding = $('#boarding_start_group').is(':visible');
      const boardingStart = $('#boarding_start_datetime').val();
      const boardingEnd = $('#boarding_end_datetime').val();

      if (!customer || !pet || !service) {
        $('#alert_message').text('Please fill in all required fields.');
        alert_modal.showModal();
        return;
      }

      if (isAlaCarte && secondaryServicesSelected.length === 0) {
        $('#alert_message').text('Please select at least one secondary service for ala carte.');
        alert_modal.showModal();
        return;
      }

      if (isPackage && !packageSelected) {
        $('#alert_message').text('Please select a package.');
        alert_modal.showModal();
        return;
      }

      if (!isGroupClasses && !isPackage && (!date || !timeSlot) && !isBoarding) {
        $('#alert_message').text('Please select a date and time slot.');
        alert_modal.showModal();
        return;
      }

      if (isGroupClasses && groupClassesSelected.length === 0) {
        $('#alert_message').text('Please select at least one group class.');
        alert_modal.showModal();
        return;
      }

      if (isPackage && !date) {
        $('#alert_message').text('Please select a date for the package.');
        alert_modal.showModal();
        return;
      }

      if (!isGroupClasses && !isPackage && isDaycare && !daycareDuration) {
        $('#alert_message').text('Please select Half Day or Full Day for daycare service.');
        alert_modal.showModal();
        return;
      }

      if (!isGroupClasses && !isPackage && isPrivateTraining && !privateTrainingDuration) {
        $('#alert_message').text('Please select Half Hour or One Hour for private training service.');
        alert_modal.showModal();
        return;
      }

      if (isGroupClasses || isBoarding) {
        $('#date').val('');
        $('#time_slot').val('').trigger('change');
      } else if (isPackage) {
        // For packages, set the date but clear time slot
        if (date) {
          $('#date').val(date);
        }
        $('#time_slot').val('').trigger('change');
      } else {
        if (date) {
          $('#date').val(date);
        }
      }

      if (isBoarding) {
        if (!boardingStart || !boardingEnd) {
          $('#alert_message').text('Please select both drop off and pick up date/time for boarding service.');
          alert_modal.showModal();
          return;
        }
        if (new Date(boardingStart) >= new Date(boardingEnd)) {
          $('#alert_message').text('Pick up date/time must be after drop off date/time for boarding service.');
          alert_modal.showModal();
          return;
        }
      }

      const packageId = isPackage && packageSelected ? packageSelected : null;

      $.ajax({
        url: '{{ route("get-validation-info") }}',
        method: 'POST',
        data: {
          pet_id: pet,
          service_id: service,
          package_id: packageId,
        },
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        dataType: 'json',
        success: function(response) {
          let validationMessage = '';
          if (!response.owner_status) {
            validationMessage += `<li>Pet owner's profile is inactive.</li>`;
          }
          if (!response.vaccine_status) {
            validationMessage += '<li>Pet vaccination records is not approved.</li>';
          }
          if (!response.questionnaire_status) {
            if (isPackage) {
              validationMessage += '<li>Pet questionnaire for daycare or grooming (as required by the package) is not approved.</li>';
            } else {
              validationMessage += '<li>Pet questionnaire is not approved.</li>';
            }
          }
          if (validationMessage) {
            validationMessage = `Please address the following issues before creating the appointment:<br>
              <ul style="list-style: disc; font-size: 14px; padding-left: 24px; padding-top: 6px;">${validationMessage}</ul>`;
            $('#confirm_message').html(validationMessage);
            confirm_modal.showModal();
          } else {
            const selectedStatus = $('#appointment_status').val();
            if (selectedStatus) {
              $('#form_status').val(selectedStatus === '' ? 'checked_in' : selectedStatus);
            }
            $('#update_form').submit();
          }
        },
        error: function() {
          console.error('Failed to validate appointment details.');
          $('#alert_message').text('An error occurred while validating the appointment. Please try again.');
          alert_modal.showModal();
        }
      });
    }

    /*
      Boarding form (ported from sunshine-laravel's appointments/update.blade.php).
      These functions are kept identical to sunshine-laravel.
    */
    function parseAppointmentCheckinDateTime(dateValue, timeValue) {
      if (!dateValue || !timeValue) {
        return null;
      }

      const dateParts = dateValue.split('-').map(Number);
      const timeParts = timeValue.split(':').map(Number);

      if (dateParts.length !== 3 || timeParts.length < 2) {
        return null;
      }

      const [year, month, day] = dateParts;
      const [hour, minute] = timeParts;
      const second = timeParts[2] ?? 0;
      const checkinDateTime = new Date(year, month - 1, day, hour, minute, second);

      if (Number.isNaN(checkinDateTime.getTime())) {
        return null;
      }

      return checkinDateTime;
    }

    function requiresLateCancellationModal(dateValue, timeValue) {
      const checkinDateTime = parseAppointmentCheckinDateTime(dateValue, timeValue);

      if (!checkinDateTime) {
        return true;
      }

      const millisecondsUntilCheckin = checkinDateTime.getTime() - Date.now();
      const twentyFourHoursInMilliseconds = 24 * 60 * 60 * 1000;

      return millisecondsUntilCheckin <= twentyFourHoursInMilliseconds;
    }

    function normalizeSelectedPetIds(selectedPetIds = []) {
      if (Array.isArray(selectedPetIds)) {
        return selectedPetIds.map(function(id) {
          return String(id);
        });
      }

      if (selectedPetIds) {
        return [String(selectedPetIds)];
      }

      return [];
    }

    function normalizeServiceIdList(serviceIds = []) {
      if (!Array.isArray(serviceIds)) {
        return [];
      }

      return Array.from(new Set(serviceIds.map(function(serviceId) {
        return String(serviceId);
      }).filter(function(serviceId) {
        return serviceId.trim() !== '';
      })));
    }

    function loadPets(customerId, selectedPetIds = []) {
      if (!customerId) {
        return;
      }

      const normalizedPetIds = normalizeSelectedPetIds(selectedPetIds);

      $.ajax({
        url: '{{ url("/appointment/pets") }}/' + customerId,
        type: 'GET',
        dataType: 'json',
        success: function(pets) {
          // Admin adaptation: the other services pick a single pet from a plain dropdown.
          if (!isBoardingSelectedService($('#service').val())) {
            $('#pet').empty();
            $('#pet').append('<option value="" hidden selected>Choose a pet</option>');

            $.each(pets, function(index, pet) {
              const selected = normalizedPetIds.length > 0 && String(pet.id) === normalizedPetIds[0] ? ' selected' : '';
              const petType = String(pet.type || '');
              const petSize = String(pet.size || '');
              $('#pet').append('<option value="' + pet.id + '" data-pet-type="' + petType + '" data-pet-size="' + petSize + '"' + selected + '>' + pet.name + '</option>');
            });

            return;
          }

          $('#pet').empty();

          $.each(pets, function(index, pet) {
            const selected = normalizedPetIds.includes(String(pet.id)) ? ' selected' : '';
            const petType = String(pet.type || '');
            const petSize = String(pet.size || '');
            $('#pet').append('<option value="' + pet.id + '" data-pet-type="' + petType + '" data-pet-size="' + petSize + '"' + selected + '>' + pet.name + '</option>');
          });

          $('#pet').val(normalizedPetIds).trigger('change');
          updateBoardingLocationField();

          const primaryPetId = getPrimaryPetId();
          if (isBoardingSelectedService($('#service').val())) {
            handleAdditionalServiceTimeSlotState();
          } else if (appointmentDate && primaryPetId) {
            populateTimeSlots($('#service').val(), appointmentDate, primaryPetId);
          }
        },
        error: function() {
          console.error('Failed to fetch pets for the selected customer.');
        }
      });
    }

    function isBoardingSelectedService(serviceId) {
      const service = window.servicesData.find(function(s) {
        return String(s.id) === String(serviceId);
      });

      return !!(service && service.category_name && service.category_name.toLowerCase().includes('boarding'));
    }

    function getSelectedPetIds() {
      return $('#pet').val() || [];
    }

    function getPrimaryPetId() {
      const petIds = getSelectedPetIds();
      return petIds.length > 0 ? petIds[0] : '';
    }

    function shouldUseRoomForSelectedPets() {
      return getSelectedRoomType() === 'space';
    }

    function getSelectedRoomOption() {
      return $('#room option:selected');
    }

    function getSelectedRoomType() {
      const roomType = String(getSelectedRoomOption().data('room-type') || '').trim().toLowerCase();
      return roomType.includes('space') ? 'space' : 'standard';
    }

    function getSelectedRoomKennelIds() {
      const kennelIds = String(getSelectedRoomOption().data('kennel-ids') || '').trim();

      if (!kennelIds) {
        return [];
      }

      return kennelIds.split(',').map(function(id) {
        return String(id).trim();
      }).filter(function(id) {
        return id !== '';
      });
    }

    function renderKennelOptions(kennels, selectedKennelId = '') {
      const $kennel = $('#kennel');
      $kennel.empty();
      $kennel.append('<option value="" hidden selected>Choose a kennel</option>');

      if (!kennels || kennels.length === 0) {
        $kennel.append('<option value="" disabled>No available kennels</option>');
        $kennel.val('').trigger('change');
        return;
      }

      kennels.forEach(function(kennel) {
        $kennel.append('<option value="' + kennel.id + '">' + kennel.name + '</option>');
      });

      const selectedExists = selectedKennelId && kennels.some(function(kennel) {
        return String(kennel.id) === String(selectedKennelId);
      });

      $kennel.val(selectedExists ? String(selectedKennelId) : '').trigger('change');
    }

    function refreshAvailableKennels() {
      if (!isBoardingSelectedService($('#service').val())) {
        $('#room_group').removeClass('hidden');
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('.family-pet-room-select').prop('disabled', true);
        $('.family-pet-kennel-select').prop('disabled', true);
        return;
      }

      const familyMode = getSelectedPetAssignmentMode();

      if (familyMode === 'individual') {
        $('#room_group').addClass('hidden');
        $('#kennel_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        renderFamilyPetAssignmentFields(Object.assign({}, window.initialFamilyPetAssignments || {}, getSelectedFamilyPetAssignments()));
        return;
      }

      $('#room_group').removeClass('hidden');

      if (!$('#room').val()) {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('.family-pet-room-select').prop('disabled', true);
        $('.family-pet-kennel-select').prop('disabled', true);
        renderKennelOptions(window.initialKennels || [], $('#kennel').val());
        return;
      }

      if (getSelectedRoomType() === 'space') {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('.family-pet-room-select').prop('disabled', true);
        $('.family-pet-kennel-select').prop('disabled', true);
        $('#kennel').val('').trigger('change');
        return;
      }

      const currentKennel = $('#kennel').val();

      const roomKennelIds = getSelectedRoomKennelIds();
      const roomKennels = (window.initialKennels || []).filter(function(kennel) {
        return roomKennelIds.includes(String(kennel.id));
      });

      $('#family_kennel_assignments_group').addClass('hidden');
      $('.family-pet-room-select').prop('disabled', true);
      $('.family-pet-kennel-select').prop('disabled', true);
      $('#kennel_group').removeClass('hidden');
      $('#kennel').prop('disabled', false);
      renderKennelOptions(roomKennels, currentKennel || '{{ $appointment->kennel_id }}');
    }

    function updateBoardingLocationField() {
      if (!isBoardingSelectedService($('#service').val())) {
        $('#room_group').removeClass('hidden');
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('.family-pet-room-select').prop('disabled', true);
        $('.family-pet-kennel-select').prop('disabled', true);
        return;
      }

      if (getSelectedPetAssignmentMode() === 'individual') {
        $('#room_group').addClass('hidden');
        $('#kennel_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        renderFamilyPetAssignmentFields(Object.assign({}, window.initialFamilyPetAssignments || {}, getSelectedFamilyPetAssignments()));
        return;
      }

      $('#room_group').removeClass('hidden');

      if (shouldUseRoomForSelectedPets()) {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('#kennel').val('').trigger('change');
      } else if ($('#room').val()) {
        $('#kennel_group').removeClass('hidden');
        refreshAvailableKennels();
      } else {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
      }
    }

    function getSelectedPetDetails() {
      return ($('#pet option:selected') || []).map(function(_, option) {
        return {
          id: String(option.value),
          name: $(option).text().trim(),
          size: String($(option).data('pet-size') || '').trim().toLowerCase()
        };
      }).get();
    }

    function getSelectedPetAssignmentMode() {
      const selectedPets = getSelectedPetDetails();

      if (selectedPets.length <= 1) {
        return 'shared';
      }

      return selectedPets.some(function(pet) {
        return pet.size !== 'small';
      }) ? 'individual' : 'shared';
    }

    function getRoomById(roomId) {
      return (window.initialRooms || []).find(function(room) {
        return String(room.id) === String(roomId);
      }) || null;
    }

    function getRoomTypeById(roomId) {
      const room = getRoomById(roomId);
      if (!room) {
        return '';
      }

      const roomTypes = String(room.room_types || '').toLowerCase();
      return roomTypes.includes('space') ? 'space' : 'standard';
    }

    function getKennelsForRoom(roomId) {
      const room = getRoomById(roomId);
      if (!room) {
        return [];
      }

      const roomKennelIds = String(room.kennel_ids || '')
        .split(',')
        .map(function(id) {
          return String(id).trim();
        })
        .filter(function(id) {
          return id !== '';
        });

      return (window.initialKennels || []).filter(function(kennel) {
        return roomKennelIds.includes(String(kennel.id));
      });
    }

    function getSelectedFamilyPetAssignments() {
      const assignments = {};

      $('.family-pet-room-select').each(function() {
        const petId = String($(this).data('pet-id') || '');
        const roomId = String($(this).val() || '');

        if (!petId || !roomId) {
          return;
        }

        const kennelId = String($('#family_pet_kennel_' + petId).val() || '');

        assignments[petId] = {
          room_id: roomId,
          kennel_id: kennelId || null,
        };
      });

      return assignments;
    }

    function hasMissingFamilyPetAssignments() {
      if (getSelectedPetAssignmentMode() !== 'individual') {
        return false;
      }

      const assignments = getSelectedFamilyPetAssignments();

      return getSelectedPetDetails().some(function(pet) {
        const assignment = assignments[String(pet.id)] || null;
        if (!assignment || !assignment.room_id) {
          return true;
        }

        if (getRoomTypeById(assignment.room_id) === 'standard' && !assignment.kennel_id) {
          return true;
        }

        return false;
      });
    }

    function renderFamilyPetKennelOptions($kennelSelect, roomId, selectedKennelId = '') {
      const kennels = getKennelsForRoom(roomId);
      $kennelSelect.empty();
      $kennelSelect.append('<option value="" hidden selected>Choose a kennel</option>');

      if (!kennels.length) {
        $kennelSelect.append('<option value="" disabled>No available kennels</option>');
        $kennelSelect.val('').trigger('change');
        return;
      }

      kennels.forEach(function(kennel) {
        $kennelSelect.append('<option value="' + kennel.id + '">' + kennel.name + '</option>');
      });

      const selectedExists = selectedKennelId && kennels.some(function(kennel) {
        return String(kennel.id) === String(selectedKennelId);
      });

      $kennelSelect.val(selectedExists ? String(selectedKennelId) : '').trigger('change');
    }

    function renderFamilyPetAssignmentFields(currentAssignments = {}) {
      const selectedPets = getSelectedPetDetails();
      const $container = $('#family_kennel_assignments_container');
      $container.empty();

      if (selectedPets.length <= 1) {
        $('#family_kennel_assignments_group').addClass('hidden');
        return;
      }

      let roomOptions = '<option value="" hidden selected>Choose a room</option>';
      (window.initialRooms || []).forEach(function(room) {
        roomOptions += '<option value="' + room.id + '">' + room.name + '</option>';
      });

      selectedPets.forEach(function(pet) {
        const petId = String(pet.id);
        const assignment = currentAssignments[petId] || {};
        const selectedRoomId = String(assignment.room_id || '');

        $container.append(`
          <div class="space-y-2 rounded-box border border-base-300 p-3">
            <label class="fieldset-label">${pet.name}</label>
            <select class="select w-full family-pet-room-select" id="family_pet_room_${petId}" name="family_pet_assignments[${petId}][room_id]" data-pet-id="${petId}">
              ${roomOptions}
            </select>
            <div class="space-y-2" id="family_pet_kennel_group_${petId}">
              <label class="fieldset-label" for="family_pet_kennel_${petId}">Kennel*</label>
              <select class="select w-full family-pet-kennel-select" id="family_pet_kennel_${petId}" name="family_pet_assignments[${petId}][kennel_id]" data-pet-id="${petId}">
                <option value="" hidden selected>Choose a kennel</option>
              </select>
            </div>
          </div>
        `);

        const $roomSelect = $('#family_pet_room_' + petId);
        const $kennelSelect = $('#family_pet_kennel_' + petId);
        const $kennelGroup = $('#family_pet_kennel_group_' + petId);
        $roomSelect.val(selectedRoomId || '').trigger('change');

        const toggleKennelForRoom = function(roomId, initialKennelId = '') {
          if (!roomId || getRoomTypeById(roomId) === 'space') {
            $kennelGroup.addClass('hidden');
            $kennelSelect.prop('disabled', true);
            $kennelSelect.val('').trigger('change');
            return;
          }

          $kennelGroup.removeClass('hidden');
          $kennelSelect.prop('disabled', false);
          renderFamilyPetKennelOptions($kennelSelect, roomId, initialKennelId);
        };

        toggleKennelForRoom(selectedRoomId, assignment.kennel_id || '');

        $roomSelect.off('change').on('change', function() {
          const nextRoomId = String($(this).val() || '');
          toggleKennelForRoom(nextRoomId, '');
        });
      });

      $('.family-pet-room-select').select2({
        placeholder: 'Choose a room',
        width: '100%',
        allowClear: true
      });

      $('.family-pet-kennel-select').select2({
        placeholder: 'Choose a kennel',
        width: '100%',
        allowClear: true
      });

      $('#family_kennel_assignments_group').removeClass('hidden');
    }

    function getPerPetAdditionalServicesPayload() {
      return Object.keys(window.additionalServicesByPetState || {}).reduce(function(payload, petId) {
        payload[String(petId)] = (window.additionalServicesByPetState[petId] || []).map(function(serviceId) {
          return String(serviceId);
        });
        return payload;
      }, {});
    }

    function syncAdditionalServicesByPetStateFromDom() {
      if (!window.additionalServicesByPetState) {
        window.additionalServicesByPetState = {};
      }

      const selectedPetIds = getSelectedPetIds().map(function(petId) {
        return String(petId);
      });

      Object.keys(window.additionalServicesByPetState).forEach(function(petId) {
        if (!selectedPetIds.includes(String(petId))) {
          delete window.additionalServicesByPetState[petId];
        }
      });

      $('.pet-additional-services').each(function() {
        const petId = String($(this).data('pet-id') || '');
        if (!petId) {
          return;
        }

        window.additionalServicesByPetState[petId] = ($(this).val() || []).map(function(serviceId) {
          return String(serviceId);
        });
      });

      pruneAdditionalServiceTimeSlotState();
    }

    function getSelectedAdditionalServicePairsByPet() {
      const selectedPets = getSelectedPetDetails();
      const pairs = [];

      selectedPets.forEach(function(pet) {
        const petId = String(pet.id);
        const serviceIds = (window.additionalServicesByPetState[petId] || []).map(function(serviceId) {
          return String(serviceId);
        });

        serviceIds.forEach(function(serviceId) {
          if (!serviceId) {
            return;
          }

          pairs.push({
            petId: petId,
            petName: pet.name,
            serviceId: serviceId,
            pairKey: petId + '_' + serviceId,
          });
        });
      });

      return pairs;
    }

    function pruneAdditionalServiceTimeSlotState() {
      if (!window.selectedAdditionalServiceTimeslotsByPair) {
        window.selectedAdditionalServiceTimeslotsByPair = {};
      }

      const validPairKeys = getSelectedAdditionalServicePairsByPet().map(function(pair) {
        return pair.pairKey;
      });

      Object.keys(window.selectedAdditionalServiceTimeslotsByPair).forEach(function(pairKey) {
        if (!validPairKeys.includes(pairKey)) {
          delete window.selectedAdditionalServiceTimeslotsByPair[pairKey];
        }
      });
    }

    function collectSelectedAdditionalServiceIds() {
      const perPetPayload = getPerPetAdditionalServicesPayload();
      const flattenedPerPetIds = Object.keys(perPetPayload).reduce(function(carry, petId) {
        return carry.concat(perPetPayload[petId] || []);
      }, []);

      const singleIds = $('#additional_services').prop('disabled') ? [] : ($('#additional_services').val() || []);

      return Array.from(new Set(singleIds.concat(flattenedPerPetIds).filter(function(id) {
        return String(id).trim() !== '';
      })));
    }

    function renderAdditionalServicesByPetSelectors() {
      const selectedPets = getSelectedPetDetails();
      const usePerPetSelectors = selectedPets.length > 1;
      const serviceId = $('#service').val();

      syncAdditionalServicesByPetStateFromDom();

      if (!usePerPetSelectors) {
        const singlePetId = selectedPets.length === 1 ? selectedPets[0].id : null;
        const initialSinglePetSelection = singlePetId && window.initialAdditionalServicesByPet && window.initialAdditionalServicesByPet[singlePetId]
          ? normalizeServiceIdList(window.initialAdditionalServicesByPet[singlePetId])
          : [];
        $('#additional_services_by_pet_container').addClass('hidden').empty();
        $('#additional_services_single_wrapper').removeClass('hidden');
        $('#additional_services').prop('disabled', false);
        const singlePetSelection = singlePetId && Array.isArray(window.additionalServicesByPetState[singlePetId]) && window.additionalServicesByPetState[singlePetId].length > 0
          ? normalizeServiceIdList(window.additionalServicesByPetState[singlePetId])
          : initialSinglePetSelection;
        if (singlePetSelection.length > 0) {
          $('#additional_services').val(singlePetSelection).trigger('change');
        }
        return;
      }

      const $container = $('#additional_services_by_pet_container');
      $container.empty();

      const selectedPetIds = selectedPets.map(function(pet) {
        return String(pet.id);
      });

      Object.keys(window.additionalServicesByPetState || {}).forEach(function(petId) {
        if (!selectedPetIds.includes(String(petId))) {
          delete window.additionalServicesByPetState[petId];
        }
      });

      selectedPets.forEach(function(pet) {
        const initialSelections = window.initialAdditionalServicesByPet && window.initialAdditionalServicesByPet[pet.id]
          ? normalizeServiceIdList(window.initialAdditionalServicesByPet[pet.id])
          : [];
        const currentSelections = normalizeServiceIdList(window.additionalServicesByPetState[pet.id] || initialSelections);
        window.additionalServicesByPetState[pet.id] = currentSelections;
        let optionsHtml = '';

        window.additionalServicesData.forEach(function(additionalService) {
          if (String(additionalService.id) === String(serviceId)) {
            return;
          }

          // Admin adaptation: admin lists every service here, sunshine only grooming ones.
          if (!isBoardingAdditionalService(additionalService)) {
            return;
          }

          const isSelected = currentSelections.includes(String(additionalService.id));
          optionsHtml += '<option value="' + additionalService.id + '"' + (isSelected ? ' selected' : '') + '>' + additionalService.name + '</option>';
        });

        const petBlockHtml = `
          <div class="space-y-2 rounded-box border border-base-300 p-3">
            <label class="fieldset-label">${pet.name}</label>
            <select class="select w-full pet-additional-services" name="additional_services_by_pet[${pet.id}][]" data-pet-id="${pet.id}" multiple>
              ${optionsHtml}
            </select>
          </div>
        `;

        $container.append(petBlockHtml);
      });

      $('.pet-additional-services').select2({
        placeholder: 'Choose additional services (optional)',
        allowClear: true,
        multiple: true,
        width: '100%',
        closeOnSelect: false
      }).on('change', function() {
        const petId = String($(this).data('pet-id') || '');
        if (petId) {
          window.additionalServicesByPetState[petId] = ($(this).val() || []).map(function(serviceId) {
            return String(serviceId);
          });
        }

        pruneAdditionalServiceTimeSlotState();
        handleAdditionalServiceTimeSlotState();
      });

      $('#additional_services_single_wrapper').addClass('hidden');
      $('#additional_services').prop('disabled', true).val([]).trigger('change');
      $('#additional_services_by_pet_container').removeClass('hidden');
    }

    function getSelectedAdditionalServiceForTimeSlot() {
      const selectedAdditionalServiceIds = collectSelectedAdditionalServiceIds();
      return selectedAdditionalServiceIds.length > 0 ? selectedAdditionalServiceIds[0] : null;
    }

    function getAdditionalServiceTimeSlotSelections() {
      return Object.assign({}, window.selectedAdditionalServiceTimeslotsByPair || {});
    }

    function hasMissingAdditionalServiceTimeSlots() {
      const selectedPairs = getSelectedAdditionalServicePairsByPet();
      if (selectedPairs.length === 0) {
        return false;
      }

      if (getSelectedPetIds().length <= 1) {
        return !$('#time_slot').val();
      }

      const slotSelections = getAdditionalServiceTimeSlotSelections();
      return selectedPairs.some(function(pair) {
        return !slotSelections[pair.pairKey];
      });
    }

    function handleAdditionalServiceTimeSlotState() {
      syncAdditionalServicesByPetStateFromDom();

      const serviceId = $('#service').val();

      if (!isBoardingSelectedService(serviceId)) {
        $('#single_time_slot_wrapper').removeClass('hidden');
        $('#additional_service_time_slots_container').addClass('hidden').empty();
        $('#time_slot_group label').text('Start Time - End Time*');
        $('#time_slot_group').removeClass('hidden');

        const primaryPetId = getPrimaryPetId();
        if (appointmentDate && primaryPetId) {
          populateTimeSlots(serviceId, appointmentDate, primaryPetId);
        }
        return;
      }

      const selectedAdditionalServiceIds = collectSelectedAdditionalServiceIds();
      const petId = getPrimaryPetId();
      const boardingEndDateTime = $('#boarding_end_datetime').val();
      const pickupDate = boardingEndDateTime ? boardingEndDateTime.split('T')[0] : '';
      const pickupTime = boardingEndDateTime ? boardingEndDateTime.split('T')[1] : '';

      if (selectedAdditionalServiceIds.length === 0) {
        $('#single_time_slot_wrapper').addClass('hidden');
        $('#additional_service_time_slots_container').addClass('hidden').empty();
        $('#time_slot_group').addClass('hidden');
        $('#time_slot').empty().append('<option value="" hidden selected>Choose a time slot</option>');
        $('#time_slot').val('').trigger('change');
        $('#time_slot_data').val('');
        return;
      }

      const shouldUsePerServiceTimeSlots = getSelectedPetIds().length > 1;
      if (!shouldUsePerServiceTimeSlots) {
        const additionalServiceId = getSelectedAdditionalServiceForTimeSlot();

        $('#single_time_slot_wrapper').removeClass('hidden');
        $('#additional_service_time_slots_container').addClass('hidden').empty();
        $('#time_slot_group').removeClass('hidden');
        $('#time_slot_group label').text('Start Time - End Time*');

        if (!additionalServiceId || !petId || !pickupDate || !pickupTime) {
          $('#time_slot').empty().append('<option value="" hidden selected>Select pet and pick up time first</option>');
          $('#time_slot').val('').trigger('change');
          $('#time_slot_data').val('');
          return;
        }

        populateTimeSlots(additionalServiceId, pickupDate, petId, pickupTime, true);
        return;
      }

      $('#single_time_slot_wrapper').addClass('hidden');
      $('#time_slot_group').removeClass('hidden');
      $('#time_slot_group label').text('Additional Service Time Slots*');

      const selectedPairs = getSelectedAdditionalServicePairsByPet();
      const previousSelections = getAdditionalServiceTimeSlotSelections();
      const $container = $('#additional_service_time_slots_container');
      $container.empty();

      pruneAdditionalServiceTimeSlotState();

      selectedPairs.forEach(function(pair) {
        const service = window.additionalServicesData.find(function(item) {
          return String(item.id) === String(pair.serviceId);
        });
        const serviceName = service ? service.name : 'Additional Service';
        const selectId = 'additional_service_time_slot_' + pair.petId + '_' + pair.serviceId;
        const initialSlotDetails = window.initialAdditionalServiceTimeslotDetailsByPair && window.initialAdditionalServiceTimeslotDetailsByPair[pair.pairKey]
          ? window.initialAdditionalServiceTimeslotDetailsByPair[pair.pairKey]
          : null;
        const initialSlot = initialSlotDetails
          ? String(initialSlotDetails.time_slot_id || '')
          : '';
        const selectedSlotId = previousSelections[pair.pairKey] || initialSlot;

        $container.append(`
          <div class="space-y-2 rounded-box border border-base-300 p-3">
            <label class="fieldset-label" for="${selectId}">${pair.petName} - ${serviceName}*</label>
            <select class="select w-full additional-service-time-slot-select" id="${selectId}" name="additional_service_time_slots_by_pet[${pair.petId}][${pair.serviceId}]" data-service-id="${pair.serviceId}" data-pair-key="${pair.pairKey}" data-pet-id="${pair.petId}">
              <option value="" hidden selected>Choose a time slot</option>
            </select>
          </div>
        `);

        if (!petId || !pickupDate || !pickupTime) {
          $('#' + selectId).empty().append('<option value="" hidden selected>Select pet and pick up time first</option>');
          return;
        }

        populateAdditionalServiceTimeSlotOptions($('#' + selectId), pair.serviceId, pickupDate, pair.petId, pickupTime, selectedSlotId);
      });

      $container.removeClass('hidden');

      // Keep initial values available so rerenders can still restore saved selections.

      $('.additional-service-time-slot-select').off('change').on('change', function() {
        const pairKey = String($(this).data('pair-key') || '');
        if (!pairKey) {
          return;
        }

        window.selectedAdditionalServiceTimeslotsByPair[pairKey] = String($(this).val() || '');
      });
    }

    function populateAdditionalServiceTimeSlotOptions($select, serviceId, date, petId, pickupTime, selectedSlotId = '') {
      $select.empty().append('<option value="" hidden selected>Choose a time slot</option>');

      $.ajax({
        url: '{{ route("get-appointment-timeslots") }}',
        method: 'POST',
        data: {
          service_id: serviceId,
          date: date,
          pet_id: petId,
          pickup_time: pickupTime,
          is_boarding_additional_service: 1
        },
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        dataType: 'json',
        success: function(timeSlots) {
          $select.empty().append('<option value="" hidden selected>Choose a time slot</option>');

          if (!timeSlots || timeSlots.length === 0) {
            $select.append('<option value="" disabled>No available time slots</option>');
            return;
          }

          let selectedOptionExists = false;

          timeSlots.forEach(function(slot) {
            const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);
            const isSelected = selectedSlotId && String(slotValue) === String(selectedSlotId);
            if (isSelected) {
              selectedOptionExists = true;
            }

            const disabled = slot.status !== 'available' ? 'disabled' : '';
            const displayText = formatTimeToAMPM(slot.start_time) + ' - ' + formatTimeToAMPM(slot.end_time);
            $select.append('<option value="' + slotValue + '" ' + disabled + (isSelected ? ' selected' : '') + '>' + displayText + '</option>');
          });

          if (!selectedOptionExists && selectedSlotId) {
            const pairKey = String($select.data('pair-key') || '');
            const initialSlotDetails = window.initialAdditionalServiceTimeslotDetailsByPair && window.initialAdditionalServiceTimeslotDetailsByPair[pairKey]
              ? window.initialAdditionalServiceTimeslotDetailsByPair[pairKey]
              : null;

            let fallbackLabel = 'Previously selected time slot';
            if (initialSlotDetails && initialSlotDetails.start_time && initialSlotDetails.end_time) {
              fallbackLabel = formatTimeToAMPM(initialSlotDetails.start_time) + ' - ' + formatTimeToAMPM(initialSlotDetails.end_time);
            }

            $select.append('<option value="' + selectedSlotId + '" selected>' + fallbackLabel + '</option>');
          }

          const pairKey = String($select.data('pair-key') || '');
          if (pairKey) {
            window.selectedAdditionalServiceTimeslotsByPair[pairKey] = String($select.val() || '');
          }
        },
        error: function() {
          $select.empty().append('<option value="" disabled>Failed to load time slots</option>');
        }
      });
    }

    function hasSelectedChauffeurAdditionalService(selectedAdditionalServiceIds) {
      if (!selectedAdditionalServiceIds || selectedAdditionalServiceIds.length === 0) {
        return false;
      }

      return selectedAdditionalServiceIds.some(function(serviceId) {
        const additionalService = window.additionalServicesData.find(function(s) {
          return String(s.id) === String(serviceId);
        });
        const categoryName = additionalService && additionalService.category_name
          ? additionalService.category_name.toLowerCase()
          : '';

        return categoryName.includes('chauffeur');
      });
    }

    function getTotalMinutesFromDateTimeValue(dateTimeValue) {
      if (!dateTimeValue) {
        return null;
      }

      const parts = dateTimeValue.split('T');
      if (parts.length !== 2) {
        return null;
      }

      const timeParts = parts[1].split(':');
      if (timeParts.length < 2) {
        return null;
      }

      const hours = parseInt(timeParts[0], 10);
      const minutes = parseInt(timeParts[1], 10);

      if (Number.isNaN(hours) || Number.isNaN(minutes)) {
        return null;
      }

      return (hours * 60) + minutes;
    }

    function isWithinBusinessHours(dateTimeValue) {
      const totalMinutes = getTotalMinutesFromDateTimeValue(dateTimeValue);
      if (totalMinutes === null) {
        return false;
      }

      const businessStart = (7 * 60) + 30;
      const businessEnd = (17 * 60) + 30;

      return totalMinutes >= businessStart && totalMinutes <= businessEnd;
    }

    function showAddressValidationErrors(ownerAddressValid, facilityAddressValid) {
      const messages = [];

      if (!ownerAddressValid) {
        messages.push('<li>Owner address is invalid</li>');
      }

      if (!facilityAddressValid) {
        messages.push('<li>Facility address is invalid</li>');
      }

      if (messages.length === 0) {
        return;
      }

      const html = `
        <div class="text-left">
          <p>Please address the following issues before updating the appointment:</p>
          <ul style="list-style: none; font-size: 14px; padding-top: 6px;">${messages.join('')}</ul>
        </div>
      `;

      $('#alert_message').html(html);
      alert_modal.showModal();
    }

    function submitAppointmentDetails(customer, pet, primaryPetId, service, timeSlot, selectedAdditionalServices, additionalServicesByPet, chauffeurSelected, isBoarding, boardingStart, boardingEnd, kennel, room, selectedRoomType) {

      $.ajax({
        url: '{{ route("get-validation-info") }}',
        method: 'POST',
        data: {
          pet_id: primaryPetId || null,
          pet_ids: pet,
          service_id: service,
          additional_services: selectedAdditionalServices,
          additional_services_by_pet: additionalServicesByPet,
        },
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        dataType: 'json',
        success: function(response) {
          if (chauffeurSelected && (!response.owner_address_valid || !response.facility_address_valid)) {
            showAddressValidationErrors(response.owner_address_valid, response.facility_address_valid);
            return;
          }

          let validationMessage = '';
          if (!response.owner_status) {
            validationMessage += '<li>Pet owner\'s profile is inactive.</li>';
          }
          if (response.vaccine_status === 'expired' || !response.vaccine_status) {
            const vaccineMessages = Array.isArray(response.vaccine_messages) && response.vaccine_messages.length
              ? response.vaccine_messages
              : [response.vaccine_message || (response.vaccine_status === 'expired' ? 'Pet vaccination is expired.' : 'Pet vaccination records is not approved.')];
            vaccineMessages.forEach(function(message) {
              validationMessage += '<li>' + message + '</li>';
            });
          }
          if (!response.questionnaire_status) {
            validationMessage += '<li>Pet questionnaire is not approved.</li>';
          }

          if (validationMessage) {
            $('#confirm_message').html(
              'Please address the following issues before updating the appointment:<br>' +
              '<ul style="list-style: disc; font-size: 14px; padding-left: 24px; padding-top: 6px;">' + validationMessage + '</ul>'
            );
            confirm_modal.showModal();
            return;
          }

          $('#update_form').submit();
        },
        error: function() {
          console.error('Failed to validate appointment details.');
          $('#alert_message').text('An error occurred while validating the appointment. Please try again.');
          alert_modal.showModal();
        }
      });
    }

    function changeAssignmentRoom() {
      $('#allow_assignment_conflict').val('0');
      $('#assignment_conflict_info').val('');
      assignment_modal.close();
    }

    function continueWithAssignmentConflict() {
      $('#allow_assignment_conflict').val('1');
      assignment_modal.close();
      proceedWithFormSubmission();
    }

    @php
      $canCreateEarlyBoardingDropoff = auth()->check()
        && auth()->user()->roles()->whereRaw('LOWER(title) = ?', ['owner'])->exists();
    @endphp

    const canCreateEarlyBoardingDropoff = @json($canCreateEarlyBoardingDropoff);

    /*
      Admin only (not in sunshine-laravel). Admin offers several services on this form, so
      these switch the page between sunshine's boarding form and the other services' form.
    */
    function isBoardingAdditionalService(additionalService) {
      const categoryName = additionalService && additionalService.category_name
        ? additionalService.category_name.toLowerCase()
        : '';

      return categoryName.includes('groom');
    }

    function applyPetSelectMode(isBoarding) {
      const $pet = $('#pet');

      if ($pet.prop('multiple') === isBoarding) {
        return;
      }

      const selectedPetIds = [].concat($pet.val() || []).filter(function(petId) {
        return String(petId) !== '';
      });

      if ($pet.hasClass('select2-hidden-accessible')) {
        $pet.select2('destroy');
      }

      if (isBoarding) {
        $pet.find('option[value=""]').remove();
        $pet.attr('name', 'pet[]').prop('multiple', true);
        $pet.select2({
          placeholder: "Choose pet(s)",
          allowClear: true,
          multiple: true,
          width: '100%',
          closeOnSelect: false
        });
        $pet.val(selectedPetIds);
      } else {
        $pet.attr('name', 'pet').prop('multiple', false);
        $pet.prepend('<option value="" hidden>Choose a pet</option>');
        $pet.val(selectedPetIds.length > 0 ? selectedPetIds[0] : '');
      }

      $('label[for="pet"]').text(isBoarding ? 'Pet(s)*' : 'Pet*');
    }

    function applyBoardingFormMode(isBoarding) {
      $('#service_fields').toggleClass('boarding-form-layout', isBoarding);
      applyPetSelectMode(isBoarding);
      $('#room').prop('disabled', !isBoarding);

      if (isBoarding) {
        // Selections that only belong to the other services must not be posted with boarding.
        $('#secondary_services').val(null).trigger('change.select2');
        $('#group_classes').val(null).trigger('change.select2');
        $('#group_classes_details').empty();
        if (!$('#packages').prop('disabled')) {
          $('#packages').val(null).trigger('change.select2');
          $('#packages_details').empty();
          $('#customer_package_id').val('');
        }
        return;
      }

      // Boarding only fields and state must not leak into the other services.
      $('#room_group').addClass('hidden');
      $('#kennel_group').addClass('hidden');
      $('#family_kennel_assignments_group').addClass('hidden');
      $('#family_kennel_assignments_container').empty();
      $('#kennel').prop('disabled', true);
      $('#additional_services_by_pet_container').addClass('hidden').empty();
      $('#additional_services_single_wrapper').removeClass('hidden');
      $('#additional_services').prop('disabled', false);
      $('#single_time_slot_wrapper').removeClass('hidden');
      $('#additional_service_time_slots_container').addClass('hidden').empty();
      $('#time_slot_group label').text('Start Time - End Time*');
      $('#allow_assignment_conflict').val('0');
      $('#assignment_conflict_info').val('');
      window.additionalServicesByPetState = {};
      window.selectedAdditionalServiceTimeslotsByPair = {};
    }

    function confirmAction() {
      const selectedStatus = $('#appointment_status').val();
      if (selectedStatus) {
        $('#form_status').val(selectedStatus === '' ? 'checked_in' : selectedStatus);
      }
      $('#update_form').submit();
    }

    function populateAllPackagesOptions() {
      $('#packages').empty();
      $('#packages').append('<option value="" hidden selected>Choose a package</option>');

      const packages = window.packagesData || [];
      $.each(packages, function(index, pkg) {
        const option = $('<option></option>')
          .attr('value', pkg.id)
          .attr('data-customer-package-id', '')
          .attr('data-package', JSON.stringify(pkg))
          .text(pkg.name);
        $('#packages').append(option);
      });

      $('#packages').trigger('change');
    }

    function loadCustomerPackages(customerId) {
      $.ajax({
        url: '{{ url("/appointment/customer-packages") }}/' + customerId,
        type: 'GET',
        dataType: 'json',
        success: function(customerPackages) {
          if (!customerPackages || customerPackages.length === 0) {
            populateAllPackagesOptions();
            return;
          }

          $('#packages').empty();
          $('#packages').append('<option value="" hidden selected>Choose a package</option>');

          $.each(customerPackages, function(index, cp) {
            const option = $('<option></option>')
              .attr('value', cp.id)
              .attr('data-customer-package-id', cp.customer_package_id || '')
              .attr('data-package', JSON.stringify(cp))
              .text(cp.name + (cp.remaining_days ? ' (Remaining: ' + cp.remaining_days + ' days)' : ''));
            $('#packages').append(option);
          });

          $('#packages').trigger('change');
        },
        error: function() {
          console.error('Failed to fetch customer packages.');
          populateAllPackagesOptions();
        }
      });
    }

    function renderGroupClassDetails() {
      const selectedIds = $('#group_classes').val() || [];
      const detailsDiv = $('#group_classes_details');
      detailsDiv.empty();
      if (selectedIds.length === 0) {
        return;
      }
      const classes = [
        @isset($groupClasses)
        @foreach($groupClasses as $gc)
          { id: '{{ $gc->id }}', name: '{{ addslashes($gc->name) }}', price: '{{ number_format($gc->price, 2) }}', duration: '{{ $gc->duration_amount . " " . $gc->duration_unit }}', schedule: '{{ addslashes($gc->schedule) }}', started_at: '{{ \Carbon\Carbon::parse($gc->started_at)->format('M d, Y') }}', description: `{!! addslashes($gc->description) !!}` },
        @endforeach
        @endisset
      ];
      selectedIds.forEach(function(id) {
        const c = classes.find(x => String(x.id) === String(id));
        if (c) {
          const html = `
            <div class="p-3 border border-base-300 rounded-box">
              <p class="font-medium">${c.name} - $${c.price}</p>
              <p class="text-sm text-base-content/70">Starts: ${c.started_at} | Duration: ${c.duration}</p>
              <p class="text-sm text-base-content/70">Schedule: ${c.schedule}</p>
              <p class="text-sm mt-2">${c.description || ''}</p>
            </div>
          `;
          detailsDiv.append(html);
        }
      });
    }

    function renderPackageDetails() {
      const selectedId = $('#packages').val();
      const detailsDiv = $('#packages_details');
      detailsDiv.empty();
      if (!selectedId) {
        return;
      }
      
      const packageData = window.packagesData.find(function(p) { return String(p.id) === String(selectedId); });
      
      if (packageData) {
        let servicesList = 'No services';
        if (packageData.service_ids) {
          const serviceIds = packageData.service_ids.split(',').map(id => id.trim()).filter(id => id);
          const serviceNames = [];
          serviceIds.forEach(function(id) {
            const service = window.servicesData.find(function(s) { return String(s.id) === String(id); });
            if (service) {
              serviceNames.push(service.name);
            }
          });
          if (serviceNames.length > 0) {
            servicesList = serviceNames.join(', ');
          } else {
            servicesList = serviceIds.length + ' service(s)';
          }
        }
        const html = `
          <div class="p-3 border border-base-300 rounded-box">
            <p class="font-medium">${packageData.name} - $${parseFloat(packageData.price).toFixed(2)}</p>
            ${packageData.days ? `<p class="text-sm text-base-content/70">Duration: ${packageData.days} day(s)</p>` : ''}
            <p class="text-sm text-base-content/70">Services: ${servicesList}</p>
            ${packageData.description ? `<p class="text-sm mt-2">${packageData.description}</p>` : ''}
          </div>
        `;
        detailsDiv.append(html);
      } else {
        console.error('Package data not found for ID:', selectedId);
      }
    }
  </script>
@endsection