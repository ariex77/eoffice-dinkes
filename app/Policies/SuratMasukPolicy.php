<?php

namespace App\Policies;

use App\Models\SuratMasuk;
use App\Models\User;
use App\Services\AlurDisposisi;

class SuratMasukPolicy
{
    public function view(User $user, SuratMasuk $surat): bool
    {
        return $user->is_active && SuratMasuk::query()->visibleTo($user)->whereKey($surat->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole('admin', 'petugas_persuratan');
    }

    public function update(User $user, SuratMasuk $surat): bool
    {
        return $this->create($user);
    }

    public function disposisi(User $user, SuratMasuk $surat): bool
    {
        return $user->is_active
            && $this->view($user, $surat)
            && app(AlurDisposisi::class)->langkah($user, $surat) !== null;
    }
}
