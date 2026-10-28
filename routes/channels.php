<?php

use App\Models\ChatSession;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Channel Authorization
|--------------------------------------------------------------------------
|
| The private chat channel of section 5.9 is open only to the participants:
| the owning customer, and the admin team until an admin claims the session.
|
*/

Broadcast::channel('chat.{sessionId}', function ($user, int $sessionId): bool {
    $session = ChatSession::query()->find($sessionId);

    if ($session === null) {
        return false;
    }

    if ($session->user_id === $user->id) {
        return true;
    }

    // FR-61: any admin may watch an unclaimed session; once one admin replies
    // first, only that admin stays on the channel.
    return in_array($user->role, ['admin', 'super_admin'], true)
        && ($session->admin_id === null || $session->admin_id === $user->id);
});
