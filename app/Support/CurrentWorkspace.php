<?php

namespace App\Support;

use App\Models\Workspace;

/**
 * Holds the active tenant for the current request (bound as a scoped singleton).
 * Setting it also scopes Spatie roles and permissions to that workspace.
 */
class CurrentWorkspace
{
    protected ?Workspace $workspace = null;

    public function set(?Workspace $workspace): void
    {
        $this->workspace = $workspace;

        setPermissionsTeamId($workspace?->id);
    }

    public function get(): ?Workspace
    {
        return $this->workspace;
    }

    public function id(): ?int
    {
        return $this->workspace?->id;
    }

    public function check(): bool
    {
        return $this->workspace !== null;
    }

    /**
     * Run a callback with a different workspace active, restoring the previous one afterwards.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runAs(Workspace $workspace, callable $callback): mixed
    {
        $previous = $this->workspace;
        $this->set($workspace);

        try {
            return $callback();
        } finally {
            $this->set($previous);
        }
    }
}
