<?php

namespace App\Modules\JpRiwayat\Models;

use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sertifikat bangkom (JPR-02). Berkas luar diunggah pegawai; `is_internal` = terbitan aplikasi (F5, punya out_issued_documents).
 *
 * @property int $id
 * @property int $employee_id
 * @property int $jp_record_id
 * @property string $issuer
 * @property string|null $number
 * @property string $file_path
 * @property string $sha256
 * @property bool $is_internal
 * @property-read Employee $employee
 * @property-read JpRecord $jpRecord
 */
class Certificate extends Model
{
    protected $table = 'out_certificates';

    protected $fillable = ['employee_id', 'jp_record_id', 'issuer', 'number', 'file_path', 'sha256', 'is_internal'];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<JpRecord, $this> */
    public function jpRecord(): BelongsTo
    {
        return $this->belongsTo(JpRecord::class);
    }
}
