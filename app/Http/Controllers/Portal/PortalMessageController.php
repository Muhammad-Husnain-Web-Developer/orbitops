<?php

namespace App\Http\Controllers\Portal;

use App\Events\CommentPosted;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Project;
use App\Support\PortalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PortalMessageController extends PortalController
{
    public function index(Request $request): Response
    {
        $projects = $this->projects()->orderByRaw('completed_at is not null')->orderBy('name')->get(['id', 'name', 'color', 'status', 'completed_at']);
        $morph = (new Project)->getMorphClass();

        $latest = Comment::with('author')->where('commentable_type', $morph)->whereIn('commentable_id', $projects->pluck('id'))
            ->latest()->get()->unique('commentable_id')->keyBy('commentable_id');

        $selected = $projects->firstWhere('id', $request->integer('project')) ?? $projects->sortByDesc(fn ($project) => $latest->get($project->id)?->created_at)->first();
        $clientUsers = $this->clientUserIds();

        return inertia('Portal/Messages', [
            'threads' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'last' => isset($latest[$project->id]) ? PortalPresenter::message($latest[$project->id], $clientUsers, $request->user()->id) : null,
            ])->sortByDesc(fn ($thread) => $thread['last']['created_at'] ?? '')->values(),
            'selected' => $selected?->id,
            'messages' => $selected
                ? $selected->messages()->with('author')->oldest()->get()->map(fn ($comment) => PortalPresenter::message($comment, $clientUsers, $request->user()->id))
                : [],
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->ensureOwn($project->client_id);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $comment = $project->messages()->create(['user_id' => $request->user()->id, 'body' => trim($data['body'])]);

        CommentPosted::dispatch($comment);
        Activity::record('message.posted', 'sent a message on', $project);

        return back();
    }
}
