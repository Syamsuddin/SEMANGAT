<?php

namespace App\Modules\Fondasi\Enums;

/** Izin spatie F1 — peta peran → izin di RolePermissionSeeder. */
enum Permission: string
{
    case EmployeeViewAny = 'employee.view_any';
    case IntegrationView = 'integration.view';
    case IntegrationManage = 'integration.manage';
    case AssessmentViewDetail = 'assessment.view_detail';
    case AuditView = 'audit.view';
    case RoleAssign = 'role.assign';

    /** @return list<Role> */
    public function roles(): array
    {
        return match ($this) {
            self::EmployeeViewAny => [Role::Atasan, Role::KoordinatorOpd, Role::Pengelola, Role::Auditor],
            self::IntegrationView => [Role::Pengelola, Role::Clo, Role::Auditor],
            self::IntegrationManage => [Role::Pengelola],
            self::AssessmentViewDetail => [Role::Pengelola, Role::Auditor],
            self::AuditView => [Role::Auditor],
            self::RoleAssign => [Role::Pengelola],
        };
    }
}
