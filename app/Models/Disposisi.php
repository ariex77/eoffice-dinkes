<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    protected $table = 'disposisi';

    public const INSTRUKSI = [
        'Untuk diketahui',
        'Tindak lanjuti',
        'Koordinasikan',
        'Wakili / hadiri',
        'Siapkan bahan / jawaban',
        'Teliti dan laporkan',
        'Proses sesuai ketentuan',
        'Arsipkan',
    ];

    public const STATUS = [
        'baru' => 'Baru',
        'dibaca' => 'Dibaca',
        'diproses' => 'Diproses',
        'diteruskan' => 'Diteruskan',
        'selesai' => 'Selesai',
    ];

    /** Status yang masih menunggu tindakan penerima. */
    public const AKTIF = ['baru', 'dibaca', 'diproses'];

    protected $fillable = [
        'surat_masuk_id', 'parent_id', 'tahap', 'dari_user_id', 'kepada_user_id', 'instruksi', 'catatan',
        'batas_waktu', 'sifat', 'status', 'dibaca_at', 'selesai_at', 'laporan',
    ];

    protected function casts(): array
    {
        return [
            'instruksi' => 'array',
            'batas_waktu' => 'date',
            'dibaca_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function dari()
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepada()
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Disposisi::class, 'parent_id');
    }

    public function tahapLabel(): ?string
    {
        return $this->tahap ? (\App\Services\AlurDisposisi::TAHAP[$this->tahap] ?? $this->tahap) : null;
    }

    public function isAktif(): bool
    {
        return in_array($this->status, self::AKTIF, true);
    }

    public function terlambat(): bool
    {
        return $this->isAktif() && $this->batas_waktu && $this->batas_waktu->isBefore(today());
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'selesai' => 'success',
            'diteruskan' => 'secondary',
            'diproses' => 'info',
            'dibaca' => 'warning',
            default => 'primary',
        };
    }
}
