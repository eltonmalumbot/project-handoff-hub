<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'github_id', 'github_login'];

    protected $hidden = ['password', 'remember_token'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function isAllowedOwner(): bool
    {
        return in_array((string) $this->github_id, config('handoff.allowed_github_ids'), true);
    }
}
