<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'block_id',
        'floor_number',
        'room_number',
        'base_price',
        'custom_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'floor_number' => 'integer',
        'base_price' => 'integer',
        'custom_price' => 'integer',
    ];

    protected $appends = [
        'effective_price',
    ];

    /**
     * Effective price accessor: custom_price ?? base_price
     */
    protected function effectivePrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->custom_price ?? $this->base_price,
        );
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function activeTenant(): HasOne
    {
        return $this->hasOne(Tenant::class)->where('status', 'active');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
