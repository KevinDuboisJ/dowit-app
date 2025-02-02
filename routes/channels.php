<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user.{id}', function ($user, $id) {
    // Only authenticated users can subscribe to their specific channels.
    return (int) $user->id === (int) $id;
});

Broadcast::channel('team.{id}', function ($user, $teamId) {
    // Check if the user belongs to the team
    return $user->belongsToTeam($teamId);
});

Broadcast::channel('channel_for_everyone', function ($user) {
    return true;
});
