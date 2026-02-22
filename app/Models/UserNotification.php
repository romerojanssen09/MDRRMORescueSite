<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'related_report_id',
        'is_read',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedReport()
    {
        return $this->belongsTo(EmergencyReport::class, 'related_report_id');
    }
}
