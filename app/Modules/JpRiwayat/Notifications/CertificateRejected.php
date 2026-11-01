<?php

namespace App\Modules\JpRiwayat\Notifications;

use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Notifications\Notification;

/** JPR-02: sertifikat luar ditolak — alasan terlihat pegawai (FON-07, kanal database). */
class CertificateRejected extends Notification
{
    public function __construct(public readonly JpRecord $record) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'jp_record_id' => $this->record->id,
            'message' => __('jp.notifications.rejected', ['title' => $this->record->title, 'jp' => $this->record->jp]),
            'note' => $this->record->reject_reason,
            'url' => route('jp.overview'),
        ];
    }
}
