<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'id',
        'full_name',
        'email',
        'phone',
        'role',
        'rescue_team_id',
        'avatar_url',
        'encrypted_password',
        'expo_push_token',
    ];

    protected $hidden = [
        'encrypted_password',
        'remember_token',
    ];

    // Override the password attribute to use encrypted_password
    public function setPasswordAttribute($value)
    {
        $this->attributes['encrypted_password'] = $value;
    }

    public function getAuthPassword()
    {
        return $this->encrypted_password;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function emergencyReports(): HasMany
    {
        return $this->hasMany(EmergencyReport::class, 'citizen_id');
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(EmergencyReport::class, 'assigned_rescuer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function rescueTeam()
    {
        return $this->belongsTo(RescueTeam::class, 'rescue_team_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRescuer(): bool
    {
        return $this->role === 'rescuer';
    }

    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }
}
