<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Unggahan Berkas
    |---------------------------------------------------------------------------
    |
    | Batas ukuran & tipe berkas untuk foto nota dan bukti transfer. Nilai di
    | sini adalah satu-satunya sumber kebenaran: aturan validasi Livewire,
    | config Livewire (config/livewire.php), dan penjaga di Action merujuk
    | ke sini lewat App\Support\Uploads.
    |
    | Ubah lewat env UPLOAD_MAX_KB (dalam kilobyte, default 102400 = 100 MB).
    | config/livewire.php membaca env yang sama karena dimuat lebih dulu
    | (urutan ksort) sehingga tidak bisa memanggil config('ulaman.*').
    |
    */

    'upload' => [

        'max_kb' => (int) env('UPLOAD_MAX_KB', 102400),

        'image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],

        'bukti_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],

    ],

];
