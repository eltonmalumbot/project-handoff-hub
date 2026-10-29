<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('handoff:browser-setup')]
#[Description('Siapkan SQLite khusus browser QA; hanya di environment testing')]
class BrowserSetup extends Command
{
    public function handle(): int
    {
        $path = config('database.connections.sqlite.database');
        if (! app()->environment('testing') || config('database.default') !== 'sqlite' || $path !== database_path('browser.sqlite')) {
            $this->error('Gunakan APP_ENV=testing dan DB_DATABASE absolut ke database/browser.sqlite.');

            return self::FAILURE;
        }
        if (! is_file($path)) {
            touch($path);
        }

        return $this->call('migrate', ['--force' => true]);
    }
}
