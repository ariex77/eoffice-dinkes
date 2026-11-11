<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();
        $now = now();

        $stat = [
            'masuk_bulan_ini' => SuratMasuk::visibleTo($u)
                ->whereYear('tanggal_terima', $now->year)->whereMonth('tanggal_terima', $now->month)->count(),
            'belum_disposisi' => $u->hasRole('kepala_dinas', 'petugas_persuratan')
                ? SuratMasuk::where('status', 'baru')->count() : null,
            'disposisi_aktif' => $u->disposisiMasuk()->whereIn('status', Disposisi::AKTIF)->count(),
            'keluar_bulan_ini' => SuratKeluar::visibleTo($u)
                ->whereYear('tanggal_surat', $now->year)->whereMonth('tanggal_surat', $now->month)->count(),
            'draf_keluar' => SuratKeluar::visibleTo($u)->where('status', 'draft')->count(),
        ];

        $disposisi = $u->disposisiMasuk()->with(['suratMasuk', 'dari'])
            ->whereIn('status', Disposisi::AKTIF)->orderByRaw('batas_waktu is null, batas_waktu')->limit(6)->get();

        $suratBaru = SuratMasuk::visibleTo($u)->orderByDesc('id')->limit(6)->get();

        return view('dashboard', compact('stat', 'disposisi', 'suratBaru'));
    }
}
