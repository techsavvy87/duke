<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);
    }

    public static function generateInvoiceNumber()
    {
        // Invoice numbers are unique across all environments, so the sequence ignores the demo scope
        $latestInvoice = self::withoutGlobalScope('demo_environment')->latest()->first();
        if ($latestInvoice) {
            $lastNumber = intval(substr($latestInvoice->invoice_number, 3));
            return 'INV' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }
        return 'INV000001';
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class, 'invoice_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'invoice_id', 'id');
    }
}
