<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for in-app notifications: honours each user's channel preferences and
 * renders one payload for the database, realtime broadcast and email.
 */
abstract class WorkspaceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Skip quietly if the subject was deleted before the job ran.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Preference key from User::NOTIFICATION_TYPES.
     */
    abstract public function type(): string;

    /**
     * @return array{title: string, body?: string|null, url?: string|null, actor?: User|null, workspace_id?: int|null}
     */
    abstract protected function content(object $notifiable): array;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['mail'];
        }

        $channels = [];

        if ($notifiable->wantsNotification($this->type(), 'database')) {
            array_push($channels, 'database', 'broadcast');
        }

        if ($notifiable->wantsNotification($this->type(), 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $content = $this->content($notifiable);
        $actor = $content['actor'] ?? null;

        $payload = [
            'type' => $this->type(),
            'title' => $content['title'],
            'body' => $content['body'] ?? null,
            'url' => $content['url'] ?? null,
            'actor' => $actor ? [
                'name' => $actor->name,
                'initials' => $actor->initials,
                'avatar_url' => $actor->avatar_url,
            ] : null,
        ];

        // Omit rather than store null: a JSON null is not SQL NULL when filtering by workspace.
        if (! empty($content['workspace_id'])) {
            $payload['workspace_id'] = $content['workspace_id'];
        }

        return $payload;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->toArray($notifiable),
            'id' => $this->id,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content($notifiable);

        $message = (new MailMessage)
            ->subject($content['title'])
            ->greeting($content['title']);

        if (! empty($content['body'])) {
            $message->line($content['body']);
        }

        if (! empty($content['url'])) {
            $message->action('Open in OrbitOps', $content['url']);
        }

        return $message->line('You can change which emails you receive in Settings → Notifications.');
    }
}
