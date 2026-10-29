@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="title-row"><div><h1>Dashboard</h1><p>{{ $counts['Aktif'] }} proyek aktif. Simpan checkpoint sebelum beralih sesi.</p></div><a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/>Tambah proyek</a></div>
<div class="kpi-strip">@foreach($counts as $label=>$value)<div class="kpi"><span>{{ $label }}</span><strong>{{ number_format($value,0,',','.') }}</strong><small>{{ match($label){'Total proyek'=>'Termasuk arsip, di luar sampah','Aktif'=>'Planning hingga Blocked','Blocked'=>'Perlu tindak lanjut',default=>'Dalam tahap pengujian'} }}</small></div>@endforeach</div>
<div class="dashboard-grid"><section class="panel"><div class="section-heading"><h2>Proyek terakhir diperbarui</h2><a href="{{ route('projects.index') }}">Lihat semua <x-icon name="arrow"/></a></div>
@forelse($projects as $project)<article class="project-row"><div class="project-identity"><a href="{{ route('projects.show',$project) }}">{{ $project->name }}</a><p>{{ $project->category ?: 'Tanpa kategori' }} <span>·</span> {{ $project->priority }}</p></div><div class="row-end"><x-status :value="$project->status"/><small>{{ $project->updated_at->format('d M, H:i') }} WIB</small></div></article>
@empty<div class="empty-state"><h3>Mulai dari satu proyek.</h3><p>Tambahkan repositori atau catat proyek secara manual. Checkpoint Anda akan tersimpan di sini.</p><a class="text-link" href="{{ route('projects.create') }}">Tambahkan proyek pertama <x-icon name="arrow"/></a></div>@endforelse
</section>
<section class="panel"><div class="section-heading"><h2>Checkpoint terbaru</h2><x-icon name="branch"/></div><div class="timeline compact">
@forelse($checkpoints as $checkpoint)<article class="timeline-item"><small>{{ $checkpoint->occurred_at->format('d M Y · H:i') }} WIB</small><a href="{{ route('projects.show',$checkpoint->project) }}#handoff">{{ $checkpoint->title }}</a><p>{{ $checkpoint->project->name }}</p><div class="commit-meta"><x-icon name="branch"/>{{ $checkpoint->branch ?: 'Branch belum dicatat' }} @if($checkpoint->commit_sha)<code>{{ substr($checkpoint->commit_sha,0,7) }}</code>@endif</div></article>
@empty<div class="empty-state"><h3>Konteks dimulai di sini.</h3><p>Simpan branch, tes, dan langkah berikutnya sebagai checkpoint proyek.</p></div>@endforelse</div></section></div>
<section class="workflow-note"><x-mark/><div><h2>Lanjutkan dari titik terakhir.</h2><p>Buka proyek, catat hasil pekerjaan, lalu buat handoff dari checkpoint yang Anda pilih.</p></div><a class="btn secondary" href="{{ route('projects.index') }}">Buka proyek<x-icon name="arrow"/></a></section>
@endsection
