{{-- Service-specific checkout details. Entered during In Progress / On Property for the services with a
     service report workflow (see hasServiceReportWorkflow), and on the Completed page for the others.
     Expects: $appointment, $checkout, $lastAppointmentRatings; optional: $petBehaviors, $showServiceNotes, $ratingSubject. --}}
@php
  $showServiceNotes = $showServiceNotes ?? true;
  $ratingSubject = $ratingSubject ?? strtolower($appointment->service->name);
@endphp
<div class="mt-4 space-y-2 text-sm">
  @if (isPrivateTrainingService($appointment->service))
  @if(!empty($lastAppointmentRatings))
  <div>
    <p class="font-medium mb-2">Star Rating from Last Appointment:</p>
    @php
      $obedienceCommands = ['sit', 'down', 'stay', 'come', 'loose_leash_walking'];
    @endphp
    <div class="mb-4 ms-4 space-y-3">
      @foreach($obedienceCommands as $command)
        @php
          $commandLabel = ucwords(str_replace('_', ' ', $command));
          $lastRating = isset($lastAppointmentRatings[$command]) ? (int)$lastAppointmentRatings[$command] : 0;
        @endphp
        <div class="flex items-center gap-2">
          <p class="text-sm font-medium mb-1">{{ $commandLabel }}:</p>
          <div class="flex items-center gap-1">
            @for($i = 0; $i <= 5; $i++)
              <span class="iconify lucide--star size-5" style="color: {{ $i <= $lastRating ? '#fbbf24' : '#d1d5db' }};"></span>
            @endfor
          </div>
        </div>
      @endforeach
    </div>
  </div>
  <hr class="my-4" style="color: lightgray"/>
  @endif
  <div>
    <div class="flex items-center gap-3 mb-2">
      <p class="font-medium" style="flex-grow: unset">Basic obedience (5-star rating)</p>
      <div class="dropdown dropdown-end dropdown-hover">
        <div tabindex="0" role="button" class="cursor-help">
          <span class="iconify lucide--info size-4 mt-1"></span>
        </div>
        <div tabindex="0" class="dropdown-content z-[1] menu p-4 shadow bg-base-100 rounded-box w-80 border border-base-300" style="left: -10px">
          <div class="space-y-2 text-xs">
            <p class="font-semibold text-sm mb-2">5 Star Rating System:</p>
            <p><span class="font-medium">0 star</span> – pet does not recognize command</p>
            <p><span class="font-medium">1 star</span> – pet has infrequent response to command in a quiet environment</p>
            <p><span class="font-medium">2 stars</span> – pet has intermittent response to command but highly distracted</p>
            <p><span class="font-medium">3 stars</span> – pet has regular response to command but effected by distractions</p>
            <p><span class="font-medium">4 stars</span> – pet has reliable response to command but has some challenges when distracted</p>
            <p><span class="font-medium">5 stars</span> – pet has dependable response to command regardless of environment</p>
          </div>
        </div>
      </div>
    </div>
    @php
      $obedienceRatings = $checkout && $checkout->flows && isset($checkout->flows['obedience_ratings']) ? $checkout->flows['obedience_ratings'] : [];
      $obedienceCommands = ['sit', 'down', 'stay', 'come', 'loose_leash_walking'];
    @endphp
    <div class="mb-2 ms-4 space-y-3">
      @foreach($obedienceCommands as $command)
        @php
          $commandLabel = ucwords(str_replace('_', ' ', $command));
          $currentRating = isset($obedienceRatings[$command]) ? $obedienceRatings[$command] : 0;
        @endphp
        <div class="flex items-center gap-2">
          <p class="text-sm font-medium mb-1">{{ $commandLabel }}:</p>
          <div class="flex items-center gap-1 star-rating-container" data-rating="{{ $currentRating }}" data-command="{{ $command }}">
            @for($i = 1; $i <= 5; $i++)
              <input type="radio" class="hidden" name="obedience_rating_{{ $command }}" value="{{ $i }}" id="rating_{{ $command }}_{{ $i }}" {{ $currentRating == $i ? 'checked' : '' }} />
              <label for="rating_{{ $command }}_{{ $i }}" class="cursor-pointer">
                <span class="iconify lucide--star size-5 star-rating-icon" data-star-value="{{ $i }}" data-command="{{ $command }}" style="color: {{ $i <= $currentRating ? '#fbbf24' : '#d1d5db' }}; transition: color 0.2s;"></span>
              </label>
            @endfor
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @else
  <div>
    <p class="font-medium mb-2">Rating:</p>
    <div class="mb-2 ms-4">
      <div class="space-y-2">
        <label class="flex items-center gap-2">
          <input type="radio" class="radio radio-xs" name="rating"
            value="green" {{ $checkout && $checkout->flows && isset($checkout->flows['rating']) && $checkout->flows['rating'] === 'green' ? 'checked' : '' }} />
          <span class="text-sm">Green (no issues)</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" class="radio radio-xs" name="rating"
            value="yellow" {{ $checkout && $checkout->flows && isset($checkout->flows['rating']) && $checkout->flows['rating'] === 'yellow' ? 'checked' : '' }} />
          <span class="text-sm">Yellow (mild reaction to {{ $ratingSubject }}, specifically</span>
          <input placeholder="Touch to write" id="rating_yellow_detail" name="rating_yellow_detail" class="input input-ghost input-xs" aria-label="Input" type="text" style="max-width: 220px;" value="{{ $checkout && $checkout->flows && isset($checkout->flows['rating_yellow_detail']) ? $checkout->flows['rating_yellow_detail'] : '' }}"/>
          <span class="text-sm">)</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" class="radio radio-xs" name="rating"
            value="purple" {{ $checkout && $checkout->flows && isset($checkout->flows['rating']) && $checkout->flows['rating'] === 'purple' ? 'checked' : '' }} />
          <span class="text-sm">Purple (reacts to {{ $ratingSubject }}, go slow with</span>
          <input placeholder="Touch to write" id="rating_purple_detail" name="rating_purple_detail" class="input input-ghost input-xs" aria-label="Input" type="text" style="max-width: 220px;" value="{{ $checkout && $checkout->flows && isset($checkout->flows['rating_purple_detail']) ? $checkout->flows['rating_purple_detail'] : '' }}"/>
          <span class="text-sm">)</span>
        </label>
      </div>
    </div>
  </div>
  @endif
  <div>
    <p class="font-medium mb-2">Pictures:</p>
    <div class="mb-2 ms-4">
      <input aria-label="File" id="checkout_pictures" name="checkout_pictures[]" class="file-input w-full" type="file" multiple accept="image/*" />
    </div>
    @if($checkout && $checkout->flows && isset($checkout->flows['pictures']) && is_array($checkout->flows['pictures']))
    <div class="mt-2 ms-4 flex flex-wrap gap-2">
      @foreach($checkout->flows['pictures'] as $picture)
      <div class="relative">
        <img src="{{ asset('storage/checkouts/' . $picture) }}" alt="Checkout Picture" class="w-24 h-24 object-cover rounded-lg border">
      </div>
      @endforeach
    </div>
    @endif
  </div>
  @if ($showServiceNotes)
  <div class="mt-3">
    <p class="font-medium mb-2">Notes for {{ $appointment->service->name }}:</p>
    <div class="mb-2 ms-4">
      <textarea class="textarea textarea-bordered w-full" placeholder="Add any notes about the {{ strtolower($appointment->service->name) }}..." id="checkout_service_notes" name="checkout_service_notes" rows="3">{{ $checkout && $checkout->flows && isset($checkout->flows['service_notes']) ? $checkout->flows['service_notes'] : '' }}</textarea>
    </div>
  </div>
  @endif
</div>
@if (isPrivateTrainingService($appointment->service))
<hr class="mt-5" style="color: lightgray"/>
<div class="mt-4 text-sm space-y-4">
  <div class="divider my-4"></div>
  <div>
    <p class="font-medium mb-2">Current ratings:</p>
    <div class="mb-2 ms-4">
      <textarea class="textarea textarea-bordered w-full" id="training_current_ratings" name="training_current_ratings" rows="3" placeholder="Enter current ratings summary...">{{ $checkout && $checkout->flows && isset($checkout->flows['training_current_ratings']) ? $checkout->flows['training_current_ratings'] : '' }}</textarea>
    </div>
  </div>
  <div>
    <p class="font-medium mb-2">Goal for next lesson:</p>
    <div class="mb-2 ms-4">
      <input class="input input-bordered w-full" id="training_targets" name="training_targets" type="text" placeholder="Enter targets for next lesson..." value="{{ $checkout && $checkout->flows && isset($checkout->flows['training_targets']) ? $checkout->flows['training_targets'] : '' }}" />
    </div>
  </div>
  <div>
    <p class="font-medium mb-2">Homework for owner:</p>
    <div class="mb-2 ms-4">
      <textarea class="textarea textarea-bordered w-full" id="training_homework" name="training_homework" rows="3" placeholder="Enter homework for owner...">{{ $checkout && $checkout->flows && isset($checkout->flows['training_homework']) ? $checkout->flows['training_homework'] : '' }}</textarea>
    </div>
  </div>
</div>
@endif
@isset($petBehaviors)
<hr class="mt-5" style="color: lightgray"/>
<div class="mt-4 text-sm space-y-4">
  <div>
    <p class="font-medium mb-2">Behavior:</p>
    <div class="mb-2 ms-4 pet-behavior">
      @php
        if (hasServiceReportWorkflow($appointment->service)) {
          // Only the behavior staff recorded for this appointment, never pre-filled from the pet profile.
          $savedBehaviorIds = ($checkout && is_array($checkout->flows) && is_array($checkout->flows['behavior_ids'] ?? null))
            ? $checkout->flows['behavior_ids']
            : [];
          $selectedBehaviorIds = collect($savedBehaviorIds)->map(fn ($id) => (string) $id)->toArray();
        } else {
          $selectedBehaviorIds = is_array($appointment->pet->pet_behavior_id ?? null)
            ? collect($appointment->pet->pet_behavior_id)->map(fn ($id) => (string) $id)->toArray()
            : (!empty($appointment->pet->pet_behavior_id) ? [(string) $appointment->pet->pet_behavior_id] : []);
        }
      @endphp
      <select class="select w-full" id="pet_behavior_id" name="checkout_behavior_ids[]" multiple>
        @foreach($petBehaviors as $behavior)
          <option
            value="{{ $behavior->id }}"
            data-icon-b64="{{ base64_encode($behavior->icon?->icon ?? '') }}"
            {{ in_array((string) $behavior->id, $selectedBehaviorIds, true) ? 'selected' : '' }}
          >
            {{ $behavior->description }}
          </option>
        @endforeach
      </select>
    </div>
  </div>
</div>
@endisset
