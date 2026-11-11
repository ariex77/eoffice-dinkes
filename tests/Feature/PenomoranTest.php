<?php

namespace Tests\Feature;

use App\Models\NomorSurat;
use App\Services\PenomoranService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\EofficeTestCase;

class PenomoranTest extends EofficeTestCase
{
    private function svc(): PenomoranService
    {
        return app(PenomoranService::class);
    }

    private function romawi(): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month];
    }

    public function test_format_baru_keputusan_sesuai_contoh_perka(): void
    {
        $n = $this->svc()->ambil($this->jenis('Keputusan Kepala Dinas'), null, $this->u('staf.hku'), null, 'SK', '400.7.7.2');

        $this->assertSame('400.7.7.2/DINKES/1/'.now()->year, $n->nomor_lengkap);
        $this->assertNull($n->kategori);
    }

    public function test_format_baru_surat_dinas_diawali_kategori_keamanan(): void
    {
        $b = $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, 'x', '400.7.7.2', 'dipesan', 'B');
        $t = $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, 'y', '400.7.2.1', 'dipesan', 'T');

        $this->assertSame('B/400.7.7.2/DINKES/1/'.now()->year, $b->nomor_lengkap);
        $this->assertSame('T/400.7.2.1/DINKES/2/'.now()->year, $t->nomor_lengkap);
    }

    public function test_surat_dinas_tanpa_kategori_dianggap_biasa(): void
    {
        $n = $this->svc()->ambil($this->jenis('Surat Undangan'), null, $this->u('staf.hku'), null, 'x', '400.7.7.2');

        $this->assertStringStartsWith('B/400.7.7.2/DINKES/', $n->nomor_lengkap);
    }

    public function test_peraturan_instruksi_surat_edaran_memakai_nomor_tahun(): void
    {
        $a = $this->svc()->ambil($this->jenis('Peraturan Kepala Dinas'), null, $this->u('admin'));
        $b = $this->svc()->ambil($this->jenis('Peraturan Kepala Dinas'), null, $this->u('admin'));

        $this->assertSame('1 Tahun '.now()->year, $a->nomor_lengkap);
        $this->assertSame('2 Tahun '.now()->year, $b->nomor_lengkap);
    }

    public function test_nota_dinas_memuat_kode_jabatan_klasifikasi_bulan_dan_tahun(): void
    {
        $n = $this->svc()->ambil(
            $this->jenis('Nota Dinas'), null, $this->u('staf.hku'), $this->u('kasubag.hku'), 'Nota', '400.7.7.2'
        );

        $this->assertSame('1/KASUBAG-HKU/400.7.7.2/'.$this->romawi().'/'.now()->year, $n->nomor_lengkap);
    }

    public function test_format_lama_memakai_kode_unit(): void
    {
        config(['eoffice.format_nomor' => 'lama']);

        $n = $this->svc()->ambil($this->jenis('Keputusan Kepala Dinas'), $this->unit('SET-1'), $this->u('staf.hku'));

        $this->assertSame('440/DINKES/SET-1/'.now()->year.'/1', $n->nomor_lengkap);
    }

    public function test_format_lama_wajib_unit_berkode(): void
    {
        config(['eoffice.format_nomor' => 'lama']);

        $this->expectException(ValidationException::class);
        $this->svc()->ambil($this->jenis('Keputusan Kepala Dinas'), null, $this->u('staf.hku'));
    }

    public function test_format_baru_wajib_kode_klasifikasi_valid(): void
    {
        $this->expectException(ValidationException::class);
        $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, 'x', '999.9.9');
    }

    public function test_nota_dinas_wajib_penandatangan_berkode_jabatan(): void
    {
        $this->expectException(ValidationException::class);
        $this->svc()->ambil($this->jenis('Nota Dinas'), null, $this->u('staf.hku'), null, 'x', '400.7.7.2');
    }

    public function test_versi_format_mengikuti_tanggal_berlaku_perka_2027(): void
    {
        config(['eoffice.format_nomor' => 'auto']);

        $this->assertSame('lama', $this->svc()->versi('2026-12-31'));
        $this->assertSame('lama', $this->svc()->versi('2027-01-01'));
        $this->assertSame('baru', $this->svc()->versi('2027-01-02'));
        $this->assertSame('baru', $this->svc()->versi('2027-06-15'));
    }

    public function test_urutan_nomor_bertambah_tanpa_kembar(): void
    {
        $nomor = [];
        foreach (range(1, 5) as $i) {
            $nomor[] = $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, "s{$i}", '400.7.7.2')->urutan;
        }

        $this->assertSame([1, 2, 3, 4, 5], $nomor);
    }

    public function test_mode_per_jenis_urutan_terpisah(): void
    {
        $a = $this->svc()->ambil($this->jenis('Keputusan Kepala Dinas'), null, $this->u('admin'), null, null, '400.7.7.2');
        $b = $this->svc()->ambil($this->jenis('Standar Prosedur Operasional (SPO)'), null, $this->u('admin'), null, null, '400.7.7.2');

        $this->assertSame(1, $a->urutan);
        $this->assertSame(1, $b->urutan);
    }

    public function test_mode_satu_urutan_berbagi_nomor_se_dinas(): void
    {
        config(['eoffice.counter_mode' => 'satu_urutan']);

        $a = $this->svc()->ambil($this->jenis('Keputusan Kepala Dinas'), null, $this->u('admin'), null, null, '400.7.7.2');
        $b = $this->svc()->ambil($this->jenis('Standar Prosedur Operasional (SPO)'), null, $this->u('admin'), null, null, '400.7.7.2');

        $this->assertSame([1, 2], [$a->urutan, $b->urutan]);
        $this->assertNotSame($a->nomor_lengkap, $b->nomor_lengkap);
    }

    public function test_urutan_kembali_ke_satu_di_tahun_baru(): void
    {
        Carbon::setTestNow('2026-12-31 10:00:00');
        $lama = $this->svc()->ambil($this->jenis('Peraturan Kepala Dinas'), null, $this->u('admin'));
        $lama2 = $this->svc()->ambil($this->jenis('Peraturan Kepala Dinas'), null, $this->u('admin'));

        Carbon::setTestNow('2027-01-05 09:00:00');
        $baru = $this->svc()->ambil($this->jenis('Peraturan Kepala Dinas'), null, $this->u('admin'));

        $this->assertSame('2 Tahun 2026', $lama2->nomor_lengkap);
        $this->assertSame('1 Tahun 2027', $baru->nomor_lengkap);
        $this->assertSame('1 Tahun 2026', $lama->nomor_lengkap);
    }

    public function test_nomor_batal_tidak_dipakai_ulang(): void
    {
        $a = $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, 'x', '400.7.7.2');

        $this->actingAs($this->u('staf.hku'))
            ->post(route('penomoran.batal', $a), ['alasan_batal' => 'Salah ketik'])
            ->assertSessionHasNoErrors();

        $b = $this->svc()->ambil($this->jenis('Surat Dinas'), null, $this->u('staf.hku'), null, 'y', '400.7.7.2');

        $this->assertSame('batal', $a->fresh()->status);
        $this->assertSame(2, $b->urutan);
    }

    public function test_ambil_nomor_lewat_halaman_web(): void
    {
        $this->actingAs($this->u('staf.hku'))
            ->post(route('penomoran.store'), [
                'jenis_naskah_id' => $this->jenis('Surat Dinas')->id,
                'kode_klasifikasi' => '400.7.7.2',
                'kategori' => 'B',
                'perihal' => 'Permohonan data imunisasi',
            ])
            ->assertRedirect(route('penomoran.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, NomorSurat::count());
        $this->assertSame('B/400.7.7.2/DINKES/1/'.now()->year, NomorSurat::first()->nomor_lengkap);
    }

    public function test_kode_klasifikasi_arsip_termuat_lengkap(): void
    {
        $this->assertSame(165, \App\Models\KlasifikasiArsip::count());
        $this->assertSame('Imunisasi', \App\Models\KlasifikasiArsip::where('kode', '400.7.7.2')->value('uraian'));
    }
}
