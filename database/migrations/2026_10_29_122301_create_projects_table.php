<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('repository_url', 500)->nullable();
            $table->string('category', 80)->nullable();
            $table->string('status', 20)->default('Planning');
            $table->string('priority', 20)->default('Medium');
            $table->string('deployment_url', 500)->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('github_snapshot')->nullable();
            $table->timestamp('github_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'uuid']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
