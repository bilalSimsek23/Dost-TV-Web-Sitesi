<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserInvitationService
{
    /**
     * Create a new 72-hour invitation for a user and dispatch notification.
     */
    public function createInvitation(User $user, ?User $actor = null): array
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $actorName = $actor?->name ?? auth()->user()?->name ?? 'Sistem';

        $invitation = DB::transaction(function () use ($user, $tokenHash, $actor) {
            // Invalidate any previous pending invitations
            UserInvitation::where('user_id', $user->id)
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);

            return UserInvitation::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addHours(72),
                'created_by' => $actor?->id ?? auth()->id(),
            ]);
        });

        $mailSent = false;
        try {
            $user->notify(new UserInvitationNotification($token, $user->name, 72));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error("Kullanıcı davet e-postası gönderilemedi (User ID: {$user->id}): {$e->getMessage()}", [
                'user_id' => $user->id,
            ]);
        }

        AuditLogger::log(
            action: 'invited',
            message: "{$actorName}, {$user->name} kullanıcısına davet gönderdi.",
            subject: $user,
            subjectLabel: $user->name,
            user: $actor,
        );

        return [
            'invitation' => $invitation,
            'token' => $token,
            'mail_sent' => $mailSent,
        ];
    }

    /**
     * Resend an invitation with a fresh 72-hour token.
     */
    public function resendInvitation(User $user, ?User $actor = null): array
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $actorName = $actor?->name ?? auth()->user()?->name ?? 'Sistem';

        $invitation = DB::transaction(function () use ($user, $tokenHash, $actor) {
            UserInvitation::where('user_id', $user->id)
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);

            return UserInvitation::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addHours(72),
                'created_by' => $actor?->id ?? auth()->id(),
            ]);
        });

        $mailSent = false;
        try {
            $user->notify(new UserInvitationNotification($token, $user->name, 72));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error("Kullanıcı davet tekrarı e-postası gönderilemedi (User ID: {$user->id}): {$e->getMessage()}", [
                'user_id' => $user->id,
            ]);
        }

        AuditLogger::log(
            action: 'invitation_resent',
            message: "{$actorName}, {$user->name} kullanıcısının davetini tekrar gönderdi.",
            subject: $user,
            subjectLabel: $user->name,
            user: $actor,
        );

        return [
            'invitation' => $invitation,
            'token' => $token,
            'mail_sent' => $mailSent,
        ];
    }

    /**
     * Cancel all active pending invitations for a user.
     */
    public function cancelInvitation(User $user, ?User $actor = null): void
    {
        $actorName = $actor?->name ?? auth()->user()?->name ?? 'Sistem';

        UserInvitation::where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);

        AuditLogger::log(
            action: 'invitation_cancelled',
            message: "{$actorName}, {$user->name} kullanıcısının davetini iptal etti.",
            subject: $user,
            subjectLabel: $user->name,
            user: $actor,
        );
    }

    /**
     * Find a valid unexpired, uncancelled, unaccepted invitation by plaintext token.
     */
    public function findValidInvitation(string $token): ?UserInvitation
    {
        if (blank($token)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        return UserInvitation::with('user')
            ->where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Accept invitation, set user's password, and mark invitation as accepted.
     */
    public function acceptInvitation(string $token, string $password): bool
    {
        $invitation = $this->findValidInvitation($token);

        if (! $invitation || ! $invitation->user) {
            return false;
        }

        $user = $invitation->user;

        DB::transaction(function () use ($invitation, $user, $password) {
            $user->update([
                'password' => $password,
                'is_active' => true,
            ]);

            $invitation->update([
                'accepted_at' => now(),
            ]);
        });

        AuditLogger::log(
            action: 'updated',
            message: "{$user->name}, daveti kabul ederek şifresini belirledi.",
            subject: $user,
            subjectLabel: $user->name,
            user: $user,
        );

        return true;
    }
}
