import { test, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
const widths = [390,768,1440];
async function login(page) {
 await page.route('**/auth/github', async route => {
  const response = await route.fetch({ maxRedirects: 0 });
  const url = new URL(response.headers().location);
  expect(url.searchParams.get('scope')).toBe('');
  await route.fulfill({response,status:302,headers:{...response.headers(),location:`http://127.0.0.1:8000/auth/github/callback?code=browser-test&state=${url.searchParams.get('state')}`}});
 });
 await page.goto('/login');
 await page.getByRole('link',{name:'Masuk dengan GitHub'}).click();
 await expect(page).toHaveURL(/\/(dashboard|projects)$/);
 await page.goto('/dashboard');
}
async function noOverflow(page) {
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBe(true);
}
for (const width of widths) {
 test(`complete private workflow at ${width}px`,async({page,context})=>{
  await page.setViewportSize({width,height:width===390?844:width===768?1024:900});
  const errors=[];page.on('pageerror',error=>errors.push(error.message));
  await mkdir('docs/screenshots',{recursive:true});
  await page.goto('/projects');await expect(page).toHaveURL(/\/login$/);
  await page.screenshot({path:`docs/screenshots/login-${width}.png`,fullPage:true});
  await login(page);
  if(width<1100){await page.getByRole('button',{name:'Buka navigasi'}).click();await expect(page.getByLabel('Navigasi utama')).toBeVisible();await page.keyboard.press('Escape');await expect(page.getByLabel('Navigasi utama')).not.toBeVisible();}
  await noOverflow(page);
  await page.getByRole('link',{name:'Tambah proyek',exact:true}).click();
  const name=`Handoff Hub QA ${width}`;
  await page.getByLabel('Nama proyek').fill(name);
  await page.getByLabel('Kategori',{exact:true}).fill('Developer tools');
  await page.getByLabel('Deskripsi',{exact:true}).fill('Catatan QA browser untuk alur Laravel, checkpoint, dan handoff yang akurat.');
  await page.getByLabel('URL repositori GitHub').fill('https://github.com/eltonmalumbot/project-handoff-hub');
  await page.getByLabel(/^Status/).selectOption('Development');
  await page.getByLabel(/^Prioritas/).selectOption('High');
  await page.getByLabel('Tag',{exact:true}).fill('laravel, livewire');
  await noOverflow(page);
  await page.getByRole('button',{name:'Simpan proyek'}).click();
  await expect(page.getByRole('heading',{level:1,name})).toBeVisible();
  const projectUrl=page.url();
  await page.reload();await expect(page.getByRole('heading',{level:1,name})).toBeVisible();
  await page.getByRole('link',{name:'Edit proyek',exact:true}).click();
  await page.getByLabel('Catatan',{exact:true}).fill('Konteks ini disimpan melalui formulir browser.');
  await page.getByRole('button',{name:'Simpan proyek'}).click();
  await expect(page.locator('#overview').getByText('Konteks ini disimpan melalui formulir browser.',{exact:true})).toBeVisible();
  await page.getByRole('button',{name:'Sync GitHub'}).click();
  await expect(page.getByRole('alert')).toContainText('Repositori tidak ditemukan atau privat');
  await page.getByRole('link',{name:'Checkpoint baru',exact:true}).click();
  await page.getByLabel('Judul checkpoint').fill(`Implementasi awal ${width}`);
  await page.getByLabel('Branch',{exact:true}).fill('feat/laravel-handoff-hub-mvp');
  await page.getByLabel('Commit SHA').fill('a1b2c3d');
  await page.getByLabel('Ringkasan implementasi').fill('Form proyek dan riwayat checkpoint tersimpan.');
  await page.getByLabel('Tugas selesai').fill('Menyimpan proyek dari browser.');
  await page.getByLabel('Tes yang dijalankan').fill('Catatan manual QA; hasil pipeline belum diverifikasi.');
  await page.getByLabel('Kondisi deployment').fill('Belum deploy ke produksi.');
  await page.getByLabel('Blocker',{exact:true}).fill('Kredensial OAuth produksi belum tersedia.');
  await page.getByLabel('Tugas berikutnya').fill('Verifikasi callback OAuth produksi.');
  await page.getByRole('button',{name:'Simpan checkpoint'}).click();
  await expect(page.getByRole('heading',{name:`Implementasi awal ${width}`})).toBeVisible();
  await page.getByRole('link',{name:'Checkpoint baru',exact:true}).click();
  await page.getByLabel('Judul checkpoint').fill(`Tahap berikutnya ${width}`);
  await page.getByLabel('Tanggal dan waktu (WIB)').fill(new Date(Date.now()+2*86400000).toISOString().slice(0,16));
  await page.getByLabel('Tugas berikutnya').fill('Langkah terbaru dari checkpoint kedua.');
  await page.getByRole('button',{name:'Simpan checkpoint'}).click();
  await expect(page.locator('.markdown-preview')).toContainText('Langkah terbaru dari checkpoint kedua.');
  await page.waitForFunction(()=>window.Livewire && window.Livewire.all().length>0);
  const options=await page.locator('#checkpoint-select option').allTextContents();
  expect(options).toHaveLength(3);
  await page.getByLabel('Dasar checkpoint').selectOption({label:options.find(s=>s.includes(`Implementasi awal ${width}`))});
  await expect(page.locator('.markdown-preview')).toContainText('Verifikasi callback OAuth produksi.');
  await expect(page.locator('.markdown-preview')).not.toContainText('Langkah terbaru dari checkpoint kedua.');
  await page.getByRole('button',{name:'Buat ulang',exact:true}).click();
  await expect(page.locator('.markdown-preview')).toContainText('belum diverifikasi aplikasi');
  await context.grantPermissions(['clipboard-read','clipboard-write']);
  await page.getByRole('button',{name:'Salin Markdown'}).click();
  await expect(page.locator('[data-copy-status]')).toContainText('berhasil disalin');
  const clipboard=await page.evaluate(()=>navigator.clipboard.readText());expect(clipboard).toContain('Verifikasi callback OAuth produksi.');
  const downloadPromise=page.waitForEvent('download');await page.getByRole('link',{name:'Unduh .md',exact:true}).click();const download=await downloadPromise;expect(download.suggestedFilename()).toMatch(/\.md$/);await download.saveAs(`test-results/handoff-${width}.md`);
  await noOverflow(page);await page.screenshot({path:`docs/screenshots/project-${width}.png`,fullPage:true});
  await page.goto('/projects');await page.waitForFunction(()=>window.Livewire && window.Livewire.all().length>0);await page.getByLabel('Cari proyek',{exact:true}).fill(name);
  await expect(page.locator('.project-card')).toHaveCount(1);if(width<768){await page.getByRole('button',{name:'Filter & urutan'}).click();}await page.getByLabel('Status',{exact:true}).selectOption('Testing');await expect(page.getByText('Belum ada proyek yang cocok.')).toBeVisible();
  await page.getByLabel('Status',{exact:true}).selectOption('');await expect(page.locator('.project-card')).toHaveCount(1);
  await page.screenshot({path:`docs/screenshots/projects-${width}.png`,fullPage:true});
  await page.goto('/backup');const exportPromise=page.waitForEvent('download');await page.getByRole('link',{name:'Unduh backup JSON'}).click();const backup=await exportPromise;const backupPath=`test-results/backup-${width}.json`;await backup.saveAs(backupPath);
  await page.getByLabel('Berkas backup JSON').setInputFiles(backupPath);await page.getByRole('button',{name:'Pratinjau impor'}).click();await expect(page.getByRole('heading',{name:'Pratinjau impor'})).toBeVisible();
  await page.getByLabel('Saya sudah memeriksa hasil pratinjau.').check();await page.getByRole('button',{name:'Impor backup',exact:true}).click();await expect(page.getByRole('status').first()).toContainText('0 checkpoint baru');
  await noOverflow(page);await page.screenshot({path:`docs/screenshots/backup-${width}.png`,fullPage:true});
  await page.goto(projectUrl);await expect(page.locator('.timeline h3')).toHaveCount(2);
  await page.getByRole('button',{name:'Arsipkan',exact:true}).click();await expect(page.locator('.title-with-status')).toContainText('Archived');
  await page.getByText('Pindahkan ke sampah',{exact:true}).click();await page.getByLabel(`Ketik nama proyek “${name}” untuk mengonfirmasi.`).fill('salah');await page.getByRole('button',{name:'Konfirmasi hapus'}).click();await expect(page.getByRole('alert')).toBeVisible();
  await page.getByText('Pindahkan ke sampah',{exact:true}).click();await page.getByLabel(`Ketik nama proyek “${name}” untuk mengonfirmasi.`).fill(name);await page.getByRole('button',{name:'Konfirmasi hapus'}).click();await expect(page).toHaveURL(/\/projects$/);
  await page.waitForFunction(()=>window.Livewire && window.Livewire.all().length>0);if(width<768){await page.getByRole('button',{name:'Filter & urutan'}).click();}await page.getByLabel('Tampilkan',{exact:true}).selectOption('trash');await expect(page.locator('.project-card').filter({hasText:name})).toBeVisible();await page.locator('.project-card').filter({hasText:name}).getByRole('button',{name:'Pulihkan'}).click();await expect(page.getByRole('heading',{level:1,name})).toBeVisible();
  await page.goto('/dashboard');await noOverflow(page);await page.screenshot({path:`docs/screenshots/dashboard-${width}.png`,fullPage:true});
  if(width<1100){await page.getByRole('button',{name:'Buka navigasi'}).click();}
  await page.getByRole('button',{name:'Keluar',exact:true}).click();await expect(page).toHaveURL(/\/login$/);await page.goto(projectUrl);await expect(page).toHaveURL(/\/login$/);expect(errors).toEqual([]);
 });
}
test('CSRF, OAuth state and sensitive routes reject invalid requests',async({page,request})=>{
 const response=await request.post('/projects',{form:{name:'CSRF attempt',status:'Planning',priority:'Low'}});expect(response.status()).toBe(419);
 await page.goto('/auth/github/callback?code=forged&state=wrong');await expect(page).toHaveURL(/\/login$/);await expect(page.getByRole('alert')).toContainText('kedaluwarsa');
 const backup=await request.get('/backup/export',{maxRedirects:0});expect(backup.status()).toBe(302);
});
