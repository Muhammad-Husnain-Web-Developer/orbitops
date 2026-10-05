<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientNoteController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $client->notes()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        $this->toast('Note added');

        return back();
    }
}
