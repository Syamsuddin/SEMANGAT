<?php

namespace App\Modules\JpRiwayat\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Notifications\CertificateRejected;
use App\Modules\JpRiwayat\Notifications\CertificateVerified;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * JPR-02: verifikator (pengelola / koord. OPD) memverifikasi — boleh mengoreksi JP — atau menolak dengan alasan wajib
 * yang terlihat pegawai. Pegawai dikabari lewat notifikasi in-app (FON-07).
 */
class VerifyCertificate
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function verify(User $user, JpRecord $record, mixed $jp = null): JpRecord
    {
        Gate::forUser($user)->authorize('verify', $record);

        $data = Validator::make(['jp' => $jp ?? $record->jp], [
            'jp' => ['required', 'numeric', 'gt:0', 'max:999.9', 'decimal:0,1'],
        ], [], __('jp.attributes'))->validate();

        $record = DB::transaction(function () use ($user, $record, $data) {
            // Muat ulang di bawah kunci: verifikator serentak tidak saling menimpa; status `pending` dicek ulang.
            $locked = $this->lock($user, $record);
            $before = (string) $locked->jp;
            $locked->forceFill(['status' => JpStatus::Verified, 'jp' => $data['jp'], 'verified_by' => $user->id, 'verified_at' => now(), 'reject_reason' => null])->save();
            $this->audit->log(AuditAction::Update, $locked, changes: ['status' => JpStatus::Verified->value, 'jp' => ['from' => $before, 'to' => (string) $locked->jp]], userId: $user->id);

            return $locked;
        });

        $record->employee->user?->notify(new CertificateVerified($record));

        return $record;
    }

    public function reject(User $user, JpRecord $record, ?string $reason): JpRecord
    {
        Gate::forUser($user)->authorize('verify', $record);

        $data = Validator::make(['reject_reason' => $reason], [
            'reject_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [], __('jp.attributes'))->validate();

        $record = DB::transaction(function () use ($user, $record, $data) {
            $locked = $this->lock($user, $record);
            $locked->forceFill(['status' => JpStatus::Rejected, 'verified_by' => $user->id, 'verified_at' => now(), 'reject_reason' => $data['reject_reason']])->save();
            $this->audit->log(AuditAction::Update, $locked, changes: ['status' => JpStatus::Rejected->value], userId: $user->id);

            return $locked;
        });

        $record->employee->user?->notify(new CertificateRejected($record));

        return $record;
    }

    private function lock(User $user, JpRecord $record): JpRecord
    {
        $locked = JpRecord::query()->lockForUpdate()->findOrFail($record->id);
        Gate::forUser($user)->authorize('verify', $locked);

        return $locked;
    }
}
