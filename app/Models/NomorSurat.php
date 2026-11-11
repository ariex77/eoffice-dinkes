<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorSurat extends Model
{
    protected $table = 'nomor_surat';

    public const STATUS = ['dipesan' => 'Dipesan', 'terpakai' => 'Terpakai', 'batal' => 'Batal'];

    protected $fillable = [
        'jenis_naskah_id', 'unit_id', 'counter_scope', 'tahun', 'urutan', 'kategori', 'kode_klasifikasi', 'nomor_lengkap',
        'tanggal', 'perihal', 'user_id', 'penandatangan_id', 'status', 'alasan_batal',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jenis()
    {
        return $this->belongsTo(JenisNaskah::class, 'jenis_naskah_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function pengambil()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penandatangan()
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    public function suratKeluar()
    {
        return $this->hasOne(SuratKeluar::class);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'terpakai' => 'success',
            'batal' => 'danger',
            default => 'warning',
        };
    }
}
