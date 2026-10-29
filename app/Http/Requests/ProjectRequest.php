<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAllowedOwner() && (! $this->route('project') || $this->user()->can('update', $this->route('project')));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->tags)) {
            $this->merge(['tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags))))]);
        }
        if (is_string($this->repository_url)) {
            $this->merge(['repository_url' => preg_replace('/\.git$/', '', rtrim($this->repository_url, '/'))]);
        }
    }

    public static function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:10000'],
            'repository_url' => ['nullable', 'string', 'max:500', 'regex:~^https://github\.com/[A-Za-z0-9-]+/[A-Za-z0-9_.-]+$~'],
            'category' => ['nullable', 'string', 'max:80'], 'status' => ['required', Rule::in(Project::STATUSES)], 'priority' => ['required', Rule::in(Project::PRIORITIES)],
            'deployment_url' => ['nullable', 'url:http,https', 'max:500'], 'tags' => ['nullable', 'array', 'max:20'], 'tags.*' => ['string', 'max:40'], 'notes' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function rules(): array
    {
        return self::fieldRules();
    }
}
