<?php

namespace App\Modules\Fondasi\Exceptions;

use LogicException;

class ImmutableAuditLog extends LogicException
{
    public function __construct()
    {
        parent::__construct('Jejak audit tidak dapat diubah atau dihapus.');
    }
}
