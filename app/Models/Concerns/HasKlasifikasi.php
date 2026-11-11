<?php

namespace App\Models\Concerns;

trait HasKlasifikasi
{
    public function klasifikasiColor(): string
    {
        return match ($this->klasifikasi) {
            'SR', 'R' => 'danger',
            'T' => 'dark',
            default => 'secondary',
        };
    }

    public function sifatColor(): string
    {
        return match ($this->sifat) {
            'segera' => 'warning',
            'sangat_segera' => 'danger',
            default => 'light',
        };
    }
}
