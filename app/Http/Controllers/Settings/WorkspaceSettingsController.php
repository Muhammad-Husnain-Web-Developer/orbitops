<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Support\AccountSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'PKR', 'AED', 'INR'];

    public function edit(): Response
    {
        $workspace = $this->workspace();
        $this->authorize('update', $workspace);

        return inertia('Settings/Workspace', [
            'settings' => [
                ...$workspace->only(['name', 'industry', 'accent', 'currency', 'timezone', 'invoice_prefix', 'payment_terms']),
                'default_tax_rate' => (float) $workspace->default_tax_rate,
                'logo_url' => $workspace->logo_url,
                'initials' => $workspace->initials,
            ],
            'accents' => Workspace::ACCENTS,
            'currencies' => self::CURRENCIES,
            'timezones' => fn () => AccountSettings::timezones(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('update', $workspace);

        $request->merge(['invoice_prefix' => strtoupper((string) $request->input('invoice_prefix'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'industry' => ['nullable', 'string', 'max:80'],
            'accent' => ['required', Rule::in(Workspace::ACCENTS)],
            'currency' => ['required', Rule::in(self::CURRENCIES)],
            'timezone' => ['required', 'timezone:all'],
            'invoice_prefix' => ['required', 'alpha_num:ascii', 'min:2', 'max:6'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['required', 'integer', 'min:0', 'max:120'],
            'logo' => ['nullable', File::image()->max(2 * 1024)],
            'remove_logo' => ['boolean'],
        ], ['invoice_prefix.alpha_num' => 'Use letters and numbers only, like ACM.']);

        $workspace->fill(collect($data)->except(['logo', 'remove_logo'])->all());

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($workspace->logo_path) {
                Storage::disk('public')->delete($workspace->logo_path);
            }

            $workspace->logo_path = $request->hasFile('logo') ? $request->file('logo')->store("logos/{$workspace->id}", 'public') : null;
        }

        $workspace->save();
        $this->toast('Workspace settings saved');

        return back();
    }
}
