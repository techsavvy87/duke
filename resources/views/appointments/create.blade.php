@extends('layouts.main')
@section('title', 'Create Appointment')

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
  <h3 class="text-lg font-medium">Create Appointment</h3>
  <div class="breadcrumbs hidden p-0 text-sm sm:inline">
    <ul>
      <li><a href="{{ route('dashboard') }}">PawPrints</a></li>
      <li><a href="{{ route('appointments') }}">Appointments</a></li>
      <li class="opacity-80">Create</li>
    </ul>
  </div>
</div>
<div class="mt-3">
  @include('layouts.alerts')
  <form action="{{ route('create-appointment') }}" method="POST" id="create_form">
    @csrf
    <input type="hidden" name="allow_assignment_conflict" id="allow_assignment_conflict" value="0" />
    <input type="hidden" name="assignment_conflict_info" id="assignment_conflict_info" value="" />
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        <div class="fieldset mt-2 grid grid-cols-1 gap-6 xl:grid-cols-2">
          <div class="space-y-2">
            <label class="fieldset-label" for="customer">Customer*</label>
            <select class="select w-full" name="customer" id="customer">
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
            <select class="select w-full" name="service" id="service" onchange="changeService(this)" @if ($serviceId) disabled @endif>
              <option value="" hidden selected>Choose a service</option>
              @foreach($services as $service)
                <option value="{{ $service->id }}" @if ($service->id == $serviceId) selected @endif>{{ $service->name }}</option>
              @endforeach
            </select>
            @if ($serviceId)
              <input type="hidden" name="service" value="{{ $serviceId }}" />
            @endif
          </div>
          <div class="xl:col-span-2" id="additional_services_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="additional_services">Additional Services</label>
              <div id="additional_services_single_wrapper">
                <select class="select w-full" name="additional_services[]" id="additional_services" multiple>
                  @foreach($additionalServices as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
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
                @foreach($secondaryServices as $service)
                  <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="xl:col-span-2 hidden" id="group_classes_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="group_classes">Group Classes*</label>
              <select class="select w-full" name="group_class_ids[]" id="group_classes" multiple>
                @isset($groupClasses)
                  @foreach($groupClasses as $cls)
                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                  @endforeach
                @endisset
              </select>
              <div id="group_classes_details" class="mt-3 space-y-2"></div>
            </div>
          </div>
          <div class="xl:col-span-2 hidden" id="packages_group">
            <div class="space-y-2">
              <label class="fieldset-label" for="packages">Packages*</label>
              <select class="select w-full" name="package_id" id="packages">
                <option value="" hidden selected>Choose a package</option>
                @isset($packages)
                  @foreach($packages as $package)
                    <option value="{{ $package->id }}" data-package='@json($package)'>{{ $package->name }}</option>
                  @endforeach
                @endisset
              </select>
              <input type="hidden" name="customer_package_id" id="customer_package_id" />
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
                <p class="text-start" id="button_cally_target">-</p>
                <span class="iconify lucide--chevron-down text-base-content/70 size-4"></span>
              </div>
              <div class="dropdown-content mt-2" tabindex="0">
                <calendar-date class="cally bg-base-100 rounded-box shadow-md transition-all hover:shadow-lg" id="button_cally_element" value="-" >
                  <span class="iconify lucide--chevron-left" slot="previous"></span>
                  <span class="iconify lucide--chevron-right" slot="next"></span>
                  <calendar-month></calendar-month>
                </calendar-date>
              </div>
            </div>
          </div>
          <div class="space-y-2 hidden" id="daycare_duration_group">
            <label class="fieldset-label" for="daycare_duration">Duration*</label>
            <select class="select w-full" name="daycare_duration" id="daycare_duration">
              <option value="" hidden selected>Choose duration</option>
              <option value="half">Half Day</option>
              <option value="full">Full Day</option>
            </select>
          </div>
          <div class="space-y-2 hidden" id="private_training_duration_group">
            <label class="fieldset-label" for="private_training_duration">Duration*</label>
            <select class="select w-full" name="private_training_duration" id="private_training_duration">
              <option value="" hidden selected>Choose duration</option>
              <option value="half">Half Hour</option>
              <option value="one">One Hour</option>
            </select>
          </div>
          <div class="space-y-2" id="time_slot_group">
            <label class="fieldset-label" for="time_slot">Start Time - End Time*</label>
            <div id="single_time_slot_wrapper">
              <select class="select w-full" name="time_slot" id="time_slot">
                <option value="" hidden selected>Choose a time slot</option>
              </select>
              <input type="hidden" name="time_slot_data" id="time_slot_data" />
            </div>
            <div id="additional_service_time_slots_container" class="hidden space-y-3"></div>
          </div>
          <div class="space-y-2 hidden" id="boarding_start_group">
            <label class="fieldset-label">Drop Off Date/Time*</label>
            <input type="datetime-local" class="input w-full" id="boarding_start_datetime" name="boarding_start_datetime" format="YYYY-MM-DD HH:mm" placeholder="Select drop off date/time" />
          </div>
          <div class="space-y-2 hidden" id="boarding_end_group">
            <label class="fieldset-label">Pick Up Date/Time*</label>
            <input type="datetime-local" class="input w-full" id="boarding_end_datetime" name="boarding_end_datetime" format="YYYY-MM-DD HH:mm" placeholder="Select drop off date/time" />
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
                >{{ $room->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="space-y-2 hidden" id="kennel_group">
            <label class="fieldset-label" for="kennel">Kennel*</label>
            <select class="select w-full" name="kennel" id="kennel">
              <option value="" hidden selected>Choose a kennel</option>
              @foreach($kennels as $kennel)
                <option value="{{ $kennel->id }}" data-kennel-type="{{ $kennel->kennel_type ?? 'Canine' }}">{{ $kennel->name }} ({{ $kennel->kennel_type ?? 'Canine' }})</option>
              @endforeach
            </select>
          </div>
          <div class="space-y-2 hidden xl:col-span-4" id="family_kennel_assignments_group">
            <label class="fieldset-label">Kennel Assignments*</label>
            <div id="family_kennel_assignments_container" class="grid grid-cols-1 gap-3 xl:grid-cols-2"></div>
          </div>
        </div>
        <div class="fieldset mt-3 grid grid-cols-1 gap-6 xl:grid-cols-2" id="staff_group">
          <div class="space-y-2">
            <label class="fieldset-label" for="staff">Staff</label>
            <select class="select w-full" name="staff" id="staff">
              <option value="" hidden selected>Choose a staff</option>
            </select>
          </div>
        </div>
        <div class="fieldset mt-3 grid grid-cols-1 gap-6">
          <div class="space-y-2" id="appointment_notes_group">
            <label class="fieldset-label" for="appointment_notes">Appointment Notes</label>
            <textarea class="textarea w-full" id="appointment_notes" name="appointment_notes" rows="3">{{ old('appointment_notes') }}</textarea>
          </div>
          <div class="space-y-2" id="wait_listed_group">
            <label class="label cursor-pointer justify-start gap-3 px-0">
              <input type="checkbox" name="is_wait_listed" id="is_wait_listed" class="checkbox checkbox-sm" value="1" />
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
      <button class="btn btn-sm btn-primary" type="button" id="save_appointment_btn" onclick="saveAppointment()">
        <span class="loading loading-spinner size-3.5" style="display:none;"></span>
        <span class="iconify lucide--check size-4 save-icon"></span>
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
      <button class="btn btn-primary btn-sm btn-soft" onclick="confirmAction()">Yes</button>
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
    /*
      Several fields are rebuilt dynamically with Select2 and then trigger
      change events programmatically. In some browsers this can force the page
      back to the top because the rebuilt Select2 input receives focus.
      This keeps the current scroll position after programmatic changes only,
      without changing the existing form logic.
    */
    function preserveScrollAfterProgrammaticChange() {
      $(document).on('change', 'select, input', function(event) {
        if (event.originalEvent) {
          return;
        }

        // Admin adaptation: only the boarding form rebuilds its fields.
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        const scrollX = window.scrollX || window.pageXOffset || 0;
        const scrollY = window.scrollY || window.pageYOffset || 0;

        if (scrollY <= 0) {
          return;
        }

        window.requestAnimationFrame(function() {
          if ((window.scrollY || window.pageYOffset || 0) < scrollY) {
            window.scrollTo(scrollX, scrollY);
          }
        });

        window.setTimeout(function() {
          if ((window.scrollY || window.pageYOffset || 0) < scrollY) {
            window.scrollTo(scrollX, scrollY);
          }
        }, 50);
      });
    }

    document.getElementById("button_cally_element")?.addEventListener("change", (e) => {
      document.getElementById("button_cally_target").innerText = e.target.value

      const serviceId = $('#service').val();
      const petId = $('#pet').val();
      const daycareDuration = $('#daycare_duration').val();
      const privateTrainingDuration = $('#private_training_duration').val();

      populateTimeSlots(serviceId, e.target.value, petId, daycareDuration, privateTrainingDuration);
    });

    $(document).ready(function() {
      preserveScrollAfterProgrammaticChange();
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
      window.initialFamilyPetAssignments = {};

      $('#customer').select2({
        placeholder: "Choose a customer",
        ajax: {
          url: '{{ route("get-appointment-customers") }}',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              q: params.term
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
          var $container = $(`
            <div class="flex items-center gap-2">
              <span class="font-medium">${customer.first_name} ${customer.last_name}</span>
              <span class="text-sm text-base-content/70">(${customer.email} | ${customer.phone_number})</span>
            </div>
          `);
          return $container;
        }
      });

      $('#customer').on('select2:select', function (e) {
        const selectedData = e.params.data;
        const customerId = selectedData.id;

        // Fetch pets for the selected customer
        $.ajax({
          url: '{{ url("/appointment/pets") }}/' + customerId,
          type: 'GET',
          dataType: 'json',
          success: function(pets) {
            const isBoarding = isBoardingSelectedService($('#service').val());

            // Clear existing options
            $('#pet').empty();
            if (!isBoarding) {
              $('#pet').append('<option value="" hidden selected>Choose a pet</option>');
            }

            // Populate the pet dropdown with new options
            $.each(pets, function(index, pet) {
              const petType = pet.type || '';
              const petSize = pet.size || '';
              $('#pet').append('<option value="' + pet.id + '" data-pet-type="' + petType + '" data-pet-size="' + petSize + '">' + pet.name + '</option>');
            });

            if (isBoarding) {
              $('#pet').val([]).trigger('change');
            }
          },
          error: function() {
            console.error('Failed to fetch pets for the selected customer.');
          }
        });

        const serviceId = $('#service').val();
        if (serviceId) {
          const service = window.servicesData.find(function(s) { return String(s.id) === String(serviceId); });
          if (service && service.category_name && service.category_name.toLowerCase().includes('package')) {
            loadCustomerPackages(customerId);
          }
        }
      });

      $('#pet').on('change', function() {
        if (isBoardingSelectedService($('#service').val())) {
          renderAdditionalServicesByPetSelectors();
          updateBoardingLocationField();
          refreshAdditionalServiceTimeSlotsIfNeeded();
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
          var $container = $(`
            <div class="flex items-center gap-2">
              <span class="font-medium">${staff.first_name} ${staff.last_name}</span>
              <span class="text-sm text-base-content/70">(${staff.email} | ${staff.phone_number})</span>
            </div>
          `);
          return $container;
        }
      });

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

      $('#room').on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        refreshAvailableKennels();
      });

      $('#boarding_start_datetime, #boarding_end_datetime').on('change', function() {
        if (!isBoardingSelectedService($('#service').val())) {
          return;
        }

        refreshAdditionalServiceTimeSlotsIfNeeded();
        refreshAvailableKennels();
      });

      window.originalAdditionalOptions = $('#additional_services').html();
      window.additionalServicesByPetState = {};
      window.selectedAdditionalServiceTimeslotsByPair = {};

      // Define servicesData globally so it's accessible to all functions
      window.servicesData = [];
      @foreach($services->merge($secondaryServices) as $s)
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

        syncAdditionalServiceTimeSlotVisibility();
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

      var selectedServiceId = $('#service').val();
      if (selectedServiceId) {
        checkServiceType(selectedServiceId);
        updateAdditionalServices(selectedServiceId);

        if (isBoardingSelectedService(selectedServiceId)) {
          renderAdditionalServicesByPetSelectors();
          updateBoardingLocationField();
          syncAdditionalServiceTimeSlotVisibility();
          refreshAdditionalServiceTimeSlotsIfNeeded();
          refreshAvailableKennels();
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

      $('#group_classes').select2({
        placeholder: "Select group classes",
        multiple: true,
        width: '100%',
        closeOnSelect: false
      }).on('change', function() {
        renderGroupClassDetails();
      });

      $('#packages').select2({
        placeholder: "Choose a package",
        width: '100%',
        allowClear: true
      }).on('change', function() {
        renderPackageDetails();
      });
    });

    function changeService(ele) {
      applyBoardingFormMode(isBoardingSelectedService($(ele).val()));

      if (isBoardingSelectedService($(ele).val())) {
        const serviceId = $(ele).val();

        $('#time_slot').empty();
        $('#time_slot').append('<option value="" hidden selected>Choose a time slot</option>');
        $('#time_slot_data').val('');
        $('#additional_services').empty();

        checkServiceType(serviceId);
        updateAdditionalServices(serviceId);
        renderAdditionalServicesByPetSelectors();
        syncAdditionalServiceTimeSlotVisibility();
        refreshAdditionalServiceTimeSlotsIfNeeded();
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

      // Clear additional services selection and repopulate based on new service
      $('#additional_services').empty();

      checkServiceType(serviceId);

      const secondaryServiceIds = $('#secondary_services').val() || [];
      populateTimeSlots(serviceId, date, petId, daycareDuration, privateTrainingDuration, secondaryServiceIds);
      updateAdditionalServices(serviceId);
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
        updateBoardingLocationField();
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

    function updateAdditionalServices(selectedServiceId) {
      if (isBoardingSelectedService(selectedServiceId)) {
        var currentValues = $('#additional_services').val() || [];

        try {
          $('#additional_services').select2('destroy');
        } catch (e) {
          console.error('Failed to destroy select2 for additional services.');
        }

        $('#additional_services').html(window.originalAdditionalOptions);

        var service = window.servicesData.find(function(s) { return s.id == selectedServiceId; });
        const isBoarding = service && service.category_name && service.category_name.toLowerCase().includes('boarding');

        if (isBoarding) {
          $('#additional_services option').each(function() {
            var optionVal = $(this).val();
            var additionalService = window.additionalServicesData.find(function(s) {
              return String(s.id) === String(optionVal);
            });

            // Admin adaptation: admin lists every service here, sunshine only grooming ones.
            if (!isBoardingAdditionalService(additionalService)) {
              $(this).remove();
            }
          });
        }

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

          syncAdditionalServiceTimeSlotVisibility();
        });

        if (currentValues.length > 0) {
          $('#additional_services').val(currentValues).trigger('change');
        } else {
          syncAdditionalServiceTimeSlotVisibility();
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

      if (currentValues.length > 0) {
        $('#additional_services').val(currentValues).trigger('change');
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
                  displayText = formatTimeToAMPM(slot.start_time) + ' - ' + formatTimeToAMPM(slot.end_time);
                } else {
                  const start = formatTimeToAMPM(slot.start_time);
                  const end = formatTimeToAMPM(slot.end_time);
                  displayText = start + ' - ' + end;
                }
                const disabled = slot.status !== 'available' ? 'disabled' : '';
                const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);
                $('#time_slot').append('<option value="' + slotValue + '" ' + disabled + ' data-slot-data="' + encodeURIComponent(JSON.stringify(slot)) + '">' + displayText + '</option>');
              });
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
                displayText = formatTimeToAMPM(slot.start_time) + ' - ' + formatTimeToAMPM(slot.end_time);
              } else {
                const start = formatTimeToAMPM(slot.start_time);
                const end = formatTimeToAMPM(slot.end_time);
                displayText = start + ' - ' + end;
              }
              const disabled = slot.status !== 'available' ? 'disabled' : '';
              const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);
              $('#time_slot').append('<option value="' + slotValue + '" ' + disabled + ' data-slot-data="' + encodeURIComponent(JSON.stringify(slot)) + '">' + displayText + '</option>');
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
          <p>Please address the following issues before creating the appointment:</p>
          <ul style="list-style: none; font-size: 14px; padding-top: 6px;">${messages.join('')}</ul>
        </div>
      `;

      $('#alert_message').html(html);
      alert_modal.showModal();
    }

    function setSaveButtonLoading(isLoading) {
      const $saveButton = $('#save_appointment_btn');
      if ($saveButton.length === 0) {
        return;
      }

      if (isLoading) {
        $saveButton.find('.loading').css('display', 'inline-block');
        $saveButton.find('.save-icon').css('display', 'none');
        $saveButton.prop('disabled', true);
        $saveButton.contents().filter(function() {
          return this.nodeType === 3 && this.nodeValue.trim() === 'Save';
        }).remove();
        if ($saveButton.contents().filter(function() {
          return this.nodeType === 3 && this.nodeValue.trim() === 'Loading';
        }).length === 0) {
          $saveButton.append('Loading');
        }
      } else {
        $saveButton.find('.loading').css('display', 'none');
        $saveButton.find('.save-icon').css('display', 'inline-block');
        $saveButton.prop('disabled', false);
        $saveButton.contents().filter(function() {
          return this.nodeType === 3 && this.nodeValue.trim() === 'Loading';
        }).remove();
        if ($saveButton.contents().filter(function() {
          return this.nodeType === 3 && this.nodeValue.trim() === 'Save';
        }).length === 0) {
          $saveButton.append('Save');
        }
      }
    }

    function saveAppointment() {
      if ($('#save_appointment_btn').prop('disabled')) {
        return;
      }

      if (isBoardingSelectedService($('#service').val())) {
        const customer = $('#customer').val();
        const pet = getSelectedPetIds();
        const primaryPetId = getPrimaryPetId();
        const service = $('#service').val();
        const date = $('#button_cally_target').text();
        const timeSlot = $('#time_slot').val();

        const isBoarding = $('#boarding_start_group').is(':visible');
        const isWaitListed = $('#is_wait_listed').is(':checked');
        const boardingStart = $('#boarding_start_datetime').val();
        const boardingEnd = $('#boarding_end_datetime').val();
        const scheduledAdditionalServiceId = getSelectedAdditionalServiceForTimeSlot();
        const kennel = $('#kennel').val();
        const room = $('#room').val();
        const selectedRoomType = getSelectedRoomType();
        const familyKennelMode = getSelectedPetAssignmentMode();
        const familyPetAssignments = familyKennelMode === 'individual' ? getSelectedFamilyPetAssignments() : {};

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

        if (isBoarding && scheduledAdditionalServiceId && hasMissingAdditionalServiceTimeSlots()) {
          $('#alert_message').text('Please select a valid time slot for each additional service.');
          alert_modal.showModal();
          return;
        }

        if (isBoarding) {
          $('#date').val('');
          if (!scheduledAdditionalServiceId) {
            $('#time_slot_group').addClass('hidden');
            $('#time_slot').val('').trigger('change');
            $('#time_slot_data').val('');
          }
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
          const isDropOffInvalid = boardingStartMinutes === null
            || isEarlyDropOff
            || isLateDropOff;
          const isPickUpInvalid = !isWithinBusinessHours(boardingEnd);

          if (isDropOffInvalid && isPickUpInvalid) {
            $('#alert_message').text('Drop off and Pick up must be between 7:30 AM and 5:30 PM.');
            alert_modal.showModal();
            return;
          }
          if (isPickUpInvalid) {
            $('#alert_message').text('Pick up must be before 5:30 PM.');
            alert_modal.showModal();
            return;
          }
          if (isDropOffInvalid) {
            $('#alert_message').text('Drop off must be after 7:30 AM.');
            alert_modal.showModal();
            return;
          }
        }

        const selectedAdditionalServices = collectSelectedAdditionalServiceIds();
        const additionalServicesByPet = getPerPetAdditionalServicesPayload();
        const chauffeurSelected = hasSelectedChauffeurAdditionalService(selectedAdditionalServices);

        const submitAppointment = function() {
          setSaveButtonLoading(true);

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
              setSaveButtonLoading(false);

              let validationMessage = '';
              if (!response.owner_status) {
                validationMessage += `<li>Pet owner's profile is inactive.</li>`;
              }
              if (response.vaccine_status === 'expired' || !response.vaccine_status) {
                const vaccineMessages = Array.isArray(response.vaccine_messages) && response.vaccine_messages.length
                  ? response.vaccine_messages
                  : [response.vaccine_message || (response.vaccine_status === 'expired' ? 'Pet vaccination is expired.' : 'Pet vaccination records is not approved.')];
                vaccineMessages.forEach(function(message) {
                  validationMessage += '<li>' + message + '</li>';
                });
              }
              if (response.questionnaire_required && !response.questionnaire_status) {
                validationMessage += '<li>Pet questionnaire is not approved.</li>';
              }
              if (validationMessage) {
                validationMessage = `Please address the following issues before creating the appointment:<br>
                  <ul style="list-style: disc; font-size: 14px; padding-left: 24px; padding-top: 6px;">${validationMessage}</ul>`;
                $('#confirm_message').html(validationMessage);
                confirm_modal.showModal();
              } else {
                $('#create_form').attr('action', '{{ route("create-appointment") }}');
                $('#create_form').submit();
              }
            },
            error: function() {
              setSaveButtonLoading(false);
              console.error('Failed to validate appointment details.');
              $('#alert_message').text('An error occurred while validating the appointment. Please try again.');
              alert_modal.showModal();
            }
          });
        };

        if (!isBoarding) {
          submitAppointment();
          return;
        }

        if (isWaitListed) {
          submitAppointment();
          return;
        }

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
          },
          success: function(response) {
            if (response.conflict && $('#allow_assignment_conflict').val() !== '1') {
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

            submitAppointment();
          },
          error: function() {
            setSaveButtonLoading(false);
            $('#alert_message').text('An error occurred while validating the assignment. Please try again.');
            alert_modal.showModal();
          }
        });

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

      if (!isGroupClasses && isDaycare && !daycareDuration) {
        $('#alert_message').text('Please select Half Day or Full Day for daycare service.');
        alert_modal.showModal();
        return;
      }

      if (!isGroupClasses && isPrivateTraining && !privateTrainingDuration) {
        $('#alert_message').text('Please select Half Hour or One Hour for private training service.');
        alert_modal.showModal();
        return;
      }

      if (isGroupClasses || isBoarding) {
        $('#date').val('');
        $('#time_slot').val('').trigger('change');
      } else if (isPackage) {
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
      const selectedAdditionalServices = $('#additional_services').val() || [];
      const chauffeurSelected = hasSelectedChauffeurAdditionalService(selectedAdditionalServices);

      setSaveButtonLoading(true);

      $.ajax({
        url: '{{ route("get-validation-info") }}',
        method: 'POST',
        data: {
          pet_id: pet,
          service_id: service,
          package_id: packageId,
          additional_services: selectedAdditionalServices,
        },
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        dataType: 'json',
        success: function(response) {
          setSaveButtonLoading(false);

          if (chauffeurSelected && (!response.owner_address_valid || !response.facility_address_valid)) {
            showAddressValidationErrors(response.owner_address_valid, response.facility_address_valid);
            return;
          }

          let validationMessage = '';
          if (!response.owner_status) {
            validationMessage += `<li>Pet owner's profile is inactive.</li>`;
          }
          if (response.vaccine_status === 'expired') {
            validationMessage += '<li>Pet vaccination is expired.</li>';
          } else if (!response.vaccine_status) {
            validationMessage += '<li>Pet vaccination records is not approved.</li>';
          }
          if (!response.questionnaire_status) {
            const isPackageCheck = $('#packages_group').is(':visible');
            if (isPackageCheck) {
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
            // Set form action based on appointment type
            const isPackageCheck = $('#packages_group').is(':visible');
            const packageSelectedCheck = $('#packages').val();
            if (isPackageCheck && packageSelectedCheck) {
              $('#create_form').attr('action', '{{ route("create-package-appointment") }}');
            } else {
              $('#create_form').attr('action', '{{ route("create-appointment") }}');
            }
            $('#create_form').submit();
          }
        },
        error: function() {
          setSaveButtonLoading(false);
          console.error('Failed to validate appointment details.');
          $('#alert_message').text('An error occurred while validating the appointment. Please try again.');
          alert_modal.showModal();
        }
      });
    }

    /*
      Boarding form (ported from sunshine-laravel's appointments/create.blade.php).
      These functions are kept identical to sunshine-laravel.
    */
    function isBoardingSelectedService(serviceId) {
      const service = window.servicesData.find(function(s) {
        return String(s.id) === String(serviceId);
      });

      return !!(service && service.category_name && service.category_name.toLowerCase().includes('boarding'));
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
        renderFamilyPetAssignmentFields(getSelectedFamilyPetAssignments());
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
      renderKennelOptions(roomKennels, currentKennel);
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

      // Admin adaptation: every pet of a family stay gets its own room and kennel,
      // sunshine only splits the family when one of the pets is not small.
      return 'individual';
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
        $('#additional_services_by_pet_container').addClass('hidden').empty();
        $('#additional_services_single_wrapper').removeClass('hidden');
        $('#additional_services').prop('disabled', false);
        if (singlePetId && Array.isArray(window.additionalServicesByPetState[singlePetId])) {
          $('#additional_services').val(window.additionalServicesByPetState[singlePetId]).trigger('change');
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
        const currentSelections = (window.additionalServicesByPetState[pet.id] || []).map(function(serviceId) {
          return String(serviceId);
        });
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
        syncAdditionalServiceTimeSlotVisibility();
      });

      $('#additional_services_single_wrapper').addClass('hidden');
      $('#additional_services').prop('disabled', true).val([]).trigger('change');
      $('#additional_services_by_pet_container').removeClass('hidden');
    }

    function getSelectedAdditionalServiceForTimeSlot() {
      const selectedAdditionalServiceIds = collectSelectedAdditionalServiceIds();
      return selectedAdditionalServiceIds.length > 0 ? selectedAdditionalServiceIds[0] : null;
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
        renderFamilyPetAssignmentFields(getSelectedFamilyPetAssignments());
        return;
      }

      $('#room_group').removeClass('hidden');

      if (shouldUseRoomForSelectedPets()) {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
        $('#kennel').val('').trigger('change');
      } else if ($('#room').val()) {
        refreshAvailableKennels();
      } else {
        $('#kennel_group').addClass('hidden');
        $('#family_kennel_assignments_group').addClass('hidden');
        $('#kennel').prop('disabled', true);
      }
    }

    function syncAdditionalServiceTimeSlotVisibility() {
      const selectedServiceId = $('#service').val();
      const selectedService = window.servicesData.find(function(s) {
        return String(s.id) === String(selectedServiceId);
      });
      const isBoarding = selectedService && selectedService.category_name && selectedService.category_name.toLowerCase().includes('boarding');

      if (!isBoarding) {
        $('#single_time_slot_wrapper').removeClass('hidden');
        $('#additional_service_time_slots_container').addClass('hidden').empty();
        $('#time_slot_group').removeClass('hidden');
        $('#time_slot_group label').text('Start Time - End Time*');
        return;
      }

      const selectedAdditionalServiceIds = collectSelectedAdditionalServiceIds();

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
        const petId = getPrimaryPetId();
        const boardingEndDateTime = $('#boarding_end_datetime').val();
        const pickupDate = boardingEndDateTime ? boardingEndDateTime.split('T')[0] : '';
        const pickupTime = boardingEndDateTime ? boardingEndDateTime.split('T')[1] : '';

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
      renderAdditionalServiceTimeSlotSelectors();
    }

    function refreshAdditionalServiceTimeSlotsIfNeeded() {
      const selectedServiceId = $('#service').val();
      const selectedService = window.servicesData.find(function(s) {
        return String(s.id) === String(selectedServiceId);
      });
      const isBoarding = selectedService && selectedService.category_name && selectedService.category_name.toLowerCase().includes('boarding');

      if (!isBoarding) {
        return;
      }

      if (getSelectedPetIds().length <= 1) {
        const additionalServiceId = getSelectedAdditionalServiceForTimeSlot();
        const petId = getPrimaryPetId();
        const boardingEndDateTime = $('#boarding_end_datetime').val();
        const pickupDate = boardingEndDateTime ? boardingEndDateTime.split('T')[0] : '';
        const pickupTime = boardingEndDateTime ? boardingEndDateTime.split('T')[1] : '';

        if (!additionalServiceId || !petId || !pickupDate || !pickupTime) {
          return;
        }

        populateTimeSlots(additionalServiceId, pickupDate, petId, pickupTime, true);
        return;
      }

      renderAdditionalServiceTimeSlotSelectors();
    }

    function getAdditionalServiceTimeSlotSelections() {
      return Object.assign({}, window.selectedAdditionalServiceTimeslotsByPair || {});
    }

    function getSelectedAdditionalServicePairsByPet() {
      const selectedPets = getSelectedPetDetails();
      const perPetPayload = getPerPetAdditionalServicesPayload();
      const pairs = [];

      selectedPets.forEach(function(pet) {
        const petId = String(pet.id);
        const serviceIds = (perPetPayload[petId] || []).map(function(serviceId) {
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

    function hasMissingAdditionalServiceTimeSlots() {
      const selectedAdditionalServiceIds = collectSelectedAdditionalServiceIds();
      if (selectedAdditionalServiceIds.length === 0) {
        return false;
      }

      if (getSelectedPetIds().length <= 1) {
        return !$('#time_slot').val();
      }

      const selectedPairs = getSelectedAdditionalServicePairsByPet();
      if (selectedPairs.length === 0) {
        return false;
      }

      const slotSelections = getAdditionalServiceTimeSlotSelections();
      return selectedPairs.some(function(pair) {
        return !slotSelections[pair.pairKey];
      });
    }

    function renderAdditionalServiceTimeSlotSelectors() {
      const selectedPairs = getSelectedAdditionalServicePairsByPet();
      const boardingEndDateTime = $('#boarding_end_datetime').val();
      const pickupDate = boardingEndDateTime ? boardingEndDateTime.split('T')[0] : '';
      const pickupTime = boardingEndDateTime ? boardingEndDateTime.split('T')[1] : '';
      const previousSelections = getAdditionalServiceTimeSlotSelections();

      const $container = $('#additional_service_time_slots_container');
      $container.empty();

      pruneAdditionalServiceTimeSlotState();

      if (selectedPairs.length === 0) {
        $container.addClass('hidden');
        return;
      }

      selectedPairs.forEach(function(pair) {
        const service = window.additionalServicesData.find(function(item) {
          return String(item.id) === String(pair.serviceId);
        });

        const serviceName = service ? service.name : 'Additional Service';
        const selectId = 'additional_service_time_slot_' + pair.petId + '_' + pair.serviceId;

        $container.append(`
          <div class="space-y-2 rounded-box border border-base-300 p-3">
            <label class="fieldset-label" for="${selectId}">${pair.petName} - ${serviceName}*</label>
            <select class="select w-full additional-service-time-slot-select" id="${selectId}" name="additional_service_time_slots_by_pet[${pair.petId}][${pair.serviceId}]" data-service-id="${pair.serviceId}" data-pair-key="${pair.pairKey}" data-pet-id="${pair.petId}">
              <option value="" hidden selected>Choose a time slot</option>
            </select>
          </div>
        `);
      });

      $container.removeClass('hidden');

      if (!pickupDate || !pickupTime) {
        $('.additional-service-time-slot-select').each(function() {
          $(this).empty().append('<option value="" hidden selected>Select pet and pick up time first</option>');
        });
        return;
      }

      $('.additional-service-time-slot-select').each(function() {
        const $select = $(this);
        const serviceId = String($select.data('service-id'));
        const petId = String($select.data('pet-id'));
        const pairKey = String($select.data('pair-key'));
        populateAdditionalServiceTimeSlotOptions($select, serviceId, pickupDate, petId, pickupTime, previousSelections[pairKey] || '');
      });

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

          timeSlots.forEach(function(slot) {
            const slotValue = slot.is_virtual ? slot.start_time : (slot.id || slot.start_time);
            const isSelected = selectedSlotId && String(slotValue) === String(selectedSlotId);
            const disabled = slot.status !== 'available' ? 'disabled' : '';
            const displayText = formatTimeToAMPM(slot.start_time) + ' - ' + formatTimeToAMPM(slot.end_time);
            $select.append('<option value="' + slotValue + '" ' + disabled + (isSelected ? ' selected' : '') + '>' + displayText + '</option>');
          });

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

    function changeAssignmentRoom() {
      $('#allow_assignment_conflict').val('0');
      $('#assignment_conflict_info').val('');
      assignment_modal.close();
    }

    function continueWithAssignmentConflict() {
      $('#allow_assignment_conflict').val('1');
      assignment_modal.close();
      saveAppointment();
    }


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
      confirm_modal.close();
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

    function loadCustomerPackages(customerId) {
      $.ajax({
        url: '{{ url("/appointment/customer-packages") }}/' + customerId,
        type: 'GET',
        dataType: 'json',
        success: function(customerPackages) {
          $('#packages').empty();
          $('#packages').append('<option value="" hidden selected>Choose a package</option>');

          $.each(customerPackages, function(index, cp) {
            const option = $('<option></option>')
              .attr('value', cp.id)
              .attr('data-customer-package-id', cp.customer_package_id || '')  // Store customer_package_id
              .attr('data-package', JSON.stringify(cp))
              .text(cp.name + (cp.remaining_days ? ' (Remaining: ' + cp.remaining_days + ' days)' : ''));
            $('#packages').append(option);
          });

          $('#packages').trigger('change');
        },
        error: function() {
          console.error('Failed to fetch customer packages.');
          $('#packages').empty();
          $('#packages').append('<option value="" hidden selected>Choose a package</option>');
          $('#packages').trigger('change');
        }
      });
    }

    function renderPackageDetails() {
      const selectedId = $('#packages').val();
      const detailsDiv = $('#packages_details');
      detailsDiv.empty();
      if (!selectedId) {
        $('#customer_package_id').val('');
        return;
      }
      const selectedOption = $('#packages option:selected');
      const packageData = selectedOption.data('package');
      const customerPackageId = selectedOption.attr('data-customer-package-id');
      
      if (customerPackageId) {
        $('#customer_package_id').val(customerPackageId);
      } else {
        $('#customer_package_id').val('');
      }
      
      if (packageData) {
        let servicesList = 'No services';
        if (packageData.service_ids) {
          const serviceIds = packageData.service_ids.split(',').map(id => id.trim());
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
            ${packageData.remaining_days !== undefined ? `<p class="text-sm text-base-content/70">Remaining Days: ${packageData.remaining_days} day(s)</p>` : (packageData.days ? `<p class="text-sm text-base-content/70">Remaining Days: ${packageData.days} day(s)</p>` : '')}
            <p class="text-sm text-base-content/70">Services: ${servicesList}</p>
            ${packageData.description ? `<p class="text-sm mt-2">${packageData.description}</p>` : ''}
          </div>
        `;
        detailsDiv.append(html);
      }
    }


    // Initialize modals
    const confirm_modal = document.getElementById('confirm_modal');
    const assignment_modal = document.getElementById('assignment_modal');
  </script>
@endsection