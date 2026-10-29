<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Dashboard') · Project Handoff Hub</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
@auth
<div class="app-shell">
<aside class="sidebar" id="navigation" aria-label="Navigasi utama">
<a class="brand" href="{{ route('dashboard') }}"><x-mark/><span>Project<br><strong>Handoff Hub</strong></span></a>
<div class="workspace"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span><strong>Workspace pribadi</strong><small>{{ '@'.auth()->user()->github_login }}</small></span><span class="lock"><x-icon name="lock"/></span></div>
<p class="nav-caption">Workspace</p>
<nav>
<a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><x-icon name="grid"/>Dashboard</a>
<a class="nav-link {{ request()->routeIs('projects.*','checkpoints.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><x-icon name="folder"/>Proyek</a>
<a class="nav-link {{ request()->routeIs('backup.*') ? 'active' : '' }}" href="{{ route('backup.index') }}"><x-icon name="archive"/>Backup & pemulihan</a>
</nav>
<div class="sidebar-bottom"><p><x-icon name="lock"/> Hanya untuk pemilik</p><small>Checkpoint tersimpan.<br>Konteks tetap tersambung.</small><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn secondary logout" type="submit"><x-icon name="logout"/>Keluar</button></form></div>
</aside>
<div class="app-body">
<header class="topbar"><button class="menu-button btn secondary" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu"/></button><span>Workspace pribadi <span class="slash">/</span> @yield('title', 'Dashboard')</span><span class="topbar-end">Asia/Jakarta <x-icon name="lock"/></span></header>
<main id="main" class="sheet" tabindex="-1">
@include('partials.feedback')
@yield('content')
<footer class="page-footer"><span>Project Handoff Hub</span><span>Catat progres. Lanjutkan dengan konteks.</span></footer>
</main></div></div>
@else
<main id="main" class="guest-main">@include('partials.feedback')@yield('content')</main>
@endauth
@livewireScripts
</body></html>
