<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->memberships()->exists()) {
            return redirect()->route('dashboard');
        }

        return inertia('Onboarding/CreateWorkspace', ['accents' => Workspace::ACCENTS]);
    }

    public function store(Request $request, CreateWorkspace $createWorkspace): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'industry' => ['nullable', 'string', 'max:80'],
            'accent' => ['required', Rule::in(Workspace::ACCENTS)],
        ]);

        $workspace = $createWorkspace->handle($request->user(), $data);

        $this->toast("{$workspace->name} is ready", description: 'Start by adding your first client or project.');

        return redirect()->route('dashboard');
    }
}
