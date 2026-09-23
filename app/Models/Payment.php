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
        'payment_date' => 'date',
    ];

    protected $appends = [
        'receipt_url',
        'formatted_amount',
    ];

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
}
