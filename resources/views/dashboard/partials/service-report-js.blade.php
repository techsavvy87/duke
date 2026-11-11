  // Service report: the service-specific checkout details, saved during In Progress / On Property
  // and shown read-only once the appointment is completed.
  function buildServiceReportFormData() {
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');

    const flows = {};

    // Rating (non-training services)
    const ratingElement = $('input[name="rating"]:checked');
    if (ratingElement.length) {
      flows.rating = ratingElement.val();
      const ratingYellowDetail = $('#rating_yellow_detail').val();
      const ratingPurpleDetail = $('#rating_purple_detail').val();
      if (ratingYellowDetail) flows.rating_yellow_detail = ratingYellowDetail;
      if (ratingPurpleDetail) flows.rating_purple_detail = ratingPurpleDetail;
    }

    const serviceNotes = $('#checkout_service_notes').length ? $('#checkout_service_notes').val() : null;
    if (serviceNotes) {
      flows.service_notes = serviceNotes;
    }

    // Training completion data
    const obedienceRatings = {};
    ['sit', 'down', 'stay', 'come', 'loose_leash_walking'].forEach(function(command) {
      const ratingInput = $('input[name="obedience_rating_' + command + '"]:checked');
      if (ratingInput.length && ratingInput.val() !== undefined) {
        obedienceRatings[command] = parseInt(ratingInput.val());
      }
    });
    if (Object.keys(obedienceRatings).length > 0) {
      flows.obedience_ratings = obedienceRatings;
    }

    const trainingCurrentRatings = $('#training_current_ratings').length ? $('#training_current_ratings').val() : null;
    const trainingTargets = $('#training_targets').length ? $('#training_targets').val() : null;
    const trainingHomework = $('#training_homework').length ? $('#training_homework').val() : null;
    if (trainingCurrentRatings) flows.training_current_ratings = trainingCurrentRatings;
    if (trainingTargets) flows.training_targets = trainingTargets;
    if (trainingHomework) flows.training_homework = trainingHomework;

    // Only sync behavior when the selector is on the page, so a form without it never clears the pet's behavior.
    if ($('#pet_behavior_id').length) {
      flows.behavior_ids = ($('#pet_behavior_id').val() || [])
        .map(function (id) { return parseInt(id, 10); })
        .filter(function (id) { return !isNaN(id); });
    }

    formData.append('flows', JSON.stringify(flows));

    const pictures = $('#checkout_pictures').length ? $('#checkout_pictures')[0].files : [];
    for (let i = 0; i < pictures.length; i++) {
      formData.append('pictures[]', pictures[i]);
    }

    return formData;
  }

  // Resolves immediately when the page has no service report section (other statuses and services).
  function saveServiceReport() {
    if (!$('#service_report_fields').length) {
      return $.Deferred().resolve().promise();
    }

    return $.ajax({
      url: '{{ route("save-appointment-service-report", $appointment->id) }}',
      method: 'POST',
      data: buildServiceReportFormData(),
      processData: false,
      contentType: false
    });
  }

  function getServiceReportErrorMessage(xhr, fallback) {
    return xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : fallback;
  }

  // Completed page: fill the read-only service report section with the archive report.
  function loadServiceReportSection() {
    const $content = $('#service_report_content');
    if (!$content.length) {
      return;
    }

    $.ajax({
      url: $content.data('fetch-url'),
      method: 'GET',
      dataType: 'json'
    }).done(function(response) {
      $content.html(response.html || '<p class="text-base-content/70">No service report information has been recorded.</p>');
    }).fail(function() {
      $content.html('<p class="text-error">Failed to load the service report. Please refresh the page to try again.</p>');
    });
  }

  $(document).ready(loadServiceReportSection);
