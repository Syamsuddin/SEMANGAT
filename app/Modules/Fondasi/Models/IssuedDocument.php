<?php

namespace App\Modules\Fondasi\Models;

use App\Modules\Fondasi\Database\Factories\IssuedDocumentFactory;
use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registri PDF terbitan (FON-09). Dibuat hanya oleh App\Support\DocumentIssuer (F3).
 *
 * @property int $id
 * @property string $uuid
 * @property DocumentType $type
 * @property int|null $employee_id
 * @property string $sha256
 * @property Carbon $issued_at
 * @property Carbon|null $revoked_at
 * @property-read Employee|null $employee
 */
class IssuedDocument extends Model
{
    /** @use HasFactory<IssuedDocumentFactory> */
    use HasFactory;

    protected $table = 'out_issued_documents';

    protected $fillable = ['uuid', 'type', 'documentable_type', 'documentable_id', 'employee_id', 'file_path', 'sha256', 'issued_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['type' => DocumentType::class, 'issued_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function newFactory(): IssuedDocumentFactory
    {
        return IssuedDocumentFactory::new();
    }
}
