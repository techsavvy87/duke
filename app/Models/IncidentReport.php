<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);
    }

    protected $casts = [
        'appointment_previous_statuses' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Incidents whose comma-separated appointment_ids contain the given appointment.
     */
    public function scopeForAppointment($query, $appointmentId)
    {
        return $query->whereRaw('FIND_IN_SET(?, appointment_ids)', [$appointmentId]);
    }

    /**
     * Open incidents saved from Appointment Detail whose appointments haven't been moved to Issue yet;
     * the appointment's Confirm does that.
     */
    public function scopeAwaitingConfirm($query)
    {
        return $query->open()->whereNotExists(function ($sub) {
            $sub->selectRaw(1)
                ->from('appointments')
                ->whereRaw('FIND_IN_SET(appointments.id, incident_reports.appointment_ids)')
                ->where('appointments.status', 'issue');
        });
    }

    public function isAwaitingConfirm(): bool
    {
        return $this->isOpen()
            && !Appointment::whereIn('id', $this->appointment_id_list)->where('status', 'issue')->exists();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Open the incident: remember each linked appointment's current status (restored on resolve)
     * and move the appointments to Issue.
     */
    public function activate(): void
    {
        $appointmentIds = $this->appointment_id_list;

        $this->appointment_previous_statuses = Appointment::whereIn('id', $appointmentIds)->pluck('status', 'id')->all();
        $this->status = self::STATUS_OPEN;
        $this->save();

        Appointment::whereIn('id', $appointmentIds)->update(['status' => 'issue']);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getAppointmentIdListAttribute(): array
    {
        return self::splitIds($this->appointment_ids);
    }

    public function getInvolvedStaffsAttribute()
    {
        $ids = self::splitIds($this->staff_ids);

        return $ids ? User::with('profile')->whereIn('id', $ids)->get() : collect();
    }

    public function getInvolvedPetsAttribute()
    {
        $ids = self::splitIds($this->pet_ids);

        return $ids ? PetProfile::whereIn('id', $ids)->orderBy('name')->get() : collect();
    }

    private static function splitIds(?string $value): array
    {
        return array_values(array_map('intval', array_filter(array_map('trim', explode(',', $value ?? '')))));
    }
}
