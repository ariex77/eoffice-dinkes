<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kode klasifikasi arsip kesehatan (400.7.x) — Lampiran Perka No. 1 Tahun 2027, Bab II.A.7
        Schema::create('klasifikasi_arsip', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 40)->unique();
            $t->string('uraian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasifikasi_arsip');
    }
};
