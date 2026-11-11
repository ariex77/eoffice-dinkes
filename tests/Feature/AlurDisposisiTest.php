<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use Tests\EofficeTestCase;

/** Alur: Agendaris -> Kepala Dinas -> Sekretaris -> Kasubbag HKU/Keuangan atau Kabid -> Fungsional/Staf. */
class AlurDisposisiTest extends EofficeTestCase
{
    public function test_surat_yang_dicatat_agendaris_langsung_ke_kepala_dinas(): void
    {
        $surat = $this->catatSurat();

        $d = Disposisi::where('surat_masuk_id', $surat->id)->get();
        $this->assertCount(1, $d);
        $this->assertSame($this->u('kadis')->id, $d[0]->kepada_user_id);
        $this->assertSame('pimpinan', $d[0]->tahap);
        $this->assertSame('didisposisi', $surat->fresh()->status);
        $this->assertSame(1, $surat->nomor_agenda);
    }

    public function test_nomor_agenda_surat_masuk_berurutan(): void
    {
        $a = $this->catatSurat();
        $b = $this->catatSurat(['nomor_surat' => 'B/124/2026']);

        $this->assertSame([1, 2], [$a->nomor_agenda, $b->nomor_agenda]);
    }

    public function test_kepala_dinas_hanya_boleh_meneruskan_ke_sekretaris(): void
    {
        $surat = $this->catatSurat();

        $this->disposisi('kadis', $surat, ['kabid.kesmas'])->assertSessionHasErrors('kepada_user_ids.0');
        $this->disposisi('kadis', $surat, ['kasubag.hku'])->assertSessionHasErrors('kepada_user_ids.0');
        $this->assertSame(1, Disposisi::where('surat_masuk_id', $surat->id)->count());

        $this->disposisi('kadis', $surat, ['sekretaris'])->assertSessionHasNoErrors();

        $baru = Disposisi::where('surat_masuk_id', $surat->id)->where('kepada_user_id', $this->u('sekretaris')->id)->first();
        $this->assertSame('filtrasi', $baru->tahap);
        $this->assertSame('diteruskan', Disposisi::where('tahap', 'pimpinan')->first()->status);
    }

    public function test_sekretaris_menyaring_ke_kasubbag_dan_kabid_sekaligus(): void
    {
        $surat = $this->catatSurat();
        $this->disposisi('kadis', $surat, ['sekretaris']);

        // staf tidak boleh jadi tujuan langsung dari Sekretaris
        $this->disposisi('sekretaris', $surat, ['staf.hku'])->assertSessionHasErrors('kepada_user_ids.0');

        $this->disposisi('sekretaris', $surat, ['kasubag.hku', 'kasubag.keuangan', 'kabid.kesmas', 'kabid.p2p'])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, Disposisi::where('tahap', 'kerja')->count());
    }

    public function test_kasubbag_dan_kabid_hanya_menugaskan_staf_di_unitnya(): void
    {
        $surat = $this->catatSurat();
        $this->disposisi('kadis', $surat, ['sekretaris']);
        $this->disposisi('sekretaris', $surat, ['kasubag.hku', 'kabid.kesmas']);

        // Kasubbag HKU tidak boleh menugaskan staf unit lain
        $this->disposisi('kasubag.hku', $surat, ['staf.keuangan'])->assertSessionHasErrors('kepada_user_ids.0');
        $this->disposisi('kasubag.hku', $surat, ['staf.hku'])->assertSessionHasNoErrors();

        // Kabid Kesmas tidak boleh menugaskan fungsional bidang P2P
        $this->disposisi('kabid.kesmas', $surat, ['fungsional.p2p'])->assertSessionHasErrors('kepada_user_ids.0');
        $this->disposisi('kabid.kesmas', $surat, ['fungsional.kesmas'])->assertSessionHasNoErrors();

        $this->assertSame(2, Disposisi::where('tahap', 'pelaksana')->count());
    }

    public function test_alur_penuh_hingga_penyelesaian_disposisi(): void
    {
        $surat = $this->catatSurat();
        $this->disposisi('kadis', $surat, ['sekretaris']);
        $this->disposisi('sekretaris', $surat, ['kasubag.hku', 'kabid.kesmas']);
        $this->disposisi('kasubag.hku', $surat, ['staf.hku']);
        $this->disposisi('kabid.kesmas', $surat, ['fungsional.kesmas']);

        $d1 = Disposisi::where('kepada_user_id', $this->u('staf.hku')->id)->firstOrFail();
        $d2 = Disposisi::where('kepada_user_id', $this->u('fungsional.kesmas')->id)->firstOrFail();

        $this->actingAs($this->u('staf.hku'))
            ->post(route('disposisi.selesai', $d1), ['laporan' => 'Draf balasan sudah dibuat.'])
            ->assertSessionHasNoErrors();

        // satu cabang masih berjalan -> surat belum selesai
        $this->assertSame('didisposisi', $surat->fresh()->status);

        $this->actingAs($this->u('fungsional.kesmas'))
            ->post(route('disposisi.selesai', $d2), ['laporan' => 'Laporan kegiatan disusun.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('selesai', $surat->fresh()->status);
        $this->assertSame('selesai', $d1->fresh()->status);
        $this->assertNotNull($d1->fresh()->selesai_at);
    }

    public function test_pejabat_perantara_tidak_boleh_menyelesaikan_disposisi(): void
    {
        $surat = $this->catatSurat();
        $kadisDisposisi = Disposisi::where('surat_masuk_id', $surat->id)->firstOrFail();

        $this->actingAs($this->u('kadis'))
            ->post(route('disposisi.selesai', $kadisDisposisi), ['laporan' => 'Selesai'])
            ->assertStatus(422);

        $this->assertSame('baru', $kadisDisposisi->fresh()->status);
    }

    public function test_orang_lain_tidak_boleh_menyelesaikan_disposisi_milik_pelaksana(): void
    {
        $surat = $this->catatSurat();
        $this->disposisi('kadis', $surat, ['sekretaris']);
        $this->disposisi('sekretaris', $surat, ['kasubag.hku']);
        $this->disposisi('kasubag.hku', $surat, ['staf.hku']);
        $d = Disposisi::where('kepada_user_id', $this->u('staf.hku')->id)->firstOrFail();

        $this->actingAs($this->u('staf.keuangan'))
            ->post(route('disposisi.selesai', $d), ['laporan' => 'x'])
            ->assertForbidden();
    }

    public function test_pegawai_di_luar_tahapnya_tidak_dapat_membuat_disposisi(): void
    {
        $surat = $this->catatSurat();

        // Sekretaris belum menerima apa pun dari Kepala Dinas
        $this->disposisi('sekretaris', $surat, ['kasubag.hku'])->assertForbidden();
        // Staf tidak punya peran disposisi
        $this->disposisi('staf.hku', $surat, ['staf.keuangan'])->assertForbidden();
    }

    public function test_penerima_membuka_surat_status_menjadi_dibaca(): void
    {
        $surat = $this->catatSurat();

        $this->actingAs($this->u('kadis'))->get(route('surat-masuk.show', $surat))->assertOk();

        $d = Disposisi::where('kepada_user_id', $this->u('kadis')->id)->firstOrFail();
        $this->assertSame('dibaca', $d->status);
        $this->assertNotNull($d->dibaca_at);
    }

    public function test_instruksi_dan_penerima_wajib_diisi(): void
    {
        $surat = $this->catatSurat();

        $this->actingAs($this->u('kadis'))
            ->post(route('disposisi.store', $surat), ['sifat' => 'biasa'])
            ->assertSessionHasErrors(['kepada_user_ids', 'instruksi']);
    }
}
