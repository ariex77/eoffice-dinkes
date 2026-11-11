<?php

return [
    'timezone' => 'Asia/Jakarta',

    // Nama SKPD pada nomor surat
    'nama_skpd' => 'DINKES',

    // FORMAT NOMOR
    //  lama : Perka No. 1 Tahun 2024  -> 440/DINKES/SET-1/2026/104
    //  baru : Perka No. 1 Tahun 2027  -> 400.7.7.2/DINKES/104/2027  (surat dinas: B/400.7.7.2/DINKES/104/2027)
    //  auto : memakai format baru mulai 'format_baru_berlaku' (Perka 2027 berlaku sejak ditetapkan, 2 Januari 2027)
    // Untuk mencoba format baru sebelum tanggal itu: isi EOFFICE_FORMAT_NOMOR=baru di file .env
    'format_nomor' => env('EOFFICE_FORMAT_NOMOR', 'auto'),
    'format_baru_berlaku' => '2027-01-02',

    // Kode tetap, hanya untuk format LAMA
    'kode_skpd' => '440',

    // Urutan nomor untuk naskah ber-pola "standar" (Keputusan, SPO, SP, ST, SPPD, Surat Dinas, dst.)
    //  per_jenis   : tiap jenis punya urutan sendiri (kecuali yang berbagi counter_key di tabel jenis_naskah)
    //  satu_urutan : semuanya berbagi SATU urutan se-Dinas (mencegah nomor kembar antarjenis)
    'counter_mode' => env('EOFFICE_COUNTER_MODE', 'per_jenis'),

    // Nota Dinas & Memorandum: nomor/kode jabatan/kode klasifikasi arsip/bulan/tahun
    'kode_klasifikasi_nota' => '440', // bawaan format LAMA; format baru mewajibkan memilih kode klasifikasi arsip
    'nota_bulan_romawi' => true,

    // Alur distribusi surat masuk (dokumen Alur_Surat.docx):
    // Agendaris -> Kepala Dinas -> Sekretaris (filtrasi) -> Kasubbag HKU / Kasubbag Keuangan / Kepala Bidang
    //           -> Pejabat Fungsional / Staf (penyelesaian)
    'alur' => [
        // Kasubbag yang boleh menerima disposisi dari Sekretaris = pengguna berperan "kepala_seksi"
        // pada unit berkode berikut (tabel units.kode_nomor). Tambahkan 'SET-3' bila Subbag Program ikut.
        'unit_subbag' => ['SET-1', 'SET-2'],
    ],

    'max_upload_kb' => 10240, // 10 MB
];
