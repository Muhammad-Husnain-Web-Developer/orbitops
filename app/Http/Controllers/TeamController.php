<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\Client;
use App\Models\Invitation;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\Insights;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, Insights $insights): Response
    {
        abort_unless($request->user()->can('team.view'), 403);

        $workspace = $this->workspace();
        $canManage = $request->user()->can('manageMembers', $workspace);

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.workspace_id', $workspace->id)
            ->where('model_has_roles.model_type', $request->user()->getMorphClass())
            ->pluck('roles.name', 'model_has_roles.model_id');

        $from = now()->subWeeks(4)->startOfDay();
        $utilization = $insights->utilizationByMember($workspace, $from, now())->keyBy('id');
        $openTasks = Task::open()->whereNotNull('assignee_id')->selectRaw('assignee_id, count(*) as total')->groupBy('assignee_id')->pluck('total', 'assignee_id');
        $running = TimeEntry::running()->pluck('user_id')->flip();

        $people = $workspace->users()->orderBy('name')->get();
        $clientNames = Client::whereIn('id', $people->pluck('pivot.client_id')->filter())->pluck('name', 'id');
        $limit = config("orbitops.plans.{$workspace->plan}.limits.members");

        return inertia('Team/Index', [
            'members' => $people->filter(fn ($user) => $user->pivot->client_id === null)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'initials' => $user->initials,
                'avatar_url' => $user->avatar_url,
                'title' => $user->pivot->title,
                'role' => $roles[$user->id] ?? null,
                'is_owner' => $workspace->owner_id === $user->id,
                'is_me' => $user->id === $request->user()->id,
                'status' => $user->pivot->status,
                'weekly_capacity' => (int) $user->pivot->weekly_capacity,
                'joined_at' => $user->pivot->joined_at,
                'last_active_at' => $user->pivot->last_active_at ?? $user->last_active_at,
                'hours' => $utilization[$user->id]['hours'] ?? 0,
                'utilization' => $utilization[$user->id]['utilization'] ?? 0,
                'open_tasks' => (int) ($openTasks[$user->id] ?? 0),
                'tracking' => $running->has($user->id),
            ])->values(),
            'clients' => $people->filter(fn ($user) => $user->pivot->client_id !== null)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'initials' => $user->initials,
                'avatar_url' => $user->avatar_url,
                'client' => ['id' => $user->pivot->client_id, 'name' => $clientNames[$user->pivot->client_id] ?? 'Former client'],
                'last_active_at' => $user->pivot->last_active_at ?? $user->last_active_at,
            ])->values(),
            'invitations' => fn () => $canManage
                ? Invitation::pending()->with(['inviter:id,name', 'client:id,name'])->latest()->get()->map(fn (Invitation $invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'client' => $invitation->client?->name,
                    'invited_by' => $invitation->inviter?->name,
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at,
                    'url' => route('invitations.show', $invitation->token),
                ])
                : [],
            'seats' => [
                'used' => $people->whereNull('pivot.client_id')->count() + ($canManage ? Invitation::pending()->where('role', '!=', 'client')->count() : 0),
                'limit' => $limit,
                'plan' => config("orbitops.plans.{$workspace->plan}.name"),
            ],
            'roles' => collect([WorkspaceRole::Admin, WorkspaceRole::Manager, WorkspaceRole::Member])->map(fn ($role) => ['value' => $role->value, 'label' => $role->label(), 'description' => $role->description()]),
            'clientOptions' => fn () => $canManage ? Client::orderBy('name')->get(['id', 'name']) : [],
            'canManage' => $canManage,
        ]);
    }
}
