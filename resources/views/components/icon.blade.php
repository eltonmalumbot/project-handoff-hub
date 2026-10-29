@props(['name'])
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('folder')<path d="M3 7V4h6l3 3h9v13H3V7Z"/>@break
@case('archive')<path d="M4 8h16v13H4zM3 3h18v5H3zM10 12h4"/>@break
@case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 14v3"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('arrow')<path d="M5 12h14m-5-5 5 5-5 5"/>@break
@case('branch')<circle cx="6" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M6 7v10M18 7c0 6-12 3-12 8"/>@break
@case('logout')<path d="M10 3H4v18h6M9 12h12m-5-5 5 5-5 5"/>@break
@case('github')<path d="M9 19c-4 1-4-2-6-2m12 5v-3.5c0-1 .1-1.5-.5-2 4.5-.5 6.5-2.5 6.5-6 0-1.5-.5-2.5-1.5-3.5.5-1.5.5-2.5 0-4-2 0-3 1-4 1.5a13 13 0 0 0-7 0C7 2 6 2 4 2c-.5 1.5-.5 2.5 0 4C3 7 2.5 8 2.5 9.5c0 3.5 2 5.5 6.5 6-.6.5-.5 1-.5 2V22"/>@break
@case('copy')<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>@break
@case('download')<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>@break
@case('sync')<path d="M20 7a9 9 0 0 0-15-2L3 7m0-5v5h5M4 17a9 9 0 0 0 15 2l2-2m0 5v-5h-5"/>@break
@endswitch</svg>
