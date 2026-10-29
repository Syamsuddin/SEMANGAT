<?php

use App\Modules\Fondasi\Actions\RecordSensitiveRead;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Exceptions\ImmutableAuditLog;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

// F1 menguji mekanismenya; halaman laporan asesmen rinci memakai Action ini pada F2 (keputusan 2026-10-29).

it('FON-05 pengelola membuka data rinci tanpa alasan → ditolak dan tidak ada baris audit', function () {
    $record = Employee::factory()->create();

    expect(fn () => app(RecordSensitiveRead::class)(userWithRole(Role::Pengelola), $record, '  '))
        ->toThrow(ValidationException::class);

    expect(AuditLog::query()->where('action', 'read_sensitive')->count())->toBe(0);
})->group('fondasi');

it('FON-05 dengan alasan → tepat 1 baris read_sensitive dengan reason terisi', function () {
    $record = Employee::factory()->create();
    $pengelola = userWithRole(Role::Pengelola);

    app(RecordSensitiveRead::class)($pengelola, $record, 'Verifikasi keberatan hasil asesmen');

    $log = AuditLog::query()->where('action', 'read_sensitive')->sole();
    expect($log->reason)->toBe('Verifikasi keberatan hasil asesmen')
        ->and($log->user_id)->toBe($pengelola->id)
        ->and($log->auditable_id)->toBe($record->id)
        ->and($log->ip_address)->not->toBeEmpty();
})->group('fondasi');

it('FON-05 peran tanpa izin assessment.view_detail ditolak', function (Role $role) {
    expect(fn () => app(RecordSensitiveRead::class)(userWithRole($role), Employee::factory()->create(), 'alasan yang cukup panjang'))
        ->toThrow(AuthorizationException::class);
})->with([Role::Asn, Role::Atasan, Role::KoordinatorOpd, Role::Clo, Role::AdminTeknis])->group('fondasi');

it('FON-05 baris audit tidak dapat diubah atau dihapus, termasuk oleh auditor', function () {
    $record = Employee::factory()->create();
    app(RecordSensitiveRead::class)(userWithRole(Role::Pengelola), $record, 'Verifikasi keberatan hasil asesmen');
    $log = AuditLog::query()->where('action', 'read_sensitive')->sole();
    $this->actingAs(userWithRole(Role::Auditor));

    expect(fn () => $log->update(['reason' => 'diubah']))->toThrow(ImmutableAuditLog::class)
        ->and(fn () => $log->delete())->toThrow(ImmutableAuditLog::class)
        ->and(fn () => AuditLog::query()->whereKey($log->id)->update(['reason' => 'x']))->toThrow(ImmutableAuditLog::class)
        ->and(fn () => AuditLog::query()->whereKey($log->id)->delete())->toThrow(ImmutableAuditLog::class);

    expect($log->fresh()->reason)->toBe('Verifikasi keberatan hasil asesmen');
})->group('fondasi');
