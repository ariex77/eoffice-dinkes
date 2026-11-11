<?php

namespace Tests\Feature;

use App\Models\NomorSurat;
use App\Models\SuratKeluar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\EofficeTestCase;

class SuratKeluarTest extends EofficeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function buatDraf(array $override = [], string $oleh = 'staf.hku'): SuratKeluar
    {
        $this->actingAs($this->u($oleh))
            ->post(route('surat-keluar.store'), $override + [
                'jenis_naskah_id' => $this->jenis('Surat Dinas')->id,
                'kode_klasifikasi' => '400.7.7.2',
                'tanggal_surat' => today()->toDateString(),
                'tujuan' => 'Kepala Puskesmas Bangkinang',
                'perihal' => 'Permintaan data imunisasi',
                'klasifikasi' => 'B',
                'sifat' => 'biasa',
                'jumlah_lampiran' => 0,
            ])->assertSessionHasNoErrors();

        return SuratKeluar::latest('id')->firstOrFail();
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf');
    }

    private array $lengkap = [
        'cek_nomor' => 1, 'cek_cap' => 1, 'cek_ttd' => 1, 'cek_alamat' => 1, 'metode_kirim' => 'kurir',
    ];

    public function test_draf_surat_dinas_mendapat_nomor_format_baru(): void
    {
        $s = $this->buatDraf();

        $this->assertSame('B/400.7.7.2/DINKES/1/'.now()->year, $s->nomor->nomor_lengkap);
        $this->assertSame('B', $s->nomor->kategori);
        $this->assertSame('draft', $s->status);
        $this->assertSame('terpakai', $s->nomor->status);
    }

    public function test_registrasi_ditolak_bila_pindaian_belum_diunggah(): void
    {
        $s = $this->buatDraf();

        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-keluar.registrasi', $s), $this->lengkap + ['tanggal_kirim' => today()->toDateString()])
            ->assertSessionHasErrors('file');

        $this->assertSame('draft', $s->fresh()->status);
    }

    public function test_registrasi_ditolak_bila_checklist_tidak_lengkap(): void
    {
        $s = $this->buatDraf(['file' => $this->pdf()]);
        $data = $this->lengkap + ['tanggal_kirim' => today()->toDateString()];
        unset($data['cek_ttd']);

        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-keluar.registrasi', $s), $data)
            ->assertSessionHasErrors('cek_ttd');

        $this->assertSame('draft', $s->fresh()->status);
    }

    public function test_registrasi_berhasil_mencatat_agenda_keluar(): void
    {
        $s = $this->buatDraf(['file' => $this->pdf()]);

        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-keluar.registrasi', $s), $this->lengkap + ['tanggal_kirim' => today()->toDateString()])
            ->assertSessionHasNoErrors();

        $s->refresh();
        $this->assertSame('terkirim', $s->status);
        $this->assertSame(1, $s->nomor_agenda);
        $this->assertSame($this->u('persuratan')->id, $s->diregistrasi_oleh);
        $this->assertTrue($s->checklist['cek_cap']);
    }

    public function test_nota_dinas_tidak_perlu_cap(): void
    {
        $s = $this->buatDraf([
            'jenis_naskah_id' => $this->jenis('Nota Dinas')->id,
            'penandatangan_id' => $this->u('kasubag.hku')->id,
            'file' => $this->pdf(),
        ]);

        $data = $this->lengkap + ['tanggal_kirim' => today()->toDateString()];
        unset($data['cek_cap']);

        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-keluar.registrasi', $s), $data)
            ->assertSessionHasNoErrors();

        $this->assertSame('terkirim', $s->fresh()->status);
    }

    public function test_lampiran_wajib_diperiksa_bila_ada(): void
    {
        $s = $this->buatDraf(['jumlah_lampiran' => 2, 'file' => $this->pdf()]);

        $this->actingAs($this->u('persuratan'))
            ->post(route('surat-keluar.registrasi', $s), $this->lengkap + ['tanggal_kirim' => today()->toDateString()])
            ->assertSessionHasErrors('cek_lampiran');
    }

    public function test_staf_biasa_tidak_boleh_meregistrasi(): void
    {
        $s = $this->buatDraf(['file' => $this->pdf()]);

        $this->actingAs($this->u('staf.hku'))
            ->post(route('surat-keluar.registrasi', $s), $this->lengkap + ['tanggal_kirim' => today()->toDateString()])
            ->assertForbidden();
    }

    public function test_kategori_keamanan_pada_nomor_tidak_boleh_diubah(): void
    {
        $s = $this->buatDraf();

        $this->actingAs($this->u('staf.hku'))
            ->put(route('surat-keluar.update', $s), [
                'tanggal_surat' => today()->toDateString(),
                'tujuan' => 'Kepala Puskesmas Bangkinang',
                'perihal' => 'Permintaan data imunisasi',
                'klasifikasi' => 'R',
                'sifat' => 'biasa',
            ])->assertSessionHasErrors('klasifikasi');

        $this->assertSame('B', $s->fresh()->klasifikasi);
    }

    public function test_pelimpahan_wewenang_wajib_dasar_tertulis(): void
    {
        $this->actingAs($this->u('staf.hku'))
            ->post(route('surat-keluar.store'), [
                'jenis_naskah_id' => $this->jenis('Surat Dinas')->id,
                'kode_klasifikasi' => '400.7.7.2',
                'tanggal_surat' => today()->toDateString(),
                'tujuan' => 'X', 'perihal' => 'Y', 'klasifikasi' => 'B', 'sifat' => 'biasa',
                'mandat' => 'plh',
            ])->assertSessionHasErrors('dasar_mandat');

        $this->assertSame(0, SuratKeluar::count());
    }

    public function test_membatalkan_draf_menandai_nomor_batal_dan_tidak_dipakai_ulang(): void
    {
        $s = $this->buatDraf();
        $nomorId = $s->nomor_surat_id;

        $this->actingAs($this->u('staf.hku'))->delete(route('surat-keluar.destroy', $s))->assertRedirect(route('surat-keluar.index'));

        $this->assertSame(0, SuratKeluar::count());
        $this->assertSame('batal', NomorSurat::find($nomorId)->status);

        $berikut = $this->buatDraf();
        $this->assertSame(2, $berikut->nomor->urutan);
    }

    public function test_memakai_nomor_yang_sudah_dipesan(): void
    {
        $this->actingAs($this->u('staf.hku'))->post(route('penomoran.store'), [
            'jenis_naskah_id' => $this->jenis('Surat Dinas')->id,
            'kode_klasifikasi' => '400.7.7.2',
            'kategori' => 'T',
            'perihal' => 'Dipesan dulu',
        ])->assertSessionHasNoErrors();

        $nomor = NomorSurat::firstOrFail();
        $this->assertSame('dipesan', $nomor->status);

        $dasar = [
            'nomor_surat_id' => $nomor->id,
            'tanggal_surat' => today()->toDateString(),
            'tujuan' => 'X', 'perihal' => 'Y', 'sifat' => 'biasa',
        ];

        // kategori tidak sama dengan nomor -> ditolak
        $this->actingAs($this->u('staf.hku'))->post(route('surat-keluar.store'), $dasar + ['klasifikasi' => 'B'])
            ->assertSessionHasErrors('klasifikasi');
        $this->assertSame('dipesan', $nomor->fresh()->status);

        // kategori sama -> berhasil
        $this->actingAs($this->u('staf.hku'))->post(route('surat-keluar.store'), $dasar + ['klasifikasi' => 'T'])
            ->assertSessionHasNoErrors();

        $this->assertSame('terpakai', $nomor->fresh()->status);
        $this->assertSame($nomor->id, SuratKeluar::firstOrFail()->nomor_surat_id);
    }

    public function test_nomor_pesanan_orang_lain_tidak_bisa_dipakai(): void
    {
        $this->actingAs($this->u('staf.hku'))->post(route('penomoran.store'), [
            'jenis_naskah_id' => $this->jenis('Surat Dinas')->id,
            'kode_klasifikasi' => '400.7.7.2',
            'kategori' => 'B',
        ]);
        $nomor = NomorSurat::firstOrFail();

        $this->actingAs($this->u('staf.keuangan'))->post(route('surat-keluar.store'), [
            'nomor_surat_id' => $nomor->id,
            'tanggal_surat' => today()->toDateString(),
            'tujuan' => 'X', 'perihal' => 'Y', 'klasifikasi' => 'B', 'sifat' => 'biasa',
        ])->assertSessionHasErrors('nomor_surat_id');
    }

    public function test_draf_hanya_terlihat_oleh_pihak_berwenang(): void
    {
        $s = $this->buatDraf();

        $this->actingAs($this->u('staf.keuangan'))->get(route('surat-keluar.show', $s))->assertForbidden();
        $this->actingAs($this->u('staf.hku'))->get(route('surat-keluar.show', $s))->assertOk();
        $this->actingAs($this->u('persuratan'))->get(route('surat-keluar.show', $s))->assertOk();
    }

    public function test_pindaian_dapat_diunduh_pihak_berwenang_saja(): void
    {
        $s = $this->buatDraf(['file' => $this->pdf()]);

        $this->actingAs($this->u('staf.hku'))->get(route('surat-keluar.berkas', [$s, 'unduh' => 1]))->assertOk();
        $this->actingAs($this->u('staf.keuangan'))->get(route('surat-keluar.berkas', $s))->assertForbidden();
    }
}
