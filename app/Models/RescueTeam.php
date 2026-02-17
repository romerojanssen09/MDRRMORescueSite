<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescueTeam extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'team_name',
        'specialization',
        'status',
        'province',
        'municipality',
        'barangay',
        'street_address',
        'latitude',
        'longitude',
        'members_count',
    ];

    protected $casts = [
        'members_count' => 'integer',
    ];

    public function members()
    {
        return $this->hasMany(User::class, 'rescue_team_id');
    }

    public function assignedReports()
    {
        return $this->hasMany(EmergencyReport::class, 'assigned_team_id');
    }
}
