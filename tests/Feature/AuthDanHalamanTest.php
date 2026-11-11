<?php

namespace Tests\Feature;

use Tests\EofficeTestCase;

class AuthDanHalamanTest extends EofficeTestCase
{
    private const AKUN = [
        'admin', 'persuratan', 'kadis', 'sekretaris', 'kasubag.hku', 'kasubag.keuangan',
        'kabid.kesmas', 'kabid.p2p', 'staf.hku', 'staf.keuangan', 'fungsional.kesmas', 'fungsional.p2p',
    ];

    public function test_login_berhasil_dan_diarahkan_ke_beranda(): void
    {
        $this->post(route('login.attempt'), ['email' => 'persuratan@dinkes.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_gagal_dengan_kata_sandi_salah(): void
    {
        $this->post(route('login.attempt'), ['email' => 'persuratan@dinkes.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->u('staf.hku')->update(['is_active' => false]);

        $this->post(route('login.attempt'), ['email' => 'staf.hku@dinkes.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_halaman_surat_wajib_login(): void
    {
        foreach (['surat-masuk.index', 'surat-keluar.index', 'penomoran.index', 'disposisi.index'] as $rute) {
            $this->get(route($rute))->assertRedirect('/login');
        }
    }

    public function test_hanya_admin_yang_mengakses_manajemen_pengguna(): void
    {
        $this->actingAs($this->u('admin'))->get(route('users.index'))->assertOk();
        $this->actingAs($this->u('admin'))->get(route('users.create'))->assertOk();

        foreach (['kadis', 'sekretaris', 'persuratan', 'staf.hku'] as $nama) {
            $this->actingAs($this->u($nama))->get(route('users.index'))->assertForbidden();
        }
    }

    public function test_admin_dapat_menambah_pengguna(): void
    {
        $this->actingAs($this->u('admin'))->post(route('users.store'), [
            'name' => 'Pegawai Baru', 'email' => 'baru@dinkes.test', 'password' => 'rahasia123',
            'role' => 'staf', 'unit_id' => $this->unit('SET-2')->id, 'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $this->post('/logout');
        $this->post(route('login.attempt'), ['email' => 'baru@dinkes.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));
    }

    /** Semua halaman utama dapat dibuka oleh setiap peran tanpa error tampilan. */
    public function test_semua_halaman_utama_terbuka_untuk_setiap_peran(): void
    {
        $rute = ['dashboard', 'surat-masuk.index', 'surat-keluar.index', 'surat-keluar.create',
            'penomoran.index', 'penomoran.create', 'disposisi.index'];

        foreach (self::AKUN as $nama) {
            $this->actingAs($this->u($nama));
            foreach ($rute as $r) {
                $this->get(route($r))->assertOk();
            }
        }
    }

    public function test_dashboard_menampilkan_disposisi_yang_menunggu(): void
    {
        $this->catatSurat(['perihal' => 'Undangan Rakor Imunisasi']);

        $this->actingAs($this->u('kadis'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Undangan Rakor Imunisasi');
    }
}
