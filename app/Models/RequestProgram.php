<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestProgram extends Model
{
    use HasFactory;

    protected $table = 'request_program';

    protected $fillable = [
        'user_id',
        'requested_by',
        'program_name',
        'description',
        'requested_date',
        'requested_features',
        'status',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'requested_features' => 'array',
    ];

    /**
     * Get the user that owns the program request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}