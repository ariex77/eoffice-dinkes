@extends('layouts.app')
@section('title', 'Surat Masuk '.$surat->agendaLabel())
@section('content')
@php
    $me = auth()->user();
    $tahapNama = \App\Services\AlurDisposisi::TAHAP;
    $dicapai = $surat->disposisi->pluck('tahap')->filter()->unique()->all();
    $terakhir = $surat->disposisi->last()?->tahap;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <a href="{{ route('surat-masuk.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Surat masuk</a>
        <h1 class="h4 mb-0">{{ $surat->perihal }}</h1>
        <div class="text-muted">Agenda {{ $surat->agendaLabel() }}</div>
    </div>
    @can('update', $surat)<a href="{{ route('surat-masuk.edit', $surat) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Ubah</a>@endcan
</div>

{{-- Penanda kemajuan alur --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
        <span class="badge rounded-pill text-bg-success"><i class="bi bi-check2"></i> Dicatat Agendaris</span>
        @foreach(\App\Services\AlurDisposisi::URUTAN as $k)
            <i class="bi bi-chevron-right text-muted"></i>
            <span class="badge rounded-pill text-bg-{{ $k === $terakhir ? 'primary' : (in_array($k, $dicapai) ? 'success' : 'light border text-muted') }}">{{ $tahapNama[$k] }}</span>
        @endforeach
        @if($surat->status === 'selesai')<i class="bi bi-chevron-right text-muted"></i><span class="badge rounded-pill text-bg-success"><i class="bi bi-flag-fill"></i> Selesai</span>@endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-{{ $surat->klasifikasiColor() }}">{{ $surat->klasifikasi }} · {{ \App\Models\SuratMasuk::KLASIFIKASI[$surat->klasifikasi] }}</span>
                    <span class="badge text-bg-{{ $surat->sifatColor() }} border">{{ \App\Models\SuratMasuk::SIFAT[$surat->sifat] }}</span>
                    <span class="badge text-bg-{{ $surat->statusColor() }}">{{ \App\Models\SuratMasuk::STATUS[$surat->status] }}</span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nomor surat</dt><dd class="col-sm-8">{{ $surat->nomor_surat }}</dd>
                    <dt class="col-sm-4">Tanggal surat</dt><dd class="col-sm-8">@tgl($surat->tanggal_surat)</dd>
                    <dt class="col-sm-4">Tanggal terima</dt><dd class="col-sm-8">@tgl($surat->tanggal_terima)</dd>
                    <dt class="col-sm-4">Asal surat</dt><dd class="col-sm-8">{{ $surat->asal_surat }}</dd>
                    <dt class="col-sm-4">Unit tujuan</dt><dd class="col-sm-8">{{ $surat->unitTujuan?->label() ?? '—' }}</dd>
                    <dt class="col-sm-4">Lampiran</dt><dd class="col-sm-8">{{ $surat->jumlah_lampiran }}</dd>
                    <dt class="col-sm-4">Isi ringkas</dt><dd class="col-sm-8">{!! nl2br(e($surat->ringkasan ?: '—')) !!}</dd>
                    <dt class="col-sm-4">Dicatat oleh</dt><dd class="col-sm-8">{{ $surat->penerima->name }}</dd>
                </dl>
                @if($surat->file_path)
                    <hr>
                    <a href="{{ route('surat-masuk.berkas', $surat) }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye me-1"></i>Lihat berkas</a>
                    <a href="{{ route('surat-masuk.berkas', [$surat, 'unduh' => 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Unduh</a>
                    <span class="small text-muted ms-1">{{ $surat->file_name }}</span>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Rekam jejak disposisi <span class="text-muted fw-normal small">(audit trail)</span></div>
            <ul class="list-group list-group-flush">
                @forelse($surat->disposisi as $d)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <div>
                                <strong>{{ $d->dari->name }}</strong> <i class="bi bi-arrow-right"></i> <strong>{{ $d->kepada->name }}</strong>
                                <span class="label-sm">({{ $d->kepada->jabatan ?? $d->kepada->roleLabel() }})</span>
                            </div>
                            <span class="badge text-bg-{{ $d->statusColor() }}">{{ \App\Models\Disposisi::STATUS[$d->status] }}</span>
                        </div>
                        @if($d->tahapLabel())<div class="label-sm mb-1">Tahap: {{ $d->tahapLabel() }}</div>@endif
                        <div class="my-1">@foreach($d->instruksi ?? [] as $i)<span class="badge text-bg-light border me-1">{{ $i }}</span>@endforeach</div>
                        @if($d->catatan)<div class="small">{{ $d->catatan }}</div>@endif
                        <div class="label-sm">
                            Dikirim {{ $d->created_at->translatedFormat('d M Y H:i') }}
                            @if($d->dibaca_at) · dibaca {{ $d->dibaca_at->translatedFormat('d M Y H:i') }} @endif
                            @if($d->selesai_at) · selesai {{ $d->selesai_at->translatedFormat('d M Y H:i') }} @endif
                            @if($d->batas_waktu) · batas @tgl($d->batas_waktu) @if($d->terlambat())<span class="text-danger fw-semibold">terlambat</span>@endif @endif
                            · {{ \App\Models\SuratMasuk::SIFAT[$d->sifat] }}
                        </div>
                        @if($d->laporan)<div class="mt-2 p-2 bg-success-subtle rounded small"><strong>Hasil penyelesaian:</strong> {{ $d->laporan }}</div>@endif

                        @if($d->kepada_user_id == $me->id && $d->isAktif())
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                @if(in_array($d->status, ['baru', 'dibaca']))
                                    <form method="post" action="{{ route('disposisi.proses', $d) }}">@csrf<button class="btn btn-sm btn-outline-info">Mulai proses</button></form>
                                @endif
                                @if(in_array($d->id, $selesaiIds))
                                    <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#selesai-{{ $d->id }}"><i class="bi bi-check2-circle me-1"></i>Penyelesaian Disposisi</button>
                                @endif
                            </div>
                            @if(in_array($d->id, $selesaiIds))
                                <form method="post" action="{{ route('disposisi.selesai', $d) }}" class="collapse mt-2" id="selesai-{{ $d->id }}">
                                    @csrf
                                    <textarea name="laporan" rows="2" class="form-control mb-2" placeholder="Hasil pekerjaan (mis. draf balasan sudah dibuat, telaahan staf, laporan kegiatan)…" required></textarea>
                                    <button class="btn btn-sm btn-success">Simpan penyelesaian</button>
                                </form>
                            @endif
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">Belum ada disposisi.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        @if($langkah)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">{{ $langkah['label'] }}</div>
                <form method="post" action="{{ route('disposisi.store', $surat) }}" class="card-body">
                    @csrf
                    <label class="form-label fw-semibold">Instruksi *</label>
                    <div class="mb-3">
                        @foreach(\App\Models\Disposisi::INSTRUKSI as $i)
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="instruksi[]" value="{{ $i }}" id="i{{ $loop->index }}" @checked(in_array($i, old('instruksi', [])))><label class="form-check-label" for="i{{ $loop->index }}">{{ $i }}</label></div>
                        @endforeach
                    </div>

                    <label class="form-label fw-semibold">Kepada *</label>
                    @if($langkah['tahap'] === 'kerja')
                        <div class="form-text mb-1">Pilih sesuai substansi surat: umum/kepegawaian/hukum → Kasubbag HKU; keuangan/aset/SPM → Kasubbag Keuangan; teknis kesehatan → Kepala Bidang terkait.</div>
                    @endif
                    <div class="border rounded p-2 mb-3" style="max-height:240px;overflow:auto">
                        @php
                            $grup = fn ($t) => $t->role === 'kepala_bidang' ? 'Jalur Teknis Kesehatan' : ($t->role === 'kepala_seksi' ? 'Jalur Administrasi & Keuangan' : ($t->unit?->nama ?? '—'));
                        @endphp
                        @forelse($targets->groupBy($grup) as $namaGrup => $list)
                            <div class="label-sm mt-1 text-uppercase">{{ $namaGrup }}</div>
                            @foreach($list as $t)
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="kepada_user_ids[]" value="{{ $t->id }}" id="u{{ $t->id }}" @checked(in_array($t->id, old('kepada_user_ids', [])))><label class="form-check-label" for="u{{ $t->id }}">{{ $t->name }} <span class="label-sm">{{ $t->jabatan ?? $t->roleLabel() }}</span></label></div>
                            @endforeach
                        @empty
                            <div class="text-muted small">Belum ada akun aktif yang sesuai untuk tahap ini. Hubungi admin.</div>
                        @endforelse
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Sifat</label>
                            <select name="sifat" class="form-select">@foreach(\App\Models\SuratMasuk::SIFAT as $k => $v)<option value="{{ $k }}" @selected(old('sifat', $surat->sifat) === $k)>{{ $v }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label">Batas waktu</label><input type="date" name="batas_waktu" min="{{ today()->toDateString() }}" value="{{ old('batas_waktu') }}" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Catatan</label><textarea name="catatan" rows="2" class="form-control">{{ old('catatan') }}</textarea></div>
                    <button class="btn btn-success w-100"><i class="bi bi-send me-1"></i>Kirim disposisi</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
