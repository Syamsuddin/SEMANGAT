<?php

namespace App\Modules\Fondasi\Enums;

/** Izin spatie (F1–F4) — peta peran → izin di RolePermissionSeeder. */
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
    case IdpViewUnlinked = 'idp.view_unlinked';
    case JpVerify = 'jp.verify';
    case JpViewRecap = 'jp.view_recap';

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
            // M06 IDP-10: daftar "IDP tanpa tautan SKP" — atasan T, koord. O, pengelola/auditor B (docs/05; clo hanya agregat).
            self::IdpViewUnlinked => [Role::Atasan, Role::KoordinatorOpd, Role::Pengelola, Role::Auditor],
            // M12 JPR-02: verifikator sertifikat luar = pengelola (K) / koord. OPD (O) (docs/06 § 6).
            self::JpVerify => [Role::KoordinatorOpd, Role::Pengelola],
            // M12 JPR-06: rekap O/K/A/B (docs/05; clo & dewan agregat tanpa identitas bila n<5).
            self::JpViewRecap => [Role::KoordinatorOpd, Role::Pengelola, Role::Clo, Role::DewanPengarah, Role::Auditor],
        };
    }
}
