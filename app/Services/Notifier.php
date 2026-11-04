<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Creates the in-app notifications for the events people care about.
 * A notification problem must never break the action that triggered it, so every send is guarded.
 */
class Notifier
{
    private const ROLES = ['spaces' => 'Aadi Properties', 'haul' => 'Aadi UV', 'raketeer' => 'Aadi Raket'];

    public function applicationSubmitted(User $applicant, string $service): void
    {
        $this->toAdmins(new AppNotification('application', 'New provider application', "{$applicant->name} applied for ".(self::ROLES[$service] ?? 'a provider role').'.', '/admin/approvals', 'warning'));
    }

    public function applicationDecided(User $applicant, string $service, string $status, ?string $note): void
    {
        $role = self::ROLES[$service] ?? 'provider role';
        $this->send($applicant, $status === 'approved'
            ? new AppNotification('approved', "You're approved for {$role}", 'You can now publish listings for this role.', '/my-listings', 'good')
            : new AppNotification('rejected', "{$role} application needs an update", $note ? Str::limit($note, 140) : 'Please review the feedback and apply again.', '/provider-applications/'.$service, 'warning'));
    }

    public function ticketCreated(SupportTicket $ticket): void
    {
        $this->toAdmins(new AppNotification('ticket', 'New support ticket', "{$ticket->reference}: ".Str::limit($ticket->subject, 90), '/admin/tickets', $ticket->priority === 'high' ? 'warning' : 'info'));
    }

    public function ticketReplied(SupportTicket $ticket): void
    {
        if ($ticket->user) $this->send($ticket->user, new AppNotification('ticket-reply', 'Aadi support replied', "We answered {$ticket->reference} by email. Check your inbox.", null, 'info'));
    }

    public function ticketResolved(SupportTicket $ticket): void
    {
        if ($ticket->user) $this->send($ticket->user, new AppNotification('ticket-resolved', 'Your support request is resolved', "{$ticket->reference}: ".Str::limit($ticket->subject, 90), null, 'good'));
    }

    public function reviewReceived(User $owner, string $listingTitle, int $rating, string $href): void
    {
        $this->send($owner, new AppNotification('review', 'New review on your listing', Str::limit($listingTitle, 70).' · '.str_repeat('★', $rating).str_repeat('☆', 5 - $rating), $href, 'good'));
    }

    private function toAdmins(AppNotification $notification): void
    {
        try { Notification::send(User::where('is_admin', true)->get(), $notification); }
        catch (\Throwable $error) { report($error); }
    }

    private function send(User $user, AppNotification $notification): void
    {
        try { $user->notify($notification); }
        catch (\Throwable $error) { report($error); }
    }
}
