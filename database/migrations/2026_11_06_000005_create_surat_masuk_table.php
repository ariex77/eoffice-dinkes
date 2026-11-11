<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surat_masuk', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('tahun');
            $t->unsignedInteger('nomor_agenda');
            $t->date('tanggal_terima');
            $t->date('tanggal_surat');
            $t->string('nomor_surat', 150);
            $t->string('asal_surat', 200);
            $t->string('perihal');
            $t->text('ringkasan')->nullable();
            $t->string('klasifikasi', 2)->default('B'); // SR | R | T | B
            $t->string('sifat', 15)->default('biasa');   // biasa | segera | sangat_segera
            $t->unsignedSmallInteger('jumlah_lampiran')->default(0);
            $t->foreignId('unit_tujuan_id')->nullable()->constrained('units')->nullOnDelete();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->string('status', 15)->default('baru');   // baru | didisposisi | selesai
            $t->foreignId('diterima_oleh')->constrained('users');
            $t->timestamps();

            $t->unique(['tahun', 'nomor_agenda']);
            $t->index('tanggal_terima');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuk');
    }
};
