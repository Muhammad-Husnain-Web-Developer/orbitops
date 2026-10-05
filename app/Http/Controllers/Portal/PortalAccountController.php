<?php

namespace App\Http\Controllers\Portal;

use App\Support\AccountSettings;
use Illuminate\Http\Request;
use Inertia\Response;

class PortalAccountController extends PortalController
{
    /**
     * Profile, password, two-factor and sessions for client portal users.
     */
    public function __invoke(Request $request): Response
    {
        return inertia('Portal/Account', [
            'profile' => AccountSettings::profile($request->user()),
            'timezones' => fn () => AccountSettings::timezones(),
            ...AccountSettings::security($request),
            'theme' => $request->user()->theme ?? 'dark',
            'isDemo' => $request->user()->isDemo(),
        ]);
    }
}
