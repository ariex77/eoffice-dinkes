<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KlasifikasiArsip extends Model
{
    protected $table = 'klasifikasi_arsip';

    public $timestamps = false;

    protected $fillable = ['kode', 'uraian'];
}
