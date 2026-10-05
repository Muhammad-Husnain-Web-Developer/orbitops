<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    /**
     * Sign a visitor into the seeded demo workspace as a team owner or a client.
     */
    public function __invoke(Request $request, string $persona = 'team'): RedirectResponse
    {
        abort_unless(config('orbitops.demo_login'), 404);

        $user = User::where('email', $persona === 'client' ? 'client@orbitops.app' : 'demo@orbitops.app')->first();

        if (! $user) {
            $this->toast('The demo workspace is not set up', 'warning', 'Run "php artisan db:seed" to create it.');

            return back();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $this->toast('Welcome to the OrbitOps demo', 'info', $persona === 'client' ? 'You are viewing the client portal as Northstar Media.' : 'You are signed in as the owner of Acme Studio.');

        return redirect()->route($persona === 'client' ? 'portal.dashboard' : 'dashboard');
    }
}
