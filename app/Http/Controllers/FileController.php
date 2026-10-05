<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttachmentResource;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Support\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /** Uploads are stored privately; these types cover documents, design files, media and archives. */
    public const ALLOWED = 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,md,zip,fig,sketch,psd,ai,mp4,mov,json';

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Attachment::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'project' => (string) $request->query('project', ''),
            'kind' => (string) $request->query('kind', ''),
        ];

        $files = Attachment::query()
            ->with(['uploader', 'project:id,name,color', 'client:id,name'])
            ->when($filters['search'], fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['project'], fn ($query, $project) => $query->where('project_id', $project))
            ->latest()
            ->get()
            ->when($filters['kind'], fn ($files, $kind) => $files->filter(fn ($file) => $file->kind() === $kind)->values());

        return inertia('Files/Index', [
            'files' => AttachmentResource::collection($files),
            'filters' => $filters,
            'projects' => fn () => Project::orderBy('name')->get(['id', 'name', 'color']),
            'usage' => fn () => [
                'bytes' => (int) Attachment::sum('size'),
                'count' => Attachment::count(),
                'limit_gb' => config("orbitops.plans.{$this->workspace()->plan}.limits.storage_gb", 2),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attachment::class);

        $workspaceId = app(CurrentWorkspace::class)->id();

        $data = $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:20480', 'mimes:'.self::ALLOWED],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('workspace_id', $workspaceId)],
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('workspace_id', $workspaceId)],
            'task_id' => ['nullable', Rule::exists('tasks', 'id')->where('workspace_id', $workspaceId)],
            'visible_to_client' => ['boolean'],
        ], [
            'files.*.max' => 'Each file must be 20 MB or smaller.',
            'files.*.mimes' => 'That file type is not supported.',
        ]);

        $task = isset($data['task_id']) ? Task::find($data['task_id']) : null;
        $projectId = $data['project_id'] ?? $task?->project_id;
        $clientId = $data['client_id'] ?? ($projectId ? Project::find($projectId)?->client_id : null);

        $attachments = DB::transaction(fn () => collect($request->file('files'))->map(function ($file) use ($request, $workspaceId, $task, $projectId, $clientId, $data) {
            $name = $file->getClientOriginalName();
            $path = $file->storeAs("workspaces/{$workspaceId}/files", Str::uuid().'-'.Str::limit(Str::slug(pathinfo($name, PATHINFO_FILENAME)), 60, '').'.'.$file->getClientOriginalExtension(), 'local');

            return Attachment::create([
                'attachable_type' => $task ? 'task' : null,
                'attachable_id' => $task?->id,
                'project_id' => $projectId,
                'client_id' => $clientId,
                'uploaded_by' => $request->user()->id,
                'name' => $name,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'visible_to_client' => (bool) ($data['visible_to_client'] ?? false),
            ]);
        }));

        Activity::record('file.uploaded', 'uploaded', $attachments->first(), $attachments->count() > 1 ? ['label' => "{$attachments->count()} files"] : []);

        $this->toast($attachments->count() > 1 ? "{$attachments->count()} files uploaded" : 'File uploaded');

        return back();
    }

    public function update(Request $request, Attachment $attachment): RedirectResponse
    {
        $this->authorize('update', $attachment);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:190'],
            'visible_to_client' => ['sometimes', 'boolean'],
        ]);

        $attachment->update($data);

        if (array_key_exists('visible_to_client', $data)) {
            $this->toast($data['visible_to_client'] ? 'Shared with client' : 'Hidden from client', 'info');
        }

        return back();
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        $attachment->delete();

        $this->toast('File deleted');

        return back();
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return $attachment->downloadResponse();
    }
}
