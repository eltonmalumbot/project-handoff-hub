<?php

namespace Tests\Feature;

use App\Livewire\HandoffPreview;
use App\Livewire\ProjectList;
use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\User;
use App\Services\BackupService;
use App\Services\GitHubRepositoryService;
use App\Services\HandoffGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class HubTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['handoff.allowed_github_ids' => ['118357175', '999999'], 'services.github.client_id' => 'test-client', 'services.github.client_secret' => 'test-secret', 'services.github.redirect' => 'http://localhost/auth/github/callback']);
        $this->owner = User::factory()->create(['github_id' => '118357175', 'github_login' => 'eltonmalumbot']);
        Http::preventStrayRequests();
    }

    private function project(array $attributes = []): Project
    {
        return Project::factory()->for($this->owner)->create($attributes);
    }

    private function projectData(array $attributes = []): array
    {
        return array_merge(['name' => 'Handoff Laravel', 'description' => 'Private project', 'repository_url' => 'https://github.com/eltonmalumbot/project-handoff-hub', 'category' => 'Devtools', 'status' => 'Development', 'priority' => 'High', 'tags' => ['laravel']], $attributes);
    }

    private function mockIdentity(string $id): void
    {
        $identity = (new SocialiteUser)->map(['id' => $id, 'nickname' => 'eltonmalumbot', 'name' => 'Elton']);
        $driver = Mockery::mock(GithubProvider::class);
        $driver->shouldReceive('setScopes')->with([])->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($identity);
        Socialite::shouldReceive('driver')->with('github')->andReturn($driver);
    }

    public function test_guests_cannot_access_private_routes_or_backups(): void
    {
        $project = $this->project();
        $checkpoint = Checkpoint::factory()->for($project)->create();
        foreach (['/dashboard', '/projects', '/projects/create', "/projects/{$project->id}", '/backup', '/backup/export', "/projects/{$project->id}/handoff/{$checkpoint->id}"] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/projects', $this->projectData())->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Masuk dengan GitHub');
    }

    public function test_oauth_redirect_uses_state_and_no_broad_scopes(): void
    {
        $response = $this->get('/auth/github')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertNotEmpty($query['state']);
        $this->assertSame('', $query['scope']);
        $response->assertSessionHas('state');
    }

    public function test_oauth_rejects_invalid_state_with_real_provider(): void
    {
        $this->withSession(['state' => 'expected'])->get('/auth/github/callback?code=abc&state=wrong')->assertRedirect('/login')->assertSessionHasErrors('oauth');
        $this->assertGuest();
    }

    public function test_oauth_accepts_immutable_allowed_id_without_storing_tokens(): void
    {
        $this->mockIdentity('118357175');
        $this->get('/auth/github/callback?code=test')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->owner);
        $this->assertDatabaseCount('users', 1);
        $this->assertArrayNotHasKey('github_token', $this->owner->fresh()->getAttributes());
    }

    public function test_oauth_rejects_same_login_with_unlisted_numeric_id(): void
    {
        $this->mockIdentity('123456');
        $this->get('/auth/github/callback?code=test')->assertRedirect('/login')->assertSessionHasErrors('oauth');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_empty_allowlist_fails_closed(): void
    {
        config(['handoff.allowed_github_ids' => []]);
        $this->actingAs($this->owner)->get('/dashboard')->assertForbidden();
        $this->assertFalse($this->owner->isAllowedOwner());
    }

    public function test_revoked_owner_session_loses_access(): void
    {
        $this->actingAs($this->owner);
        config(['handoff.allowed_github_ids' => []]);
        $this->get('/projects')->assertForbidden();
        $this->get('/backup/export')->assertForbidden();
    }

    public function test_oauth_cancellation_missing_config_and_rate_limit(): void
    {
        $this->get('/auth/github/callback?error=access_denied')->assertSessionHasErrors('oauth');
        config(['services.github.client_id' => null]);
        $this->get('/auth/github')->assertRedirect('/login')->assertSessionHasErrors('oauth');
        for ($i = 0; $i < 10; $i++) {
            $this->get('/auth/github');
        }$this->get('/auth/github')->assertStatus(429);
    }

    public function test_logout_invalidates_session(): void
    {
        $this->actingAs($this->owner)->withSession(['private' => 'value'])->post('/logout')->assertRedirect('/login')->assertSessionMissing('private');
        $this->assertGuest();
    }

    public function test_owner_can_create_read_and_update_project(): void
    {
        $this->actingAs($this->owner)->post('/projects', $this->projectData(['tags' => 'laravel, livewire', 'user_id' => 999, 'github_snapshot' => ['ci' => 'success']]))->assertSessionHasNoErrors()->assertRedirect();
        $project = $this->owner->projects()->sole();
        $this->assertSame(['laravel', 'livewire'], $project->tags);
        $this->assertNull($project->github_snapshot);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Handoff Laravel');
        $this->put(route('projects.update', $project), $this->projectData(['name' => 'Updated hub']))->assertRedirect();
        $this->assertSame('Updated hub', $project->fresh()->name);
    }

    public function test_invalid_project_form_preserves_tags_without_rendering_error(): void
    {
        $this->actingAs($this->owner)->from('/projects/create')->post('/projects', $this->projectData(['name' => '', 'tags' => ['laravel', 'livewire']]))->assertRedirect('/projects/create')->assertSessionHasErrors('name');
        $this->get('/projects/create')->assertOk()->assertSee('laravel, livewire');
    }

    public function test_repository_change_invalidates_previous_github_evidence(): void
    {
        $project = $this->project(['github_snapshot' => ['full_name' => 'old/repo'], 'github_synced_at' => now()]);
        $this->actingAs($this->owner)->put(route('projects.update', $project), $this->projectData())->assertRedirect();
        $this->assertNull($project->fresh()->github_snapshot);
        $this->assertNull($project->fresh()->github_synced_at);
    }

    public function test_validation_rejects_unsafe_urls_and_invalid_fields(): void
    {
        $this->actingAs($this->owner)->post('/projects', $this->projectData(['name' => '', 'status' => 'Unknown', 'priority' => 'Urgent', 'deployment_url' => 'javascript:alert(1)', 'repository_url' => 'https://github.com.evil.test/owner/repo', 'tags' => array_fill(0, 21, 'a')]))->assertSessionHasErrors(['name', 'status', 'priority', 'deployment_url', 'repository_url', 'tags']);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_cross_owner_data_access_is_forbidden(): void
    {
        $other = User::factory()->create(['github_id' => '999999']);
        $project = Project::factory()->for($other)->create();
        $checkpoint = Checkpoint::factory()->for($project)->create();
        $this->actingAs($this->owner);
        foreach ([route('projects.show', $project), route('projects.edit', $project), route('checkpoints.create', $project), route('handoff.download', [$project, $checkpoint])] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->put(route('projects.update', $project), $this->projectData())->assertForbidden();
        $this->delete(route('projects.destroy', $project), ['confirmation' => $project->name])->assertForbidden();
        $this->post(route('projects.sync', $project))->assertForbidden();
        $this->post(route('checkpoints.store', $project), ['title' => 'Attack', 'occurred_at' => now()])->assertForbidden();
        $this->post(route('projects.archive', $project))->assertForbidden();
    }

    public function test_delete_requires_exact_confirmation_and_history_is_recoverable(): void
    {
        $project = $this->project();
        $checkpoint = Checkpoint::factory()->for($project)->create();
        $this->actingAs($this->owner)->delete(route('projects.destroy', $project), ['confirmation' => 'wrong'])->assertSessionHasErrors('confirmation');
        $this->assertNotSoftDeleted($project);
        $this->delete(route('projects.destroy', $project), ['confirmation' => $project->name])->assertRedirect('/projects');
        $this->assertSoftDeleted($project);
        $this->assertDatabaseHas('checkpoints', ['id' => $checkpoint->id]);
        $this->get(route('projects.show', $project))->assertNotFound();
        $this->post(route('projects.restore', $project->id))->assertRedirect();
        $this->assertNotSoftDeleted($project);
        $this->assertSame(1, $project->checkpoints()->count());
    }

    public function test_archive_keeps_checkpoint_history_and_foreign_restore_is_denied(): void
    {
        $project = $this->project();
        Checkpoint::factory()->for($project)->create();
        $this->actingAs($this->owner)->post(route('projects.archive', $project))->assertRedirect();
        $this->assertSame('Archived', $project->fresh()->status);
        $this->assertSame(1, $project->checkpoints()->count());
        $other = Project::factory()->create();
        $other->delete();
        $this->post(route('projects.restore', $other->id))->assertForbidden();
    }

    public function test_checkpoints_are_append_only_and_sorted_chronologically(): void
    {
        $project = $this->project();
        $this->actingAs($this->owner);
        $this->post(route('checkpoints.store', $project), ['title' => 'Latest', 'occurred_at' => '2026-10-29T18:00', 'tests_executed' => 'Belum dijalankan'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('checkpoints.store', $project), ['title' => 'Historical', 'occurred_at' => '2026-10-28T18:00'])->assertRedirect();
        $this->assertSame(['Latest', 'Historical'], $project->checkpoints()->pluck('title')->all());
        $this->assertDatabaseCount('checkpoints', 2);
        $this->get(route('projects.show', $project))->assertOk()->assertSeeInOrder(['Latest', 'Historical']);
    }

    public function test_checkpoint_input_validation(): void
    {
        $project = $this->project();
        $this->actingAs($this->owner)->post(route('checkpoints.store', $project), ['title' => '', 'occurred_at' => 'not-date', 'commit_sha' => 'not-sha', 'pr_number' => 0, 'pr_url' => 'https://evil.test/pr/1', 'blockers' => str_repeat('a', 20001)])->assertSessionHasErrors(['title', 'occurred_at', 'commit_sha', 'pr_number', 'pr_url', 'blockers']);
        $this->assertDatabaseCount('checkpoints', 0);
    }

    public function test_handoff_is_deterministic_and_manual_results_are_not_certified(): void
    {
        $project = $this->project();
        $checkpoint = Checkpoint::factory()->for($project)->create(['tests_executed' => 'composer test: 4 passed', 'deployment_state' => 'Belum deploy', 'next_tasks' => 'Jalankan browser QA']);
        $this->actingAs($this->owner);
        $service = app(HandoffGeneratorService::class);
        $markdown = $service->generate($project, $checkpoint);
        $this->assertSame($markdown, $service->generate($project, $checkpoint));
        foreach (['1. Identifikasi', '2. Repositori', '3. Branch', '4. Commit', '5. Status', '6. Fitur', '7. Tes', '8. Kondisi', '9. Bug', '10. Batasan', '11. Tugas', '12. Tindakan', '13. Instruksi'] as $heading) {
            $this->assertStringContainsString('## '.$heading, $markdown);
        }
        $this->assertStringContainsString('belum diverifikasi aplikasi', $markdown);
        $this->assertStringContainsString('bukan bukti tes lulus', $markdown);
        $this->assertStringContainsString('composer test: 4 passed', $markdown);
        $this->assertStringContainsString('Belum deploy', $markdown);
        $response = $this->get(route('handoff.download', [$project, $checkpoint]))->assertOk()->assertDownload();
        $this->assertSame($markdown, $response->streamedContent());
    }

    public function test_empty_handoff_has_explicit_unavailable_values(): void
    {
        $project = $this->project();
        $this->actingAs($this->owner);
        $markdown = app(HandoffGeneratorService::class)->generate($project);
        $this->assertStringContainsString('Tanpa checkpoint', $markdown);
        $this->assertStringContainsString('Informasi tidak tersedia.', $markdown);
        $this->assertStringContainsString('Belum ada sinkronisasi', $markdown);
    }

    public function test_historical_selection_and_tamper_protection_in_livewire(): void
    {
        $project = $this->project();
        $old = Checkpoint::factory()->for($project)->create(['title' => 'Old checkpoint', 'occurred_at' => now()->subDay(), 'next_tasks' => 'Old task']);
        $new = Checkpoint::factory()->for($project)->create(['title' => 'New checkpoint', 'next_tasks' => 'New task']);
        $foreign = Checkpoint::factory()->create();
        $this->actingAs($this->owner);
        Livewire::test(HandoffPreview::class, ['project' => $project])->assertSet('checkpointId', (string) $new->id)->set('checkpointId', (string) $old->id)->assertSee('Old task')->assertDontSee('New task');
        Livewire::test(HandoffPreview::class, ['project' => $project])->set('checkpointId', (string) $foreign->id)->assertNotFound();
        $this->get(route('handoff.download', [$project, $foreign]))->assertNotFound();
    }

    public function test_handoff_preview_strips_html_and_unsafe_links(): void
    {
        $project = $this->project();
        Checkpoint::factory()->for($project)->create(['implementation_summary' => '<img src=x onerror=alert(1)> [bad](javascript:alert(1))']);
        $this->actingAs($this->owner);
        Livewire::test(HandoffPreview::class, ['project' => $project])->assertDontSee('<img src=x', false)->assertDontSee('href="javascript:', false);
    }

    public function test_dashboard_counts_are_database_owned_values(): void
    {
        $this->project(['status' => 'Blocked']);
        $this->project(['status' => 'Testing']);
        $this->project(['status' => 'Archived']);
        $deleted = $this->project();
        $deleted->delete();
        Project::factory()->create();
        $this->actingAs($this->owner)->get('/dashboard')->assertOk()->assertViewHas('counts', ['Total proyek' => 3, 'Aktif' => 2, 'Blocked' => 1, 'Testing' => 1]);
    }

    public function test_project_search_filter_sort_and_trash_are_scoped(): void
    {
        $first = $this->project(['name' => 'Laravel Hub', 'priority' => 'Critical', 'status' => 'Development']);
        $this->project(['name' => 'Test App', 'priority' => 'Low', 'status' => 'Testing']);
        $other = Project::factory()->create(['name' => 'Foreign Secret']);
        $trashed = $this->project(['name' => 'Trashed Hub']);
        $trashed->delete();
        $this->actingAs($this->owner);
        Livewire::test(ProjectList::class)->assertSee('Laravel Hub')->assertDontSee('Foreign Secret')->assertDontSee('Trashed Hub')->set('search', 'laravel')->assertSee('Laravel Hub')->assertDontSee('Test App')->set('search', '')->set('status', 'Testing')->assertSee('Test App')->assertDontSee('Laravel Hub')->set('status', '')->set('priority', 'Critical')->assertSee('Laravel Hub')->assertDontSee('Test App');
        Livewire::test(ProjectList::class)->set('sort', 'priority')->assertSeeInOrder(['Laravel Hub', 'Test App'])->set('scope', 'trash')->assertSee('Trashed Hub')->assertDontSee('Test App');
    }

    private function fakeGithub(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.github.com/repos/eltonmalumbot/project-handoff-hub' => Http::response(['full_name' => 'eltonmalumbot/project-handoff-hub', 'default_branch' => 'main', 'private' => true, 'language' => 'PHP', 'stargazers_count' => 0]), 'api.github.com/repos/*/commits?*' => Http::response([['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Implement hub', 'committer' => ['date' => '2026-10-29T00:00:00Z']]]]), 'api.github.com/repos/*/pulls?*' => Http::response([['number' => 1, 'title' => 'Hub MVP', 'state' => 'open', 'head' => ['ref' => 'feature']]]), 'api.github.com/repos/*/status' => Http::response(['state' => 'pending', 'total_count' => 0]), 'api.github.com/repos/*/check-runs?*' => Http::response(['total_count' => 1, 'check_runs' => [['name' => 'tests', 'status' => 'completed', 'conclusion' => 'failure']]])]);
    }

    public function test_github_sync_stores_only_api_snapshot_and_failed_checks_remain_failures(): void
    {
        $this->fakeGithub();
        $project = $this->project(['repository_url' => 'https://github.com/eltonmalumbot/project-handoff-hub']);
        $this->actingAs($this->owner)->post(route('projects.sync', $project))->assertSessionHasNoErrors()->assertRedirect();
        $project->refresh();
        $this->assertSame('main', $project->github_snapshot['default_branch']);
        $this->assertSame('failure', data_get($project->github_snapshot, 'checks.0.conclusion'));
        $this->assertNotNull($project->github_synced_at);
        $markdown = app(HandoffGeneratorService::class)->generate($project);
        $this->assertStringContainsString('completed / failure', $markdown);
        $this->assertStringContainsString('tidak ada bukti tes lulus', $markdown);
        $this->assertStringContainsString('bukan snapshot historis', $markdown);
        Http::assertSentCount(5);
    }

    public function test_github_errors_do_not_erase_previous_snapshot(): void
    {
        $project = $this->project(['repository_url' => 'https://github.com/eltonmalumbot/project-handoff-hub', 'github_snapshot' => ['default_branch' => 'previous']]);
        $this->actingAs($this->owner);
        foreach ([401, 403, 404, 429, 500] as $status) {
            Http::fake(['*' => Http::response([], $status)]);
            try {
                app(GitHubRepositoryService::class)->sync($project);
                $this->fail('Expected sync failure');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('github', $exception->errors());
            }$this->assertSame('previous', $project->fresh()->github_snapshot['default_branch']);
        }
    }

    public function test_github_timeouts_and_partial_permissions_are_reported(): void
    {
        $project = $this->project(['repository_url' => 'https://github.com/eltonmalumbot/project-handoff-hub']);
        Http::fake(['*' => Http::failedConnection()]);
        try {
            app(GitHubRepositoryService::class)->sync($project);
            $this->fail('Expected timeout');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('timeout', $exception->errors()['github'][0]);
        }
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.github.com/repos/eltonmalumbot/project-handoff-hub' => Http::response(['full_name' => 'eltonmalumbot/project-handoff-hub', 'default_branch' => 'main']), '*' => Http::response([], 403)]);
        app(GitHubRepositoryService::class)->sync($project);
        $this->assertCount(2, $project->fresh()->github_snapshot['warnings']);
        $this->assertNull($project->fresh()->github_snapshot['commit']);
    }

    public function test_github_never_follows_user_hosts_or_writes_repositories(): void
    {
        $service = app(GitHubRepositoryService::class);
        $this->assertSame('eltonmalumbot/project-handoff-hub', $service->repositoryPath('https://github.com/eltonmalumbot/project-handoff-hub.git'));
        foreach (['http://github.com/a/b', 'https://github.com.evil/a/b', 'https://github.com/a/..', 'https://github.com/a/b?token=x', 'https://user:pass@github.com/a/b'] as $url) {
            try {
                $service->repositoryPath($url);
                $this->fail('Expected invalid URL');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('repository_url', $exception->errors());
            }
        }
        Http::assertNothingSent();
    }

    public function test_export_import_roundtrip_is_idempotent_and_preserves_old_history(): void
    {
        $project = $this->project(['notes' => 'Owner context']);
        $checkpoint = Checkpoint::factory()->for($project)->create(['implementation_summary' => 'Existing work']);
        $trash = $this->project();
        $trash->delete();
        $service = app(BackupService::class);
        $this->actingAs($this->owner);
        $data = $service->export($this->owner);
        $this->assertCount(2, $data['projects']);
        $json = json_encode($data);
        $this->assertStringNotContainsString('github_id', $json);
        $this->assertStringNotContainsString('github_snapshot', $json);
        $validated = $service->validate($json);
        $this->assertSame(['new' => 0, 'existing' => 2, 'checkpoints' => 1], $service->preview($this->owner, $validated));
        $result = $service->import($this->owner, $validated);
        $this->assertSame(1, $result['skipped']);
        $this->assertDatabaseCount('checkpoints', 1);
        $validated['projects'][0]['notes'] = 'Should not overwrite';
        $validated['projects'][0]['checkpoints'][0]['implementation_summary'] = 'Should not overwrite';
        $validated['projects'][0]['checkpoints'][] = array_merge($validated['projects'][0]['checkpoints'][0], ['uuid' => (string) Str::uuid(), 'title' => 'New history']);
        $service->import($this->owner, $validated);
        $this->assertSame('Owner context', $project->fresh()->notes);
        $this->assertSame('Existing work', $checkpoint->fresh()->implementation_summary);
        $this->assertDatabaseCount('checkpoints', 2);
        $other = User::factory()->create(['github_id' => '999999']);
        $service->import($other, $validated);
        $this->assertSame(2, $other->projects()->withTrashed()->count());
        $this->assertSame(2, $other->projects()->first()->checkpoints()->count());
    }

    public function test_backup_http_requires_preview_and_confirmation(): void
    {
        $project = $this->project();
        Checkpoint::factory()->for($project)->create();
        $this->actingAs($this->owner);
        $this->get('/backup/export')->assertOk()->assertDownload();
        $this->post('/backup/import', ['confirm' => 1])->assertSessionHasErrors('backup');
        $data = app(BackupService::class)->export($this->owner);
        $upload = UploadedFile::fake()->createWithContent('backup.json', json_encode($data));
        $this->post('/backup/preview', ['backup' => $upload])->assertRedirect('/backup')->assertSessionHas('backup_preview');
        $this->get('/backup')->assertOk()->assertSee('Pratinjau impor');
        $this->post('/backup/import', [])->assertSessionHasErrors('confirm');
        $this->post('/backup/import', ['confirm' => 1])->assertRedirect('/backup')->assertSessionMissing('backup_preview');
        $this->assertDatabaseCount('checkpoints', 1);
    }

    public function test_invalid_backup_version_size_fields_and_duplicates_are_rejected(): void
    {
        $service = app(BackupService::class);
        $project = $this->project();
        Checkpoint::factory()->for($project)->create();
        $data = $service->export($this->owner);
        $version = $data;
        $version['version'] = 99;
        $unsafe = $data;
        $unsafe['projects'][0]['deployment_url'] = 'javascript:alert(1)';
        $unknown = $data;
        $unknown['projects'][0]['user_id'] = 123;
        $duplicate = $data;
        $duplicate['projects'][0]['checkpoints'][] = $duplicate['projects'][0]['checkpoints'][0];
        foreach (['{broken', str_repeat('x', 2097153), json_encode($version), json_encode($unsafe), json_encode($unknown), json_encode($duplicate)] as $json) {
            try {
                $service->validate($json);
                $this->fail('Expected invalid backup');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_rendered_forms_and_security_headers(): void
    {
        $project = $this->project();
        $this->actingAs($this->owner);
        foreach (['/dashboard', '/projects', '/projects/create', route('projects.edit',$project), route('checkpoints.create',$project), '/backup'] as $url) {
            $this->get($url)->assertOk()->assertHeader('X-Frame-Options','DENY')->assertHeader('X-Content-Type-Options','nosniff');
        }
    }
}
