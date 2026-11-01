<?php

namespace App\Modules\Idp\Notifications;

use App\Modules\Idp\Models\Idp;
use Illuminate\Notifications\Notification;

/** IDP Diajukan ke atasan (docs/06 § 4 langkah 3) — kanal database (F3); kanal surel/WA menyusul FON-07. */
class IdpSubmitted extends Notification
{
    public function __construct(public readonly Idp $idp, public readonly ?string $note = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'idp_id' => $this->idp->id,
            'period_year' => $this->idp->period_year,
            'message' => __('idp.notifications.submitted', ['name' => $this->idp->employee->name, 'year' => $this->idp->period_year]),
            'note' => $this->note,
        ];
    }
}
