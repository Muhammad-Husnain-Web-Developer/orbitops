<?php

namespace App\Http\Controllers\Portal;

use App\Events\CommentPosted;
use App\Events\MilestoneReviewed;
use App\Models\Activity;
use App\Models\Milestone;
use App\Support\PortalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;

class PortalApprovalController extends PortalController
{
    public function index(): Response
    {
        $milestones = Milestone::with('project:id,name,color')
            ->whereIn('project_id', $this->projects()->select('id'))
            ->where('requires_approval', true)
            ->whereNotNull('approval_status')
            ->orderByRaw("approval_status = 'pending' desc")
            ->latest('updated_at')
            ->get();

        return inertia('Portal/Approvals', [
            'pending' => $milestones->where('approval_status', 'pending')->map(fn ($milestone) => PortalPresenter::milestone($milestone))->values(),
            'history' => $milestones->where('approval_status', '!=', 'pending')->map(fn ($milestone) => PortalPresenter::milestone($milestone))->values(),
        ]);
    }

    /**
     * Approve a delivered milestone, or send it back with what needs to change.
     */
    public function review(Request $request, Milestone $milestone): RedirectResponse
    {
        $milestone->loadMissing('project');
        $this->ensureOwn($milestone->project?->client_id);

        if (! $milestone->requires_approval || $milestone->approval_status !== 'pending') {
            $this->toast('This milestone is not waiting for your review', 'info');

            return back();
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'changes_requested'])],
            'note' => ['nullable', 'required_if:decision,changes_requested', 'string', 'max:2000'],
        ], ['note.required_if' => 'Tell the team what should change.']);

        $user = $request->user();
        $approved = $data['decision'] === 'approved';

        DB::transaction(function () use ($milestone, $data, $user, $approved) {
            $milestone->forceFill([
                'approval_status' => $data['decision'],
                'approval_note' => $data['note'] ?? null,
                'approved_by' => $approved ? $user->id : null,
                'approved_at' => $approved ? now() : null,
                // Requested changes reopen the milestone for the team.
                'status' => $approved ? $milestone->status : 'in_progress',
                'completed_at' => $approved ? $milestone->completed_at : null,
            ])->save();

            Activity::record($approved ? 'milestone.approved' : 'milestone.changes_requested', $approved ? 'approved milestone' : 'requested changes on', $milestone);

            if (filled($data['note'] ?? null)) {
                $comment = $milestone->project->messages()->create([
                    'user_id' => $user->id,
                    'body' => ($approved ? "Approved “{$milestone->name}”: " : "Changes requested on “{$milestone->name}”: ").$data['note'],
                ]);
                CommentPosted::dispatch($comment);
            }
        });

        MilestoneReviewed::dispatch($milestone, $user);

        $this->toast($approved ? 'Milestone approved' : 'Changes requested', description: $approved ? 'Thanks! The team has been notified.' : 'The team will pick this up and let you know.');

        return back();
    }
}
