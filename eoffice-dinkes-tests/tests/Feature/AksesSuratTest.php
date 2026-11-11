<?php

namespace Tests\Feature;

use Tests\EofficeTestCase;

/** Pasal 58: surat SR/R/T hanya untuk Kepala Dinas, agendaris, dan pihak yang terlibat disposisi. */
class AksesSuratTest extends EofficeTestCase
{
    public function test_surat_rahasia_tertutup_bagi_yang_di_luar_rantai_disposisi(): void
    {
        $surat = $this->catatSurat(['klasifikasi' => 'R', 'perihal' => 'Perihal Rahasia Khusus']);

        foreach (['kasubag.keuangan', 'sekretaris', 'staf.hku', 'kabid.p2p', 'admin'] as $nama) {
            $this->actingAs($this->u($nama))->get(route('surat-masuk.show', $surat))->assertForbidden();
        }

        foreach (['kadis', 'persuratan'] as $nama) {
            $this->actingAs($this->u($nama))->get(route('surat-masuk.show', $surat))->assertOk();
        }
    }

    public function test_surat_rahasia_terbuka_bagi_pihak_yang_menerima_disposisi(): void
    {
        $surat = $this->catatSurat(['klasifikasi' => 'R']);

        $this->actingAs($this->u('sekretaris'))->get(route('surat-masuk.show', $surat))->assertForbidden();

        $this->disposisi('kadis', $surat, ['sekretaris']);

        $this->actingAs($this->u('sekretaris'))->get(route('surat-masuk.show', $surat))->assertOk();
        $this->actingAs($this->u('kasubag.keuangan'))->get(route('surat-masuk.show', $surat))->assertForbidden();
    }

    public function test_daftar_surat_menyembunyikan_surat_rahasia(): void
    {
        $this->catatSurat(['klasifikasi' => 'R', 'perihal' => 'Perihal Rahasia Khusus']);
        $this->catatSurat(['klasifikasi' => 'B', 'perihal' => 'Perihal Biasa Terbuka', 'nomor_surat' => 'B/2/2026']);

        $this->actingAs($this->u('sekretaris'))
            ->get(route('surat-masuk.index'))
            ->assertOk()
            ->assertSee('Perihal Biasa Terbuka')
            ->assertDontSee('Perihal Rahasia Khusus');

        $this->actingAs($this->u('kadis'))
            ->get(route('surat-masuk.index'))
            ->assertSee('Perihal Rahasia Khusus')
            ->assertSee('Perihal Biasa Terbuka');
    }

    public function test_surat_biasa_terbuka_bagi_sekretaris_tetapi_tidak_bagi_staf_yang_tidak_terlibat(): void
    {
        $surat = $this->catatSurat(['klasifikasi' => 'B']);

        $this->actingAs($this->u('sekretaris'))->get(route('surat-masuk.show', $surat))->assertOk();
        $this->actingAs($this->u('staf.keuangan'))->get(route('surat-masuk.show', $surat))->assertForbidden();
    }

    public function test_hanya_agendaris_dan_admin_yang_boleh_mencatat_surat_masuk(): void
    {
        foreach (['kadis', 'sekretaris', 'staf.hku', 'kabid.kesmas'] as $nama) {
            $this->actingAs($this->u($nama))->get(route('surat-masuk.create'))->assertForbidden();
        }
        $this->actingAs($this->u('persuratan'))->get(route('surat-masuk.create'))->assertOk();
    }
}
