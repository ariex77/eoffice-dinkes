<?php

namespace App\Http\Controllers;

use App\Models\JenisNaskah;
use App\Models\KlasifikasiArsip;
use App\Models\NomorSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\Unit;
use App\Models\User;
use App\Services\PenomoranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratKeluarController extends Controller
{
    public function __construct(private PenomoranService $penomoran)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $tahun = $request->integer('tahun') ?: now()->year;

        $q = SuratKeluar::with(['nomor', 'jenis', 'unit'])->visibleTo($u)
            ->whereYear('tanggal_surat', $tahun)->orderByDesc('id');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('perihal', 'like', "%{$s}%")->orWhere('tujuan', 'like', "%{$s}%")
                    ->orWhereHas('nomor', fn ($n) => $n->where('nomor_lengkap', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return view('surat-keluar.index', [
            'items' => $q->paginate(15)->withQueryString(),
            'tahun' => $tahun,
            'tahunList' => collect(range(now()->year, max(now()->year - 5, 2024))),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', SuratKeluar::class);
        $u = $request->user();

        return view('surat-keluar.form', [
            'surat' => new SuratKeluar(['tanggal_surat' => today(), 'klasifikasi' => 'B', 'sifat' => 'biasa']),
            'jenis' => JenisNaskah::where('is_active', true)->orderBy('kelompok')->orderBy('nama')->get(),
            'units' => $u->nomorUnits()->get(),
            'signers' => User::penandatanganList()->get(),
            'dipesan' => NomorSurat::with('jenis')->where('user_id', $u->id)->where('status', 'dipesan')->orderBy('id')->get(),
            'klasifikasi' => KlasifikasiArsip::orderBy('kode')->get(),
            'versi' => $this->penomoran->versi(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', SuratKeluar::class);
        $u = $request->user();

        $d = $request->validate($this->rules() + [
            'nomor_surat_id' => ['nullable', 'integer'],
            'jenis_naskah_id' => ['required_without:nomor_surat_id', 'nullable', 'exists:jenis_naskah,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'kode_klasifikasi' => ['nullable', 'exists:klasifikasi_arsip,kode'],
        ]);

        $this->validasiMandat($d);

        if (empty($d['nomor_surat_id']) && ! empty($d['unit_id']) && ! $u->nomorUnits()->whereKey($d['unit_id'])->exists()) {
            throw ValidationException::withMessages(['unit_id' => 'Anda tidak berwenang memakai unit tersebut.']);
        }

        $surat = DB::transaction(function () use ($d, $u) {
            if (! empty($d['nomor_surat_id'])) {
                $nomor = NomorSurat::whereKey($d['nomor_surat_id'])->where('user_id', $u->id)
                    ->where('status', 'dipesan')->lockForUpdate()->first();

                if (! $nomor) {
                    throw ValidationException::withMessages(['nomor_surat_id' => 'Nomor yang dipilih tidak tersedia.']);
                }
                if ($nomor->kategori && $nomor->kategori !== $d['klasifikasi']) {
                    throw ValidationException::withMessages([
                        'klasifikasi' => "Klasifikasi keamanan harus {$nomor->kategori}, sesuai kategori pada nomor {$nomor->nomor_lengkap}.",
                    ]);
                }
                $nomor->update(['status' => 'terpakai']);
            } else {
                $nomor = $this->penomoran->ambil(
                    JenisNaskah::findOrFail($d['jenis_naskah_id']),
                    ! empty($d['unit_id']) ? Unit::find($d['unit_id']) : null,
                    $u,
                    ! empty($d['penandatangan_id']) ? User::find($d['penandatangan_id']) : null,
                    $d['perihal'],
                    $d['kode_klasifikasi'] ?? null,
                    'terpakai',
                    $d['klasifikasi'],
                );
            }

            return SuratKeluar::create($this->fields($d) + [
                'nomor_surat_id' => $nomor->id,
                'jenis_naskah_id' => $nomor->jenis_naskah_id,
                'unit_id' => $nomor->unit_id,
                'status' => 'draft',
                'dibuat_oleh' => $u->id,
            ]);
        });

        $this->simpanBerkas($request, $surat);

        return redirect()->route('surat-keluar.show', $surat)
            ->with('success', "Draf surat dibuat dengan nomor {$surat->nomor->nomor_lengkap}.");
    }

    public function show(SuratKeluar $suratKeluar)
    {
        Gate::authorize('view', $suratKeluar);
        $suratKeluar->load(['nomor', 'jenis', 'unit', 'penandatangan', 'pembuat', 'peregistrasi']);

        return view('surat-keluar.show', ['surat' => $suratKeluar]);
    }

    public function edit(SuratKeluar $suratKeluar)
    {
        Gate::authorize('update', $suratKeluar);

        return view('surat-keluar.form', [
            'surat' => $suratKeluar->load('nomor'),
            'signers' => User::penandatanganList()->get(),
        ]);
    }

    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        Gate::authorize('update', $suratKeluar);
        $d = $request->validate($this->rules());
        $this->validasiMandat($d);

        // Kategori keamanan yang sudah menjadi bagian nomor surat tidak boleh berubah
        if ($suratKeluar->nomor->kategori && $suratKeluar->nomor->kategori !== $d['klasifikasi']) {
            throw ValidationException::withMessages([
                'klasifikasi' => "Klasifikasi keamanan harus {$suratKeluar->nomor->kategori}, sesuai kategori pada nomor surat.",
            ]);
        }

        $suratKeluar->update($this->fields($d));
        $this->simpanBerkas($request, $suratKeluar);

        return redirect()->route('surat-keluar.show', $suratKeluar)->with('success', 'Draf surat diperbarui.');
    }

    /** Membatalkan draf: nomor ditandai batal (tidak dipakai ulang), berkas dihapus. */
    public function destroy(Request $request, SuratKeluar $suratKeluar)
    {
        Gate::authorize('delete', $suratKeluar);

        DB::transaction(function () use ($suratKeluar) {
            $suratKeluar->nomor->update(['status' => 'batal', 'alasan_batal' => 'Draf surat dibatalkan']);
            if ($suratKeluar->file_path) {
                Storage::disk('local')->delete($suratKeluar->file_path);
            }
            $suratKeluar->delete();
        });

        return redirect()->route('surat-keluar.index')->with('success', 'Draf surat dibatalkan. Nomornya tercatat batal dan tidak dipakai ulang.');
    }

    /** Registrasi pengiriman: pemeriksaan kelengkapan (Pasal 84) + buku agenda keluar (Pasal 86). */
    public function registrasi(Request $request, SuratKeluar $suratKeluar)
    {
        Gate::authorize('registrasi', $suratKeluar);
        $suratKeluar->load('jenis');

        if (! $suratKeluar->file_path) {
            return back()->withErrors(['file' => 'Unggah pindaian surat yang sudah ditandatangani terlebih dahulu (pertinggal digital).']);
        }

        $rules = [
            'cek_nomor' => ['accepted'],
            'cek_ttd' => ['accepted'],
            'cek_alamat' => ['accepted'],
            'tanggal_kirim' => ['required', 'date', 'after_or_equal:'.$suratKeluar->tanggal_surat->toDateString()],
            'metode_kirim' => ['required', Rule::in(array_keys(SuratKeluar::METODE))],
        ];
        if ($suratKeluar->jenis->pakai_cap) {
            $rules['cek_cap'] = ['accepted'];
        }
        if ($suratKeluar->jumlah_lampiran > 0) {
            $rules['cek_lampiran'] = ['accepted'];
        }

        $d = $request->validate($rules, [
            'accepted' => 'Pemeriksaan ":attribute" harus dicentang sebelum surat diregistrasi.',
        ], [
            'cek_nomor' => 'nomor naskah dinas',
            'cek_cap' => 'cap dinas',
            'cek_ttd' => 'tanda tangan',
            'cek_alamat' => 'alamat yang dituju',
            'cek_lampiran' => 'lampiran',
        ]);

        $tahun = (int) date('Y', strtotime($d['tanggal_kirim']));

        DB::transaction(function () use ($suratKeluar, $d, $tahun, $request) {
            $suratKeluar->update([
                'tahun_agenda' => $tahun,
                'nomor_agenda' => $this->penomoran->next("{$tahun}|AGENDA_KELUAR"),
                'tanggal_kirim' => $d['tanggal_kirim'],
                'metode_kirim' => $d['metode_kirim'],
                'checklist' => array_map(fn () => true, array_intersect_key($d, array_flip(['cek_nomor', 'cek_cap', 'cek_ttd', 'cek_alamat', 'cek_lampiran']))),
                'status' => 'terkirim',
                'diregistrasi_oleh' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Surat diregistrasi dengan nomor agenda keluar {$suratKeluar->fresh()->agendaLabel()}.");
    }

    public function berkas(Request $request, SuratKeluar $suratKeluar)
    {
        Gate::authorize('view', $suratKeluar);
        abort_unless($suratKeluar->file_path && Storage::disk('local')->exists($suratKeluar->file_path), 404);

        return $request->boolean('unduh')
            ? Storage::disk('local')->download($suratKeluar->file_path, $suratKeluar->file_name)
            : Storage::disk('local')->response($suratKeluar->file_path, $suratKeluar->file_name);
    }

    private function rules(): array
    {
        return [
            'tanggal_surat' => ['required', 'date'],
            'tujuan' => ['required', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:2000'],
            'sifat' => ['required', Rule::in(array_keys(SuratMasuk::SIFAT))],
            'klasifikasi' => ['required', Rule::in(array_keys(SuratMasuk::KLASIFIKASI))],
            'jumlah_lampiran' => ['nullable', 'integer', 'min:0', 'max:999'],
            'penandatangan_id' => ['nullable', 'exists:users,id'],
            'mandat' => ['nullable', Rule::in(array_keys(SuratKeluar::MANDAT))],
            'dasar_mandat' => ['nullable', 'string', 'max:255'],
            'tembusan' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('eoffice.max_upload_kb')],
        ];
    }

    /** Pasal 69-73: pelimpahan wewenang (a.n./u.b./Plt./Plh.) harus tertulis. */
    private function validasiMandat(array $d): void
    {
        if (! empty($d['mandat']) && empty($d['dasar_mandat'])) {
            throw ValidationException::withMessages([
                'dasar_mandat' => 'Isi dasar pelimpahan wewenang tertulis (mis. nomor SK/surat pelimpahan).',
            ]);
        }
    }

    private function fields(array $d): array
    {
        return [
            'tanggal_surat' => $d['tanggal_surat'],
            'tujuan' => $d['tujuan'],
            'perihal' => $d['perihal'],
            'ringkasan' => $d['ringkasan'] ?? null,
            'sifat' => $d['sifat'],
            'klasifikasi' => $d['klasifikasi'],
            'jumlah_lampiran' => (int) ($d['jumlah_lampiran'] ?? 0),
            'penandatangan_id' => $d['penandatangan_id'] ?? null,
            'mandat' => $d['mandat'] ?? null,
            'dasar_mandat' => ! empty($d['mandat']) ? $d['dasar_mandat'] : null,
            'tembusan' => $d['tembusan'] ?? null,
        ];
    }

    private function simpanBerkas(Request $request, SuratKeluar $surat): void
    {
        if (! ($file = $request->file('file'))) {
            return;
        }
        if ($surat->file_path) {
            Storage::disk('local')->delete($surat->file_path);
        }
        $surat->update([
            'file_path' => $file->store('surat-keluar/'.$surat->tanggal_surat->year, 'local'),
            'file_name' => $file->getClientOriginalName(),
        ]);
    }
}
