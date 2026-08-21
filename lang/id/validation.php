<?php

/*
|--------------------------------------------------------------------------
| Pesan Validasi Bahasa Indonesia (Ulaman Purchase Log)
|--------------------------------------------------------------------------
| Digunakan karena APP_LOCALE=id. Melengkapi berkas terjemahan JSON template
| (lang/id.json) yang menangani string UI __(), sedangkan berkas ini khusus
| pesan validasi Laravel.
*/

return [
    'accepted' => ':attribute harus diterima.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'array' => ':attribute harus berupa larik.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':attribute harus memiliki antara :min sampai :max item.',
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'string' => ':attribute harus antara :min sampai :max karakter.',
    ],
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus tanggal yang sama dengan :date.',
    'different' => ':attribute dan :other harus berbeda.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'ends_with' => ':attribute harus diakhiri salah satu dari: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute wajib diisi.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
    ],
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'lt' => [
        'numeric' => ':attribute harus lebih kecil dari :value.',
    ],
    'lte' => [
        'numeric' => ':attribute harus lebih kecil dari atau sama dengan :value.',
    ],
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute harus memiliki minimal :min item.',
        'numeric' => ':attribute minimal bernilai :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi apabila :other bernilai :value.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'url' => ':attribute harus berupa URL yang valid.',

    /*
    | Pesan kustom per aturan/atribut.
    */
    'custom' => [
        'items' => [
            'required' => 'Nota harus memiliki minimal satu item.',
            'min' => 'Nota harus memiliki minimal satu item.',
        ],
        'form.items' => [
            'required' => 'Nota harus memiliki minimal satu item.',
            'min' => 'Nota harus memiliki minimal satu item.',
        ],
    ],

    /*
    | Label atribut agar pesan berbahasa Indonesia yang wajar.
    */
    'attributes' => [
        'nama' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'tanggal' => 'tanggal',
        'supplierId' => 'supplier',
        'nomorNota' => 'nomor nota',
        'categoryId' => 'kategori',
        'metodeBayar' => 'metode bayar',
        'status' => 'status',
        'simbol' => 'simbol',
        'warna' => 'warna',
        'alamat' => 'alamat',
        'telepon' => 'telepon',
        'pic' => 'PIC',
        'items' => 'item',
        'form.tanggal' => 'tanggal',
        'form.supplierId' => 'supplier',
        'form.nomorNota' => 'nomor nota',
        'form.items' => 'item',
        'form.items.*.deskripsi' => 'deskripsi',
        'form.items.*.qty' => 'qty',
        'form.items.*.hargaSatuan' => 'harga satuan',
        'form.items.*.diskonTipe' => 'tipe diskon',
        'form.items.*.diskonNilai' => 'nilai diskon',
    ],
];
