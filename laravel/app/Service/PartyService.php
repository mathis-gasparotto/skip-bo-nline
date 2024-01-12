<?php

namespace App\Service;

use App\Events\PartyStarted;
use App\Events\UserJoined;
use App\Events\UserLeaved;
use App\Events\UserMove;
use App\Helper\GlobalHelper;
use App\Helper\PartyHelper;
use App\Models\Party;
use App\Models\PartyUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PartyService
{
    /**
     * @param User $user
     * @param string $code
     * @return void
     * @throws \Exception
     */
    public function startParty(User $user, string $code): void
    {
        $party = $this->getParty($code, PartyHelper::CODE_TYPE_JOIN_CODE);

        $this->checkForStartParty($user, $party);

        $partyUsers = $party->partyUsers()->get();
        foreach ($partyUsers as $partyUser) {
            $user = $partyUser->user;
            $user->setCurrentParty($party);
            $this->generateUserHand($user, $party);
            PartyStarted::dispatch($party->join_code, $user->id, $party->id);
        }
        $party->status = PartyHelper::STATUS_STARTED;
        $party->save();
    }

    /**
     * @param User $user
     * @param Party $party
     * @return void
     * @throws \Exception
     */
    public function checkForStartParty(User $user, Party $party): void
    {
        if ($party->author->id !== $user->id) {
            throw new \Exception('You\'re not the host of this party', 403);
        }
        if ($party->status == PartyHelper::STATUS_STARTED) {
            throw new \Exception('Party has already started', 400);
        }
        if ($party->status == PartyHelper::STATUS_FINISHED) {
            throw new \Exception('Party is already finished', 400);
        }
        if ($party->getUserCount() < 2) {
            throw new \Exception('Not enough players', 400);
        }
    }

    /**
     * @param User $user
     * @param string $partyId
     * @param string $identifierType
     * @return void
     * @throws \Exception
     */
    public function leaveParty(User $user, string $partyId, string $identifierType = PartyHelper::CODE_TYPE_PARTY_ID): void
    {
        $party = $this->getParty($partyId, $identifierType);

        if ($party->status == PartyHelper::STATUS_PENDING) {
            $userParty = PartyUser::where('user_id', $user->id)->where('party_id', $party->id)->first();
            if (!$userParty) {
                throw new \Exception('You are not on this party', 403);
            }
            $userParty->delete();
        } elseif ($party->status == PartyHelper::STATUS_STARTED) {
            if ($user->currentParty != $party) {
                throw new \Exception('You are not on this party', 403);
            }
            $user->deleteCurrentParty();
        }

        UserLeaved::dispatch($user, $party->id, $party->join_code);
    }

    /**
     * @param User $user
     * @return Party
     */
    public function createParty(User $user): Party
    {
        $card_draw_count = PartyHelper::CARD_DRAW_COUNT;

        $party = new Party([
            'join_code' => $this->generateJoinCode(),
            'stack' => json_encode([array(), array(), array(), array()]),
            'card_draw_count' => $card_draw_count,
            'status' => PartyHelper::STATUS_PENDING
        ]);
        $party->author()->associate($user);
        $party->userToPlay()->associate($user);
        $party->save();

        $this->joinParty($user, $party);

        return $party;
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
     * @param string $partyId
     * @param User $user
     * @param string $paramKey
     * @return Party
     * @throws \Exception
     */
    public function checkForJoinParty(string $partyId, User $user, string $paramKey = PartyHelper::CODE_TYPE_PARTY_ID): Party
    {
        $party = $this->getParty($partyId, $paramKey);
        if ($user->currentParty == $party) {
            return $party;
        }
        if ($party->status == PartyHelper::STATUS_STARTED) {
            throw new \Exception('Party has already stated', 400);
        }
        if ($party->status == PartyHelper::STATUS_FINISHED) {
            throw new \Exception('Party is finished', 400);
        }
        if ($user->currentParty) {
            throw new \Exception('Already on a party', 400);
        }
        if ($party->getUserCount() >= 4) {
            throw new \Exception('Party is full', 400);
        }
        return $party;
    }

    /**
     * @param string $partyId
     * @param string $paramKey
     * @return Party
     * @throws \Exception
     */
    public function getParty(string $partyId, string $paramKey = PartyHelper::CODE_TYPE_PARTY_ID): Party
    {
        $party = match ($paramKey) {
            PartyHelper::CODE_TYPE_PARTY_ID => Party::find($partyId),
            PartyHelper::CODE_TYPE_JOIN_CODE => Party::where('join_code', $partyId)->first(),
        };
        if (!$party) {
            throw new \Exception('Party not found', 404);
        }
        return $party;
    }

    /**
     * @param User $user
     * @param Party $party
     * @return void
     */
    public function joinParty(User $user, Party $party): void
    {
        $partyUser = $party->partyUsers()->where('user_id', $user->id)->first();

        if(!$partyUser) {
            $partyUser = (new PartyUser());

            $partyUser->party()->associate($party);
            $partyUser->user()->associate($user);

            $partyUser->hand = json_encode(array());
            $partyUser->deck = json_encode([array(), array(), array(), array()]);
            $partyUser->card_draw_count = $party->card_draw_count;
            $partyUser->card_draw = null;
            $partyUser->save();
        }

        UserJoined::dispatch($user, $party->id, $party->join_code);
    }

    /**
     * @param User $user
     * @param Party $party
     * @return void
     * @throws \Exception
     */
    private function generateUserHand(User $user, Party $party): void
    {
        $partyUser = $party->partyUsers()->where('user_id', $user->id)->where('party_id', $party->id)->first();
        if (!$partyUser) {
            throw new \Exception('User not found in party', 404);
        }

        $userHand = [
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
            GlobalHelper::randomCard(),
        ];

        $partyUser->hand = json_encode($userHand);
        $partyUser->card_draw = json_encode(GlobalHelper::randomCard());
        $partyUser->save();
    }

    /**
     * @return string
     */
    private function generateJoinCode(): string
    {
        return explode('-', (string) Str::uuid())[0];
    }

    /**
     * @param string $partyId
     * @param int $currentUserId
     * @return Collection<PartyUser>
     * @throws \Exception
     */
    public function getOtherPartyUsers(string $partyId, int $currentUserId): Collection
    {
        return PartyUser::where('party_id', $partyId)->where('user_id', '!=' , $currentUserId)->get();
    }

    /**
     * @param PartyUser $partyUser
     * @return array
     * @throws \Exception
     */
    public function pickPlayerCard(PartyUser $partyUser): array
    {
        if ($partyUser->card_draw_count <= 0) {
            throw new \Exception('You cannot draw another card', 400);
        }

        $cardDrawCount = $partyUser->card_draw_count - 1;
        $cardDraw =  $cardDrawCount > 0 ? GlobalHelper::randomCard() : null;

        $partyUser->card_draw = $cardDraw ? json_encode($cardDraw) : null;
        $partyUser->card_draw_count = $cardDrawCount;
        $partyUser->save();

        return [$cardDraw, $cardDrawCount];
    }

    /**
     * @param User $user
     * @param string $partyId
     * @param string $from
     * @param string $to
     * @param string|null $cardUid
     * @param int|null $fromStackIndex
     * @param int|null $toStackIndex
     * @return array
     * @throws \Exception
     */
    public function move(User $user, string $partyId, string $from, string $to, string|null $cardUid, int|null $fromStackIndex, int|null $toStackIndex): array
    {
        $partyUser = $user->getPartyUser($partyId);
        $party = $partyUser->party;

        if ($user->currentParty != $party) {
            throw new \Exception('You are not on this party', 403);
        }
        if ($party->status != PartyHelper::STATUS_STARTED) {
            throw new \Exception('Party has not started yet', 400);
        }
        if ($party->userToPlay->id != $user->id) {
            throw new \Exception('It\'s not your turn', 400);
        }

        if ($from === PartyHelper::MOVE_TYPE_HAND && $to === PartyHelper::MOVE_TYPE_DECK && $toStackIndex !== null) {
            return $this->moveHandToDeck($user, $partyUser, $cardUid, $toStackIndex);

        } elseif ($from === PartyHelper::MOVE_TYPE_HAND && $to === PartyHelper::MOVE_TYPE_PARTY_STACK && $toStackIndex !== null) {
            return $this->moveHandToPartyStack($user, $partyUser, $cardUid, $toStackIndex);

        } elseif ($from === PartyHelper::MOVE_TYPE_DECK && $to === PartyHelper::MOVE_TYPE_PARTY_STACK && $fromStackIndex !== null && $toStackIndex !== null) {
            return $this->moveDeckToPartyStack($user, $partyUser, $cardUid, $fromStackIndex, $toStackIndex);

        } elseif ($from === PartyHelper::MOVE_TYPE_PLAYER_CARD_DRAW && $to === PartyHelper::MOVE_TYPE_PARTY_STACK && $toStackIndex !== null) {
            return $this->movePlayerDrawToPartyStack($user, $partyUser, $toStackIndex);

        } else {
            throw new \Exception('Invalid move', 400);
        }
    }

    /**
     * @param User $user
     * @param PartyUser $partyUser
     * @param string $cardUid
     * @param int $stackIndex
     * @return array
     * @throws \Exception
     */
    private function moveHandToDeck(User $user, PartyUser $partyUser, string $cardUid, int $stackIndex): array
    {
        $hand = json_decode($partyUser->hand);
        $deck = json_decode($partyUser->deck);
        $card = array_values(array_filter($hand, fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        $hand = array_values(array_filter($hand, fn ($card) => $card->uid != $cardUid));
        $deck[$stackIndex][] = $card;

        $partyUser->hand = json_encode($hand);
        $partyUser->deck = json_encode($deck);
        $partyUser->save();

        $nextPlayer = $this->nextUser($user, $partyUser->party);

        UserMove::dispatch(
            $user->id,
            $partyUser->party->id,
            $deck,
            json_decode($partyUser->party->stack),
            json_decode($partyUser->card_draw),
            $partyUser->card_draw_count,
            $nextPlayer->id
        );

        return [
            'hand' => $hand,
            'deck' => $deck
        ];
    }

    /**
     * @param User $user
     * @param PartyUser $partyUser
     * @param string $cardUid
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function moveHandToPartyStack(User $user, PartyUser $partyUser, string $cardUid, int $toStackIndex): array
    {
        $hand = json_decode($partyUser->hand);
        $party = $partyUser->party;
        $partyStack = json_decode($party->stack);

        $card = array_values(array_filter($hand, fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        // Check if the card can be placed on the stack
        $this->checkCardForPartyStack($card->value, $partyStack[$toStackIndex]);

        $hand = array_values(array_filter($hand, fn ($card) => $card->uid != $cardUid));
        $partyStack[$toStackIndex][] = $card;

        $partyUser->hand = json_encode($hand);
        $party->stack = json_encode($partyStack);
        $partyUser->save();
        $party->save();

        UserMove::dispatch(
            $user->id,
            $partyUser->party->id,
            json_decode($partyUser->deck),
            $partyStack,
            json_decode($partyUser->card_draw),
            $partyUser->card_draw_count,
            $party->userToPlay->id
        );

        return [
            'hand' => $hand,
            'partyStack' => $partyStack
        ];
    }

    /**
     * @param User $user
     * @param PartyUser $partyUser
     * @param string $cardUid
     * @param int $fromStackIndex
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function moveDeckToPartyStack(User $user, PartyUser $partyUser, string $cardUid, int $fromStackIndex, int $toStackIndex): array
    {
        $deck = json_decode($partyUser->deck);
        $party = $partyUser->party;
        $partyStack = json_decode($party->stack);

        $card = array_values(array_filter($deck[$fromStackIndex], fn ($card) => $card->uid == $cardUid));
        if (!isset($card[0])) {
            throw new \Exception('Card not found', 404);
        } else {
            $card = $card[0];
        }

        // Check if the card can be placed on the stack
        $this->checkCardForPickFromPlayerDeck($card->uid, $deck[$fromStackIndex]);

        // Check if the card can be placed on the stack
        $this->checkCardForPartyStack($card->value, $partyStack[$toStackIndex]);

        $deck[$fromStackIndex] = array_values(array_filter($deck[$fromStackIndex], fn ($card) => $card->uid != $cardUid));
        $partyStack[$toStackIndex][] = $card;

        $partyUser->deck = json_encode($deck);
        $party->stack = json_encode($partyStack);
        $partyUser->save();
        $party->save();

        UserMove::dispatch(
            $user->id,
            $partyUser->party->id,
            $deck,
            $partyStack,
            json_decode($partyUser->card_draw),
            $partyUser->card_draw_count,
            $party->userToPlay->id
        );

        return [
            'deck' => $deck,
            'partyStack' => $partyStack
        ];
    }

    /**
     * @param User $user
     * @param PartyUser $partyUser
     * @param int $toStackIndex
     * @return array
     * @throws \Exception
     */
    private function movePlayerDrawToPartyStack(User $user, PartyUser $partyUser, int $toStackIndex): array
    {
        $playerCardDraw = json_decode($partyUser->card_draw);
        if (!$playerCardDraw) {
            throw new \Exception('Card not found', 404);
        }

        $party = $partyUser->party;
        $partyStack = json_decode($party->stack);

        // Check if the card can be placed on the stack
        $this->checkCardForPartyStack($playerCardDraw->value, $partyStack[$toStackIndex]);

        [$newCardDraw, $newCardDrawCount] = $this->pickPlayerCard($partyUser);

        $partyStack[$toStackIndex][] = $playerCardDraw;

        $party->stack = json_encode($partyStack);
        $party->save();

        UserMove::dispatch(
            $user->id,
            $partyUser->party->id,
            json_decode($partyUser->deck),
            $partyStack,
            $newCardDraw,
            $newCardDrawCount,
            $party->userToPlay->id
        );

        return [
            'newCardDraw' => $newCardDraw,
            'newCardDrawCount' => $newCardDrawCount,
            'partyStack' => $partyStack
        ];
    }

    /**
     * @param User $user
     * @param Party $party
     * @return User
     */
    private function nextUser(User $user, Party $party): User
    {
        $nextPlayer = $this->getOpponents($user, $party)->first()->user;

        $party->userToPlay()->associate($nextPlayer);
        $party->save();

        return $nextPlayer;
    }

    /**
     * @param User $user
     * @param Party $party
     * @return Collection
     */
    public function getOpponents(User $user, Party $party): Collection
    {
        $opponents = $party->partyUsers()->orderBy('created_at')->get();

        $indexToRemove = $opponents->search(fn (PartyUser $partyUser) => $partyUser->user_id === $user->id);

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
    private function checkCardForPartyStack(int $cardValue, array $stack): void
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
}
