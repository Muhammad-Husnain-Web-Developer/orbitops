<?php

namespace App\Http\Controllers\Settings;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Roles whose permissions can be tailored. The owner always has everything and
     * clients only ever see the portal.
     */
    protected const EDITABLE = ['admin', 'manager', 'member'];

    public function index(): Response
    {
        $workspace = $this->workspace();
        $this->authorize('manageRoles', $workspace);

        $roles = Role::with('permissions')->where('workspace_id', $workspace->id)->get()->keyBy('name');
        $counts = DB::table('model_has_roles')->where('workspace_id', $workspace->id)->selectRaw('role_id, count(*) as total')->groupBy('role_id')->pluck('total', 'role_id');

        return inertia('Settings/Roles', [
            'roles' => collect(WorkspaceRole::cases())->filter(fn ($role) => $roles->has($role->value))->map(fn (WorkspaceRole $role) => [
                'id' => $roles[$role->value]->id,
                'name' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'editable' => in_array($role->value, self::EDITABLE, true),
                'members' => (int) ($counts[$roles[$role->value]->id] ?? 0),
                'permissions' => $roles[$role->value]->permissions->pluck('name')->values(),
                'defaults' => $role->defaultPermissions(),
            ])->values(),
            'groups' => collect(PermissionCatalog::groups())->map(fn ($group, $key) => [
                'key' => $key,
                'label' => $group['label'],
                'permissions' => collect($group['permissions'])->map(fn ($label, $name) => compact('name', 'label'))->values(),
            ])->values(),
        ]);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manageRoles', $workspace);

        // Roles are per workspace; never touch another workspace's role by id.
        $model = Role::where('workspace_id', $workspace->id)->findOrFail($role);
        abort_unless(in_array($model->name, self::EDITABLE, true), 403, 'This role cannot be changed.');

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::all())],
        ]);

        $model->syncPermissions($data['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->toast(WorkspaceRole::from($model->name)->label().' permissions saved', description: count($data['permissions']).' of '.count(PermissionCatalog::all()).' permissions enabled.');

        return back();
    }
}
