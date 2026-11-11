<?php

namespace App\Policies;

use App\Models\SuratKeluar;
use App\Models\User;

class SuratKeluarPolicy
{
    public function view(User $user, SuratKeluar $surat): bool
    {
        return $user->is_active && SuratKeluar::query()->visibleTo($user)->whereKey($surat->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /** Draf hanya bisa diubah/dibatalkan oleh pembuatnya atau petugas persuratan. */
    public function update(User $user, SuratKeluar $surat): bool
    {
        return $surat->status === 'draft'
            && ($user->hasRole('admin', 'petugas_persuratan') || $surat->dibuat_oleh == $user->id);
    }

    public function delete(User $user, SuratKeluar $surat): bool
    {
        return $this->update($user, $surat);
    }

    public function registrasi(User $user, SuratKeluar $surat): bool
    {
        return $surat->status === 'draft' && $user->hasRole('admin', 'petugas_persuratan');
    }
}
