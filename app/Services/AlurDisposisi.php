<?php

namespace App\Services;

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;

/**
 * Alur distribusi surat masuk (sumber: dokumen Alur_Surat.docx):
 *
 *  1. Agendaris/Staf HKU mencatat surat  ->  otomatis dikirim ke Kepala Dinas          [tahap: pimpinan]
 *  2. Kepala Dinas memberi disposisi, HANYA ke Sekretaris Dinas                         [tahap: filtrasi]
 *  3. Sekretaris menyaring berdasarkan substansi dan meneruskan ke:
 *       - Kasubbag HKU        (umum / kepegawaian / hukum)
 *       - Kasubbag Keuangan   (keuangan / aset / SPM)
 *       - Kepala Bidang       (teknis kesehatan: P2P, Yankes, Kesmas, SDK)                [tahap: kerja]
 *  4. Kasubbag / Kepala Bidang memberi instruksi kerja ke Pejabat Fungsional / Staf     [tahap: pelaksana]
 *  5. Fungsional / Staf mengerjakan, lalu menekan "Penyelesaian Disposisi".
 */
class AlurDisposisi
{
    public const TAHAP = [
        'pimpinan' => 'Arahan Kepala Dinas',
        'filtrasi' => 'Filtrasi Sekretaris Dinas',
        'kerja' => 'Distribusi Kasubbag / Kepala Bidang',
        'pelaksana' => 'Eksekusi Fungsional / Staf',
    ];

    /** Urutan tahap untuk penanda kemajuan di halaman surat. */
    public const URUTAN = ['pimpinan', 'filtrasi', 'kerja', 'pelaksana'];

    public function isSubbag(User $u): bool
    {
        return $u->hasRole('kepala_seksi')
            && in_array($u->unit?->kode_nomor, config('eoffice.alur.unit_subbag', []), true);
    }

    public function kepalaDinas(): ?User
    {
        return User::query()->where('is_active', true)->where('role', 'kepala_dinas')->orderBy('id')->first();
    }

    /** Disposisi terakhir yang sedang menunggu tindakan $user pada surat ini. */
    public function disposisiAktif(User $user, SuratMasuk $surat): ?Disposisi
    {
        return Disposisi::where('surat_masuk_id', $surat->id)
            ->where('kepada_user_id', $user->id)
            ->whereIn('status', Disposisi::AKTIF)
            ->orderByDesc('id')
            ->first();
    }

    /** Dipanggil setelah agendaris selesai mencatat surat: langsung dikirim ke akun Kepala Dinas. */
    public function mulai(SuratMasuk $surat, User $agendaris): ?Disposisi
    {
        $kadis = $this->kepalaDinas();
        if (! $kadis) {
            return null;
        }

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $surat->id,
            'tahap' => 'pimpinan',
            'dari_user_id' => $agendaris->id,
            'kepada_user_id' => $kadis->id,
            'instruksi' => ['Untuk arahan Kepala Dinas'],
            'sifat' => $surat->sifat,
            'status' => 'baru',
        ]);

        $surat->update(['status' => 'didisposisi']);

        return $disposisi;
    }

    /**
     * Langkah yang boleh dilakukan $user pada surat ini, atau null bila tidak ada.
     * Isi: tahap tujuan, label tombol, query calon penerima, disposisi induk, dan apakah instruksi wajib.
     */
    public function langkah(User $user, SuratMasuk $surat): ?array
    {
        $aktif = $this->disposisiAktif($user, $surat);
        $tahap = $aktif?->tahap;
        $next = null;

        if ($user->hasRole('kepala_dinas') && $tahap === 'pimpinan') {
            // Kepala Dinas hanya meneruskan ke Sekretaris Dinas
            $next = ['filtrasi', 'Disposisikan ke Sekretaris Dinas', $this->akunAktif($user)->where('role', 'sekretaris')];
        } elseif ($user->hasRole('sekretaris') && $tahap === 'filtrasi') {
            $next = ['kerja', 'Disposisi lanjutan ke Kasubbag / Kepala Bidang', $this->tujuanFiltrasi($user)];
        } elseif (($this->isSubbag($user) || $user->hasRole('kepala_bidang')) && $tahap === 'kerja') {
            $next = ['pelaksana', 'Tugaskan ke Fungsional / Staf', $this->pelaksana($user)];
        }

        if (! $next) {
            return null;
        }

        [$tujuan, $label, $targets] = $next;

        return [
            'tahap' => $tujuan,
            'label' => $label,
            'targets' => $targets,
            'parent' => $aktif,
            'instruksi_wajib' => true,
        ];
    }

    /**
     * Penyelesaian hanya oleh pelaksana (Fungsional/Staf). Pengecualian: Kasubbag/Kepala Bidang yang
     * tidak memiliki satu pun staf aktif di unitnya boleh menyelesaikan sendiri agar surat tidak macet.
     */
    public function bolehSelesai(Disposisi $d, User $user): bool
    {
        if ($d->tahap === 'pelaksana' || $d->tahap === null) {
            return true;
        }

        if ($d->tahap === 'kerja') {
            return ! $this->pelaksana($user)->exists();
        }

        return false;
    }

    private function akunAktif(User $user)
    {
        return User::query()->where('is_active', true)->where('id', '!=', $user->id);
    }

    /** Calon penerima dari Sekretaris: Kasubbag (HKU, Keuangan) dan seluruh Kepala Bidang. */
    private function tujuanFiltrasi(User $user)
    {
        $kodeSubbag = config('eoffice.alur.unit_subbag', []);

        return $this->akunAktif($user)->where(function ($q) use ($kodeSubbag) {
            $q->where('role', 'kepala_bidang')
                ->orWhere(function ($x) use ($kodeSubbag) {
                    $x->where('role', 'kepala_seksi')
                        ->whereHas('unit', fn ($u) => $u->whereIn('kode_nomor', $kodeSubbag));
                });
        });
    }

    /** Pejabat Fungsional / Staf di unit pejabat tersebut dan unit di bawahnya. */
    private function pelaksana(User $user)
    {
        return $this->akunAktif($user)
            ->whereIn('role', ['fungsional', 'staf'])
            ->whereIn('unit_id', $user->unitIds());
    }
}
