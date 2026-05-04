<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use FrozonFreak\PlanManager\Facades\PlanManager;
use FrozonFreak\PlanManager\Models\UsageCorrection;
use FrozonFreak\PlanManager\Models\UsageMeter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UsageCorrectionController
{
    public function index(): View
    {
        return view('plan-manager::admin.usage-corrections.index', [
            'corrections' => UsageCorrection::query()->with('meter')->latest()->paginate(20),
            'meters' => UsageMeter::query()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'meter_code' => ['required', 'string'],
            'type' => ['required', 'in:adjustment,set_to'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['required', 'string'],
        ]);

        $subject = $this->resolveSubject($data['subject_type'], $data['subject_id']);
        abort_unless($subject, 404, 'Subject not found.');

        PlanManager::correctUsage($subject, $data['meter_code'], (float) $data['quantity'], $data['reason'], [
            'type' => $data['type'],
            'actor' => $request->user(),
        ]);

        return back()->with('status', 'Usage correction recorded.');
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
