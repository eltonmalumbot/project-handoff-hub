<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_workspace_backup_can_be_restored(): void
    {
        config(['handoff.allowed_github_ids' => ['118357175']]);
        $user = User::factory()->create(['github_id' => '118357175']);
        $service = app(BackupService::class);
        $data = $service->validate(json_encode($service->export($user)));
        $this->assertSame(['projects' => 0, 'checkpoints' => 0, 'skipped' => 0], $service->import($user, $data));
    }

    public function test_current_handoff_without_checkpoint_downloads(): void
    {
        config(['handoff.allowed_github_ids' => ['118357175']]);
        $user = User::factory()->create(['github_id' => '118357175']);
        $project = Project::factory()->for($user)->create();
        $response = $this->actingAs($user)->get(route('handoff.current', $project))->assertOk()->assertDownload();
        $this->assertStringContainsString('Tanpa checkpoint', $response->streamedContent());
    }

    public function test_production_check_reports_missing_configuration_without_printing_values(): void
    {
        $this->artisan('handoff:production-check')->expectsOutput('FAIL APP_ENV=production')->assertExitCode(1);
    }

    public function test_browser_setup_refuses_non_testing_or_non_browser_database(): void
    {
        $this->artisan('handoff:browser-setup')->assertExitCode(1);
    }
}
