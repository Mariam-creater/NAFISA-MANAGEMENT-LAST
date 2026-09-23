<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'full_name',
        'age',
        'email',
        'whatsapp_number',
        'occupation',
        'avatar_path',
        'id_document_path',
        'emergency_name',
        'emergency_phone',
        'emergency_relationship',
        'check_in_date',
        'rent_due_date',
        'status',
    ];

    protected $casts = [
        'age' => 'integer',
        'check_in_date' => 'date',
        'rent_due_date' => 'date',
    ];

    protected $appends = [
        'avatar_url',
        'id_document_url',
        'remaining_due_days',
        'due_status',
        'retention_days_left',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('payment_date');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->orderByDesc('created_at');
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
                    return Storage::url($this->avatar_path);
                }
                // Luxury initials or default avatar
                return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=FCE7F3&color=DB2777&bold=true';
            }
        );
    }

    protected function idDocumentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id_document_path && Storage::disk('public')->exists($this->id_document_path)
                ? Storage::url($this->id_document_path)
                : null
        );
    }

    protected function remainingDueDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->rent_due_date) return 0;
                $today = Carbon::today();
                $due = Carbon::parse($this->rent_due_date)->startOfDay();
                return (int) $today->diffInDays($due, false);
            }
        );
    }

    protected function dueStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $days = $this->remaining_due_days;
                if ($days < 0) return 'overdue';
                if ($days <= 3) return 'due_soon';
                return 'upcoming';
            }
        );
    }

    protected function retentionDaysLeft(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->deleted_at) return null;
                $deletion = Carbon::parse($this->deleted_at);
                $expiry = $deletion->copy()->addDays(365);
                $remaining = (int) Carbon::now()->diffInDays($expiry, false);
                return max(0, $remaining);
            }
        );
    }
}
