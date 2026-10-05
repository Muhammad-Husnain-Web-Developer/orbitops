<?php

namespace App\Http\Controllers;

use App\Events\CommentPosted;
use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMessageController extends Controller
{
    /**
     * Team reply on the client-visible project thread.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $comment = $project->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        CommentPosted::dispatch($comment);
        Activity::record('message.posted', 'replied to the client on', $project);

        return back();
    }
}
