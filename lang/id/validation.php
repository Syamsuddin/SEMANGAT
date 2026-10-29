<?php

// Subset yang dipakai F1; kunci lain jatuh ke bawaan (APP_FALLBACK_LOCALE=en).
return [
    'required' => ':attribute wajib diisi.',
    'file' => ':attribute harus berupa berkas.',
    'max' => [
        'file' => ':attribute maksimal :max kilobita.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => ['string' => ':attribute minimal :min karakter.'],
    'extensions' => ':attribute harus berekstensi: :values.',
    'mimetypes' => ':attribute harus bertipe: :values.',
    'string' => ':attribute harus berupa teks.',
    'uploaded' => ':attribute gagal diunggah.',
    'attributes' => [
        'file' => 'berkas',
        'username' => 'NIP',
        'password' => 'kata sandi',
        'code' => 'kode',
        'reason' => 'alasan',
    ],
];
