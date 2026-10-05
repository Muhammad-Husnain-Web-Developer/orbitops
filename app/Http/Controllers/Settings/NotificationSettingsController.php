<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return inertia('Settings/Notifications', [
            'types' => collect(User::NOTIFICATION_TYPES)->map(fn ($type, $key) => ['key' => $key, 'label' => $type['label'], 'description' => $type['description']])->values(),
            'preferences' => $request->user()->resolvedNotificationPreferences(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = ['preferences' => ['required', 'array']];
        foreach (array_keys(User::NOTIFICATION_TYPES) as $type) {
            $rules["preferences.{$type}.database"] = ['boolean'];
            $rules["preferences.{$type}.mail"] = ['boolean'];
        }

        $data = $request->validate($rules);

        // Keep only known types and channels.
        $preferences = collect(User::NOTIFICATION_TYPES)->mapWithKeys(fn ($config, $type) => [$type => [
            'database' => (bool) data_get($data, "preferences.{$type}.database", true),
            'mail' => (bool) data_get($data, "preferences.{$type}.mail", false),
        ]])->all();

        $request->user()->update(['notification_preferences' => $preferences]);
        $this->toast('Notification preferences saved');

        return back();
    }
}
