<?php

namespace App\Modules\Fondasi\Enums;

/** Nilai sys_audit_logs.action (docs/07). */
enum AuditAction: string
{
    case ReadSensitive = 'read_sensitive';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Login = 'login';
    case Export = 'export';
    case RoleAssign = 'role_assign';
    case RoleRevoke = 'role_revoke';
}
