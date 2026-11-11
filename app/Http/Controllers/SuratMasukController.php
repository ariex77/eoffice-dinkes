<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\Unit;
use App\Services\AlurDisposisi;
use App\Services\PenomoranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratMasukController extends Controller
{
    public function __construct(private PenomoranService $penomoran, private AlurDisposisi $alur)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $tahun = $request->integer('tahun') ?: now()->year;

        $q = SuratMasuk::with('unitTujuan')->visibleTo($u)->where('tahun', $tahun)
            ->orderByDesc('nomor_agenda');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('perihal', 'like', "%{$s}%")
                    ->orWhere('nomor_surat', 'like', "%{$s}%")
                    ->orWhere('asal_surat', 'like', "%{$s}%");
            });
        }
        if ($request->filled('klasifikasi')) {
            $q->where('klasifikasi', $request->query('klasifikasi'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return view('surat-masuk.index', [
            'items' => $q->paginate(15)->withQueryString(),
            'tahun' => $tahun,
            'tahunList' => SuratMasuk::query()->distinct()->pluck('tahun')->push(now()->year)->unique()->sortDesc()->values(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', SuratMasuk::class);

        return view('surat-masuk.form', [
            'surat' => new SuratMasuk(['tanggal_terima' => today(), 'klasifikasi' => 'B', 'sifat' => 'biasa']),
            'units' => Unit::where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', SuratMasuk::class);
        $data = $this->clean($request->validate($this->rules()));

        $tahun = (int) date('Y', strtotime($data['tanggal_terima']));

        $surat = DB::transaction(function () use ($data, $tahun, $request) {
            return SuratMasuk::create($data + [
                'tahun' => $tahun,
                'nomor_agenda' => $this->penomoran->next("{$tahun}|AGENDA_MASUK"),
                'status' => 'baru',
                'diterima_oleh' => $request->user()->id,
            ]);
        });

        if ($file = $request->file('file')) {
            $surat->update([
                'file_path' => $file->store("surat-masuk/{$tahun}", 'local'),
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        $awal = $this->alur->mulai($surat, $request->user());
        $pesan = "Surat masuk tercatat dengan nomor agenda {$surat->agendaLabel()}.";

        if ($awal) {
            return redirect()->route('surat-masuk.show', $surat)
                ->with('success', $pesan." Otomatis dikirim ke {$awal->kepada->name} (Kepala Dinas).");
        }

        return redirect()->route('surat-masuk.show', $surat)
            ->with('success', $pesan.' PERHATIAN: belum ada akun Kepala Dinas yang aktif, surat belum dikirim. Periksa data pengguna.');
    }

    public function show(Request $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('view', $suratMasuk);
        $u = $request->user();

        // Penerima membuka surat -> disposisi berstatus "dibaca"
        Disposisi::where('surat_masuk_id', $suratMasuk->id)
            ->where('kepada_user_id', $u->id)->where('status', 'baru')
            ->update(['status' => 'dibaca', 'dibaca_at' => now()]);

        $suratMasuk->load(['unitTujuan', 'penerima', 'disposisi.dari.unit', 'disposisi.kepada.unit']);

        $langkah = $this->alur->langkah($u, $suratMasuk);
        $targets = $langkah ? $langkah['targets']->with('unit')->orderBy('name')->get() : collect();

        $selesaiIds = $suratMasuk->disposisi
            ->filter(fn ($d) => $d->kepada_user_id == $u->id && $d->isAktif() && $this->alur->bolehSelesai($d, $u))
            ->pluck('id')->all();

        return view('surat-masuk.show', [
            'surat' => $suratMasuk, 'targets' => $targets, 'langkah' => $langkah, 'selesaiIds' => $selesaiIds,
        ]);
    }

    public function edit(SuratMasuk $suratMasuk)
    {
        Gate::authorize('update', $suratMasuk);

        return view('surat-masuk.form', [
            'surat' => $suratMasuk,
            'units' => Unit::where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('update', $suratMasuk);
        $data = $this->clean($request->validate($this->rules()));
        $suratMasuk->update($data);

        if ($file = $request->file('file')) {
            if ($suratMasuk->file_path) {
                Storage::disk('local')->delete($suratMasuk->file_path);
            }
            $suratMasuk->update([
                'file_path' => $file->store("surat-masuk/{$suratMasuk->tahun}", 'local'),
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        return redirect()->route('surat-masuk.show', $suratMasuk)->with('success', 'Data surat masuk diperbarui.');
    }

    public function berkas(Request $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('view', $suratMasuk);
        abort_unless($suratMasuk->file_path && Storage::disk('local')->exists($suratMasuk->file_path), 404);

        return $request->boolean('unduh')
            ? Storage::disk('local')->download($suratMasuk->file_path, $suratMasuk->file_name)
            : Storage::disk('local')->response($suratMasuk->file_path, $suratMasuk->file_name);
    }

    private function clean(array $data): array
    {
        unset($data['file']);
        $data['jumlah_lampiran'] = (int) ($data['jumlah_lampiran'] ?? 0);

        return $data;
    }

    private function rules(): array
    {
        return [
            'tanggal_terima' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_surat' => ['required', 'date', 'before_or_equal:tanggal_terima'],
            'nomor_surat' => ['required', 'string', 'max:150'],
            'asal_surat' => ['required', 'string', 'max:200'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:2000'],
            'klasifikasi' => ['required', Rule::in(array_keys(SuratMasuk::KLASIFIKASI))],
            'sifat' => ['required', Rule::in(array_keys(SuratMasuk::SIFAT))],
            'jumlah_lampiran' => ['nullable', 'integer', 'min:0', 'max:999'],
            'unit_tujuan_id' => ['nullable', 'exists:units,id'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('eoffice.max_upload_kb')],
        ];
    }
}
