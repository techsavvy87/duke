<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected static function booted(): void
    {
        // Payments recorded by the Stripe webhook have no authenticated user, so they follow their invoice
        static::creating(function (Transaction $transaction) {
            inheritDemoAccountId($transaction, [
                [Invoice::class, $transaction->invoice_id],
                [Appointment::class, $transaction->appointment_id],
                [User::class, $transaction->user_id],
            ]);
        });
        registerDemoEnvironmentScope(static::class);
    }

    protected $fillable = [
        'appointment_id',
        'invoice_id',
        'user_id',
        'tran_date',
        'amount',
        'payment_method',
        'authorization_code',
        'notes',
        'last_payment_id',
        'stripe_transaction_id',
    ];

    protected $casts = [
        'tran_date' => 'datetime',
        'amount' => 'float',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}