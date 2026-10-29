<?php

namespace App\Modules\Fondasi\Models;

use App\Modules\Fondasi\Exceptions\ImmutableAuditLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder AuditLog: ubah/hapus massal ditolak (FON-05).
 *
 * @extends Builder<AuditLog>
 */
class ImmutableAuditLogBuilder extends Builder
{
    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        throw new ImmutableAuditLog;
    }

    public function delete(): mixed
    {
        throw new ImmutableAuditLog;
    }

    public function forceDelete(): mixed
    {
        throw new ImmutableAuditLog;
    }
}
