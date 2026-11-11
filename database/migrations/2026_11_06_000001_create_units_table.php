<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete();
            $t->string('kode_nomor', 30)->nullable()->unique(); // SET-1, KESMAS-1, dst.
            $t->string('nama');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
