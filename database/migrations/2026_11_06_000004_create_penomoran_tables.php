<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Penghitung atomik. scope_key contoh: "2026|surat_dinas|ALL" atau "2026|AGENDA_MASUK"
        Schema::create('penomoran_counters', function (Blueprint $t) {
            $t->id();
            $t->string('scope_key', 120)->unique();
            $t->unsignedInteger('last_number')->default(0);
            $t->timestamps();
        });

        Schema::create('nomor_surat', function (Blueprint $t) {
            $t->id();
            $t->foreignId('jenis_naskah_id')->constrained('jenis_naskah');
            $t->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $t->string('counter_scope', 120);
            $t->unsignedSmallInteger('tahun');
            $t->unsignedInteger('urutan');
            $t->string('nomor_lengkap');
            $t->date('tanggal');
            $t->string('perihal')->nullable();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 15)->default('dipesan'); // dipesan | terpakai | batal
            $t->string('alasan_batal')->nullable();
            $t->timestamps();

            $t->unique(['counter_scope', 'urutan']);
            $t->index('nomor_lengkap');
            $t->index(['tahun', 'jenis_naskah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_surat');
        Schema::dropIfExists('penomoran_counters');
    }
};
