<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function table(string $name): string
    {
        return (string) config('plan-manager.table_prefix', 'plan_manager_').$name;
    }

    public function up(): void
    {
        Schema::create($this->table('usage_corrections'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->foreignId('usage_meter_id')->constrained($this->table('usage_meters'))->cascadeOnDelete();
            $table->foreignId('usage_record_id')->nullable()->constrained($this->table('usage_records'))->nullOnDelete();
            $table->string('correction_type')->default('adjustment')->index();
            $table->decimal('quantity', 20, 6);
            $table->text('reason');
            $table->string('actor_type')->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->foreignId('resulting_usage_record_id')->nullable()->constrained($this->table('usage_records'))->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::create($this->table('plan_change_requests'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->foreignId('current_plan_id')->nullable()->constrained($this->table('plans'))->nullOnDelete();
            $table->foreignId('current_plan_version_id')->nullable()->constrained($this->table('plan_versions'))->nullOnDelete();
            $table->foreignId('target_plan_id')->constrained($this->table('plans'))->cascadeOnDelete();
            $table->foreignId('target_plan_version_id')->constrained($this->table('plan_versions'))->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('billing_cycle')->nullable();
            $table->string('requested_by_type')->nullable();
            $table->string('requested_by_id', 64)->nullable();
            $table->string('reviewed_by_type')->nullable();
            $table->string('reviewed_by_id', 64)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('review_note')->nullable();
            $table->json('preview')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['requested_by_type', 'requested_by_id'], 'pm_pcr_requested_by_idx');
            $table->index(['reviewed_by_type', 'reviewed_by_id'], 'pm_pcr_reviewed_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table('plan_change_requests'));
        Schema::dropIfExists($this->table('usage_corrections'));
    }
};
