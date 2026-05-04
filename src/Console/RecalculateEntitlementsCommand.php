<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Console;

use FrozonFreak\PlanManager\Actions\RecalculateEntitlementSnapshot;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class RecalculateEntitlementsCommand extends Command
{
    protected $signature = 'plan-manager:recalculate-entitlements {--subject-type=} {--subject-id=} {--all}';

    protected $description = 'Recalculate entitlement snapshots for one subject or all assigned subjects.';

    public function handle(RecalculateEntitlementSnapshot $action): int
    {
        if ($this->option('all')) {
            SubscriptionAssignment::query()->select(['subject_type', 'subject_id'])->distinct()->each(function ($row) use ($action): void {
                $subject = $this->resolveSubject($row->subject_type, (string) $row->subject_id);
                if ($subject) {
                    $action->handle($subject);
                }
            });
            $this->info('Entitlements recalculated.');

            return self::SUCCESS;
        }

        $subjectType = $this->option('subject-type');
        $subjectId = $this->option('subject-id');
        if (! $subjectType || ! $subjectId) {
            $this->error('Provide --all or both --subject-type and --subject-id.');

            return self::FAILURE;
        }

        $subject = $this->resolveSubject((string) $subjectType, (string) $subjectId);
        if (! $subject) {
            $this->error('Subject not found.');

            return self::FAILURE;
        }

        $action->handle($subject);
        $this->info('Entitlement recalculated.');

        return self::SUCCESS;
    }

    private function resolveSubject(string $type, string $id): ?Model
    {
        $class = Relation::getMorphedModel($type) ?? $type;
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($id);
    }
}
