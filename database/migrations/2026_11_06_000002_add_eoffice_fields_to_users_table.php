<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('nip', 25)->nullable()->unique()->after('name');
            $t->string('jabatan')->nullable()->after('nip');
            $t->string('kode_jabatan', 30)->nullable()->after('jabatan'); // dipakai penomoran Nota Dinas/Memorandum
            $t->string('role', 30)->default('staf')->after('password');
            $t->foreignId('unit_id')->nullable()->after('role')->constrained('units')->nullOnDelete();
            $t->boolean('is_active')->default(true)->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('unit_id');
            $t->dropColumn(['nip', 'jabatan', 'kode_jabatan', 'role', 'is_active']);
        });
    }
};
