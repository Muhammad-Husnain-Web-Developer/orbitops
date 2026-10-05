<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function edit(Request $request): Response
    {
        return inertia('Settings/Appearance', [
            'theme' => $request->user()->theme ?? 'dark',
        ]);
    }

    /**
     * Saved from the theme toggle (JSON) and from the settings page.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::in(['light', 'dark', 'system'])]]);

        $request->user()->update(['theme' => $data['theme']]);

        if ($request->wantsJson()) {
            return response()->json(['theme' => $data['theme']]);
        }

        $this->toast('Appearance saved');

        return back();
    }
}
