<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        $ability = 'workspace:'.$this->workspace()->id;

        return inertia('Settings/Api', [
            'tokens' => $request->user()->tokens()->latest()->get()
                ->filter(fn (PersonalAccessToken $token) => in_array($ability, $token->abilities ?? [], true))
                ->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => array_values(array_intersect($token->abilities ?? [], ['read', 'write'])),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'created_at' => $token->created_at?->toIso8601String(),
                ])->values(),
            'baseUrl' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(['read', 'write'])],
        ]);

        // A write token can always read; every token is bound to this workspace.
        $abilities = array_values(array_unique([...$data['abilities'], 'read', 'workspace:'.$this->workspace()->id]));
        $token = $request->user()->createToken($data['name'], $abilities);

        // Shown once; only a hash is stored.
        Inertia::flash('token', ['name' => $data['name'], 'plain' => $token->plainTextToken]);
        $this->toast('API token created', description: 'Copy it now. It won’t be shown again.');

        return back();
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->firstOrFail()->delete();
        $this->toast('API token revoked', 'info');

        return back();
    }
}
