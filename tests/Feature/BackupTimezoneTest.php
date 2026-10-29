<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BackupTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_preserves_instants_and_original_timestamps_across_timezones(): void
    {
        config(['handoff.allowed_github_ids' => ['118357175', '999999']]);
        $owner = User::factory()->create(['github_id' => '118357175']);
        $target = User::factory()->create(['github_id' => '999999']);
        $project = Project::factory()->for($owner)->create(['created_at' => '2025-01-01 19:00:00', 'updated_at' => '2025-01-02 19:00:00']);
        $checkpoint = Checkpoint::factory()->for($project)->create(['occurred_at' => '2026-10-29T12:00:00Z', 'created_at' => '2026-10-29 19:01:00', 'updated_at' => '2026-10-29 19:01:00']);
        $service = app(BackupService::class);
        $data = $service->validate(json_encode($service->export($owner)));
        $service->import($target, $data);
        $copy = $target->projects()->sole();
        $copiedCheckpoint = $copy->checkpoints()->sole();
        $this->assertSame('2026-10-29 19:00:00', $checkpoint->fresh()->occurred_at->format('Y-m-d H:i:s'));
        $this->assertSame($project->fresh()->created_at->timestamp, $copy->created_at->timestamp);
        $this->assertSame($project->fresh()->updated_at->timestamp, $copy->updated_at->timestamp);
        $this->assertSame($checkpoint->fresh()->created_at->timestamp, $copiedCheckpoint->created_at->timestamp);
        $this->assertSame($checkpoint->fresh()->occurred_at->timestamp, $copiedCheckpoint->occurred_at->timestamp);
    }

    public function test_uuid_case_does_not_bypass_duplicate_checkpoint_detection(): void
    {
        config(['handoff.allowed_github_ids' => ['118357175']]);
        $owner = User::factory()->create(['github_id' => '118357175']);
        $project = Project::factory()->for($owner)->create();
        Checkpoint::factory()->for($project)->create();
        $service = app(BackupService::class);
        $data = $service->export($owner);
        $duplicate = $data['projects'][0]['checkpoints'][0];
        $duplicate['uuid'] = strtoupper($duplicate['uuid']);
        $data['projects'][0]['checkpoints'][] = $duplicate;
        $this->expectException(ValidationException::class);
        $service->validate(json_encode($data));
    }
}
