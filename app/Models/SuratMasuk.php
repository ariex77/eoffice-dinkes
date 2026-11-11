<?php

namespace App\Models;

use App\Models\Concerns\HasKlasifikasi;
use Illuminate\Database\Eloquent\Model;

class SuratMasuk extends Model
{
    use HasKlasifikasi;

    protected $table = 'surat_masuk';

    // Pasal 56 & 61 Perka: kode derajat klasifikasi keamanan
    public const KLASIFIKASI = [
        'SR' => 'Sangat Rahasia',
        'R' => 'Rahasia',
        'T' => 'Terbatas',
        'B' => 'Biasa/Terbuka',
    ];

    public const SIFAT = ['biasa' => 'Biasa', 'segera' => 'Segera', 'sangat_segera' => 'Sangat Segera'];

    public const STATUS = ['baru' => 'Baru', 'didisposisi' => 'Didisposisi', 'selesai' => 'Selesai'];

    protected $fillable = [
        'tahun', 'nomor_agenda', 'tanggal_terima', 'tanggal_surat', 'nomor_surat', 'asal_surat',
        'perihal', 'ringkasan', 'klasifikasi', 'sifat', 'jumlah_lampiran', 'unit_tujuan_id',
        'file_path', 'file_name', 'status', 'diterima_oleh',
    ];

    protected function casts(): array
    {
        return ['tanggal_terima' => 'date', 'tanggal_surat' => 'date'];
    }

    public function unitTujuan()
    {
        return $this->belongsTo(Unit::class, 'unit_tujuan_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function disposisi()
    {
        return $this->hasMany(Disposisi::class)->orderBy('id');
    }

    public function agendaLabel(): string
    {
        return sprintf('%04d/%d', $this->nomor_agenda, $this->tahun);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'didisposisi' => 'info',
            'selesai' => 'success',
            default => 'primary',
        };
    }

    /**
     * Satu-satunya sumber aturan "siapa boleh melihat surat masuk" (Pasal 58).
     * - SR/R/T: hanya Kepala Dinas, petugas persuratan, dan pihak yang terlibat disposisi.
     * - Biasa/Terbuka: ditambah admin, sekretaris, dan unit tujuan.
     */
    public function scopeVisibleTo($query, User $u)
    {
        if ($u->hasRole('kepala_dinas', 'petugas_persuratan')) {
            return $query;
        }

        return $query->where(function ($w) use ($u) {
            $w->whereHas('disposisi', function ($d) use ($u) {
                $d->where(function ($x) use ($u) {
                    $x->where('kepada_user_id', $u->id)->orWhere('dari_user_id', $u->id);
                });
            });

            if ($u->hasRole('admin', 'sekretaris')) {
                $w->orWhere('klasifikasi', 'B');
            } elseif ($ids = $u->unitIds()) {
                $w->orWhere(function ($x) use ($ids) {
                    $x->where('klasifikasi', 'B')->whereIn('unit_tujuan_id', $ids);
                });
            }
        });
    }
}
