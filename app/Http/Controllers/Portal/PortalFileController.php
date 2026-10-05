<?php

namespace App\Http\Controllers\Portal;

use App\Models\Attachment;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalFileController extends PortalController
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'project' => (string) $request->query('project', ''),
        ];

        $files = Attachment::with(['project:id,name,color', 'uploader'])
            ->where('client_id', $this->clientId())
            ->where('visible_to_client', true)
            ->when($filters['search'], fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(is_numeric($filters['project']), fn ($query) => $query->where('project_id', $filters['project']))
            ->latest()
            ->get();

        return inertia('Portal/Files', [
            'files' => $files->map(fn (Attachment $file) => PortalPresenter::file($file)),
            'filters' => $filters,
            'projects' => $this->projects()->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->ensureOwn($attachment->client_id);
        abort_unless($attachment->visible_to_client, 404);

        return $attachment->downloadResponse();
    }
}
