<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\AccountSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return inertia('Settings/General', [
            'profile' => AccountSettings::profile($request->user()),
            'timezones' => fn () => AccountSettings::timezones(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'title' => ['nullable', 'string', 'max:80'],
            'timezone' => ['required', 'timezone:all'],
            'avatar' => ['nullable', File::image()->max(2 * 1024)->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000))],
            'remove_avatar' => ['boolean'],
        ]);

        $emailChanged = strcasecmp($data['email'], $user->email) !== 0;
        $user->fill(collect($data)->only(['name', 'email', 'title', 'timezone'])->all());

        if ($request->hasFile('avatar') || $request->boolean('remove_avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'public') : null;
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            $this->toast('Profile saved', 'info', "We sent a verification link to {$user->email}.");
        } else {
            $this->toast('Profile saved');
        }

        return back();
    }
}
