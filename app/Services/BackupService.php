<?php

namespace App\Services;

use App\Http\Requests\CheckpointRequest;
use App\Http\Requests\ProjectRequest;
use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BackupService
{
    public function export(User $user): array
    {
        abort_unless($user->isAllowedOwner(), 403);

        return ['version' => 1, 'exported_at' => now()->toIso8601String(), 'projects' => $user->projects()->withTrashed()->with('checkpoints')->orderBy('id')->get()->map(fn (Project $project) => array_merge($project->only(array_merge(['uuid', 'created_at', 'updated_at'], Project::FIELDS)), ['deleted_at' => $project->deleted_at?->toIso8601String(), 'checkpoints' => $project->checkpoints->map(fn (Checkpoint $checkpoint) => array_merge($checkpoint->only(array_merge(['uuid', 'created_at', 'updated_at'], Checkpoint::FIELDS)), ['occurred_at' => $checkpoint->occurred_at->toIso8601String()]))->all()]))->all()];
    }

    public function validate(string $json): array
    {
        if (strlen($json) > 2097152) {
            throw ValidationException::withMessages(['backup' => 'Backup maksimal 2 MiB.']);
        }
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ValidationException::withMessages(['backup' => 'Berkas JSON tidak valid atau terlalu dalam.']);
        }
        $rules = ['version' => ['required', 'integer', 'in:1'], 'exported_at' => ['required', 'date'], 'projects' => ['present', 'array', 'max:100'], 'projects.*' => ['required', 'array:'.implode(',', array_merge(['uuid', 'deleted_at', 'checkpoints', 'created_at', 'updated_at'], Project::FIELDS))], 'projects.*.uuid' => ['required', 'uuid', 'distinct'], 'projects.*.created_at' => ['sometimes', 'date'], 'projects.*.updated_at' => ['sometimes', 'date'], 'projects.*.checkpoints.*.created_at' => ['sometimes', 'date'], 'projects.*.checkpoints.*.updated_at' => ['sometimes', 'date'], 'projects.*.deleted_at' => ['nullable', 'date'], 'projects.*.checkpoints' => ['present', 'array', 'max:500'], 'projects.*.checkpoints.*' => ['required', 'array:'.implode(',', array_merge(['uuid', 'created_at', 'updated_at'], Checkpoint::FIELDS))], 'projects.*.checkpoints.*.uuid' => ['required', 'uuid']];
        foreach (ProjectRequest::fieldRules() as $key => $rule) {
            $rules['projects.*.'.$key] = $rule;
        }
        foreach (CheckpointRequest::fieldRules() as $key => $rule) {
            $rules['projects.*.checkpoints.*.'.$key] = $rule;
        }
        $validated = Validator::make(is_array($data) ? $data : [], $rules)->validate();
        $projectUuids = [];
        foreach ($validated['projects'] as &$project) {
            $project['uuid'] = strtolower($project['uuid']);
            $projectUuids[] = $project['uuid'];
            foreach ($project['checkpoints'] as &$checkpoint) {
                $checkpoint['uuid'] = strtolower($checkpoint['uuid']);
            }unset($checkpoint);
        }unset($project);
        if (count($projectUuids) !== count(array_unique($projectUuids))) {
            throw ValidationException::withMessages(['backup' => 'UUID proyek duplikat.']);
        }
        $total = 0;
        foreach ($validated['projects'] as $project) {
            $total += count($project['checkpoints']);
            $uuids = array_column($project['checkpoints'], 'uuid');
            if (count($uuids) !== count(array_unique($uuids))) {
                throw ValidationException::withMessages(['backup' => 'UUID checkpoint duplikat dalam satu proyek.']);
            }
        }
        if ($total > 5000) {
            throw ValidationException::withMessages(['backup' => 'Backup maksimal 5000 checkpoint.']);
        }

        return $validated;
    }

    public function preview(User $user, array $data): array
    {
        abort_unless($user->isAllowedOwner(), 403);
        $existing = $user->projects()->withTrashed()->whereIn('uuid', array_column($data['projects'], 'uuid'))->count();

        return ['new' => count($data['projects']) - $existing, 'existing' => $existing, 'checkpoints' => array_sum(array_map(fn (array $project) => count($project['checkpoints']), $data['projects']))];
    }

    public function import(User $user, array $data): array
    {
        abort_unless($user->isAllowedOwner(), 403);

        return DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $result = ['projects' => 0, 'checkpoints' => 0, 'skipped' => 0];
            foreach ($data['projects'] as $row) {
                $project = $user->projects()->withTrashed()->where('uuid', $row['uuid'])->first();
                if (! $project) {
                    $project = $user->projects()->make(collect($row)->only(Project::FIELDS)->all());
                    $project->uuid = $row['uuid'];
                    foreach (['created_at', 'updated_at'] as $timestamp) {
                        if (isset($row[$timestamp])) {
                            $project->$timestamp = CarbonImmutable::parse($row[$timestamp])->setTimezone(config('app.timezone'));
                        }
                    }$project->deleted_at = isset($row['deleted_at']) ? CarbonImmutable::parse($row['deleted_at'])->setTimezone(config('app.timezone')) : null;
                    $project->save();
                    $result['projects']++;
                }
                foreach ($row['checkpoints'] as $checkpoint) {
                    if ($project->checkpoints()->where('uuid', $checkpoint['uuid'])->exists()) {
                        $result['skipped']++;

                        continue;
                    }
                    $record = $project->checkpoints()->make(collect($checkpoint)->only(Checkpoint::FIELDS)->all());
                    $record->uuid = $checkpoint['uuid'];
                    foreach (['created_at', 'updated_at'] as $timestamp) {
                        if (isset($checkpoint[$timestamp])) {
                            $record->$timestamp = CarbonImmutable::parse($checkpoint[$timestamp])->setTimezone(config('app.timezone'));
                        }
                    }$record->save();
                    $result['checkpoints']++;
                }
            }

            return $result;
        });
    }
}
