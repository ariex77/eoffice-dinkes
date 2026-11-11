<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surat_keluar', function (Blueprint $t) {
            $t->id();
            $t->foreignId('nomor_surat_id')->unique()->constrained('nomor_surat');
            $t->foreignId('jenis_naskah_id')->constrained('jenis_naskah');
            $t->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $t->date('tanggal_surat');
            $t->string('tujuan');
            $t->string('perihal');
            $t->text('ringkasan')->nullable();
            $t->string('sifat', 15)->default('biasa');
            $t->unsignedSmallInteger('jumlah_lampiran')->default(0);
            $t->string('klasifikasi', 2)->default('B');
            $t->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('mandat', 5)->nullable();          // an | ub | plt | plh
            $t->string('dasar_mandat')->nullable();       // dasar pelimpahan tertulis (Pasal 69-73)
            $t->text('tembusan')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->string('status', 15)->default('draft');   // draft | terkirim

            // Registrasi pengiriman (Pasal 84 & 86)
            $t->unsignedSmallInteger('tahun_agenda')->nullable();
            $t->unsignedInteger('nomor_agenda')->nullable();
            $t->date('tanggal_kirim')->nullable();
            $t->string('metode_kirim', 15)->nullable();
            $t->json('checklist')->nullable();

            $t->foreignId('dibuat_oleh')->constrained('users');
            $t->foreignId('diregistrasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['tahun_agenda', 'nomor_agenda']);
            $t->index('tanggal_surat');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};
