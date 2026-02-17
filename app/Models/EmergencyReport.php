<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'citizen_id',
        'assigned_rescuer_id',
        'assigned_team_id',
        'emergency_type',
        'description',
        'location',
        'latitude',
        'longitude',
        'status',
        'photo_url',
        'show_name',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'show_name' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function assignedRescuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_rescuer_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(RescueTeam::class, 'assigned_team_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'report_id');
    }
}
