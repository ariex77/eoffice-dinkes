<?php

namespace Database\Seeders;

use App\Models\JenisNaskah;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KlasifikasiArsipSeeder::class);
        $unit = $this->unitOrganisasi();
        $this->jenisNaskah();
        $this->penggunaDemo($unit);
    }

    /** Struktur & kode bidang: Perka No. 1/2024, Lampiran Bab II.A.3.b */
    private function unitOrganisasi(): array
    {
        $root = Unit::create(['nama' => 'Dinas Kesehatan Kabupaten Kampar']);
        $unit = ['root' => $root];

        $grup = [
            'Sekretariat' => [
                ['SET-1', 'Subbagian Hukum, Kepegawaian dan Umum'],
                ['SET-2', 'Subbagian Keuangan dan Pengelolaan Aset'],
                ['SET-3', 'Subbagian Program, Informasi dan Hubungan Masyarakat'],
            ],
            'Bidang Kesehatan Masyarakat' => [
                ['KESMAS-1', 'Kesehatan Keluarga dan Gizi Masyarakat'],
                ['KESMAS-2', 'Promosi dan Pemberdayaan Masyarakat'],
                ['KESMAS-3', 'Kesehatan Lingkungan, Kesehatan Kerja dan Olahraga'],
            ],
            'Bidang Pencegahan dan Pengendalian Penyakit' => [
                ['P2P-1', 'Surveilans dan Imunisasi'],
                ['P2P-2', 'Pencegahan dan Pengendalian Penyakit Menular'],
                ['P2P-3', 'Pencegahan dan Pengendalian Penyakit Tidak Menular dan Kesehatan Jiwa'],
            ],
            'Bidang Pelayanan Kesehatan' => [
                ['YANKES-1', 'Pelayanan Kesehatan Primer dan Kesehatan Tradisional'],
                ['YANKES-2', 'Pelayanan Kesehatan Rujukan'],
                ['YANKES-3', 'Pembiayaan dan Jaminan Kesehatan Masyarakat'],
            ],
            'Bidang Sumber Daya Kesehatan' => [
                ['SDK-1', 'Kefarmasian, Alat Kesehatan dan Perbekalan Kesehatan Rumah Tangga'],
                ['SDK-2', 'Pengembangan Sarana dan Prasarana'],
                ['SDK-3', 'Kelompok Jabatan Fungsional Substansi Sumber Daya Manusia Kesehatan'],
            ],
        ];

        foreach ($grup as $nama => $anak) {
            $g = Unit::create(['parent_id' => $root->id, 'nama' => $nama]);
            $unit[$nama] = $g;
            foreach ($anak as [$kode, $namaAnak]) {
                $unit[$kode] = Unit::create(['parent_id' => $g->id, 'kode_nomor' => $kode, 'nama' => $namaAnak]);
            }
        }

        foreach ([['LABKESDA', 'UPT Labkesda'], ['IFK', 'UPT IFK'], ['PSC', 'PSC']] as [$kode, $nama]) {
            $unit[$kode] = Unit::create(['parent_id' => $root->id, 'kode_nomor' => $kode, 'nama' => $nama]);
        }

        return $unit;
    }

    /**
     * pola: standar | nomor_tahun | nota. Jenis dengan counter_key sama berbagi urutan nomor.
     * Perka menyatakan penomoran Surat Undangan, Perjanjian, Kuasa, Berita Acara, Keterangan,
     * Pengantar, Pengumuman & Laporan "sama dengan Surat Dinas" -> default berbagi counter 'surat_dinas'.
     * Ubah counter_key bila Dinas ingin buku nomor terpisah.
     */
    private function jenisNaskah(): void
    {
        $rows = [
            // nama, kelompok, pola, counter_key, pakai_cap
            ['Peraturan Kepala Dinas', 'arahan', 'nomor_tahun', 'peraturan', true],
            ['Keputusan Kepala Dinas', 'arahan', 'standar', 'keputusan', true],
            ['Standar Prosedur Operasional (SPO)', 'arahan', 'standar', 'spo', true],
            ['Surat Perintah', 'arahan', 'standar', 'surat_perintah', true],
            ['Surat Tugas', 'arahan', 'standar', 'surat_tugas', true],
            ['Surat Perintah Perjalanan Dinas (SPPD)', 'arahan', 'standar', 'sppd', true],
            ['Nota Dinas', 'korespondensi', 'nota', 'nota_dinas', false],
            ['Memorandum', 'korespondensi', 'nota', 'memorandum', false],
            ['Surat Dinas', 'korespondensi', 'standar', 'surat_dinas', true],
            ['Instruksi', 'khusus', 'nomor_tahun', 'instruksi', true],
            ['Surat Edaran', 'khusus', 'nomor_tahun', 'surat_edaran', true],
            ['Surat Undangan', 'khusus', 'standar', 'surat_dinas', true],
            ['Surat Perjanjian', 'khusus', 'standar', 'surat_dinas', true],
            ['Surat Kuasa', 'khusus', 'standar', 'surat_dinas', true],
            ['Berita Acara', 'khusus', 'standar', 'surat_dinas', true],
            ['Surat Keterangan', 'khusus', 'standar', 'surat_dinas', true],
            ['Surat Pengantar', 'khusus', 'standar', 'surat_dinas', true],
            ['Pengumuman', 'khusus', 'standar', 'surat_dinas', true],
            ['Laporan', 'khusus', 'standar', 'surat_dinas', true],
        ];

        // Format baru (Perka 2027): jenis berikut diawali kategori keamanan, mis. B/400.7.7.2/DINKES/104/2027
        $berKategori = ['Surat Dinas', 'Surat Undangan', 'Surat Perjanjian', 'Surat Kuasa', 'Berita Acara',
            'Surat Keterangan', 'Surat Pengantar', 'Pengumuman', 'Laporan'];

        foreach ($rows as [$nama, $kelompok, $pola, $key, $cap]) {
            JenisNaskah::create([
                'nama' => $nama, 'kelompok' => $kelompok, 'pola' => $pola,
                'counter_key' => $key, 'pakai_cap' => $cap, 'pakai_kategori' => in_array($nama, $berKategori, true),
            ]);
        }
    }

    /** Akun contoh untuk uji coba. Kata sandi "password" — WAJIB diganti/dihapus sebelum produksi. */
    private function penggunaDemo(array $unit): void
    {
        $rows = [
            // nama, email, role, unit, kode_jabatan (placeholder), jabatan
            ['Administrator Sistem', 'admin@dinkes.test', 'admin', 'root', null, 'Administrator'],
            ['Agendaris (Demo)', 'persuratan@dinkes.test', 'petugas_persuratan', 'SET-1', null, 'Agendaris / Pengadministrasi Persuratan'],
            ['Kepala Dinas (Demo)', 'kadis@dinkes.test', 'kepala_dinas', 'root', 'KADIS', 'Kepala Dinas'],
            ['Sekretaris (Demo)', 'sekretaris@dinkes.test', 'sekretaris', 'Sekretariat', 'SEKDIN', 'Sekretaris Dinas'],
            ['Kasubbag HKU (Demo)', 'kasubag.hku@dinkes.test', 'kepala_seksi', 'SET-1', 'KASUBAG-HKU', 'Kepala Subbagian Hukum, Kepegawaian dan Umum'],
            ['Kasubbag Keuangan (Demo)', 'kasubag.keuangan@dinkes.test', 'kepala_seksi', 'SET-2', 'KASUBAG-KEU', 'Kepala Subbagian Keuangan dan Pengelolaan Aset'],
            ['Kabid Kesmas (Demo)', 'kabid.kesmas@dinkes.test', 'kepala_bidang', 'Bidang Kesehatan Masyarakat', 'KABID-KESMAS', 'Kepala Bidang Kesehatan Masyarakat'],
            ['Kabid P2P (Demo)', 'kabid.p2p@dinkes.test', 'kepala_bidang', 'Bidang Pencegahan dan Pengendalian Penyakit', 'KABID-P2P', 'Kepala Bidang Pencegahan dan Pengendalian Penyakit'],
            ['Staf HKU (Demo)', 'staf.hku@dinkes.test', 'staf', 'SET-1', null, 'Staf Subbagian HKU'],
            ['Staf Keuangan (Demo)', 'staf.keuangan@dinkes.test', 'staf', 'SET-2', null, 'Staf Subbagian Keuangan'],
            ['Fungsional Kesmas (Demo)', 'fungsional.kesmas@dinkes.test', 'fungsional', 'KESMAS-1', null, 'Pejabat Fungsional Bidang Kesmas'],
            ['Fungsional P2P (Demo)', 'fungsional.p2p@dinkes.test', 'fungsional', 'P2P-1', null, 'Pejabat Fungsional Bidang P2P'],
        ];

        foreach ($rows as [$nama, $email, $role, $u, $kodeJab, $jabatan]) {
            User::create([
                'name' => $nama, 'email' => $email, 'password' => 'password', 'role' => $role,
                'unit_id' => $unit[$u]->id, 'kode_jabatan' => $kodeJab, 'jabatan' => $jabatan, 'is_active' => true,
            ]);
        }
    }
}
