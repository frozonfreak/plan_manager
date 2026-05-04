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
        Schema::create($this->table('plans'), function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('plan_versions'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained($this->table('plans'))->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft')->index();
            $table->string('billing_cycle')->nullable()->index();
            $table->decimal('display_price', 20, 6)->nullable();
            $table->string('currency', 3)->nullable()->default('INR');
            $table->string('external_price_id')->nullable()->index();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['plan_id', 'version']);
        });

        Schema::create($this->table('features'), function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->index();
            $table->json('default_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('plan_feature_values'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_version_id')->constrained($this->table('plan_versions'))->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained($this->table('features'))->cascadeOnDelete();
            $table->json('value');
            $table->timestamps();
            $table->unique(['plan_version_id', 'feature_id']);
        });

        Schema::create($this->table('addons'), function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('display_price', 20, 6)->nullable();
            $table->string('currency', 3)->nullable()->default('INR');
            $table->string('external_price_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('plan_addons'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_version_id')->constrained($this->table('plan_versions'))->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained($this->table('addons'))->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['plan_version_id', 'addon_id']);
        });

        Schema::create($this->table('usage_meters'), function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->string('reset_period')->default('monthly')->index();
            $table->string('aggregation')->default('sum');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('usage_records'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->foreignId('usage_meter_id')->constrained($this->table('usage_meters'))->cascadeOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['usage_meter_id']);
            $table->index(['period_start', 'period_end']);
            $table->index(['subject_type', 'subject_id', 'usage_meter_id'], 'pm_usage_subject_meter_idx');
        });

        Schema::create($this->table('subscription_assignments'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->foreignId('plan_id')->constrained($this->table('plans'))->cascadeOnDelete();
            $table->foreignId('plan_version_id')->constrained($this->table('plan_versions'))->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->string('billing_cycle')->nullable()->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('trial_type')->nullable()->index();
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable()->index();
            $table->json('trial_usage_limits')->nullable();
            $table->json('trial_usage_consumed')->nullable();
            $table->timestamp('trial_ended_at')->nullable()->index();
            $table->string('trial_end_reason')->nullable();
            $table->string('external_subscription_id')->nullable()->index();
            $table->string('billing_provider')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id', 'status'], 'pm_assign_subject_status_idx');
            $table->index(['plan_id']);
            $table->index(['plan_version_id']);
        });

        Schema::create($this->table('plan_rules'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('rule_type')->index();
            $table->json('conditions_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->integer('priority')->default(100)->index();
            $table->string('stacking_policy')->default('can_stack');
            $table->string('status')->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('entitlement_snapshots'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->foreignId('subscription_assignment_id')->nullable()->constrained($this->table('subscription_assignments'))->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained($this->table('plans'))->nullOnDelete();
            $table->foreignId('plan_version_id')->nullable()->constrained($this->table('plan_versions'))->nullOnDelete();
            $table->json('entitlements');
            $table->json('usage_summary')->nullable();
            $table->json('trial')->nullable();
            $table->json('addons')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create($this->table('plan_audit_logs'), function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('actor_type')->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->string('event')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'plan_audit_logs',
            'entitlement_snapshots',
            'plan_rules',
            'subscription_assignments',
            'usage_records',
            'usage_meters',
            'plan_addons',
            'addons',
            'plan_feature_values',
            'features',
            'plan_versions',
            'plans',
        ] as $table) {
            Schema::dropIfExists($this->table($table));
        }
    }
};
