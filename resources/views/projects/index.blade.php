@extends('layouts.app')
@section('title','Proyek')
@section('content')
<div class="title-row"><div><h1>Proyek</h1><p>Semua pekerjaan dan konteks terakhirnya.</p></div><a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/>Tambah proyek</a></div>
<livewire:project-list/>
@endsection
