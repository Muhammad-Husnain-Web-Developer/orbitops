<?php

namespace App\Http\Middleware;

use App\Enums\ClientStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Http\Resources\TimeEntryResource;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\CurrentWorkspace;
use App\Support\NotificationFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $current = app(CurrentWorkspace::class);

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'realtime' => config('broadcasting.default') === 'reverb',
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'title' => $user->title,
                    'initials' => $user->initials,
                    'avatar_url' => $user->avatar_url,
                    'theme' => $user->theme,
                    'timezone' => $user->timezone,
                    'email_verified' => $user->hasVerifiedEmail(),
                    'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                ] : null,
            ],
            'workspace' => fn () => $current->check() ? [
                'id' => $current->get()->id,
                'name' => $current->get()->name,
                'slug' => $current->get()->slug,
                'industry' => $current->get()->industry,
                'initials' => $current->get()->initials,
                'accent' => $current->get()->accent,
                'logo_url' => $current->get()->logo_url,
                'currency' => $current->get()->currency,
                'plan' => $current->get()->plan,
                'is_demo' => $current->get()->is_demo,
            ] : null,
            'workspaces' => fn () => $user && $current->check() ? $this->workspaceSwitcher($user) : [],
            'access' => fn () => $user && $current->check() ? $this->access($user, $current) : null,
            'notifications' => fn () => $user && $current->check() ? [
                'unread' => NotificationFeed::query($user, $current->id())->whereNull('read_at')->count(),
            ] : null,
            'timer' => fn () => $this->isTeamMember($user, $current) ? $this->runningTimer($user) : null,
            'counts' => fn () => $this->isTeamMember($user, $current) ? [
                'my_tasks' => Task::open()->where('assignee_id', $user->id)->count(),
                'overdue_invoices' => $user->can('invoices.view') ? Invoice::where('status', 'overdue')->count() : 0,
            ] : null,
        ];
    }

    /**
     * Static option lists, sent once and cached by the client across visits.
     *
     * @return array<string, mixed>
     */
    public function shareOnce(Request $request): array
    {
        return [
            'enums' => fn () => [
                'taskStatus' => TaskStatus::options(),
                'taskPriority' => TaskPriority::options(),
                'projectStatus' => ProjectStatus::options(),
                'invoiceStatus' => InvoiceStatus::options(),
                'expenseStatus' => ExpenseStatus::options(),
                'expenseCategory' => ExpenseCategory::options(),
                'clientStatus' => ClientStatus::options(),
                'role' => WorkspaceRole::options(),
            ],
        ];
    }

    protected function isTeamMember(?User $user, CurrentWorkspace $current): bool
    {
        return $user !== null && $current->check() && ! $user->membershipFor($current->id())?->isClient();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function runningTimer(User $user): ?array
    {
        $entry = TimeEntry::running()->where('user_id', $user->id)->with(['project:id,name,color', 'task:id,title'])->first();

        return $entry ? (new TimeEntryResource($entry))->resolve() : null;
    }

    /**
     * Every workspace the user belongs to, with their role in each, for the switcher.
     *
     * @return list<array<string, mixed>>
     */
    protected function workspaceSwitcher(User $user): array
    {
        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->id)
            ->pluck('roles.name', 'model_has_roles.workspace_id');

        return $user->memberships
            ->map(fn ($membership) => [
                'id' => $membership->workspace->id,
                'name' => $membership->workspace->name,
                'industry' => $membership->workspace->industry,
                'initials' => $membership->workspace->initials,
                'accent' => $membership->workspace->accent,
                'logo_url' => $membership->workspace->logo_url,
                'role' => $roles[$membership->workspace_id] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * The user's role and permission names in the active workspace. Used only to
     * hide actions in the UI; every action is authorized again on the server.
     *
     * @return array<string, mixed>
     */
    protected function access(User $user, CurrentWorkspace $current): array
    {
        $membership = $user->membershipFor($current->id());

        return [
            'role' => $user->getRoleNames()->first(),
            'is_client' => (bool) $membership?->isClient(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
