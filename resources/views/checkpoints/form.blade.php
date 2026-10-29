@extends('layouts.app')
@section('title','Checkpoint baru')
@section('content')
<div class="title-row"><div><h1>Checkpoint baru</h1><p>{{ $project->name }} · Riwayat lama tetap tersimpan.</p></div><a class="btn secondary" href="{{ route('projects.show',$project) }}">Batal</a></div>
<div class="notice">Semua isi checkpoint adalah catatan manual. Cantumkan perintah tes dan hasil aktual; gunakan “belum dijalankan” jika belum ada bukti.</div>
<form method="POST" action="{{ route('checkpoints.store',$project) }}" class="editor-form">@csrf
<section class="form-section"><div><h2>Titik pekerjaan</h2><p>Identifikasi branch, commit, dan waktu yang menjadi dasar kelanjutan.</p><small>* Wajib diisi · Waktu Asia/Jakarta</small></div><div class="panel form-grid">
<x-field name="title" label="Judul checkpoint" :required="true" maxlength="150"/>
<x-field name="occurred_at" label="Tanggal dan waktu (WIB)" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" :required="true"/>
<x-field name="branch" label="Branch" maxlength="255"/>
<x-field name="commit_sha" label="Commit SHA" hint="7–40 karakter heksadesimal, opsional." maxlength="40"/>
<x-field name="pr_url" label="URL pull request" type="url" maxlength="500"/>
<x-field name="pr_number" label="Nomor pull request" type="number"/>
</div></section>
<section class="form-section"><div><h2>Progres & kelanjutan</h2><p>Simpan konteks yang dibutuhkan agen berikutnya. Kolom yang kosong akan ditandai sebagai informasi tidak tersedia.</p></div><div class="panel form-grid">
@foreach(\App\Models\Checkpoint::LABELS as $name=>$label)<x-field :name="$name" :label="$label" type="textarea"/>@endforeach
<div class="form-actions"><button class="btn primary" type="submit">Simpan checkpoint<x-icon name="arrow"/></button></div></div></section></form>
@endsection
