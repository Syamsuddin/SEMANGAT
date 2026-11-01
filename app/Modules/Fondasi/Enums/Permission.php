<?php

namespace App\Modules\Fondasi\Enums;

/** Izin spatie (F1–F2) — peta peran → izin di RolePermissionSeeder. */
enum Permission: string
{
    case EmployeeViewAny = 'employee.view_any';
    case IntegrationView = 'integration.view';
    case IntegrationManage = 'integration.manage';
    case AssessmentViewDetail = 'assessment.view_detail';
    case AuditView = 'audit.view';
    case RoleAssign = 'role.assign';
    case CompetencyManage = 'competency.manage';
    case CompetencyViewAggregate = 'competency.view_aggregate';

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
            // M03: impor kamus/SKJ/hasil uji & unggah laporan rinci (K*); agregat O/A/B (docs/05).
            self::CompetencyManage => [Role::Pengelola],
            self::CompetencyViewAggregate => [Role::KoordinatorOpd, Role::Pengelola, Role::Clo, Role::DewanPengarah, Role::Auditor],
        };
    }
}
