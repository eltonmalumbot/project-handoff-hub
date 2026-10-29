<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Checkpoint extends Model
{
    use HasFactory;

    public const FIELDS = ['title', 'occurred_at', 'branch', 'commit_sha', 'pr_url', 'pr_number', 'implementation_summary', 'completed_tasks', 'tests_executed', 'ci_results', 'deployment_state', 'outstanding_bugs', 'blockers', 'next_tasks', 'security_restrictions', 'agent_instructions'];

    public const LABELS = ['implementation_summary' => 'Ringkasan implementasi', 'completed_tasks' => 'Tugas selesai', 'tests_executed' => 'Tes yang dijalankan', 'ci_results' => 'Hasil CI', 'deployment_state' => 'Kondisi deployment', 'outstanding_bugs' => 'Bug tersisa', 'blockers' => 'Blocker', 'next_tasks' => 'Tugas berikutnya', 'security_restrictions' => 'Batasan keamanan', 'agent_instructions' => 'Instruksi tambahan agen'];

    protected $fillable = self::FIELDS;

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime', 'pr_number' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (Checkpoint $checkpoint) {
            $checkpoint->uuid ??= (string) Str::uuid();
        });
    }

    protected function occurredAt(): Attribute
    {
        return Attribute::make(set: fn (mixed $value) => CarbonImmutable::parse($value)->setTimezone(config('app.timezone'))->format($this->getDateFormat()));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
