<?php

namespace App\Http\Controllers;

use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Activity;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    use ValidatesTenantReferences;

    /**
     * Start a timer. Only one runs at a time, so any running timer is stopped first.
     */
    public function start(Request $request): RedirectResponse
    {
        $this->authorize('create', TimeEntry::class);

        $data = $request->validate([
            'project_id' => ['required', $this->inWorkspace('projects')],
            'task_id' => ['nullable', $this->inWorkspace('tasks')->where('project_id', $request->integer('project_id'))],
            'description' => ['nullable', 'string', 'max:190'],
            'billable' => ['boolean'],
        ]);

        $this->stopRunning($request);

        $entry = TimeEntry::create([
            ...$data,
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'billable' => $request->boolean('billable', true),
        ]);

        $this->toast('Timer started', description: $entry->task?->title ?? $entry->description ?? $entry->project->name);

        return back();
    }

    public function stop(Request $request): RedirectResponse
    {
        $entry = $this->stopRunning($request);

        $entry
            ? $this->toast('Timer stopped', description: $entry->durationLabel().' logged on '.$entry->project->name)
            : $this->toast('No timer is running', 'info');

        return back();
    }

    protected function stopRunning(Request $request): ?TimeEntry
    {
        $entry = TimeEntry::running()->where('user_id', $request->user()->id)->first();

        if (! $entry) {
            return null;
        }

        $entry->stop();

        Activity::record('time.logged', 'logged '.$entry->durationLabel().' on', $entry->task ?? $entry->project);

        return $entry;
    }
}
