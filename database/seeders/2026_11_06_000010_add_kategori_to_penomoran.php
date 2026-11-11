<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jenis_naskah', function (Blueprint $t) {
            // Surat Dinas & turunannya diawali kategori keamanan pada format baru (mis. B/400.7.7.2/DINKES/104/2027)
            $t->boolean('pakai_kategori')->default(false)->after('pakai_cap');
        });

        Schema::table('nomor_surat', function (Blueprint $t) {
            $t->string('kategori', 2)->nullable()->after('urutan');
            $t->string('kode_klasifikasi', 40)->nullable()->after('kategori');
        });

        DB::table('jenis_naskah')->whereIn('nama', [
            'Surat Dinas', 'Surat Undangan', 'Surat Perjanjian', 'Surat Kuasa', 'Berita Acara',
            'Surat Keterangan', 'Surat Pengantar', 'Pengumuman', 'Laporan',
        ])->update(['pakai_kategori' => true]);
    }

    public function down(): void
    {
        Schema::table('nomor_surat', function (Blueprint $t) {
            $t->dropColumn(['kategori', 'kode_klasifikasi']);
        });
        Schema::table('jenis_naskah', function (Blueprint $t) {
            $t->dropColumn('pakai_kategori');
        });
    }
};
