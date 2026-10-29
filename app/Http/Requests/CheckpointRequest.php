<?php

namespace App\Http\Requests;

use App\Models\Checkpoint;
use Illuminate\Foundation\Http\FormRequest;

class CheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('project')) ?? false;
    }

    public static function fieldRules(): array
    {
        $rules = ['title' => ['required', 'string', 'max:150'], 'occurred_at' => ['required', 'date', 'after:2000-01-01', 'before:2100-01-01'], 'branch' => ['nullable', 'string', 'max:255'], 'commit_sha' => ['nullable', 'string', 'regex:/\A[0-9a-fA-F]{7,40}\z/'], 'pr_url' => ['nullable', 'string', 'max:500', 'regex:~^https://github\.com/[A-Za-z0-9-]+/[A-Za-z0-9_.-]+/pull/[1-9][0-9]*$~'], 'pr_number' => ['nullable', 'integer', 'min:1', 'max:2147483647']];
        foreach (array_keys(Checkpoint::LABELS) as $field) {
            $rules[$field] = ['nullable', 'string', 'max:20000'];
        }

        return $rules;
    }

    public function rules(): array
    {
        return self::fieldRules();
    }
}
