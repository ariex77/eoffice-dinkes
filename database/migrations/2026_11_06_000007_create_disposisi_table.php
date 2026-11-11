<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('surat_masuk_id')->constrained('surat_masuk')->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('disposisi')->nullOnDelete();
            $t->foreignId('dari_user_id')->constrained('users');
            $t->foreignId('kepada_user_id')->constrained('users');
            $t->json('instruksi')->nullable();
            $t->text('catatan')->nullable();
            $t->date('batas_waktu')->nullable();
            $t->string('sifat', 15)->default('biasa');
            $t->string('status', 15)->default('baru'); // baru | dibaca | diproses | diteruskan | selesai
            $t->timestamp('dibaca_at')->nullable();
            $t->timestamp('selesai_at')->nullable();
            $t->text('laporan')->nullable();
            $t->timestamps();

            $t->index(['kepada_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
