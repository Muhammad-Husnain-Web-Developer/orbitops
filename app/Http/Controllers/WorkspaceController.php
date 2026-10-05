<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function store(Request $request, CreateWorkspace $createWorkspace): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'industry' => ['nullable', 'string', 'max:80'],
            'accent' => ['nullable', Rule::in(Workspace::ACCENTS)],
        ]);

        $workspace = $createWorkspace->handle($request->user(), array_filter($data));

        $this->toast("Created {$workspace->name}", description: 'You are now working in your new workspace.');

        return redirect()->route('dashboard');
    }

    /**
     * Change the active tenant. Membership is checked explicitly because workspaces
     * themselves are not tenant-scoped.
     */
    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $membership = $user->memberships()->where('workspace_id', $workspace->id)->where('status', 'active')->first();

        abort_if($membership === null, 403);

        $user->switchWorkspace($workspace);

        $this->toast("Switched to {$workspace->name}", 'info');

        return redirect()->route($membership->isClient() ? 'portal.dashboard' : 'dashboard');
    }
}
