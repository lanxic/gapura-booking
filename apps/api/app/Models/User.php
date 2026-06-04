<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
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
        return match($this->role) {
            UserRole::SuperAdmin => [
                'products.manage', 'availability.manage', 'orders.view', 'orders.manage',
                'vouchers.manage', 'reports.view', 'reports.export', 'users.manage',
                'corrections.review', 'activity_logs.view', 'activity_logs.export',
                'settings.manage',
            ],
            UserRole::Admin => [
                'products.manage', 'availability.manage', 'orders.view', 'orders.manage',
                'vouchers.manage', 'reports.view', 'reports.export', 'users.manage',
                'corrections.review', 'activity_logs.view', 'activity_logs.export',
                'settings.manage',
            ],
            UserRole::Supervisor => [
                'orders.view', 'corrections.review', 'activity_logs.view',
                'supervisor.corrections',
            ],
            UserRole::Kasir    => ['kasir.collect', 'corrections.submit'],
            UserRole::Scanner  => ['scanner.scan', 'corrections.submit'],
            UserRole::Customer => [],
        };
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function correctionRequestsSubmitted()
    {
        return $this->hasMany(CorrectionRequest::class, 'requested_by');
    }

    public function correctionRequestsReviewed()
    {
        return $this->hasMany(CorrectionRequest::class, 'reviewed_by');
    }
}
