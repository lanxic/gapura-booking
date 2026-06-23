<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'is_active',
        'email_verified_at', 'email_verification_token',
        'created_by', 'cloudinary_avatar_id', 'cloudinary_avatar_url',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role'              => UserRole::class,
            'is_active'         => 'boolean',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role'        => $this->role->value,
            'permissions' => $this->getPermissions(),
        ];
    }

    public function getPermissions(): array
    {
        return match ($this->role) {
            UserRole::SuperAdmin => [
                'activities.manage', 'bookings.view', 'bookings.manage',
                'offers.manage', 'users.manage', 'activity_logs.view',
                'activity_logs.export', 'settings.manage',
            ],
            UserRole::Admin => [
                'activities.manage', 'bookings.view', 'bookings.manage',
                'offers.manage', 'users.manage', 'activity_logs.view',
                'activity_logs.export',
            ],
            UserRole::Scanner => ['scanner.scan'],
            default           => [],
        };
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
