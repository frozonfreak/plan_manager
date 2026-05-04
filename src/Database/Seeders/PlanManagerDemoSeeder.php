<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Database\Seeders;

use FrozonFreak\PlanManager\Enums\AddonType;
use FrozonFreak\PlanManager\Enums\FeatureType;
use FrozonFreak\PlanManager\Enums\PlanStatus;
use FrozonFreak\PlanManager\Enums\PlanVersionStatus;
use FrozonFreak\PlanManager\Enums\RuleStatus;
use FrozonFreak\PlanManager\Enums\RuleType;
use FrozonFreak\PlanManager\Enums\StackingPolicy;
use FrozonFreak\PlanManager\Enums\UsageResetPeriod;
use FrozonFreak\PlanManager\Models\Addon;
use FrozonFreak\PlanManager\Models\Feature;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanFeatureValue;
use FrozonFreak\PlanManager\Models\PlanRule;
use FrozonFreak\PlanManager\Models\PlanVersion;
use FrozonFreak\PlanManager\Models\UsageMeter;
use Illuminate\Database\Seeder;

final class PlanManagerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            'users.max' => ['Users', FeatureType::Integer],
            'projects.max' => ['Projects', FeatureType::Integer],
            'export.enabled' => ['Exports', FeatureType::Boolean],
            'api.enabled' => ['API', FeatureType::Boolean],
            'api_calls.monthly' => ['Monthly API calls', FeatureType::Integer],
            'storage.gb' => ['Storage GB', FeatureType::Integer],
            'support.level' => ['Support', FeatureType::String],
        ];

        foreach ($features as $code => [$name, $type]) {
            Feature::query()->updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }

        foreach (['api_calls', 'ai_tokens', 'consults'] as $meter) {
            UsageMeter::query()->updateOrCreate(['code' => $meter], [
                'name' => str($meter)->replace('_', ' ')->headline()->toString(),
                'reset_period' => UsageResetPeriod::Monthly,
                'aggregation' => 'sum',
            ]);
        }

        $values = [
            'free' => ['Free', 0, ['users.max' => 1, 'projects.max' => 3, 'export.enabled' => false, 'api.enabled' => false, 'api_calls.monthly' => 0, 'storage.gb' => 1, 'support.level' => 'community']],
            'pro' => ['Pro', 999, ['users.max' => 3, 'projects.max' => 50, 'export.enabled' => true, 'api.enabled' => true, 'api_calls.monthly' => 10000, 'storage.gb' => 10, 'support.level' => 'email']],
            'business' => ['Business', 4999, ['users.max' => 10, 'projects.max' => 500, 'export.enabled' => true, 'api.enabled' => true, 'api_calls.monthly' => 100000, 'storage.gb' => 100, 'support.level' => 'priority']],
            'enterprise' => ['Enterprise', null, ['users.max' => -1, 'projects.max' => -1, 'export.enabled' => true, 'api.enabled' => true, 'api_calls.monthly' => -1, 'storage.gb' => -1, 'support.level' => 'dedicated']],
        ];

        foreach ($values as $code => [$name, $price, $featureValues]) {
            $plan = Plan::query()->updateOrCreate(['code' => $code], ['name' => $name, 'status' => PlanStatus::Active]);
            $version = PlanVersion::query()->updateOrCreate(['plan_id' => $plan->id, 'version' => 1], [
                'status' => PlanVersionStatus::Active,
                'billing_cycle' => 'monthly',
                'display_price' => $price,
                'currency' => 'INR',
            ]);

            foreach ($featureValues as $featureCode => $value) {
                $feature = Feature::query()->where('code', $featureCode)->firstOrFail();
                PlanFeatureValue::query()->updateOrCreate([
                    'plan_version_id' => $version->id,
                    'feature_id' => $feature->id,
                ], ['value' => ['value' => $value]]);
            }
        }

        $advanced = Addon::query()->updateOrCreate(['code' => 'advanced_reports'], [
            'name' => 'Advanced Reports',
            'type' => AddonType::FeatureUnlock,
            'status' => PlanStatus::Active,
            'display_price' => 499,
            'currency' => 'INR',
        ]);
        $aiPack = Addon::query()->updateOrCreate(['code' => 'ai_credit_pack_10000'], [
            'name' => 'AI Credit Pack 10000',
            'type' => AddonType::UsagePack,
            'status' => PlanStatus::Active,
            'display_price' => 999,
            'currency' => 'INR',
        ]);

        PlanVersion::query()->each(function (PlanVersion $version) use ($advanced, $aiPack): void {
            $version->addons()->syncWithoutDetaching([
                $advanced->id => ['is_available' => $version->plan->code !== 'free'],
                $aiPack->id => ['is_available' => true],
            ]);
        });

        PlanRule::query()->updateOrCreate(['code' => 'pro-14-day-trial'], [
            'name' => 'Pro 14-day trial',
            'rule_type' => RuleType::Trial,
            'conditions_json' => ['all' => [
                ['field' => 'plan.code', 'operator' => '=', 'value' => 'pro'],
                ['field' => 'subject.has_used_trial', 'operator' => '=', 'value' => false],
            ]],
            'actions_json' => [['type' => 'start_trial', 'trial_type' => 'time_limited', 'days' => 14]],
            'priority' => 10,
            'stacking_policy' => StackingPolicy::CanStack,
            'status' => RuleStatus::Active,
        ]);

        PlanRule::query()->updateOrCreate(['code' => 'business-hybrid-trial'], [
            'name' => 'Business hybrid trial',
            'rule_type' => RuleType::Trial,
            'conditions_json' => ['all' => [['field' => 'plan.code', 'operator' => '=', 'value' => 'business']]],
            'actions_json' => [['type' => 'start_trial', 'trial_type' => 'time_and_usage_limited', 'days' => 14, 'usage_limits' => ['api_calls' => 1000]]],
            'priority' => 20,
            'stacking_policy' => StackingPolicy::CanStack,
            'status' => RuleStatus::Active,
        ]);

        PlanRule::query()->updateOrCreate(['code' => 'ai-addon-trial'], [
            'name' => 'AI add-on trial',
            'rule_type' => RuleType::TrialUsageLimit,
            'conditions_json' => ['all' => [['field' => 'addon.code', 'operator' => '=', 'value' => 'ai_credit_pack_10000']]],
            'actions_json' => [['type' => 'start_trial', 'trial_type' => 'usage_limited', 'usage_limits' => ['ai_tokens' => 50000]]],
            'priority' => 30,
            'stacking_policy' => StackingPolicy::CanStack,
            'status' => RuleStatus::Active,
        ]);
    }
}
