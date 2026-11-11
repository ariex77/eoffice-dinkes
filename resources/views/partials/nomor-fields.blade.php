{{-- Dipakai bersama oleh "Ambil Nomor" dan "Buat Surat Keluar".
     Variabel: $jenis, $units, $signers, $klasifikasi, $versi, $withSigner (bool), $withKategori (bool) --}}
@php
    $versi = $versi ?? 'lama';
    $baru = $versi === 'baru';
    $klasifikasi = $klasifikasi ?? collect();
@endphp

<div class="col-12 col-md-6">
    <label class="form-label">Jenis naskah *</label>
    <select name="jenis_naskah_id" id="jenis_naskah_id" class="form-select">
        <option value="">— pilih —</option>
        @foreach($jenis->groupBy('kelompok') as $kelompok => $list)
            <optgroup label="Naskah dinas {{ $kelompok }}">
                @foreach($list as $j)
                    <option value="{{ $j->id }}" data-pola="{{ $j->pola }}" data-kategori="{{ $j->pakai_kategori ? 1 : 0 }}" @selected(old('jenis_naskah_id') == $j->id)>{{ $j->nama }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    <div class="form-text" id="contoh-nomor"></div>
</div>

<div class="col-12 col-md-6 blok-unit">
    <label class="form-label">Unit / bidang {{ $baru ? '(opsional, tidak masuk nomor)' : '(kode nomor) *' }}</label>
    <select name="unit_id" class="form-select">
        <option value="">— {{ $baru ? 'tidak dipilih' : 'pilih' }} —</option>
        @foreach($units as $u)
            <option value="{{ $u->id }}" @selected(old('unit_id', auth()->user()->unit_id) == $u->id)>{{ $u->label() }}</option>
        @endforeach
    </select>
</div>

<div class="col-12 col-md-6 blok-klas">
    <label class="form-label">Kode klasifikasi arsip *</label>
    <input name="kode_klasifikasi" id="kode_klasifikasi" list="dl-klas" autocomplete="off" value="{{ old('kode_klasifikasi') }}"
           class="form-control" placeholder="Ketik kode atau uraian, mis. 400.7.7.2 atau imunisasi">
    <datalist id="dl-klas">
        @foreach($klasifikasi as $k)<option value="{{ $k->kode }}">{{ $k->uraian }}</option>@endforeach
    </datalist>
    <div class="form-text" id="uraian-klas">Pilih sesuai isi/substansi surat (Lampiran Perka, Bab II.A.7).</div>
</div>

@if(!empty($withKategori))
    <div class="col-12 col-md-6 blok-kategori">
        <label class="form-label">Kategori klasifikasi keamanan *</label>
        <select name="kategori" class="form-select">
            @foreach(\App\Models\SuratMasuk::KLASIFIKASI as $k => $v)<option value="{{ $k }}" @selected(old('kategori', 'B') === $k)>{{ $k }} — {{ $v }}</option>@endforeach
        </select>
        <div class="form-text">Menjadi awalan nomor, mis. B/400.7.7.2/DINKES/104/…</div>
    </div>
@endif

@if(!empty($withSigner))
    <div class="col-12 col-md-6 blok-nota">
        <label class="form-label">Penandatangan (untuk kode jabatan) *</label>
        <select name="penandatangan_id" class="form-select">
            <option value="">— pilih —</option>
            @foreach($signers as $s)<option value="{{ $s->id }}" @selected(old('penandatangan_id') == $s->id)>{{ $s->name }} ({{ $s->kode_jabatan ?? 'belum ada kode' }})</option>@endforeach
        </select>
    </div>
@endif

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('jenis_naskah_id');
    if (!sel) return;
    const baru = @json($baru);
    const th = {{ now()->year }};
    const uraian = @json($klasifikasi->pluck('uraian', 'kode'));

    function contoh(p, kat) {
        if (p === 'nomor_tahun') return 'Contoh: 12 Tahun ' + th;
        if (p === 'nota') return baru ? 'Contoh: 7/KASUBAG-HKU/400.7.7.2/XI/' + th : 'Contoh: 7/KASUBAG-HKU/{{ config('eoffice.kode_klasifikasi_nota') }}/XI/' + th;
        if (p === 'standar') {
            if (!baru) return 'Contoh: {{ config('eoffice.kode_skpd') }}/{{ config('eoffice.nama_skpd') }}/SET-1/' + th + '/104';
            return 'Contoh: ' + (kat ? 'B/' : '') + '400.7.7.2/{{ config('eoffice.nama_skpd') }}/104/' + th;
        }
        return '';
    }

    function tampil(selector, on) {
        document.querySelectorAll(selector).forEach(e => e.classList.toggle('d-none', !on));
    }

    function sync() {
        const o = sel.selectedOptions[0];
        const p = o ? o.dataset.pola : '';
        const kat = o && o.dataset.kategori === '1';
        tampil('.blok-unit', p === 'standar');
        tampil('.blok-klas', (baru && p === 'standar') || p === 'nota');
        tampil('.blok-kategori', baru && p === 'standar' && kat);
        tampil('.blok-nota', p === 'nota');
        document.getElementById('contoh-nomor').textContent = contoh(p, kat);
    }

    const kode = document.getElementById('kode_klasifikasi');
    function ur() {
        const t = document.getElementById('uraian-klas');
        if (kode && t) t.textContent = uraian[kode.value] ? '→ ' + uraian[kode.value] : 'Pilih sesuai isi/substansi surat (Lampiran Perka, Bab II.A.7).';
    }

    sel.addEventListener('change', sync);
    if (kode) kode.addEventListener('input', ur);
    sync(); ur();
})();
</script>
@endpush
