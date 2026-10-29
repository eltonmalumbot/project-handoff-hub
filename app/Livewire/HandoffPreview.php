<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\HandoffGeneratorService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HandoffPreview extends Component
{
    #[Locked]
    public int $projectId;

    public string $checkpointId = '';

    public function mount(Project $project): void
    {
        Gate::authorize('view', $project);
        $this->projectId = $project->id;
        $this->checkpointId = (string) ($project->checkpoints()->first()?->id ?? '');
    }

    public function render(): View
    {
        $project = Project::findOrFail($this->projectId);
        Gate::authorize('view', $project);
        $checkpoints = $project->checkpoints()->get();
        $checkpoint = $this->checkpointId !== '' ? $project->checkpoints()->findOrFail($this->checkpointId) : null;
        $markdown = app(HandoffGeneratorService::class)->generate($project, $checkpoint);
        $previewHtml = preg_replace_callback('/<(\/?)h([1-4])(\b[^>]*)>/', fn (array $match) => '<'.$match[1].'h'.((int) $match[2] + 2).$match[3].'>', Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]));

        return view('livewire.handoff-preview', compact('project', 'checkpoints', 'checkpoint', 'markdown', 'previewHtml'));
    }
}
