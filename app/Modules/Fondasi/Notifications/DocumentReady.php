<?php

namespace App\Modules\Fondasi\Notifications;

use App\Modules\Fondasi\Models\IssuedDocument;
use Illuminate\Notifications\Notification;

/** Dokumen terbitan (FON-09) selesai dibuat di queue `documents` — kanal database saja (FON-07, keputusan 2026-11-01). */
class DocumentReady extends Notification
{
    public function __construct(public readonly IssuedDocument $document, public readonly string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_uuid' => $this->document->uuid,
            'message' => __('fondasi.notifications.document_ready', ['type' => $this->document->type->label()]),
            'url' => $this->url,
        ];
    }
}
