<?php

namespace App\Http\Controllers;

use App\Events\CommentPosted;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $comment = $task->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        CommentPosted::dispatch($comment);
        Activity::record('comment.created', 'commented on', $task);

        return back();
    }
}
