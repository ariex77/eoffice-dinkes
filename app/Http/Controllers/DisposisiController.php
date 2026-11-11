<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Services\AlurDisposisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DisposisiController extends Controller
{
    public function __construct(private AlurDisposisi $alur)
    {
    }

    /** Kotak disposisi: yang saya terima / yang saya berikan. */
    public function index(Request $request)
    {
        $u = $request->user();
        $tab = $request->query('tab') === 'keluar' ? 'keluar' : 'masuk';

        $q = Disposisi::with(['suratMasuk', 'dari', 'kepada'])->orderByDesc('id');
        $tab === 'masuk' ? $q->where('kepada_user_id', $u->id) : $q->where('dari_user_id', $u->id);

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        } elseif ($request->query('semua') !== '1') {
            $q->whereIn('status', Disposisi::AKTIF);
        }

        return view('disposisi.index', ['items' => $q->paginate(15)->withQueryString(), 'tab' => $tab]);
    }

    /** Meneruskan surat ke tahap berikutnya. Tahap & calon penerima ditentukan alur, bukan oleh pengguna. */
    public function store(Request $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('disposisi', $suratMasuk);
        $u = $request->user();

        $langkah = $this->alur->langkah($u, $suratMasuk);
        abort_unless($langkah, 403, 'Surat ini belum berada di tahap Anda.');

        $d = $request->validate([
            'kepada_user_ids' => ['required', 'array', 'min:1'],
            'kepada_user_ids.*' => ['integer', Rule::in($langkah['targets']->pluck('id')->all())],
            'instruksi' => ['required', 'array', 'min:1'],
            'instruksi.*' => [Rule::in(Disposisi::INSTRUKSI)],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'batas_waktu' => ['nullable', 'date', 'after_or_equal:today'],
            'sifat' => ['required', Rule::in(array_keys(SuratMasuk::SIFAT))],
        ], [
            'kepada_user_ids.*.in' => 'Penerima tidak sesuai alur distribusi surat.',
        ]);

        DB::transaction(function () use ($d, $u, $suratMasuk, $langkah) {
            foreach (array_unique($d['kepada_user_ids']) as $uid) {
                Disposisi::create([
                    'surat_masuk_id' => $suratMasuk->id,
                    'parent_id' => $langkah['parent']?->id,
                    'tahap' => $langkah['tahap'],
                    'dari_user_id' => $u->id,
                    'kepada_user_id' => $uid,
                    'instruksi' => array_values($d['instruksi']),
                    'catatan' => $d['catatan'] ?? null,
                    'batas_waktu' => $d['batas_waktu'] ?? null,
                    'sifat' => $d['sifat'],
                    'status' => 'baru',
                ]);
            }

            $langkah['parent']?->update(['status' => 'diteruskan']);
            $suratMasuk->update(['status' => 'didisposisi']);
        });

        return back()->with('success', 'Disposisi dikirim.');
    }

    public function proses(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->kepada_user_id == $request->user()->id, 403);
        abort_unless(in_array($disposisi->status, ['baru', 'dibaca'], true), 422, 'Status disposisi tidak dapat diubah.');

        $disposisi->update(['status' => 'diproses', 'dibaca_at' => $disposisi->dibaca_at ?? now()]);

        return back()->with('success', 'Disposisi ditandai sedang diproses.');
    }

    /** Penyelesaian Disposisi oleh Pejabat Fungsional / Staf pelaksana. */
    public function selesai(Request $request, Disposisi $disposisi)
    {
        $u = $request->user();
        abort_unless($disposisi->kepada_user_id == $u->id, 403);
        abort_unless($disposisi->isAktif(), 422, 'Disposisi sudah tidak aktif.');
        abort_unless(
            $this->alur->bolehSelesai($disposisi, $u),
            422,
            'Pada tahap ini surat harus diteruskan sesuai alur, bukan diselesaikan.'
        );

        $d = $request->validate(['laporan' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($disposisi, $d) {
            $disposisi->update([
                'status' => 'selesai',
                'laporan' => $d['laporan'],
                'selesai_at' => now(),
                'dibaca_at' => $disposisi->dibaca_at ?? now(),
            ]);

            // Surat selesai bila tidak ada lagi disposisi yang menunggu tindakan
            $surat = $disposisi->suratMasuk;
            if (! $surat->disposisi()->whereIn('status', Disposisi::AKTIF)->exists()) {
                $surat->update(['status' => 'selesai']);
            }
        });

        return back()->with('success', 'Penyelesaian disposisi tercatat.');
    }
}
