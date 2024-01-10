<?php

namespace App\Service;

use App\Events\PartyStarted;
use App\Events\UserJoined;
use App\Events\UserLeaved;
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
     * @param string $paramKey
     * @return Party
     * @throws \Exception
     */
    public function checkForJoinParty(string $partyId, string $paramKey = PartyHelper::CODE_TYPE_PARTY_ID): Party
    {
        $party = $this->getParty($partyId, $paramKey);
        if ($party->status == PartyHelper::STATUS_STARTED) {
            throw new \Exception('Party has already stated', 400);
        }
        if ($party->status == PartyHelper::STATUS_FINISHED) {
            throw new \Exception('Party is finished', 400);
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
     * @return array
     */
    private function randomCard(): array
    {
        $cards = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $randomCard = $cards[array_rand($cards)];
        return [
            'uid' => (string) Str::uuid(),
            'value' => $randomCard,
        ];
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
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
        ];
        $userCardDraw = $this->randomCard();

        $partyUser->hand = json_encode($userHand);
        $partyUser->card_draw = json_encode($userCardDraw);
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
}
