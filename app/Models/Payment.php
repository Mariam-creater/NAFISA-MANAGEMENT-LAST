<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'receipt_serial_no',
        'amount_received',
        'base_rent_charged',
        'tax_amount',
        'total_due',
        'balance_remaining',
        'payment_status',
        'payment_method',
        'payment_date',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'amount_received' => 'integer',
        'base_rent_charged' => 'integer',
        'tax_amount' => 'integer',
        'total_due' => 'integer',
        'balance_remaining' => 'integer',
        'payment_status' => 'string',
        'payment_date' => 'date',
    ];

    protected $appends = [
        'receipt_url',
        'formatted_amount',
        'formatted_balance',
        'is_partial',
    ];

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if ($payment->total_due !== null && $payment->amount_received !== null) {
                $payment->balance_remaining = max(0, (int) ($payment->total_due - $payment->amount_received));
                if ($payment->amount_received >= $payment->total_due) {
                    $payment->payment_status = $payment->amount_received > $payment->total_due ? 'overpaid' : 'paid';
                } else {
                    $payment->payment_status = 'partial';
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class)->withTrashed();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    protected function receiptUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pdf_path && Storage::disk('public')->exists($this->pdf_path)
                ? Storage::url($this->pdf_path)
                : null
        );
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->amount_received) . ' UGX'
        );
    }

    protected function balanceRemaining(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->total_due - $this->amount_received)
        );
    }

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->balance_remaining) . ' UGX'
        );
    }

    protected function isPartial(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount_received < $this->total_due
        );
    }
}
