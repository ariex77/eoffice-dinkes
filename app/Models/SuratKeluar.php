<?php

namespace App\Models;

use App\Models\Concerns\HasKlasifikasi;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasKlasifikasi;

    protected $table = 'surat_keluar';

    public const STATUS = ['draft' => 'Draf', 'terkirim' => 'Terkirim'];

    // Pasal 68-73 Perka
    public const MANDAT = [
        'an' => 'a.n. (atas nama)',
        'ub' => 'u.b. (untuk beliau)',
        'plt' => 'Plt. (pelaksana tugas)',
        'plh' => 'Plh. (pelaksana harian)',
    ];

    public const METODE = [
        'kurir' => 'Kurir / diantar langsung',
        'pos' => 'Pos',
        'email' => 'Surel (email)',
        'aplikasi' => 'Aplikasi pesan (WhatsApp, dll.)',
    ];

    protected $fillable = [
        'nomor_surat_id', 'jenis_naskah_id', 'unit_id', 'tanggal_surat', 'tujuan', 'perihal', 'ringkasan',
        'sifat', 'jumlah_lampiran', 'klasifikasi', 'penandatangan_id', 'mandat', 'dasar_mandat', 'tembusan',
        'file_path', 'file_name', 'status', 'tahun_agenda', 'nomor_agenda', 'tanggal_kirim', 'metode_kirim',
        'checklist', 'dibuat_oleh', 'diregistrasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_kirim' => 'date',
            'checklist' => 'array',
        ];
    }

    public function nomor()
    {
        return $this->belongsTo(NomorSurat::class, 'nomor_surat_id');
    }

    public function jenis()
    {
        return $this->belongsTo(JenisNaskah::class, 'jenis_naskah_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function penandatangan()
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function peregistrasi()
    {
        return $this->belongsTo(User::class, 'diregistrasi_oleh');
    }

    public function agendaLabel(): ?string
    {
        return $this->nomor_agenda ? sprintf('%04d/%d', $this->nomor_agenda, $this->tahun_agenda) : null;
    }

    public function scopeVisibleTo($query, User $u)
    {
        if ($u->hasRole('kepala_dinas', 'petugas_persuratan')) {
            return $query;
        }

        return $query->where(function ($w) use ($u) {
            $w->where('dibuat_oleh', $u->id)->orWhere('penandatangan_id', $u->id);

            if ($u->hasRole('admin', 'sekretaris')) {
                $w->orWhere('klasifikasi', 'B');
            } elseif ($ids = $u->unitIds()) {
                $w->orWhere(function ($x) use ($ids) {
                    $x->where('klasifikasi', 'B')->whereIn('unit_id', $ids);
                });
            }
        });
    }
}
