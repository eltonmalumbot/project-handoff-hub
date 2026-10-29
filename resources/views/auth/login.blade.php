@extends('layouts.app')
@section('title','Login')
@section('content')
<div class="login-wrap"><div class="login-brand"><x-mark/><span>Project Handoff Hub</span></div>
<div class="login-grid"><section class="login-intro"><h1>Kembali dengan konteks.</h1><p>Satu tempat untuk proyek GitHub, checkpoint pekerjaan, dan handoff ke sesi berikutnya.</p><div class="handoff-path"><span>Proyek</span><x-icon name="arrow"/><span>Checkpoint</span><x-icon name="arrow"/><span>Handoff</span></div><p class="login-note">Riwayat yang utuh. Progres yang jelas.<br>Tanpa klaim tes atau deployment yang dibuat-buat.</p></section>
<section class="login-panel"><x-icon name="lock"/><h2>Workspace pribadi</h2><p>Masuk dengan akun GitHub pemilik untuk melanjutkan pekerjaan.</p><a class="btn primary github-login" href="{{ route('auth.github') }}"><x-icon name="github"/>Masuk dengan GitHub<x-icon name="arrow"/></a><small>Akses terbatas. Tidak ada pendaftaran publik.</small></section></div>
<footer class="login-footer">Dibuat untuk melanjutkan pekerjaan, dengan informasi yang bisa dipercaya.</footer></div>
@endsection
