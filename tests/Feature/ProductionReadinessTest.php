<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_all_production_configuration_gates_can_pass(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => false, 'app.url' => 'https://handoff.example.com', 'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'services.github.client_id' => 'configured', 'services.github.client_secret' => 'secret-placeholder', 'services.github.redirect' => 'https://handoff.example.com/auth/github/callback', 'handoff.allowed_github_ids' => ['118357175'], 'session.driver' => 'database', 'session.secure' => true, 'session.http_only' => true, 'session.encrypt' => true, 'session.same_site' => 'lax', 'database.default' => 'pgsql', 'database.connections.pgsql.sslmode' => 'verify-full']);
        $this->artisan('handoff:production-check')->assertSuccessful();
    }

    public function test_multiple_allowed_owners_fail_single_owner_production_gate(): void
    {
        config(['handoff.allowed_github_ids' => ['118357175', '999999']]);
        $this->artisan('handoff:production-check')->expectsOutput('FAIL Satu ID GitHub numerik di allowlist')->assertFailed();
    }
}
