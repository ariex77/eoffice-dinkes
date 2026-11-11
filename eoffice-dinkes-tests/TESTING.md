# Menjalankan tes otomatis

Tes memakai database TERSENDIRI. Jangan pernah mengarahkannya ke database `eoffice_dinkes`, karena setiap tes
mengosongkan database yang dipakainya.

## Persiapan satu kali

1. Nyalakan MAMP, buka phpMyAdmin, buat database baru bernama `eoffice_dinkes_test`
   (collation `utf8mb4_unicode_ci`). Tidak perlu membuat tabel; tes membuatnya sendiri.
2. Salin berkas paket ini ke proyek (titik di akhir perintah itu penting):
   ```
   cp -R ~/Downloads/eoffice-dinkes-tests/. ~/Projects/eoffice-dinkes/
   ```
   Berkas `phpunit.xml`, `tests/TestCase.php`, dan `tests/Feature/ExampleTest.php` bawaan Laravel akan tertimpa. Itu memang yang diinginkan.
3. Bila port atau kata sandi MySQL MAMP Anda berbeda dari bawaan (8889, root/root), ubah baris `DB_PORT`,
   `DB_USERNAME`, `DB_PASSWORD` di `phpunit.xml`.

## Menjalankan

```
cd ~/Projects/eoffice-dinkes
php artisan test
```

Hanya satu berkas: `php artisan test tests/Feature/PenomoranTest.php`
Hanya satu tes: `php artisan test --filter test_alur_penuh_hingga_penyelesaian_disposisi`

## Pengaman

Tes menolak berjalan jika nama database tidak berakhiran `_test`, dengan pesan "Tes dihentikan: DB_DATABASE harus berakhiran _test".

## Isi tes

| Berkas | Yang diperiksa |
|---|---|
| `PenomoranTest` | Contoh nomor Perka 2027 dan 2024, pemilihan format menurut tanggal, urutan tanpa kembar, reset tahun baru, nomor batal, 165 kode klasifikasi |
| `AlurDisposisiTest` | Agendaris → Kepala Dinas → Sekretaris → Kasubbag/Kabid → Fungsional/Staf; tujuan yang dilarang; penyelesaian disposisi |
| `AksesSuratTest` | Surat SR/R/T tertutup di luar rantai disposisi; daftar surat; siapa boleh mencatat surat |
| `SuratKeluarTest` | Nomor draf, checklist registrasi, pindaian wajib, cap tidak wajib untuk Nota Dinas, kategori nomor terkunci, nomor dipesan |
| `AuthDanHalamanTest` | Login, akun nonaktif, akses admin, dan semua halaman utama dibuka oleh 12 akun demo (menangkap error tampilan) |

## Bila ada tes yang gagal

Salin bagian yang menampilkan nama tes dan pesan kesalahan (biasanya diawali `FAILED` atau `Tests\Feature\...`)
lalu kirim ke saya. Satu kegagalan sering menjelaskan banyak kegagalan lain, jadi cukup mulai dari yang paling atas.
