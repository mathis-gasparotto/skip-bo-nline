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

Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    if (!$user->isOnTheGame($gameId)) {
        return false;
    }
    $gameService = new \App\Service\GameService();
    $game = $gameService->getGame($gameId);
    return $game->status !== \App\Helper\GameHelper::STATUS_FINISHED;
});

Broadcast::channel('game.{gameId}.started.{userId}', function ($user, $gameId, $userId) {
    if ($user->id != $userId) {
        return false;
    }
    if (!$user->isOnTheGame($gameId)) {
        return false;
    }
    $gameService = new \App\Service\GameService();
    $game = $gameService->getGame($gameId);
    return $game->status === \App\Helper\GameHelper::STATUS_PENDING;
});
