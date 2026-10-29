document.addEventListener('click', async (event) => {
 const menu = event.target.closest('.menu-button');
 if (menu) {
  const nav = document.getElementById('navigation');
  const open = nav.classList.toggle('is-open');
  menu.setAttribute('aria-expanded', String(open));
  if(open) nav.querySelector('a')?.focus();
 }
 const filter = event.target.closest('[data-filter-toggle]');
 if (filter) {
  const controls = document.getElementById(filter.getAttribute('aria-controls'));
  filter.setAttribute('aria-expanded', String(controls.classList.toggle('is-open')));
 }
 const nav = document.getElementById('navigation');
 if (nav?.classList.contains('is-open') && !menu && !nav.contains(event.target)) {
  nav.classList.remove('is-open');
  document.querySelector('.menu-button')?.setAttribute('aria-expanded','false');
 }
 const copy = event.target.closest('[data-copy-target]');
 if (copy) {
  const source = document.getElementById(copy.dataset.copyTarget);
  const status = copy.closest('.handoff-panel').querySelector('[data-copy-status]');
  try {
   await navigator.clipboard.writeText(source.value);
   status.textContent = 'Markdown berhasil disalin.';
  } catch {
   source.closest('details').open = true;
   source.focus(); source.select();
   status.textContent = 'Clipboard tidak tersedia. Sumber Markdown dipilih; gunakan Ctrl/Cmd+C.';
  }
 }
});
document.addEventListener('keydown', (event) => {
 const nav = document.getElementById('navigation');
 if(event.key === 'Tab' && nav?.classList.contains('is-open')) {
  const controls = [...nav.querySelectorAll('a[href], button:not(:disabled)')];
  const first = controls[0], last = controls.at(-1);
  if(event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
  else if(!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
 }
 if(event.key === 'Escape' && nav?.classList.contains('is-open')) {
  nav.classList.remove('is-open');
  const button = document.querySelector('.menu-button');
  button?.setAttribute('aria-expanded','false'); button?.focus();
 }
});
document.addEventListener('submit', (event) => {
 if(event.target.matches('[data-busy-form]')) {
  const button = event.target.querySelector('button[type="submit"]');
  button.disabled = true; button.textContent = button.dataset.busyLabel;
 }
});
