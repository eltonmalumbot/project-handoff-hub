<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('handoff:production-check {--database : Periksa koneksi dan status migrasi tanpa mengubah data}')]
#[Description('Periksa konfigurasi rilis tanpa mencetak rahasia')]
class ProductionCheck extends Command
{
    public function handle(): int
    {
        $url = rtrim((string) config('app.url'), '/');
        $ids = config('handoff.allowed_github_ids');
        $checks = [
            'APP_ENV=production' => app()->isProduction(),
            'APP_DEBUG=false' => ! config('app.debug'),
            'APP_URL menggunakan HTTPS' => str_starts_with($url, 'https://'),
            'APP_KEY tersedia' => filled(config('app.key')),
            'OAuth client ID dan secret tersedia' => filled(config('services.github.client_id')) && filled(config('services.github.client_secret')),
            'Callback OAuth sesuai APP_URL' => config('services.github.redirect') === $url.'/auth/github/callback',
            'Satu ID GitHub numerik di allowlist' => count($ids) === 1 && ctype_digit((string) $ids[0]),
            'Session memakai database' => config('session.driver') === 'database',
            'Cookie session secure dan HttpOnly' => (bool) config('session.secure') && (bool) config('session.http_only'),
            'Session terenkripsi' => (bool) config('session.encrypt'),
            'SameSite=lax' => config('session.same_site') === 'lax',
            'Database PostgreSQL' => config('database.default') === 'pgsql',
            'TLS database verify-full' => config('database.connections.pgsql.sslmode') === 'verify-full',
        ];
        if ($this->option('database')) {
            try {
                DB::select('select 1');
                $checks['Koneksi database'] = true;
                $repository = app('migration.repository');
                $checks['Tabel migrasi tersedia'] = $repository->repositoryExists();
                $checks['Tidak ada migrasi tertunda'] = $repository->repositoryExists() && count(array_diff(array_keys(app('migrator')->getMigrationFiles(database_path('migrations'))), $repository->getRan())) === 0;
            } catch (\Throwable $exception) {
                $checks['Koneksi database'] = false;
            }
        }
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'PASS' : 'FAIL').' '.$label);
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
