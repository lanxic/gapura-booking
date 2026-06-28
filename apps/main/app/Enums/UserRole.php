<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin  = 'super_admin';
    case Admin       = 'admin';
    case TenantAdmin = 'tenant_admin';
    case Scanner     = 'scanner';
    case Customer    = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin  => 'Super Admin',
            self::Admin       => 'Admin',
            self::TenantAdmin => 'Tenant Admin',
            self::Scanner     => 'Scanner',
            self::Customer    => 'Customer',
        };
    }

    public function jwtTtlEnvKey(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::TenantAdmin => 'JWT_ADMIN_TTL',
            self::Scanner                                    => 'JWT_SCANNER_TTL',
            self::Customer                                   => 'JWT_CUSTOMER_TTL',
        };
    }

    public function isAdminRole(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::TenantAdmin, self::Scanner]);
    }

    public function isTenantScoped(): bool
    {
        return in_array($this, [self::Admin, self::TenantAdmin, self::Scanner]);
    }
}
