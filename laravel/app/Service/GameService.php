<?php

namespace App\Service;

use App\Events\GameStarted;
use App\Events\UserJoined;
use App\Events\UserLeaved;
use App\Events\UserMove;
use App\Helper\GlobalHelper;
use App\Helper\GameHelper;
use App\Models\Game;
use App\Models\GameUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GameService
{
    /**
     * @param User $user
     * @param string $code
     * @return void
     * @throws \Exception
     */
    public function startGame(User $user, string $code): void
    {
        $game = $this->getGame($code, GameHelper::CODE_TYPE_JOIN_CODE);

        $this->checkForStartGame($user, $game);

        $gameUsers = $game->gameUsers()->get();
        foreach ($gameUsers as $gameUser) {
            $user = $gameUser->user;
            $user->setCurrentGame($game);
            $this->generateCardsOnStartForUser($gameUser);
            GameStarted::dispatch($game->join_code, $user->id, $game->id);
        }
        $game->status = GameHelper::STATUS_STARTED;
        $game->save();
    }

    /**
     * @param User $user
     * @param Game $game
     * @return void
     * @throws \Exception
     */
    public function checkForStartGame(User $user, Game $game): void
    {
        if ($game->author->id !== $user->id) {
            throw new \Exception('You\'re not the host of this game', 403);
        }
        if ($game->status == GameHelper::STATUS_STARTED) {
            throw new \Exception('Game has already started', 400);
        }
        if ($game->status == GameHelper::STATUS_FINISHED) {
            throw new \Exception('Game is already finished', 400);
        }
        if ($game->getUserCount() < 2) {
            throw new \Exception('Not enough players', 400);
        }
    }

    /**
     * @param User $user
     * @param string $gameId
     * @param string $identifierType
     * @return void
     * @throws \Exception
     */
    public function leaveGame(User $user, string $gameId, string $identifierType = GameHelper::CODE_TYPE_GAME_ID): void
    {
        $game = $this->getGame($gameId, $identifierType);

        $userGame = GameUser::where('user_id', $user->id)->where('game_id', $game->id)->first();
        if (!$userGame) {
            throw new \Exception('You are not on this game', 403);
        }
        if ($game->status == GameHelper::STATUS_PENDING) {
            $userGame->delete();
        } elseif ($game->status == GameHelper::STATUS_STARTED) {
            if ($user->currentGame != $game) {
                throw new \Exception('You are not on this game', 403);
            }
            $userGame->win = false;
            $userGame->save();
            $user->deleteCurrentGame();
        }

        UserLeaved::dispatch($user, $game->id, $game->join_code);
    }

    /**
     * @param User $user
     * @return Game
     */
    public function createGame(User $user): Game
    {
        $card_draw_count = GameHelper::CARD_DRAW_COUNT;

        $game = new Game([
            'join_code' => $this->generateJoinCode(),
            'stacks' => json_encode([array(), array(), array(), array()]),
            'card_draw_count' => $card_draw_count,
            'status' => GameHelper::STATUS_PENDING
        ]);
        $game->author()->associate($user);
        $game->userToPlay()->associate($user);
        $game->save();

        $this->joinGame($user, $game);

        return $game;
    }

    /**
     * @param Request $request
     * @param array $requirements
     * @return void
     * @throws \Exception
     */
    public function checkRequest(Request $request, array $requirements): void
    {
        try {
            $request->validate($requirements);
        } catch (\Throwable $th) {
            throw new \Exception('Invalid data', 400);
        }
    }

    /**
     * @param string $gameId
     * @param User $user
     * @param string $paramKey
     * @return Game
     * @throws \Exception
     */
    public function checkForJoinGame(string $gameId, User $user, string $paramKey = GameHelper::CODE_TYPE_GAME_ID): Game
    {
        $game = $this->getGame($gameId, $paramKey);
        if ($user->currentGame == $game) {
            return $game;
        }
        if ($game->status == GameHelper::STATUS_STARTED) {
            throw new \Exception('Game has already stated', 400);
        }
        if ($game->status == GameHelper::STATUS_FINISHED) {
            throw new \Exception('Game is finished', 400);
        }
        if ($user->currentGame) {
            throw new \Exception('Already on a game', 400);
        }
        if ($game->getUserCount() >= 4) {
            throw new \Exception('Game is full', 400);
        }
        return $game;
    }

    /**
     * @param string $gameId
     * @param string $paramKey
     * @return Game
     * @throws \Exception
     */
    public function getGame(string $gameId, string $paramKey = GameHelper::CODE_TYPE_GAME_ID): Game
    {
        $game = match ($paramKey) {
            GameHelper::CODE_TYPE_GAME_ID => Game::find($gameId),
            GameHelper::CODE_TYPE_JOIN_CODE => Game::where('join_code', $gameId)->first(),
        };
        if (!$game) {
            throw new \Exception('Game not found', 404);
        }
        return $game;
    }

    /**
     * @param User $user
     * @param Game $game
     * @return void
     */
    public function joinGame(User $user, Game $game): void
    {
        $gameUser = $game->gameUsers()->where('user_id', $user->id)->first();

        if(!$gameUser) {
            $gameUser = (new GameUser());

            $gameUser->game()->associate($game);
            $gameUser->user()->associate($user);

            $gameUser->hand = json_encode(array());
            $gameUser->deck = json_encode([array(), array(), array(), array()]);
            $gameUser->card_draw_count = $game->card_draw_count;
            $gameUser->card_draw = null;
            $gameUser->save();
        }

        UserJoined::dispatch($user, $game->id, $game->join_code);
    }

    /**
     * @param GameUser $gameUser
     * @return void
     */
    private function generateCardsOnStartForUser(GameUser $gameUser): void
    {
        $this->generateUserHand($gameUser);

        $gameUser->card_draw = json_encode(GlobalHelper::randomCard());
        $gameUser->save();
    }

    /**
     * @param GameUser $gameUser
     * @return array
     */
    private function generateUserHand(GameUser $gameUser): array
    {
        $userHand = [
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
        ];

        $gameUser->hand = json_encode($userHand);
        return $userHand;
    }

    /**
     * @return string
     */
    private function generateJoinCode(): string
    {
        return explode('-', (string) Str::uuid())[0];
    }

    /**
     * @param string $gameId
     * @param int $currentUserId
     * @return Collection<GameUser>
     * @throws \Exception
     */
    public function getOtherGameUsers(string $gameId, int $currentUserId): Collection
    {
        return GameUser::where('game_id', $gameId)->where('user_id', '!=' , $currentUserId)->get();
    }

    /**
     * @param GameUser $gameUser
     * @return array
     * @throws \Exception
     */
    public function pickPlayerCard(GameUser $gameUser): array
    {
        if ($gameUser->card_draw_count <= 0) {
            throw new \Exception('You cannot draw another card', 400);
        }

        $cardDrawCount = $gameUser->card_draw_count - 1;
        $cardDraw =  $cardDrawCount > 0 ? GlobalHelper::randomCard() : null;

        $gameUser->card_draw = $cardDraw ? json_encode($cardDraw) : null;
        $gameUser->card_draw_count = $cardDrawCount;
        $gameUser->save();

        return [$cardDraw, $cardDrawCount];
    }

    /**
     * @param User $user
     * @param string $gameId
     * @param string $from
     * @param string $to
     * @param string|null $cardUid
     * @param int|null $fromStackIndex
     * @param int|null $toStackIndex
     * @return array
     * @throws \Exception
     */
    public function move(User $user, string $gameId, string $from, string $to, string|null $cardUid, int|null $fromStackIndex, int|null $toStackIndex): array
    {
        $gameUser = $user->getGameUser($gameId);
        $game = $gameUser->game;

        if ($user->currentGame != $game) {
            throw new \Exception('You are not on this game', 403);
        }
        if ($game->status != GameHelper::STATUS_STARTED) {
            throw new \Exception('Game has not started yet', 400);
        }
        if ($game->userToPlay->id != $user->id) {
            throw new \Exception('It\'s not your turn', 400);
        }

        if ($from === GameHelper::MOVE_TYPE_HAND && $to === GameHelper::MOVE_TYPE_DECK && $toStackIndex !== null) {
            return $this->moveHandToDeck($user, $gameUser, $cardUid, $toStackIndex);

        } elseif ($from === GameHelper::MOVE_TYPE_HAND && $to === GameHelper::MOVE_TYPE_GAME_STACK && $toStackIndex !== null) {
            return $this->moveHandToGameStack($user, $gameUser, $cardUid, $toStackIndex);

        } elseif ($from === GameHelper::MOVE_TYPE_DECK && $to === GameHelper::MOVE_TYPE_GAME_STACK && $fromStackIndex !== null && $toStackIndex !== null) {
            return $this->moveDeckToGameStack($user, $gameUser, $cardUid, $fromStackIndex, $toStackIndex);

        } elseif ($from === GameHelper::MOVE_TYPE_PLAYER_CARD_DRAW && $to === GameHelper::MOVE_TYPE_GAME_STACK && $toStackIndex !== null) {
            return $this->movePlayerDrawToGameStack($user, $gameUser, $toStackIndex);

        } else {
            throw new \Exception('Invalid move', 400);
        }
    }

    /**
     * @param User $user
     * @param GameUser $gameUser
     * @param string $cardUid
     * @param int $stackIndex
     * @return array
     * @throws \Exception
     */
    private function moveHandToDeck(User $user, GameUser $gameUser, string $cardUid, int $stackIndex): array
    {
        $hand = json_decode($gameUser->hand);
        $deck = json_decode($gameUser->deck);
        $card = array_values(array_filter($hand, fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        $hand = array_values(array_filter($hand, fn ($card) => $card->uid != $cardUid));
        $deck[$stackIndex][] = $card;

        $hand = $this->updateUserHand($hand, $gameUser);

        $gameUser->deck = json_encode($deck);
        $gameUser->save();

        $nextPlayer = $this->nextUser($user, $gameUser->game);

        UserMove::dispatch(
            $user->id,
            $gameUser->game->id,
            $deck,
            json_decode($gameUser->game->stacks),
            json_decode($gameUser->card_draw),
            $gameUser->card_draw_count,
            $nextPlayer->id
        );

        return [
            'hand' => $hand,
            'deck' => $deck
        ];
    }

    /**
     * @param User $user
     * @param GameUser $gameUser
     * @param string $cardUid
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function moveHandToGameStack(User $user, GameUser $gameUser, string $cardUid, int $toStackIndex): array
    {
        $hand = json_decode($gameUser->hand);
        $game = $gameUser->game;
        $gameStacks = json_decode($game->stacks);

        $card = array_values(array_filter($hand, fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        // Check if the card can be placed on the stack
        $this->checkCardForGameStack($card->value, $gameStacks[$toStackIndex]);

        $hand = array_values(array_filter($hand, fn ($card) => $card->uid != $cardUid));
        $gameStacks[$toStackIndex][] = $card;

        $hand = $this->updateUserHand($hand, $gameUser);

        $game->stacks = json_encode($gameStacks);
        $gameUser->save();
        $game->save();

        UserMove::dispatch(
            $user->id,
            $gameUser->game->id,
            json_decode($gameUser->deck),
            $gameStacks,
            json_decode($gameUser->card_draw),
            $gameUser->card_draw_count,
            $game->userToPlay->id
        );

        return [
            'hand' => $hand,
            'gameStacks' => $gameStacks
        ];
    }

    /**
     * @param User $user
     * @param GameUser $gameUser
     * @param string $cardUid
     * @param int $fromStackIndex
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function moveDeckToGameStack(User $user, GameUser $gameUser, string $cardUid, int $fromStackIndex, int $toStackIndex): array
    {
        $deck = json_decode($gameUser->deck);
        $game = $gameUser->game;
        $gameStacks = json_decode($game->stacks);

        $card = array_values(array_filter($deck[$fromStackIndex], fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        // Check if the card can be placed on the stack
        $this->checkCardForPickFromPlayerDeck($card->uid, $deck[$fromStackIndex]);

        // Check if the card can be placed on the stack
        $this->checkCardForGameStack($card->value, $gameStacks[$toStackIndex]);

        $deck[$fromStackIndex] = array_values(array_filter($deck[$fromStackIndex], fn ($card) => $card->uid != $cardUid));
        $gameStacks[$toStackIndex][] = $card;

        $gameUser->deck = json_encode($deck);
        $game->stacks = json_encode($gameStacks);
        $gameUser->save();
        $game->save();

        UserMove::dispatch(
            $user->id,
            $gameUser->game->id,
            $deck,
            $gameStacks,
            json_decode($gameUser->card_draw),
            $gameUser->card_draw_count,
            $game->userToPlay->id
        );

        return [
            'deck' => $deck,
            'gameStacks' => $gameStacks
        ];
    }

    /**
     * @param User $user
     * @param GameUser $gameUser
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function movePlayerDrawToGameStack(User $user, GameUser $gameUser, int $toStackIndex): array
    {
        $playerCardDraw = json_decode($gameUser->card_draw);
        if (!$playerCardDraw) {
            throw new \Exception('Card not found', 404);
        }

        $game = $gameUser->game;
        $gameStacks = json_decode($game->stacks);

        // Check if the card can be placed on the stack
        $this->checkCardForGameStack($playerCardDraw->value, $gameStacks[$toStackIndex]);

        [$newCardDraw, $newCardDrawCount] = $this->pickPlayerCard($gameUser);

        $gameStacks[$toStackIndex][] = $playerCardDraw;

        $win = null;
        if ($newCardDrawCount <= 0) {
            $win = true;
            $this->win($user, $game, $gameUser);
            $game->status = GameHelper::STATUS_FINISHED;
        }

        $game->stacks = json_encode($gameStacks);
        $game->save();

        UserMove::dispatch(
            $user->id,
            $gameUser->game->id,
            json_decode($gameUser->deck),
            $gameStacks,
            $newCardDraw,
            $newCardDrawCount,
            $game->userToPlay->id,
            $win ? $user->id : null
        );

        return [
            'newCardDraw' => $newCardDraw,
            'newCardDrawCount' => $newCardDrawCount,
            'gameStacks' => $gameStacks,
            'win' => $win
        ];
    }

    /**
     * @param User $user
     * @param Game $game
     * @return User
     */
    private function nextUser(User $user, Game $game): User
    {
        $nextPlayer = $this->getOpponents($user, $game)->first()->user;

        $game->userToPlay()->associate($nextPlayer);
        $game->save();

        return $nextPlayer;
    }

    /**
     * @param User $user
     * @param Game $game
     * @return Collection
     */
    public function getOpponents(User $user, Game $game): Collection
    {
        $opponents = $game->gameUsers()->orderBy('created_at')->get();

        $indexToRemove = $opponents->search(fn (GameUser $gameUser) => $gameUser->user_id === $user->id);

        $beforeMe = $opponents->reject(fn ($value, int $index) => $index === $indexToRemove);

        $afterMe = $beforeMe->splice($indexToRemove);
        return $afterMe->merge($beforeMe);
    }

    /**
     * @param int $cardValue
     * @param array $stack
     * @return void
     * @throws \Exception
     */
    private function checkCardForGameStack(int $cardValue, array $stack): void
    {
        $lastCard = end($stack);
        $lastCardValue = $lastCard ? $lastCard->value : 0;
        if ($cardValue !== $lastCardValue + 1) {
            throw new \Exception('Invalid move', 400);
        }
    }

    /**
     * @param string $cardUid
     * @param array $stack
     * @return void
     * @throws \Exception
     */
    private function checkCardForPickFromPlayerDeck(string $cardUid, array $stack): void
    {
        $lastCardFromStack = end($stack);
        if ($lastCardFromStack->uid !== $cardUid) {
            throw new \Exception('Invalid move', 400);
        }
    }

    /**
     * @param User $user
     * @param Game $game
     * @param GameUser|null $gameUser
     * @return void
     * @throws \Exception
     */
    private function win(User $user, Game $game, ?GameUser $gameUser = null): void {
        $user->deleteCurrentGame();

        if (!$gameUser) {
            $gameUser = $user->getGameUser($game->id);
        }
        $gameUser->win = true;
        $gameUser->save();

        // set others as loose
        $this->getOpponents($user, $game)->each(function (GameUser $gameUser) {
            $gameUser->win = false;
            $gameUser->save();
            $gameUser->user->deleteCurrentGame();
        });
    }

    /**
     * @param array $hand
     * @param GameUser $gameUser
     * @return array
     */
    private function updateUserHand(array $hand, GameUser $gameUser): array
    {
        if (count($hand) <= 0) {
            $hand = $this->generateUserHand($gameUser);
        } else {
            $gameUser->hand = json_encode($hand);
        }
        return $hand;
    }
}
