<?php

namespace App\Http\Controllers;

use App\Http\Requests\MilestoneRequest;
use App\Models\Activity;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class MilestoneController extends Controller
{
    public function store(MilestoneRequest $request, Project $project): RedirectResponse
    {
        $milestone = $project->milestones()->create([
            ...$request->validated(),
            'position' => (int) $project->milestones()->max('position') + 1,
        ]);

        Activity::record('milestone.created', 'added milestone', $milestone);
        $this->toast('Milestone added');

        return back();
    }

    public function update(MilestoneRequest $request, Milestone $milestone): RedirectResponse
    {
        $data = $request->validated();
        $completing = ($data['status'] ?? null) === 'completed' && $milestone->status !== 'completed';

        $milestone->fill($data);
        $milestone->completed_at = $milestone->status === 'completed' ? ($milestone->completed_at ?? now()) : null;

        // A completed milestone that needs sign-off goes to the client portal for approval.
        if ($completing && $milestone->requires_approval && $milestone->approval_status !== 'approved') {
            $milestone->approval_status = 'pending';
        }

        $milestone->save();

        if ($completing) {
            Activity::record('milestone.completed', 'completed milestone', $milestone);
            $this->toast('Milestone completed', description: $milestone->requires_approval ? 'Sent to the client portal for approval.' : null);
        } else {
            $this->toast('Milestone updated');
        }

        return back();
    }

    public function destroy(Milestone $milestone): RedirectResponse
    {
        $this->authorize('delete', $milestone);

        $milestone->delete();
        $this->toast('Milestone removed');

        return back();
    }
}
