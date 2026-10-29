<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\GitHubRepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitHubLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_repository_can_be_synced_from_real_read_only_api(): void
    {
        if (getenv('RUN_GITHUB_LIVE_TEST') !== '1') {
            $this->markTestSkipped('Opt-in: requires external GitHub connectivity.');
        }
        config(['handoff.github_read_token' => null]);
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['repository_url' => 'https://github.com/laravel/laravel']);
        app(GitHubRepositoryService::class)->sync($project);
        $project->refresh();
        $this->assertSame('laravel/laravel', $project->github_snapshot['full_name']);
        $this->assertNotEmpty($project->github_snapshot['default_branch']);
        $this->assertNotNull($project->github_synced_at);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $project->github_snapshot['commit']['sha']);
    }
}
