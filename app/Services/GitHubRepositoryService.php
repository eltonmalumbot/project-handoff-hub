<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GitHubRepositoryService
{
    public function repositoryPath(string $url): string
    {
        if (! preg_match('~\Ahttps://github\.com/([A-Za-z0-9-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?\z~', $url, $matches) || in_array($matches[2], ['.', '..'], true)) {
            throw ValidationException::withMessages(['repository_url' => 'Gunakan URL repositori https://github.com/pemilik/repo.']);
        }

        return $matches[1].'/'.$matches[2];
    }

    private function get(string $path, array $query = []): Response
    {
        $request = Http::accept('application/vnd.github+json')->withHeaders(['X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'Project-Handoff-Hub'])->connectTimeout(5)->timeout(12)->withoutRedirecting();
        if (config('handoff.github_read_token')) {
            $request = $request->withToken(config('handoff.github_read_token'));
        }

        return $request->get('https://api.github.com/repos/'.$path, $query);
    }

    private function failure(Response $response): string
    {
        if ($response->status() === 429 || $response->header('X-RateLimit-Remaining') === '0') {
            return 'Batas API GitHub tercapai. Coba lagi setelah waktu reset GitHub.';
        }

        return match ($response->status()) {
            401,403 => 'Izin integrasi GitHub tidak mencukupi.',404 => 'Repositori tidak ditemukan atau privat. Periksa URL dan izin token baca.',default => 'GitHub tidak dapat diakses saat ini (HTTP '.$response->status().').'
        };
    }

    public function sync(Project $project): void
    {
        $path = $this->repositoryPath($project->repository_url ?? '');
        try {
            $repo = $this->get($path);
            if (! $repo->successful()) {
                throw ValidationException::withMessages(['github' => $this->failure($repo)]);
            }
            $branch = $repo->json('default_branch');
            $snapshot = ['full_name' => $repo->json('full_name'), 'description' => $repo->json('description'), 'private' => $repo->json('private'), 'language' => $repo->json('language'), 'default_branch' => $branch, 'stars' => $repo->json('stargazers_count'), 'commit' => null, 'pull_requests' => null, 'ci' => null, 'checks' => null, 'warnings' => []];
            $commit = $this->get($path.'/commits', ['sha' => $branch, 'per_page' => 1]);
            if ($commit->successful()) {
                $row = $commit->json('0');
                if (is_array($row)) {
                    $snapshot['commit'] = ['sha' => $row['sha'], 'message' => data_get($row, 'commit.message'), 'date' => data_get($row, 'commit.committer.date')];
                }
            } else {
                $snapshot['warnings'][] = 'Commit: '.$this->failure($commit);
            }
            $pulls = $this->get($path.'/pulls', ['state' => 'open', 'per_page' => 30]);
            if ($pulls->successful()) {
                $snapshot['pull_requests'] = collect($pulls->json())->map(fn (array $row) => ['number' => $row['number'], 'title' => $row['title'], 'state' => $row['state'], 'draft' => $row['draft'] ?? false, 'branch' => data_get($row, 'head.ref')])->values()->all();
                $snapshot['pull_requests_limit'] = 30;
            } else {
                $snapshot['warnings'][] = 'Pull request: '.$this->failure($pulls);
            }
            if ($sha = data_get($snapshot, 'commit.sha')) {
                $ci = $this->get($path.'/commits/'.rawurlencode($sha).'/status');
                if ($ci->successful()) {
                    $snapshot['ci'] = ['state' => $ci->json('state'), 'total_count' => $ci->json('total_count'), 'sha' => $sha];
                } else {
                    $snapshot['warnings'][] = 'CI status: '.$this->failure($ci);
                }
                $checks = $this->get($path.'/commits/'.rawurlencode($sha).'/check-runs', ['per_page' => 100]);
                if ($checks->successful()) {
                    $snapshot['checks'] = collect($checks->json('check_runs', []))->map(fn (array $row) => ['name' => $row['name'], 'status' => $row['status'], 'conclusion' => $row['conclusion'] ?? null])->all();
                    $snapshot['checks_total'] = $checks->json('total_count');
                } else {
                    $snapshot['warnings'][] = 'Checks: '.$this->failure($checks);
                }
            }
            $project->forceFill(['github_snapshot' => $snapshot, 'github_synced_at' => now()])->save();
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages(['github' => 'Koneksi GitHub gagal atau timeout. Data sinkronisasi sebelumnya tetap tersimpan.']);
        }
    }
}
