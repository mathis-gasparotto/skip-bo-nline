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

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

Broadcast::channel('party.{partyId}', function ($user, $partyId) {
//    return true;
    if (!$user->isOnTheParty($partyId)) {
        return false;
    }
    $partyService = new \App\Service\PartyService();
    $party = $partyService->getParty($partyId);
    return $party->status !== \App\Helper\PartyHelper::STATUS_FINISHED;
});

Broadcast::channel('party.{partyId}.started.{userId}', function ($user, $partyId, $userId) {
    if ($user->id != $userId) {
        return false;
    }
    if (!$user->isOnTheParty($partyId)) {
        return false;
    }
    $partyService = new \App\Service\PartyService();
    $party = $partyService->getParty($partyId);
    return $party->status === \App\Helper\PartyHelper::STATUS_PENDING;
});
