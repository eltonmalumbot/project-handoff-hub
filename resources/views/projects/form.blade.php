@extends('layouts.app')
@section('title', $project->exists ? 'Edit proyek' : 'Tambah proyek')
@section('content')
<div class="title-row"><div><h1>{{ $project->exists ? 'Edit proyek' : 'Tambah proyek' }}</h1><p>Hubungkan repositori, lalu simpan progres sebagai checkpoint.</p></div><a class="btn secondary" href="{{ $project->exists ? route('projects.show',$project) : route('projects.index') }}">Batal</a></div>
<form method="POST" action="{{ $project->exists ? route('projects.update',$project) : route('projects.store') }}" class="editor-form">@csrf @if($project->exists)@method('PUT')@endif
<section class="form-section"><div><h2>Identitas proyek</h2><p>Nama dan repositori yang akan dibawa ke handoff berikutnya.</p><small>* Wajib diisi</small></div><div class="panel form-grid">
<x-field name="name" label="Nama proyek" :value="$project->name" :required="true" maxlength="150"/>
<x-field name="category" label="Kategori" :value="$project->category" maxlength="80"/>
<x-field name="description" label="Deskripsi" type="textarea" :value="$project->description" maxlength="10000"/>
<x-field name="repository_url" label="URL repositori GitHub" type="url" :value="$project->repository_url" hint="https://github.com/pemilik/repositori · opsional" maxlength="500"/>
<x-field name="deployment_url" label="URL deployment" type="url" :value="$project->deployment_url" hint="URL ini dicatat manual; belum diverifikasi." maxlength="500"/>
</div></section>
<section class="form-section"><div><h2>Organisasi pekerjaan</h2><p>Prioritas, status, dan catatan untuk menjaga arah pekerjaan.</p></div><div class="panel form-grid">
<x-field name="status" label="Status" :value="$project->status" :options="\App\Models\Project::STATUSES" :required="true"/>
<x-field name="priority" label="Prioritas" :value="$project->priority" :options="\App\Models\Project::PRIORITIES" :required="true"/>
<x-field name="tags" label="Tag" :value="implode(', ', $project->tags ?? [])" hint="Pisahkan dengan koma. Maksimal 20 tag, 40 karakter per tag."/>
<x-field name="notes" label="Catatan" type="textarea" :value="$project->notes"/>
<div class="form-actions"><button class="btn primary" type="submit">Simpan proyek<x-icon name="arrow"/></button></div></div></section>
</form>
@endsection
