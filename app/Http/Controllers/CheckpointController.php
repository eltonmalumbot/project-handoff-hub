<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckpointRequest;
use App\Models\Checkpoint;
use App\Models\Project;
use App\Services\HandoffGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CheckpointController extends Controller
{
    public function create(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('checkpoints.form', compact('project'));
    }

    public function store(CheckpointRequest $request, Project $project): RedirectResponse
    {
        $project->checkpoints()->create($request->validated());
        $project->touch();

        return redirect()->route('projects.show', $project)->with('success', 'Checkpoint baru disimpan. Riwayat sebelumnya tetap utuh.');
    }

    public function downloadCurrent(Project $project, HandoffGeneratorService $generator): StreamedResponse
    {
        $markdown = $generator->generate($project);

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, 'handoff-'.$project->id.'.md', ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function download(Project $project, Checkpoint $checkpoint, HandoffGeneratorService $generator): StreamedResponse
    {
        $markdown = $generator->generate($project, $checkpoint);

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, 'handoff-'.$project->id.'-'.$checkpoint->id.'.md', ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
