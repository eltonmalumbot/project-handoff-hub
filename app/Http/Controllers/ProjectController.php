<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\GitHubRepositoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function create(): View
    {
        return view('projects.form', ['project' => new Project(['status' => 'Planning', 'priority' => 'Medium', 'tags' => []])]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.form', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        if (($data['repository_url'] ?? null) !== $project->repository_url) {
            $project->forceFill(['github_snapshot' => null, 'github_synced_at' => null]);
        }
        $project->fill($data)->save();

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil diperbarui.');
    }

    public function archive(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $project->update(['status' => 'Archived']);

        return back()->with('success', 'Proyek diarsipkan. Riwayat tetap tersimpan.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $request->validate(['confirmation' => ['required', 'string', Rule::in([$project->name])]]);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyek dipindahkan ke sampah. Anda dapat memulihkannya.');
    }

    public function restore(int $project): RedirectResponse
    {
        $record = Project::onlyTrashed()->findOrFail($project);
        Gate::authorize('update', $record);
        $record->restore();

        return redirect()->route('projects.show', $record)->with('success', 'Proyek dipulihkan.');
    }

    public function sync(Project $project, GitHubRepositoryService $github): RedirectResponse
    {
        Gate::authorize('update', $project);
        $github->sync($project);

        return back()->with('success','Snapshot GitHub diperbarui. Periksa peringatan jika ada data tidak tersedia.');
    }
}
