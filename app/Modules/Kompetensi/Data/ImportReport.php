<?php

namespace App\Modules\Kompetensi\Data;

/** Hasil impor berkas kompetensi. Ada galat → tidak ada yang ditulis (seluruh berkas ditolak). */
final readonly class ImportReport
{
    /** @param array<int, list<string>> $errors nomor baris => pesan */
    public function __construct(public int $rowsOk, public array $errors = [], public ?string $error = null) {}

    public function succeeded(): bool
    {
        return $this->errors === [] && $this->error === null;
    }

    /** @param array<int, list<string>> $errors */
    public static function rejected(array $errors): self
    {
        return new self(0, $errors);
    }

    public static function failed(string $error): self
    {
        return new self(0, [], $error);
    }
}
