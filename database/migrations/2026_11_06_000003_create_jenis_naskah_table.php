<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jenis_naskah', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('kelompok', 20);            // arahan | korespondensi | khusus
            $t->string('pola', 20);                // standar | nomor_tahun | nota
            $t->string('counter_key', 50);         // jenis dengan counter_key sama berbagi urutan nomor
            $t->boolean('counter_per_unit')->default(false);
            $t->boolean('pakai_cap')->default(true); // Nota Dinas & Memorandum tidak dicap
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_naskah');
    }
};
