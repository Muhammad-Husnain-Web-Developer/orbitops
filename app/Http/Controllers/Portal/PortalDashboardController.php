<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Http\Resources\InvoiceResource;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

class PortalDashboardController extends PortalController
{
    public function __invoke(Request $request): Response
    {
        $client = $this->client();
        $projectIds = $this->projects()->pluck('id');
        $clientUsers = $this->clientUserIds();

        return inertia('Portal/Dashboard', [
            'client' => $client->only(['id', 'name']),
            'projects' => $this->projects()->withProgress()->with(['milestones', 'owner'])
                ->whereIn('status', ProjectStatus::open())->orderByRaw('due_date is null')->orderBy('due_date')->get()
                ->map(fn (Project $project) => PortalPresenter::project($project)),
            'approvals' => Milestone::with('project:id,name,color')->whereIn('project_id', $projectIds)
                ->where('requires_approval', true)->where('approval_status', 'pending')->orderBy('due_date')->get()
                ->map(fn (Milestone $milestone) => PortalPresenter::milestone($milestone)),
            'invoices' => InvoiceResource::collection(Invoice::where('client_id', $client->id)->whereIn('status', InvoiceStatus::outstanding())->orderBy('due_date')->get()),
            'balance' => round((float) Invoice::where('client_id', $client->id)->whereIn('status', InvoiceStatus::outstanding())->selectRaw('COALESCE(SUM(total - amount_paid), 0) as v')->value('v'), 2),
            'messages' => Comment::with('author')->where('commentable_type', (new Project)->getMorphClass())->whereIn('commentable_id', $projectIds)
                ->latest()->limit(4)->get()->map(fn (Comment $comment) => PortalPresenter::message($comment, $clientUsers, $request->user()->id)),
            'files' => Attachment::with(['project:id,name,color', 'uploader'])->where('client_id', $client->id)->where('visible_to_client', true)
                ->latest()->limit(5)->get()->map(fn (Attachment $file) => PortalPresenter::file($file)),
            'projectNames' => $this->projects()->pluck('name', 'id'),
        ]);
    }
}
