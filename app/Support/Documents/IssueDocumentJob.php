<?php

namespace App\Support\Documents;

use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Models\User;
use App\Modules\Fondasi\Notifications\DocumentFailed;
use App\Modules\Fondasi\Notifications\DocumentReady;
use App\Support\DocumentIssuer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Terbitkan PDF di queue `documents` (NFR-01/02), lalu kabari pemohon lewat notifikasi in-app (FON-07).
 * Idempoten: bila dokumen jenis ini untuk sumber yang sama sudah terbit dan belum dicabut, tidak diterbitkan ulang.
 * Job untuk sumber yang sama diserialkan (WithoutOverlapping); baris dokumen + notifikasi "siap" ditulis dalam satu
 * transaksi, sehingga percobaan ulang tidak menemukan dokumen terbit yang notifikasinya hilang.
 * Gagal di semua percobaan → pemohon dikabari "Dokumen gagal dibuat, coba lagi" dengan tautan ke tombol ulang (docs/14).
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

    /** @return list<object> */
    public function middleware(): array
    {
        $key = 'documents:'.$this->content.':'.$this->documentable->getMorphClass().':'.$this->documentable->getKey();

        return [(new WithoutOverlapping($key))->releaseAfter(15)->expireAfter(120)];
    }

    public function handle(DocumentIssuer $issuer): void
    {
        $content = $this->resolveContent();

        DB::transaction(function () use ($issuer, $content) {
            if ($this->alreadyIssued($content)) {
                return;
            }

            $document = $issuer->issue($content->type(), $this->documentable, $this->employeeId, $content->view(), $content->data($this->documentable));

            $this->requester?->notify(new DocumentReady($document, $content->readyUrl($this->documentable)));
        });
    }

    public function failed(?Throwable $e): void
    {
        $content = $this->resolveContent();

        Log::error('Penerbitan dokumen gagal', [
            'module' => 'documents',
            'action' => 'issue',
            'type' => $content->type()->value,
            'documentable_type' => $this->documentable->getMorphClass(),
            'documentable_id' => $this->documentable->getKey(),
            'user_id' => $this->requester?->id,
            'exception' => $e === null ? null : $e::class,
        ]);

        // Job tumpang-tindih yang kehabisan percobaan padahal job lain sudah menerbitkan: tidak ada yang gagal.
        if ($this->alreadyIssued($content)) {
            return;
        }

        $this->requester?->notify(new DocumentFailed($content->type(), $content->readyUrl($this->documentable)));
    }

    private function resolveContent(): DocumentContent
    {
        /** @var DocumentContent $content */
        $content = app($this->content);

        return $content;
    }

    private function alreadyIssued(DocumentContent $content): bool
    {
        return IssuedDocument::query()
            ->where('type', $content->type()->value)
            ->where('documentable_type', $this->documentable->getMorphClass())
            ->where('documentable_id', $this->documentable->getKey())
            ->whereNull('revoked_at')
            ->exists();
    }
}
