<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('title', 150);
            $table->timestamp('occurred_at');
            $table->string('branch', 255)->nullable();
            $table->string('commit_sha', 40)->nullable();
            $table->string('pr_url', 500)->nullable();
            $table->unsignedInteger('pr_number')->nullable();
            foreach (['implementation_summary', 'completed_tasks', 'tests_executed', 'ci_results', 'deployment_state', 'outstanding_bugs', 'blockers', 'next_tasks', 'security_restrictions', 'agent_instructions'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
            $table->unique(['project_id', 'uuid']);
            $table->index(['project_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};
