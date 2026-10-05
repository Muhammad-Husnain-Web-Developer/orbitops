<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global search for the command palette. Every query runs inside the active
 * workspace (tenant scope) and each group is gated by the user's permissions.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $user = $request->user();
        $groups = [];

        if ($user->can('clients.view')) {
            $groups[] = $this->group('clients', 'Clients', Client::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('contact_name', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(5)->get()
                ->map(fn (Client $client) => [
                    'id' => "client-{$client->id}",
                    'title' => $client->name,
                    'subtitle' => collect([$client->industry, $client->contact_name])->filter()->implode(' · '),
                    'url' => route('clients.show', $client),
                ]));
        }

        if ($user->can('projects.view')) {
            $groups[] = $this->group('projects', 'Projects', Project::query()->with('client:id,name')
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->limit(5)->get()
                ->map(fn (Project $project) => [
                    'id' => "project-{$project->id}",
                    'title' => $project->name,
                    'subtitle' => collect([$project->code, $project->client?->name, $project->status->label()])->filter()->implode(' · '),
                    'url' => route('projects.show', $project),
                    'color' => $project->color,
                ]));
        }

        if ($user->can('tasks.view')) {
            // Also match task keys such as "WEB-12".
            [$code, $number] = array_pad(explode('-', strtoupper($term), 2), 2, null);

            $groups[] = $this->group('tasks', 'Tasks', Task::query()->with('project:id,name,code')
                ->where(function ($query) use ($like, $code, $number) {
                    $query->where('title', 'like', $like);

                    if ($number !== null && ctype_digit($number)) {
                        $query->orWhere(fn ($key) => $key->where('number', (int) $number)->whereHas('project', fn ($project) => $project->where('code', $code)));
                    }
                })
                ->orderByRaw("status = 'done'")
                ->limit(6)->get()
                ->map(fn (Task $task) => [
                    'id' => "task-{$task->id}",
                    'title' => $task->title,
                    'subtitle' => $task->key().' · '.$task->project->name.' · '.$task->status->label(),
                    'url' => route('tasks.index', ['task' => $task->id]),
                ]));
        }

        if ($user->can('invoices.view')) {
            $groups[] = $this->group('invoices', 'Invoices', Invoice::query()->with('client:id,name')
                ->where(fn ($query) => $query->where('number', 'like', $like)->orWhereHas('client', fn ($client) => $client->where('name', 'like', $like)))
                ->latest('issue_date')->limit(5)->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => "invoice-{$invoice->id}",
                    'title' => $invoice->number,
                    'subtitle' => $invoice->client->name.' · '.Money::format($invoice->total, $invoice->currency).' · '.$invoice->status->label(),
                    'url' => route('invoices.show', $invoice),
                ]));
        }

        if ($user->can('team.view')) {
            $groups[] = $this->group('members', 'Members', $this->workspace()->teamMembers()
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(5)->get()
                ->map(fn ($member) => [
                    'id' => "member-{$member->id}",
                    'title' => $member->name,
                    'subtitle' => $member->pivot->title ?? $member->email,
                    'url' => route('team.index', ['member' => $member->id]),
                    'avatar' => $member->only(['name', 'initials', 'avatar_url']),
                ]));
        }

        return response()->json(['groups' => array_values(array_filter($groups, fn ($group) => count($group['items'])))]);
    }

    /**
     * @param  iterable<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    protected function group(string $key, string $label, iterable $items): array
    {
        return ['key' => $key, 'label' => $label, 'items' => collect($items)->values()->all()];
    }
}
