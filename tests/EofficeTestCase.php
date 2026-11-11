<?php

namespace Tests;

use App\Models\JenisNaskah;
use App\Models\SuratMasuk;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

/** Dasar semua tes e-office: database bersih + data awal (unit, jenis naskah, klasifikasi arsip, akun demo). */
abstract class EofficeTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['eoffice.format_nomor' => 'baru', 'eoffice.counter_mode' => 'per_jenis']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function u(string $nama): User
    {
        return User::where('email', $nama.'@dinkes.test')->firstOrFail();
    }

    protected function jenis(string $nama): JenisNaskah
    {
        return JenisNaskah::where('nama', $nama)->firstOrFail();
    }

    protected function unit(string $kode): Unit
    {
        return Unit::where('kode_nomor', $kode)->firstOrFail();
    }

    /** Agendaris mencatat surat masuk lewat aplikasi (HTTP). */
    protected function catatSurat(array $override = []): SuratMasuk
    {
        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-masuk.store'), $override + [
                'tanggal_terima' => today()->toDateString(),
                'tanggal_surat' => today()->toDateString(),
                'nomor_surat' => 'B/123/UNDANGAN/2026',
                'asal_surat' => 'Dinas Kesehatan Provinsi Riau',
                'perihal' => 'Undangan Rapat Koordinasi',
                'klasifikasi' => 'B',
                'sifat' => 'biasa',
                'jumlah_lampiran' => 0,
            ])->assertSessionHasNoErrors();

        return SuratMasuk::latest('id')->firstOrFail();
    }

    /** Memberi disposisi lewat aplikasi (HTTP). */
    protected function disposisi(string $dari, SuratMasuk $surat, array $kepada)
    {
        return $this->actingAs($this->u($dari))
            ->post(route('disposisi.store', $surat), [
                'kepada_user_ids' => array_map(fn ($n) => $this->u($n)->id, $kepada),
                'instruksi' => ['Tindak lanjuti'],
                'sifat' => 'biasa',
            ]);
    }
}
