<?php

namespace App\Http\Controllers;

use App\Models\JenisNaskah;
use App\Models\KlasifikasiArsip;
use App\Models\NomorSurat;
use App\Models\SuratMasuk;
use App\Models\Unit;
use App\Models\User;
use App\Services\PenomoranService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PenomoranController extends Controller
{
    public function __construct(private PenomoranService $penomoran)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $tahun = $request->integer('tahun') ?: now()->year;

        $q = NomorSurat::with(['jenis', 'unit', 'pengambil'])->where('tahun', $tahun)->orderByDesc('id');

        // Pegawai biasa hanya melihat nomor yang ia ambil sendiri (perihal bisa sensitif)
        if (! $u->hasRole('admin', 'petugas_persuratan', 'kepala_dinas')) {
            $q->where('user_id', $u->id);
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('nomor_lengkap', 'like', "%{$s}%")->orWhere('perihal', 'like', "%{$s}%"));
        }
        if ($request->filled('jenis')) {
            $q->where('jenis_naskah_id', $request->query('jenis'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return view('penomoran.index', [
            'items' => $q->paginate(20)->withQueryString(),
            'tahun' => $tahun,
            'jenisList' => JenisNaskah::orderBy('nama')->get(),
            'tahunList' => NomorSurat::query()->distinct()->pluck('tahun')->push(now()->year)->unique()->sortDesc()->values(),
        ]);
    }

    public function create(Request $request)
    {
        return view('penomoran.create', [
            'jenis' => JenisNaskah::where('is_active', true)->orderBy('kelompok')->orderBy('nama')->get(),
            'units' => $request->user()->nomorUnits()->get(),
            'signers' => User::penandatanganList()->get(),
            'klasifikasi' => KlasifikasiArsip::orderBy('kode')->get(),
            'versi' => $this->penomoran->versi(),
        ]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $d = $request->validate([
            'jenis_naskah_id' => ['required', 'exists:jenis_naskah,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'penandatangan_id' => ['nullable', 'exists:users,id'],
            'kode_klasifikasi' => ['nullable', 'exists:klasifikasi_arsip,kode'],
            'kategori' => ['nullable', Rule::in(array_keys(SuratMasuk::KLASIFIKASI))],
            'perihal' => ['nullable', 'string', 'max:255'],
        ]);

        $unit = null;
        if (! empty($d['unit_id'])) {
            if (! $u->nomorUnits()->whereKey($d['unit_id'])->exists()) {
                throw ValidationException::withMessages(['unit_id' => 'Anda tidak berwenang mengambil nomor untuk unit tersebut.']);
            }
            $unit = Unit::find($d['unit_id']);
        }

        $nomor = $this->penomoran->ambil(
            JenisNaskah::findOrFail($d['jenis_naskah_id']),
            $unit,
            $u,
            ! empty($d['penandatangan_id']) ? User::find($d['penandatangan_id']) : null,
            $d['perihal'] ?? null,
            $d['kode_klasifikasi'] ?? null,
            'dipesan',
            $d['kategori'] ?? null,
        );

        return redirect()->route('penomoran.index')
            ->with('success', "Nomor berhasil diambil: {$nomor->nomor_lengkap}");
    }

    public function batal(Request $request, NomorSurat $nomorSurat)
    {
        $u = $request->user();

        abort_unless(
            $nomorSurat->status === 'dipesan' && ($u->hasRole('admin', 'petugas_persuratan') || $nomorSurat->user_id == $u->id),
            403,
            'Hanya nomor berstatus "dipesan" yang dapat dibatalkan.'
        );

        $d = $request->validate(['alasan_batal' => ['required', 'string', 'max:255']]);
        $nomorSurat->update(['status' => 'batal', 'alasan_batal' => $d['alasan_batal']]);

        return back()->with('success', "Nomor {$nomorSurat->nomor_lengkap} dibatalkan. Nomor tidak dipakai ulang.");
    }
}
