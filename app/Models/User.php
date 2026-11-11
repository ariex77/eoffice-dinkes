<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = [
        'admin' => 'Administrator',
        'petugas_persuratan' => 'Pengadministrasi Persuratan',
        'kepala_dinas' => 'Kepala Dinas',
        'sekretaris' => 'Sekretaris',
        'kepala_bidang' => 'Kepala Bidang',
        'kepala_seksi' => 'Kepala Subbagian / UPT',
        'fungsional' => 'Pejabat Fungsional',
        'staf' => 'Staf',
    ];

    /** Peran yang boleh menjadi penandatangan naskah dinas. */
    public const PENANDATANGAN_ROLES = ['kepala_dinas', 'sekretaris', 'kepala_bidang', 'kepala_seksi'];

    protected $fillable = [
        'name', 'nip', 'jabatan', 'kode_jabatan', 'email', 'password', 'role', 'unit_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function disposisiMasuk()
    {
        return $this->hasMany(Disposisi::class, 'kepada_user_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function canDisposisi(): bool
    {
        return $this->hasRole('kepala_dinas', 'sekretaris', 'kepala_bidang', 'kepala_seksi');
    }

    /** ID unit milik user beserta seluruh unit di bawahnya. */
    public function unitIds(): array
    {
        return $this->unit?->descendantIds() ?? [];
    }

    /** Pegawai yang boleh menerima disposisi dari user ini (unit sendiri + turunannya). */
    public function disposisiTargets()
    {
        return static::query()
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->whereIn('unit_id', $this->unitIds());
    }

    /** Unit ber-kode yang boleh dipakai user ini saat mengambil nomor surat. */
    public function nomorUnits()
    {
        $q = Unit::query()->whereNotNull('kode_nomor')->where('is_active', true)->orderBy('kode_nomor');

        if ($this->hasRole('admin', 'petugas_persuratan', 'kepala_dinas')) {
            return $q;
        }

        return $q->whereIn('id', $this->unitIds());
    }

    public static function penandatanganList()
    {
        return static::query()
            ->where('is_active', true)
            ->whereIn('role', self::PENANDATANGAN_ROLES)
            ->orderBy('name');
    }
}
