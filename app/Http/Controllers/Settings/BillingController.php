<?php

namespace App\Http\Controllers\Settings;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Invitation;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(): Response
    {
        $workspace = $this->workspace();
        $this->authorize('manageBilling', $workspace);

        return inertia('Settings/Billing', [
            'plans' => collect(config('orbitops.plans'))->map(fn ($plan, $key) => [...$plan, 'key' => $key])->values(),
            'current' => ['plan' => $workspace->plan, 'cycle' => $workspace->billing_cycle],
            'usage' => $this->usage(),
        ]);
    }

    /**
     * Switch plan. There is no payment provider in this build, so the change applies
     * immediately and no card is charged; the UI says so.
     */
    public function update(Request $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manageBilling', $workspace);

        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('orbitops.plans')))],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        // Don't allow a downgrade that the workspace has already outgrown.
        $limits = config("orbitops.plans.{$data['plan']}.limits");
        $usage = $this->usage();
        $problems = collect([
            'members' => $limits['members'] !== null && $usage['members'] > $limits['members'] ? "{$usage['members']} team members (the plan allows {$limits['members']})" : null,
            'projects' => $limits['projects'] !== null && $usage['projects'] > $limits['projects'] ? "{$usage['projects']} active projects (the plan allows {$limits['projects']})" : null,
            'storage' => $usage['storage_bytes'] > $limits['storage_gb'] * 1024 ** 3 ? 'more files than its storage allows' : null,
        ])->filter();

        if ($problems->isNotEmpty()) {
            throw ValidationException::withMessages(['plan' => 'This workspace has '.$problems->join(', ', ' and ').'. Reduce usage before switching.']);
        }

        $workspace->update(['plan' => $data['plan'], 'billing_cycle' => $data['cycle']]);
        Activity::record('workspace.plan', 'switched the workspace to the '.config("orbitops.plans.{$data['plan']}.name").' plan');

        $this->toast('Plan updated', description: config("orbitops.plans.{$data['plan']}.name").', billed '.$data['cycle'].'.');

        return back();
    }

    /**
     * @return array<string, int>
     */
    protected function usage(): array
    {
        $workspace = $this->workspace();

        return [
            'members' => $workspace->teamMembers()->count() + Invitation::pending()->where('role', '!=', 'client')->count(),
            'projects' => Project::whereIn('status', ProjectStatus::open())->count(),
            'storage_bytes' => (int) Attachment::sum('size'),
        ];
    }
}
