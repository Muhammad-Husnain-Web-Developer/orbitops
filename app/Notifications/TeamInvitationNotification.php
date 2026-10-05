<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;

class TeamInvitationNotification extends WorkspaceNotification
{
    public function __construct(public Invitation $invitation) {}

    public function type(): string
    {
        return 'team_invitation';
    }

    protected function content(object $notifiable): array
    {
        $this->invitation->loadMissing('workspace', 'inviter');

        return [
            'title' => "You're invited to join {$this->invitation->workspace->name}",
            'body' => ($this->invitation->inviter?->name ?? 'A teammate').' invited you as '.ucfirst($this->invitation->role).'.',
            'url' => route('invitations.show', $this->invitation->token),
            'actor' => $this->invitation->inviter,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content($notifiable);

        return (new MailMessage)
            ->subject($content['title'])
            ->greeting($content['title'])
            ->line($content['body'])
            ->action('Accept invitation', $content['url'])
            ->line('This invitation expires '.$this->invitation->expires_at->diffForHumans().'.');
    }
}
