<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'password', 'role', 'is_active',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role'        => $this->role->value,
            'tenant_id'   => $this->tenant_id,
            'permissions' => $this->getPermissions(),
        ];
    }

    public function getPermissions(): array
    {
        return match ($this->role) {
            UserRole::SuperAdmin => [
                'products.manage', 'bookings.view', 'bookings.manage',
                'offers.manage', 'users.manage', 'tenants.manage',
                'activity_logs.view', 'activity_logs.export', 'settings.manage',
            ],
            UserRole::Admin, UserRole::TenantAdmin => [
                'products.manage', 'bookings.view', 'bookings.manage',
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
