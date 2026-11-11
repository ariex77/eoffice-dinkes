<?php

namespace App\Services;

use App\Models\JenisNaskah;
use App\Models\KlasifikasiArsip;
use App\Models\NomorSurat;
use App\Models\SuratMasuk;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mesin penomoran. Dua versi format, dipilih menurut tanggal (lihat config/eoffice.php):
 *
 * FORMAT LAMA (Perka No. 1 Tahun 2024)
 *   standar     : 440/DINKES/SET-1/2026/104
 *   nomor_tahun : 12 Tahun 2026
 *   nota        : 7/KASUBAG-HKU/440/XI/2026
 *
 * FORMAT BARU (Perka No. 1 Tahun 2027, berlaku sejak 2 Januari 2027) — kode unit tidak lagi dipakai
 *   standar, tanpa kategori : 400.7.7.2/DINKES/104/2027        (Keputusan, SPO, SP, ST, SPPD)
 *   standar, dengan kategori: B/400.7.7.2/DINKES/104/2027      (Surat Dinas, Undangan, Perjanjian, Kuasa,
 *                                                                Berita Acara, Keterangan, Pengantar,
 *                                                                Pengumuman, Laporan)
 *   nomor_tahun : 12 Tahun 2027                                (Peraturan, Instruksi, Surat Edaran)
 *   nota        : 7/KASUBAG-HKU/400.7.7.2/XI/2027              (Nota Dinas, Memorandum — tidak berubah)
 *
 * Nomor yang sudah terbit tersimpan apa adanya; perubahan format hanya berlaku untuk nomor baru.
 */
class PenomoranService
{
    /** 'lama' atau 'baru' untuk tanggal tertentu (default: hari ini). */
    public function versi(?string $tanggal = null): string
    {
        $mode = config('eoffice.format_nomor', 'auto');
        if (in_array($mode, ['lama', 'baru'], true)) {
            return $mode;
        }

        return ($tanggal ?? now()->toDateString()) >= config('eoffice.format_baru_berlaku') ? 'baru' : 'lama';
    }

    /**
     * Ambil nomor urut berikutnya secara atomik. Aman dipanggil bersamaan oleh banyak pengguna.
     * Bila dipanggil di dalam transaksi lain dan transaksi itu gagal, nomor ikut dibatalkan (tanpa lubang).
     */
    public function next(string $scopeKey): int
    {
        return DB::transaction(function () use ($scopeKey) {
            DB::table('penomoran_counters')->insertOrIgnore([
                'scope_key' => $scopeKey,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('penomoran_counters')->where('scope_key', $scopeKey)->lockForUpdate()->first();
            $next = $row->last_number + 1;

            DB::table('penomoran_counters')->where('id', $row->id)->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

            return $next;
        });
    }

    public function ambil(
        JenisNaskah $jenis,
        ?Unit $unit,
        User $pengambil,
        ?User $penandatangan = null,
        ?string $perihal = null,
        ?string $kodeKlasifikasi = null,
        string $status = 'dipesan',
        ?string $kategori = null,
    ): NomorSurat {
        $now = now();
        $versi = $this->versi();

        if ($jenis->pola === 'standar') {
            if ($versi === 'lama' && (! $unit || ! $unit->kode_nomor)) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Pilih unit/bidang yang memiliki kode penomoran (mis. SET-1, KESMAS-1).',
                ]);
            }

            if ($versi === 'baru') {
                if (blank($kodeKlasifikasi) || ! KlasifikasiArsip::where('kode', $kodeKlasifikasi)->exists()) {
                    throw ValidationException::withMessages([
                        'kode_klasifikasi' => 'Pilih kode klasifikasi arsip yang valid (mis. 400.7.7.2 untuk Imunisasi).',
                    ]);
                }

                $kategori = $jenis->pakai_kategori ? ($kategori ?: 'B') : null;
                if ($kategori && ! array_key_exists($kategori, SuratMasuk::KLASIFIKASI)) {
                    throw ValidationException::withMessages(['kategori' => 'Kategori klasifikasi keamanan tidak valid.']);
                }
            }
        }

        if ($jenis->pola === 'nota' && (! $penandatangan || ! $penandatangan->kode_jabatan)) {
            throw ValidationException::withMessages([
                'penandatangan_id' => 'Nota Dinas/Memorandum wajib memilih penandatangan yang sudah memiliki kode jabatan.',
            ]);
        }

        if ($jenis->pola === 'nota' && $versi === 'baru'
            && (blank($kodeKlasifikasi) || ! KlasifikasiArsip::where('kode', $kodeKlasifikasi)->exists())) {
            throw ValidationException::withMessages([
                'kode_klasifikasi' => 'Pilih kode klasifikasi arsip yang valid untuk Nota Dinas/Memorandum.',
            ]);
        }

        if ($versi === 'lama' || $jenis->pola !== 'standar') {
            $kategori = null;
        }
        if ($jenis->pola === 'nomor_tahun') {
            $kodeKlasifikasi = null;
        }

        $key = $jenis->counter_key;
        if ($jenis->pola === 'standar' && config('eoffice.counter_mode') === 'satu_urutan') {
            $key = 'dinas'; // satu urutan se-Dinas untuk seluruh naskah ber-pola standar
        }
        $perUnit = $versi === 'lama' && $jenis->counter_per_unit && $unit;
        $scope = implode('|', [$now->year, $key, $perUnit ? 'U'.$unit->id : 'ALL']);

        return DB::transaction(function () use ($jenis, $unit, $pengambil, $penandatangan, $perihal, $kodeKlasifikasi, $status, $kategori, $versi, $now, $scope) {
            $urut = $this->next($scope);

            return NomorSurat::create([
                'jenis_naskah_id' => $jenis->id,
                'unit_id' => $unit?->id,
                'counter_scope' => $scope,
                'tahun' => $now->year,
                'urutan' => $urut,
                'kategori' => $kategori,
                'kode_klasifikasi' => $kodeKlasifikasi,
                'nomor_lengkap' => $this->format($versi, $jenis, $urut, $unit, $penandatangan, $kodeKlasifikasi, $kategori),
                'tanggal' => $now->toDateString(),
                'perihal' => $perihal,
                'user_id' => $pengambil->id,
                'penandatangan_id' => $penandatangan?->id,
                'status' => $status,
            ]);
        });
    }

    private function format(string $versi, JenisNaskah $jenis, int $urut, ?Unit $unit, ?User $ttd, ?string $klasifikasi, ?string $kategori): string
    {
        $now = now();

        if ($jenis->pola === 'nomor_tahun') {
            return "{$urut} Tahun {$now->year}";
        }

        if ($jenis->pola === 'nota') {
            return implode('/', [
                $urut,
                $ttd->kode_jabatan,
                $klasifikasi ?: config('eoffice.kode_klasifikasi_nota'),
                $this->bulan($now->month),
                $now->year,
            ]);
        }

        if ($versi === 'baru') {
            return implode('/', array_filter([
                $kategori,
                $klasifikasi,
                config('eoffice.nama_skpd'),
                $urut,
                $now->year,
            ], fn ($x) => $x !== null && $x !== ''));
        }

        return implode('/', [
            config('eoffice.kode_skpd'),
            config('eoffice.nama_skpd'),
            $unit->kode_nomor,
            $now->year,
            $urut,
        ]);
    }

    private function bulan(int $m): string
    {
        if (! config('eoffice.nota_bulan_romawi')) {
            return (string) $m;
        }

        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$m];
    }
}
