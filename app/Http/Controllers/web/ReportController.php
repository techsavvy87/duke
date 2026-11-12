<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\IncidentReport;
use App\Models\User;
use App\Models\PetProfile;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceCategory;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function listIncidentReports(Request $request, $serviceId)
    {
        $perPage = $request->get('per_page', 20);
        $pet = $request->get('pet');
        $staffId = $request->get('staff');
        $datetimes = $request->get('datetimes');

        $incidentReportsQuery = IncidentReport::where('service_id', $serviceId);

        // Search by pet name: find matching pet ids, then filter incident reports whose
        // comma-separated `pet_ids` contains any of those ids (MySQL FIND_IN_SET).
        if (!empty($pet)) {
            $petIds = PetProfile::where('name', 'like', "%{$pet}%")->pluck('id')->toArray();

            if (count($petIds) > 0) {
                $incidentReportsQuery->where(function ($q) use ($petIds) {
                    foreach ($petIds as $id) {
                        $q->orWhereRaw('FIND_IN_SET(?, pet_ids)', [$id]);
                    }
                });
            } else {
                // no pets match the name -> return empty result set
                $incidentReportsQuery->whereRaw('0 = 1');
            }
        }

        // filter by staff, staff is staff_id
        if (!empty($staffId) && $staffId != -1) {
            $incidentReportsQuery->where(function ($q) use ($staffId) {
                $q->orWhereRaw('FIND_IN_SET(?, staff_ids)', [$staffId]);
            });
        }

        // filter by date range
        if (!empty($datetimes)) {
            [$start, $end] = explode(' - ', $datetimes);
            // Parse to Carbon (assuming format: m/d/y h:i A)
            $startDateTime = Carbon::createFromFormat('m/d/y h:i A', trim($start))->format('Y-m-d H:i:s');
            $endDateTime = Carbon::createFromFormat('m/d/y h:i A', trim($end))->format('Y-m-d H:i:s');

            // Filter appointments where start_time is within the range
            $appointments = $appointments->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereRaw("CONCAT(date, ' ', start_time) >= ?", [$startDateTime])
                    ->whereRaw("CONCAT(date, ' ', end_time) <= ?", [$endDateTime]);
            });
        }

        $incidentReports = $incidentReportsQuery->orderBy('created_at', 'desc')->paginate($perPage);
        foreach ($incidentReports as $report) {
            $report->pets = PetProfile::whereIn('id', array_filter(array_map('trim', explode(',', $report->pet_ids))))->get();
            $report->staffs = User::whereIn('id', array_filter(array_map('trim', explode(',', $report->staff_ids))))->get();
        }

        $staffs = User::whereHas('roles', function ($query) {
                    $query->whereNot('title', 'customer');
                })->get();

        return view('reports.list-incident', compact('incidentReports', 'staffs', 'pet', 'staffId', 'datetimes', 'perPage', 'serviceId'));
    }

    public function addIncidentReport(Request $request, $serviceId)
    {
        return view('reports.create-incident', compact('serviceId'));
    }

    public function createIncidentReport(Request $request)
    {
        $request->validate([
            'pets' => 'required|array|min:1',
            'staffs' => 'required|array|min:1',
            'injury_type' => 'required|string',
            'injury_location' => 'required|string',
            'needs_treatment' => 'required|string',
            'is_emergency' => 'required|string',
            'contact_owner' => 'required|string',
            'treatment_type' => 'required|string',
            'conclusion' => 'required|string',
            'service_id' => 'required|exists:services,id',
            // set when the report is created from the Incident Report modal on Appointment Detail
            'appointment_id' => 'nullable|exists:appointments,id',
        ]);

        // only pets with an in_progress appointment for this service can be selected
        if (!empty($this->getIneligiblePetIds($request->pets, $this->getEligiblePetIds($request->service_id)))) {
            return $this->incidentFailResponse($request, 'Only pets with an In Progress appointment for this service can be selected.');
        }

        // from Appointment Detail: the report is linked to that appointment, which moves to Issue on its Confirm
        $sourceAppointment = null;
        if ($request->filled('appointment_id')) {
            $sourceAppointment = Appointment::find($request->appointment_id);
            if ($sourceAppointment->status !== 'in_progress' || (int) $sourceAppointment->service_id !== (int) $request->service_id) {
                return $this->incidentFailResponse($request, 'Incident reports can only be added to an In Progress appointment.');
            }
        }

        // save pictures
        $picturePaths = [];
        if ($request->hasFile('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $path = $picture->store('public/reports');
                $paths = explode("/", $path);
                $picturePaths[] = end($paths);
            }
        }

        $serviceId = $request->service_id;

        if ($sourceAppointment) {
            $appointmentIds = [$sourceAppointment->id];
        } else {
            // appointment ids for involved pets on the current date with the daycare service
            $appointmentIds = $this->getAppointmentIds($request->pets, $serviceId);
            if (count($appointmentIds) == 0) {
                return $this->incidentFailResponse($request, 'No valid appointments found for the selected pets with the specified service.');
            }
        }

        $incidentReport = new IncidentReport;
        $incidentReport->status = IncidentReport::STATUS_OPEN;
        $incidentReport->pet_ids = implode(',', $request->pets);
        $incidentReport->staff_ids = implode(',', $request->staffs);
        $incidentReport->appointment_ids = implode(',', $appointmentIds);
        $incidentReport->incident_description = $request->description;
        $incidentReport->pictures = !empty($picturePaths) ? implode(',', $picturePaths) : null;
        $incidentReport->injury_type = $request->injury_type;
        $incidentReport->injury_location = $request->injury_location;
        $incidentReport->needs_treatment = $request->needs_treatment;
        $incidentReport->is_emergency = $request->is_emergency;
        $incidentReport->contact_owner = $request->contact_owner;
        $incidentReport->owner_conversation_notes = $request->owner_conversation_notes;
        $incidentReport->treatment_type = $request->treatment_type;
        $incidentReport->vet_name = $request->vet_name;
        $incidentReport->vet_bill = $request->vet_bill;
        $incidentReport->vet_payment = $request->vet_payment;
        $incidentReport->vet_results = $request->vet_results;
        $incidentReport->conclusion = $request->conclusion;
        $incidentReport->service_id = $serviceId;

        DB::transaction(function () use ($incidentReport, $sourceAppointment) {
            $incidentReport->save();
            // from the Create page the appointments move to Issue right away
            if (!$sourceAppointment) {
                $incidentReport->activate();
            }
        });

        if ($sourceAppointment) {
            $message = 'Incident report created. Click Confirm to move this appointment to Issue.';
            if ($request->expectsJson()) {
                // the page reloads after the modal save; show the message there
                session()->flash('status', 'success');
                session()->flash('message', $message);
                return response()->json(['status' => 'success', 'message' => $message]);
            }
            return redirect()->route('appointment-dashboard', ['id' => $sourceAppointment->id])->with([
                'status' => 'success',
                'message' => $message
            ]);
        }

        return redirect()->route('list-incident-reports', ['serviceId' => $serviceId])->with([
            'status' => 'success',
            'message' => 'Incident report created successfully!'
        ]);
    }

    /**
     * Error response for incident saves: JSON for the Appointment Detail modals, otherwise back to the form
     * with the entered values.
     */
    private function incidentFailResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'fail', 'message' => $message], 422);
        }

        return redirect()->back()->withInput()->with([
            'status' => 'fail',
            'message' => $message
        ]);
    }

    public function editIncidentReport($id)
    {
        $incidentReport = IncidentReport::find($id);

        // prepare selected pets (re-select the submitted pets when returning from a failed update)
        $selectedPetIds = old('pets', array_filter(array_map('trim', explode(',', $incidentReport->pet_ids ?? ''))));
        $selectedPets = $selectedPetIds ? PetProfile::whereIn('id', $selectedPetIds)->get() : collect();

        // prepare selected staffs
        $selectedStaffIds = old('staffs', array_filter(array_map('trim', explode(',', $incidentReport->staff_ids ?? ''))));
        $selectedStaffs = $selectedStaffIds ? User::whereIn('id', $selectedStaffIds)->get() : collect();

        return view('reports.update-incident', compact('incidentReport', 'selectedPets', 'selectedStaffs'));
    }

    public function updateIncidentReport(Request $request)
    {
        $request->validate([
            'incident_report_id' => 'required|exists:incident_reports,id',
            'pets' => 'required|array|min:1',
            'staffs' => 'required|array|min:1',
            'injury_type' => 'required|string',
            'injury_location' => 'required|string',
            'needs_treatment' => 'required|string',
            'is_emergency' => 'required|string',
            'contact_owner' => 'required|string',
            'treatment_type' => 'required|string',
            'conclusion' => 'required|string',
            'source' => 'nullable|in:incident_list,appointment',
        ]);

        $incidentReport = IncidentReport::find($request->incident_report_id);

        // only pets with an in_progress appointment for this service can be selected,
        // except pets already on this report (their appointment status may have changed since)
        $existingPetIds = array_map('intval', array_filter(array_map('trim', explode(',', $incidentReport->pet_ids ?? ''))));
        $allowedPetIds = array_merge($this->getEligiblePetIds($incidentReport->service_id), $existingPetIds);
        if (!empty($this->getIneligiblePetIds($request->pets, $allowedPetIds))) {
            return $this->incidentFailResponse($request, 'Only pets with an In Progress appointment for this service can be selected.');
        }

        // collect current pictures submitted
        $currentPictures = [];
        if (!empty($request->current_pictures)) {
            $currentPictures = array_filter(array_map('trim', explode(',', $request->current_pictures)));
        }
        // Delete pictures that are NOT in the submitted list
        $existingPictures = [];
        if (!empty($incidentReport->pictures)) {
            $existingPictures = array_filter(array_map('trim', explode(',', $incidentReport->pictures)));
        }

        // pictures stored in DB but not present in the submitted current pictures
        $deletePictures = array_values(array_diff($existingPictures, $currentPictures));

        // optional: actually remove files from storage
        foreach ($deletePictures as $filename) {
            $filePath = 'public/reports/' . $filename;
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }

        // add new pictures
        if ($request->hasFile('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $path = $picture->store('public/reports');
                $paths = explode("/", $path);
                $currentPictures[] = end($paths);
            }
        }

        DB::beginTransaction();

        $oldAppointmentIds = $incidentReport->appointment_id_list;
        $oldPreviousStatuses = $incidentReport->appointment_previous_statuses ?? [];

        // only an open incident whose appointments are in Issue holds them; resolved incidents and ones still
        // waiting for the appointment's Confirm keep their linked appointments as they are
        $holdsAppointments = $incidentReport->isOpen() && !$incidentReport->isAwaitingConfirm();

        if (!$holdsAppointments) {
            $appointmentIds = $oldAppointmentIds;
        } else {
            // release previous appointments back to their pre-incident status
            $this->releaseIncidentAppointments($incidentReport, 'completed');
            // appointment ids for involved pets on the current date with the daycare service
            $appointmentIds = $this->getAppointmentIds($request->pets, $incidentReport->service_id);
        }
        if (count($appointmentIds) == 0) {
            DB::rollBack();
            return $this->incidentFailResponse($request, 'No valid appointments found for the selected pets with the specified service.');
        }
        if ($holdsAppointments) {
            // keep the original pre-incident status for appointments that were already on this incident
            $previousStatuses = [];
            foreach ($this->getAppointmentStatuses($appointmentIds) as $appointmentId => $currentStatus) {
                $previousStatuses[$appointmentId] = $oldPreviousStatuses[$appointmentId]
                    ?? (in_array((int) $appointmentId, $oldAppointmentIds, true) ? 'in_progress' : $currentStatus);
            }
            $incidentReport->appointment_previous_statuses = $previousStatuses;
            // update the status of appointments
            Appointment::whereIn('id', $appointmentIds)->update(['status' => 'issue']);
        }

        $incidentReport->pet_ids = implode(',', $request->pets);
        $incidentReport->staff_ids = implode(',', $request->staffs);
        $incidentReport->appointment_ids = implode(',', $appointmentIds);
        $incidentReport->incident_description = $request->description;
        $incidentReport->pictures = !empty($currentPictures) ? implode(',', $currentPictures) : null;
        $incidentReport->injury_type = $request->injury_type;
        $incidentReport->injury_location = $request->injury_location;
        $incidentReport->needs_treatment = $request->needs_treatment;
        $incidentReport->is_emergency = $request->is_emergency;
        $incidentReport->contact_owner = $request->contact_owner;
        $incidentReport->owner_conversation_notes = $request->owner_conversation_notes;
        $incidentReport->treatment_type = $request->treatment_type;
        $incidentReport->vet_name = $request->vet_name;
        $incidentReport->vet_bill = $request->vet_bill;
        $incidentReport->vet_payment = $request->vet_payment;
        $incidentReport->vet_results = $request->vet_results;
        $incidentReport->conclusion = $request->conclusion;
        $incidentReport->save();

        DB::commit();

        // edit modal on Appointment Detail: the page reloads and shows the message
        if ($request->expectsJson()) {
            $message = 'Incident report updated successfully!';
            session()->flash('status', 'success');
            session()->flash('message', $message);
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        // return to where the edit was opened from: the service dashboard (Issue appointment) or the incident list
        $redirect = $request->source === 'appointment'
            ? redirect()->route('service-dashboard', ['id' => $incidentReport->service_id])
            : redirect()->route('list-incident-reports', ['serviceId' => $incidentReport->service_id]);

        return $redirect->with([
            'status' => 'success',
            'message' => 'Incident report updated successfully!'
        ]);
    }

    /**
     * Pets eligible for a new incident: pets on an `in_progress` appointment for the given service.
     * Uses the appointment's family_pet_ids accessor so multi-pet (family) appointments are covered.
     */
    private function getEligiblePetIds($serviceId): array
    {
        return Appointment::where('service_id', $serviceId)
            ->where('status', 'in_progress')
            ->get(['id', 'pet_id', 'metadata'])
            ->flatMap(fn (Appointment $appointment) => array_merge(
                $appointment->family_pet_ids,
                $appointment->pet_id ? [(int) $appointment->pet_id] : []
            ))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Returns pet ids from the request that are not in the allowed list.
     */
    private function getIneligiblePetIds(array $requestedPetIds, array $allowedPetIds): array
    {
        return array_values(array_diff(array_map('intval', $requestedPetIds), $allowedPetIds));
    }

    public function getIncidentEligiblePets(Request $request, $serviceId)
    {
        $search = $request->get('q', '');

        $pets = PetProfile::whereIn('id', $this->getEligiblePetIds($serviceId))
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($pets);
    }

    public function resolveIncidentReport(Request $request)
    {
        $request->validate([
            'incident_report_id' => 'required|exists:incident_reports,id',
        ]);

        $incidentReport = IncidentReport::find($request->incident_report_id);

        if (!$incidentReport->isOpen() || $incidentReport->isAwaitingConfirm()) {
            return redirect()->back()->with([
                'status' => 'fail',
                'message' => $incidentReport->isResolved()
                    ? 'This incident has already been resolved.'
                    : 'This incident has not been confirmed yet.'
            ]);
        }

        DB::transaction(function () use ($incidentReport) {
            $incidentReport->status = IncidentReport::STATUS_RESOLVED;
            $incidentReport->resolved_at = now();
            $incidentReport->resolved_by = auth()->id();
            $incidentReport->save();

            // incidents created before status tracking default back to In Progress / On Property
            $this->releaseIncidentAppointments($incidentReport, 'in_progress');
        });

        // back to the service dashboard, where the appointment now shows under On Property / In Progress
        return redirect()->route('service-dashboard', ['id' => $incidentReport->service_id])->with([
            'status' => 'success',
            'message' => 'Incident resolved successfully!'
        ]);
    }

    /**
     * Current status of each appointment, keyed by appointment id.
     */
    private function getAppointmentStatuses(array $appointmentIds): array
    {
        return Appointment::whereIn('id', $appointmentIds)->pluck('status', 'id')->all();
    }

    /**
     * Move this incident's appointments out of Issue, back to the status they had before the incident.
     * Appointments still referenced by another open incident stay in Issue.
     */
    private function releaseIncidentAppointments(IncidentReport $incidentReport, string $fallbackStatus): void
    {
        $previousStatuses = $incidentReport->appointment_previous_statuses ?? [];

        $appointments = Appointment::with('service')
            ->whereIn('id', $incidentReport->appointment_id_list)
            ->where('status', 'issue')
            ->get();

        foreach ($appointments as $appointment) {
            $heldByOtherIncident = IncidentReport::open()
                ->forAppointment($appointment->id)
                ->where('id', '!=', $incidentReport->id)
                ->exists();
            if ($heldByOtherIncident) {
                continue;
            }

            $restoreStatus = $previousStatuses[$appointment->id] ?? $fallbackStatus;
            if ($restoreStatus === 'issue') {
                $restoreStatus = $fallbackStatus;
            }

            $appointment->status = $restoreStatus;
            $appointment->save();

            if ($incidentReport->isResolved()) {
                appointment_audit_log($appointment->id, "Incident #{$incidentReport->id} resolved. Appointment status changed to " . appointment_status_label($restoreStatus, $appointment->service) . ".");
            }
        }
    }

    private function getAppointmentIds($petIds, $serviceId)
    {
        // appointment ids for involved pets on the current date with the daycare service
        $appointmentIds = Appointment::whereIn('pet_id', $petIds)
            ->where('service_id', $serviceId)
            ->whereIn('status', ['checked_in', 'in_progress', 'completed'])
            ->pluck('id')
            ->toArray();

        return $appointmentIds;
    }

    public function deleteIncidentReport(Request $request)
    {
        $request->validate([
            'incident_report_id' => 'required|exists:incident_reports,id',
            // set when deleting from Appointment Detail, which is shown again afterwards
            'appointment_id' => 'nullable|exists:appointments,id',
        ]);

        $incidentReport = IncidentReport::find($request->incident_report_id);
        $serviceId = $incidentReport->service_id;

        DB::transaction(function () use ($incidentReport) {
            // release appointments still held in Issue by this incident (only appointments in Issue are touched);
            // an appointment also held by another open incident stays in Issue
            if ($incidentReport->isOpen()) {
                $this->releaseIncidentAppointments($incidentReport, 'completed');
            }

            foreach ($incidentReport->appointment_id_list as $appointmentId) {
                appointment_audit_log($appointmentId, "Incident #{$incidentReport->id} deleted.");
            }

            $incidentReport->delete();
        });

        // optional: delete associated pictures from storage
        if (!empty($incidentReport->pictures)) {
            $pictures = array_filter(array_map('trim', explode(',', $incidentReport->pictures)));
            foreach ($pictures as $filename) {
                $filePath = 'public/reports/' . $filename;
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            }
        }

        $redirect = $request->filled('appointment_id')
            ? redirect()->route('appointment-dashboard', ['id' => $request->appointment_id])
            : redirect()->route('list-incident-reports', ['serviceId' => $serviceId]);

        return $redirect->with([
            'status' => 'success',
            'message' => 'Incident report deleted successfully!'
        ]);
    }
}
