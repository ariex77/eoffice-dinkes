# E-Office Dinkes Kampar — Fase 1

Surat masuk, surat keluar, penomoran otomatis, disposisi, dan pengguna/peran.
Dasar aturan: Peraturan Kepala Dinas Kesehatan Kab. Kampar No. 1 Tahun 2027 tentang Pedoman Tata Naskah Dinas (mencabut No. 1 Tahun 2024) dan dokumen Alur_Surat.
Ditulis untuk Laravel 11/12 (PHP 8.2+), MySQL, Blade + Bootstrap 5 (CDN, responsif).

## Pemasangan (MAMP, macOS)

1. **Prasyarat**
   - MAMP: pilih PHP 8.2 atau lebih baru (MAMP > Preferences > PHP).
   - Composer (`brew install composer`).
   - Agar terminal memakai PHP MAMP, jalankan (sesuaikan nomor versi):
     `export PATH=/Applications/MAMP/bin/php/php8.3.x/bin:$PATH`
2. **Buat proyek Laravel baru**
   ```
   cd ~/Sites   # atau folder kerja Anda
   composer create-project laravel/laravel eoffice-dinkes
   ```
3. **Tumpuk paket ini ke proyek** (titik di akhir penting, agar folder digabung)
   ```
   cp -R /lokasi/eoffice-dinkes-fase1/. ~/Sites/eoffice-dinkes/
   ```
   File Laravel bawaan yang ikut tertimpa: `app/Models/User.php`, `app/Providers/AppServiceProvider.php`,
   `routes/web.php`, `database/seeders/DatabaseSeeder.php`.
4. **Buat database**: nyalakan MAMP, buka phpMyAdmin, buat database `eoffice_dinkes` (utf8mb4_unicode_ci).
5. **Edit `.env`**
   ```
   APP_NAME="E-Office Dinkes Kampar"
   APP_LOCALE=id
   APP_FALLBACK_LOCALE=en
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=8889
   DB_DATABASE=eoffice_dinkes
   DB_USERNAME=root
   DB_PASSWORD=root
   ```
   (Port 8889 dan root/root adalah bawaan MAMP. Cek di MAMP bila Anda mengubahnya.)
6. **Migrasi + data awal**
   ```
   php artisan migrate --seed
   ```
7. **Jalankan**: `php artisan serve` lalu buka http://127.0.0.1:8000
   (atau arahkan Document Root MAMP ke folder `public`).

Akun demo (kata sandi semuanya `password`, semua `@dinkes.test`): admin, persuratan (agendaris), kadis, sekretaris,
kasubag.hku, kasubag.keuangan, kabid.kesmas, kabid.p2p, staf.hku, staf.keuangan, fungsional.kesmas, fungsional.p2p.
**Hapus atau ganti sebelum dipakai sungguhan.**

## Alur surat masuk dan disposisi (sumber: Alur_Surat.docx)

```
Agendaris/Staf HKU  ->  Kepala Dinas  ->  Sekretaris Dinas (filtrasi)
                                              |-- umum / kepegawaian / hukum -> Kasubbag HKU       -> Fungsional/Staf
                                              |-- keuangan / aset / SPM      -> Kasubbag Keuangan  -> Fungsional/Staf
                                              |-- teknis kesehatan           -> Kepala Bidang      -> Fungsional/Staf
                                                                                                  -> [Penyelesaian Disposisi]
```

Alur dikunci oleh aplikasi, bukan oleh pengguna: setiap tahap hanya melihat calon penerima yang sesuai.
Agendaris mencatat surat, lalu surat otomatis masuk ke akun Kepala Dinas. Kepala Dinas hanya bisa meneruskan ke Sekretaris.
Sekretaris memilih Kasubbag HKU, Kasubbag Keuangan, dan/atau Kepala Bidang (boleh lebih dari satu).
Kasubbag/Kepala Bidang menugaskan Fungsional/Staf di unitnya. Hanya pelaksana yang menekan **Penyelesaian Disposisi**;
surat berstatus Selesai setelah semua cabang disposisi selesai. Seluruh jejak (kirim, baca, selesai, hasil) tampil di halaman surat.

## Skenario uji (±10 menit)

1. `persuratan@` → Surat Masuk → Catat (klasifikasi Biasa, unggah PDF). Surat otomatis terkirim ke Kepala Dinas.
2. `kadis@` → buka surat → hanya bisa disposisi ke Sekretaris → kirim.
3. `sekretaris@` → pilih dua tujuan sekaligus: Kasubbag HKU dan Kabid Kesmas → kirim.
4. `kasubag.hku@` → tugaskan ke Staf HKU. `kabid.kesmas@` → tugaskan ke Fungsional Kesmas.
5. `staf.hku@` dan `fungsional.kesmas@` → masing-masing tekan Penyelesaian Disposisi (isi hasil kerja).
6. Setelah keduanya selesai, status surat menjadi Selesai. Buka halaman surat untuk melihat rekam jejaknya.
7. Ulangi dengan klasifikasi **Rahasia**: `kasubag.keuangan@` tidak boleh melihat surat itu.
8. Uji surat keluar: `fungsional.kesmas@` → Surat Keluar → Buat (Surat Dinas, klasifikasi keamanan Biasa)
   → nomor `440/DINKES/KESMAS-1/2026/1` (format lama) atau `B/400.7.7.2/DINKES/1/2027` (format baru, kode 400.7.7.2)
   → unggah pindaian → `persuratan@` registrasi.

## Hak akses

| Peran | Kemampuan utama |
|---|---|
| Admin | Kelola pengguna; lihat data berklasifikasi Biasa |
| Agendaris (Pengadministrasi Persuratan) | Catat/ubah surat masuk, registrasi surat keluar, batalkan nomor, lihat semua surat |
| Kepala Dinas | Lihat semua surat; meneruskan hanya ke Sekretaris |
| Sekretaris | Lihat semua surat Biasa; filtrasi ke Kasubbag HKU/Keuangan dan Kepala Bidang |
| Kasubbag (HKU, Keuangan), Kepala Bidang | Menugaskan Fungsional/Staf di unitnya |
| Pejabat Fungsional, Staf | Mengerjakan disposisi dan menekan Penyelesaian Disposisi; buat surat keluar, ambil nomor |

Surat berklasifikasi SR/R/T hanya terlihat oleh Kepala Dinas, agendaris, dan pihak yang terlibat dalam rantai disposisinya (Pasal 58).
Berkas disimpan di `storage/app` (privat), diunduh lewat aplikasi setelah pemeriksaan hak akses.

## Aturan penomoran (Perka No. 1 Tahun 2027, berlaku sejak 2 Januari 2027)

Perka 2027 mencabut Perka 2024. Aplikasi mendukung **dua format sekaligus** dan memilihnya menurut tanggal:
sebelum 2 Januari 2027 memakai format lama, sesudahnya format baru. Nomor yang sudah terbit tidak pernah diubah.

| Jenis naskah | Format baru (2027) | Format lama (2024) |
|---|---|---|
| Keputusan, SPO, Surat Perintah, Surat Tugas, SPPD | `400.7.7.2/DINKES/104/2027` | `440/DINKES/SET-1/2026/104` |
| Surat Dinas, Undangan, Perjanjian, Kuasa, Berita Acara, Keterangan, Pengantar, Pengumuman, Laporan | `B/400.7.7.2/DINKES/104/2027` | `440/DINKES/SET-1/2026/104` |
| Peraturan, Instruksi, Surat Edaran | `12 Tahun 2027` | sama |
| Nota Dinas, Memorandum | `7/KASUBAG-HKU/400.7.7.2/XI/2027` | `7/KASUBAG-HKU/440/XI/2026` |

Perubahan utama: **kode unit (SET-1, KESMAS-1, dst.) tidak lagi ada di nomor**, diganti **kode klasifikasi arsip** (400.7.x, 165 kode
sudah dimuat dari lampiran Perka). Surat dinas diawali **kategori keamanan** (SR/R/T/B) yang diambil dari klasifikasi keamanan surat.
Pengguna memilih kode klasifikasi sesuai isi surat (kolom bisa diketik: kode atau uraian, mis. "imunisasi").

Menguji format baru sebelum 2 Januari 2027: tambahkan `EOFFICE_FORMAT_NOMOR=baru` di `.env`, lalu `php artisan config:clear`.
(Nilai: `auto` = ikut tanggal [bawaan], `lama`, `baru`.)

Nomor urut diambil lewat penghitung atomik (kunci baris database), jadi dua orang yang mengambil bersamaan tidak akan
mendapat nomor kembar. Urutan kembali ke 1 setiap tahun. Nomor batal tidak dipakai ulang.

## Hal yang perlu Anda putuskan (tertera sebagai asumsi di kode)

1. **Satu urutan atau per jenis.** Perka menyebut "nomor urut penomoran pada Dinas Kesehatan" (contoh 104), tanpa memisahkan jenis naskah.
   Pada format baru, Keputusan dan SPO tidak punya awalan pembeda, sehingga urutan terpisah bisa menghasilkan nomor yang sama
   (mis. `400.7.7.2/DINKES/1/2027` untuk dua jenis berbeda). Default saat ini: per jenis. Untuk satu urutan se-Dinas isi
   `EOFFICE_COUNTER_MODE=satu_urutan` di `.env`.
2. **Urutan unsur Surat Perintah/Tugas/SPPD.** Daftar unsur di Perka menulis "tahun terbit" sebelum "nomor urut", tetapi contohnya
   `400.7.7.2/DINKES/104/2026` (nomor urut lalu tahun). Aplikasi mengikuti **contoh**.
3. **Nota Dinas, Memorandum, Telaahan Staf.** Perka hanya menyebut unsur nota dinas/memorandum (nomor, kode jabatan penanda tangan,
   kode klasifikasi arsip, bulan, tahun) tanpa contoh; Telaahan Staf tidak punya aturan nomor sehingga belum ada di aplikasi.
   Kode jabatan di akun demo hanyalah contoh.
4. **Kasubbag penerima disposisi**: ditentukan di `config/eoffice.php` (`alur.unit_subbag`, default SET-1 dan SET-2).
5. **Kode unit** tetap tersimpan di data unit dan pengguna (untuk struktur organisasi dan hak akses), hanya tidak masuk nomor.

## Tes otomatis

Ada 56 tes (penomoran, alur disposisi, hak akses, surat keluar, login). Panduan lengkap dan persiapan database
tes (`eoffice_dinkes_test`) ada di `TESTING.md`. Singkatnya: `php artisan test`.

## Belum ada di fase 1 (usulan fase berikutnya)

Antarmuka kelola unit & jenis naskah (saat ini lewat seeder/phpMyAdmin), cetak lembar disposisi, template naskah + alur paraf,
SPT/SPPD, notifikasi WhatsApp/email, arsip + Jadwal Retensi Arsip, log audit terpisah, QR verifikasi, pengujian otomatis.
Catatan: tes bawaan `tests/Feature/ExampleTest.php` akan gagal karena `/` kini meminta login; hapus atau sesuaikan.
# eoffice-dinkes
