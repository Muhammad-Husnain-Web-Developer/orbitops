<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Event prefixes grouped into the filters people actually think in.
     */
    protected const TYPES = [
        'tasks' => ['label' => 'Tasks', 'events' => ['task.']],
        'projects' => ['label' => 'Projects & milestones', 'events' => ['project.', 'milestone.']],
        'conversation' => ['label' => 'Comments & messages', 'events' => ['comment.', 'message.']],
        'finance' => ['label' => 'Invoices & expenses', 'events' => ['invoice.', 'expense.']],
        'files' => ['label' => 'Files', 'events' => ['file.']],
        'time' => ['label' => 'Time', 'events' => ['time.']],
        'people' => ['label' => 'Clients & team', 'events' => ['client.', 'member.']],
    ];

    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->can('activity.view'), 403);

        $filters = [
            'type' => array_key_exists((string) $request->query('type'), self::TYPES) ? (string) $request->query('type') : '',
            'person' => (string) $request->query('person', ''),
            'project' => (string) $request->query('project', ''),
        ];

        $query = Activity::query()
            ->with('causer')
            ->when($filters['type'], fn ($query, $type) => $query->where(function ($inner) use ($type) {
                foreach (self::TYPES[$type]['events'] as $prefix) {
                    $inner->orWhere('event', 'like', $prefix.'%');
                }
            }))
            ->when(is_numeric($filters['person']), fn ($query) => $query->where('causer_id', $filters['person']))
            ->when(is_numeric($filters['project']), fn ($query) => $query->where('project_id', $filters['project']))
            ->visibleTo($request->user())
            ->latest()
            ->latest('id');

        return inertia('Activity/Index', [
            'activities' => Inertia::scroll(fn () => ActivityResource::collection($query->paginate(25))),
            'filters' => $filters,
            'types' => collect(self::TYPES)->map(fn ($type, $value) => ['value' => $value, 'label' => $type['label']])->values(),
            'people' => fn () => $this->workspace()->users()->orderBy('name')->get()->map->only(['id', 'name']),
            'projects' => fn () => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
