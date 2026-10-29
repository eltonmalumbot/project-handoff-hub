<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $sort = 'updated_at';

    #[Url]
    public string $scope = 'active';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isAllowedOwner(), 403);
        $query = auth()->user()->projects()->withCount('checkpoints');
        if ($this->scope === 'trash') {
            $query->onlyTrashed();
        }
        if ($this->search !== '') {
            $term = '%'.mb_strtolower(mb_substr($this->search, 0, 150)).'%';
            $query->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(description) LIKE ?', [$term])->orWhereRaw('LOWER(category) LIKE ?', [$term]);
            });
        }
        if (in_array($this->status, Project::STATUSES, true)) {
            $query->where('status', $this->status);
        }
        if (in_array($this->priority, Project::PRIORITIES, true)) {
            $query->where('priority', $this->priority);
        }
        if ($this->sort === 'priority') {
            $query->orderByRaw("CASE priority WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END");
        } elseif ($this->sort === 'status') {
            $query->orderBy('status');
        }

        return view('livewire.project-list', ['projects' => $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(12)]);
    }
}
