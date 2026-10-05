<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Membership;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base for the client portal. On top of the workspace scope, everything here is
 * limited to the signed-in contact's own client: other clients' projects, files
 * and invoices in the same workspace must never be reachable.
 */
abstract class PortalController extends Controller
{
    protected function clientId(): int
    {
        return (int) request()->attributes->get('portal_client_id');
    }

    protected function client(): Client
    {
        return Client::findOrFail($this->clientId());
    }

    /**
     * @return Builder<Project>
     */
    protected function projects(): Builder
    {
        return Project::query()->where('client_id', $this->clientId());
    }

    /**
     * Everyone with portal access in this workspace, to tell client messages from team replies.
     *
     * @return list<int>
     */
    protected function clientUserIds(): array
    {
        return Membership::where('workspace_id', $this->workspace()->id)->whereNotNull('client_id')->pluck('user_id')->all();
    }

    /**
     * 404 (not 403) for anything belonging to another client, so its existence isn't revealed.
     */
    protected function ensureOwn(?int $clientId): void
    {
        abort_unless($clientId !== null && $clientId === $this->clientId(), 404);
    }
}
