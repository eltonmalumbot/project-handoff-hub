<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['Planning', 'Development', 'Testing', 'Blocked', 'Completed', 'Archived'];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];

    public const FIELDS = ['name', 'description', 'repository_url', 'category', 'status', 'priority', 'deployment_url', 'tags', 'notes'];

    protected $fillable = self::FIELDS;

    protected function casts(): array
    {
        return ['tags' => 'array', 'github_snapshot' => 'array', 'github_synced_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            $project->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
