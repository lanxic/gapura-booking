<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin  = 'super_admin';
    case Admin       = 'admin';
    case Supervisor  = 'supervisor';
    case Kasir       = 'kasir';
    case Scanner     = 'scanner';
    case Customer    = 'customer';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin      => 'Admin',
            self::Supervisor => 'Supervisor',
            self::Kasir      => 'Kasir',
            self::Scanner    => 'Scanner',
            self::Customer   => 'Customer',
        };
    }

    public function jwtTtlEnvKey(): string
    {
        return match($this) {
            self::SuperAdmin => 'JWT_ADMIN_TTL',
            self::Admin      => 'JWT_ADMIN_TTL',
            self::Supervisor => 'JWT_SUPERVISOR_TTL',
            self::Kasir      => 'JWT_KASIR_TTL',
            self::Scanner    => 'JWT_SCANNER_TTL',
            self::Customer   => 'JWT_CUSTOMER_TTL',
        };
    }
}
