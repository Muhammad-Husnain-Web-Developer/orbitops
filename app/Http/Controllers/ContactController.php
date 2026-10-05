<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function store(ContactRequest $request): RedirectResponse
    {
        // Bots fill every field; people never see the hidden "website" input.
        if (filled($request->input('website'))) {
            return back();
        }

        ContactMessage::create([
            ...$request->safe()->except('website'),
            'ip_address' => $request->ip(),
        ]);

        $this->toast('Message sent', description: 'We will reply within one business day.');

        return back();
    }
}
