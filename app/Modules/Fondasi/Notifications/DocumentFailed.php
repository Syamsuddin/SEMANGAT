<?php

namespace App\Modules\Fondasi\Notifications;

use App\Modules\Fondasi\Enums\DocumentType;
use Illuminate\Notifications\Notification;

/** Penerbitan dokumen (FON-09) gagal di semua percobaan queue `documents` — tautan ke halaman bertombol ulang (docs/14). */
class DocumentFailed extends Notification
{
    public function __construct(public readonly DocumentType $type, public readonly string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_type' => $this->type->value,
            'message' => __('fondasi.notifications.document_failed', ['type' => $this->type->label()]),
            'url' => $this->url,
        ];
    }
}
