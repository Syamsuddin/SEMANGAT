<?php

namespace App\Support;

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Models\User;
use App\Support\Documents\DocumentContent;
use App\Support\Documents\IssueDocumentJob;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Satu-satunya pembuat PDF (docs/12, keputusan A5): Blade → dompdf + QR verifikasi → disk dokumen → out_issued_documents.
 * View menerima $qr (data URI PNG), $verifyUrl, $uuid di samping $data.
 * Sejak F4 Action memanggil queue() — PDF dibuat di queue `documents` lalu pemohon dikabari (keputusan PDF 2026-11-01).
 */
class DocumentIssuer
{
    /**
     * Antrekan penerbitan setelah transaksi pemanggil di-commit.
     *
     * @param  class-string<DocumentContent>  $content
     */
    public function queue(string $content, Model $documentable, ?int $employeeId, ?User $requester = null): void
    {
        IssueDocumentJob::dispatch($content, $documentable, $employeeId, $requester)->afterCommit();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function issue(DocumentType $type, Model $documentable, ?int $employeeId, string $view, array $data): IssuedDocument
    {
        $uuid = Str::uuid()->toString();
        $verifyUrl = $this->verifyUrl($uuid);

        $bytes = Pdf::loadView($view, $data + ['uuid' => $uuid, 'verifyUrl' => $verifyUrl, 'qr' => $this->qrDataUri($verifyUrl)])
            ->setPaper('a4')
            ->output();

        $path = 'documents/'.$type->value.'/'.$uuid.'.pdf';
        Storage::disk($this->disk())->put($path, $bytes);

        return IssuedDocument::query()->create([
            'uuid' => $uuid,
            'type' => $type,
            'documentable_type' => $documentable->getMorphClass(),
            'documentable_id' => $documentable->getKey(),
            'employee_id' => $employeeId,
            'file_path' => $path,
            'sha256' => hash('sha256', $bytes),
            'issued_at' => now(),
        ]);
    }

    public function contents(IssuedDocument $document): string
    {
        return (string) Storage::disk($this->disk())->get($document->file_path);
    }

    public function verifyUrl(string $uuid): string
    {
        return rtrim((string) config('semangat.verify_base_url'), '/').'/verifikasi/'.$uuid;
    }

    private function qrDataUri(string $text): string
    {
        // PNG lewat ext-gd: dompdf merender PNG andal, SVG tidak.
        $png = (new Writer(new GDLibRenderer(240, 2)))->writeString($text);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function disk(): string
    {
        return (string) config('semangat.documents.disk');
    }
}
