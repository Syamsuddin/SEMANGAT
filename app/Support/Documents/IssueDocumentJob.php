<?php

namespace App\Support\Documents;

use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Models\User;
use App\Modules\Fondasi\Notifications\DocumentReady;
use App\Support\DocumentIssuer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Terbitkan PDF di queue `documents` (NFR-01/02), lalu kabari pemohon lewat notifikasi in-app (FON-07).
 * Idempoten: bila dokumen jenis ini untuk sumber yang sama sudah terbit dan belum dicabut, tidak diterbitkan ulang.
 */
class IssueDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  class-string<DocumentContent>  $content
     */
    public function __construct(
        public readonly string $content,
        public readonly Model $documentable,
        public readonly ?int $employeeId,
        public readonly ?User $requester = null,
    ) {
        $this->onQueue('documents');
    }

    public function handle(DocumentIssuer $issuer): void
    {
        $content = app($this->content);

        $alreadyIssued = IssuedDocument::query()
            ->where('type', $content->type()->value)
            ->where('documentable_type', $this->documentable->getMorphClass())
            ->where('documentable_id', $this->documentable->getKey())
            ->whereNull('revoked_at')
            ->exists();

        if ($alreadyIssued) {
            return;
        }

        $document = $issuer->issue($content->type(), $this->documentable, $this->employeeId, $content->view(), $content->data($this->documentable));

        $this->requester?->notify(new DocumentReady($document, $content->readyUrl($this->documentable)));
    }
}
