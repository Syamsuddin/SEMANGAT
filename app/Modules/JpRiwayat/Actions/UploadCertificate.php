<?php

namespace App\Modules\JpRiwayat\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Enums\JpMode;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\Certificate;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\CertificateStore;
use App\Support\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * JPR-02: pegawai mengunggah sertifikat bangkom luar → CatatanJP `pending` menunggu verifikator.
 * JP diisi pegawai sesuai sertifikat lalu dikonfirmasi/dikoreksi verifikator (keputusan 2026-11-01; konversi KAT-06 di F5).
 * Duplikat ditolak saat unggah: hash berkas sama, atau judul+tanggal mulai+penyelenggara sama (docs/06 § 6).
 * Cek duplikat diulang di bawah kunci baris pegawai agar dua unggahan serentak tidak sama-sama lolos.
 */
class UploadCertificate
{
    /** MIME hasil deteksi isi → ekstensi berkas simpanan; nama berkas dari klien tidak dipakai. */
    private const EXTENSIONS = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(private readonly CertificateStore $files, private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $input */
    public function handle(User $user, array $input, ?UploadedFile $file): JpRecord
    {
        if (! $user->is_active || $user->employee_id === null) {
            throw new AuthorizationException;
        }
        $employeeId = (int) $user->employee_id;

        // Berkas argumen yang divalidasi & disimpan — kunci `file` dari isian tidak boleh menggantikannya.
        $data = Validator::make(['file' => $file] + $input, [
            'title' => ['required', 'string', 'max:255'],
            'issuer' => ['required', 'string', 'max:200'],
            'number' => ['nullable', 'string', 'max:100'],
            'portion' => ['required', Rule::enum(ActivityPortion::class)],
            'mode' => ['required', Rule::enum(JpMode::class)],
            'jp' => ['required', 'numeric', 'gt:0', 'max:999.9', 'decimal:0,1'],
            'activity_start' => ['required', 'date', 'before_or_equal:today'],
            'activity_end' => ['required', 'date', 'after_or_equal:activity_start', 'before_or_equal:today'],
            'file' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.(int) config('semangat.jp.certificates.max_kb')],
        ], [], __('jp.attributes'))->validate();

        /** @var UploadedFile $file */
        $contents = (string) $file->get();

        // Cek MIME nyata dari isi berkas (docs/21) — tidak bergantung pada nama/ekstensi atau MIME yang dilaporkan klien.
        $mime = $this->files->mimeType($contents);
        if (! isset(self::EXTENSIONS[$mime])) {
            throw ValidationException::withMessages(['file' => __('jp.errors.file_type')]);
        }

        $sha256 = hash('sha256', $contents);
        $this->rejectDuplicate($employeeId, $sha256, $data);

        $path = $this->files->put($contents, self::EXTENSIONS[$mime]);

        try {
            return DB::transaction(function () use ($user, $employeeId, $data, $path, $sha256) {
                Employee::query()->whereKey($employeeId)->lockForUpdate()->first();
                $this->rejectDuplicate($employeeId, $sha256, $data);

                $record = JpRecord::query()->create([
                    'employee_id' => $employeeId,
                    'year' => Carbon::parse($data['activity_end'])->year,
                    'title' => $data['title'],
                    'portion' => $data['portion'],
                    'mode' => $data['mode'],
                    'jp' => $data['jp'],
                    'activity_start' => $data['activity_start'],
                    'activity_end' => $data['activity_end'],
                    'status' => JpStatus::Pending,
                ]);
                $certificate = Certificate::query()->create([
                    'employee_id' => $employeeId,
                    'jp_record_id' => $record->id,
                    'issuer' => $data['issuer'],
                    'number' => $data['number'] ?? null,
                    'file_path' => $path,
                    'sha256' => $sha256,
                    'is_internal' => false,
                ]);
                $record->setRelation('certificate', $certificate);
                $this->audit->log(AuditAction::Create, $record, changes: ['status' => JpStatus::Pending->value, 'jp' => (string) $data['jp']], userId: $user->id);

                return $record;
            });
        } catch (UniqueConstraintViolationException) {
            $this->files->delete($path);
            throw ValidationException::withMessages(['file' => __('jp.errors.duplicate')]);
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }
    }

    /** @param  array<string, mixed>  $data */
    private function rejectDuplicate(int $employeeId, string $sha256, array $data): void
    {
        $sameFile = Certificate::query()->where('employee_id', $employeeId)->where('sha256', $sha256)->exists();

        // Catatan yang ditolak verifikator boleh diunggah ulang dengan berkas perbaikan.
        $sameActivity = JpRecord::query()
            ->where('employee_id', $employeeId)
            ->where('status', '!=', JpStatus::Rejected->value)
            ->where('title', $data['title'])
            ->whereDate('activity_start', $data['activity_start'])
            ->whereHas('certificate', fn ($q) => $q->where('issuer', $data['issuer']))
            ->exists();

        if ($sameFile || $sameActivity) {
            throw ValidationException::withMessages(['file' => __('jp.errors.duplicate')]);
        }
    }
}
