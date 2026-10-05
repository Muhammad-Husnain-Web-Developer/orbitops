<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class MemberSettingsController extends Controller
{
    /**
     * Members are managed on the Team page; settings links there so there's one place to do it.
     */
    public function index(): RedirectResponse
    {
        return to_route('team.index');
    }
}
