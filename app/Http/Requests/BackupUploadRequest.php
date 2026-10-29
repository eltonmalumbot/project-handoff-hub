<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAllowedOwner() ?? false;
    }

    public function rules(): array
    {
        return ['backup' => ['required', 'file', 'max:2048']];
    }
}
