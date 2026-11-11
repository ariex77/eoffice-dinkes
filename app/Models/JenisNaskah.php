<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisNaskah extends Model
{
    protected $table = 'jenis_naskah';

    protected $fillable = ['nama', 'kelompok', 'pola', 'counter_key', 'counter_per_unit', 'pakai_cap', 'pakai_kategori', 'is_active'];

    protected function casts(): array
    {
        return [
            'counter_per_unit' => 'boolean',
            'pakai_cap' => 'boolean',
            'pakai_kategori' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
