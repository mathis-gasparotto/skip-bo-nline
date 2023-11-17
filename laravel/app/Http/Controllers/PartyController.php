<?php

namespace App\Http\Controllers;

use App\Events\UserJoined;
use App\Events\UserQuited;
use App\Models\Party;
use App\Models\PartyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 *
 */
class PartyController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function join(Request $request): JsonResponse
    {
        $this->checkRequest($request, [
            'code' => 'required|string',
        ]);

        $party = $this->checkIfPartyExistsAndNotFinished($request->input('code'), 'join_code');
        $user = $request->user();
        $user->setCurrentParty($party);

        UserJoined::dispatch($request->user(), $party->id);

        return new JsonResponse(['message' => 'Joined party', 'partyId' => $party->id]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function check(Request $request): JsonResponse
    {
        $this->checkRequest($request, [
            'partyId' => 'required|string',
        ]);

        $party = $this->checkIfPartyExistsAndNotFinished($request->input('partyId'));

        $user = $request->user();
        if ($user->currentParty != $party) {
            throw new \Exception('You are not allow to join this party', 400);
        }

        return new JsonResponse(['message' => 'Good']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function quit(Request $request): JsonResponse
    {
        $this->checkRequest($request, [
            'partyId' => 'required|string',
        ]);

        $party = $this->checkIfPartyExistsAndNotFinished($request->input('partyId'));

        $user = $request->user();
        if ($user->currentParty != $party) {
            throw new \Exception('You are not on this party', 400);
        }

        $user->deleteCurrentParty();

        UserQuited::dispatch($request->user(), $party->id);

        return new JsonResponse(['message' => 'Party quited']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();

        $card_draw_count = 5;

        $party = Party::create([
            'join_code' => $this->generateJoinCode(),
            'stack1' => json_encode(array()),
            'stack2' => json_encode(array()),
            'stack3' => json_encode(array()),
            'stack4' => json_encode(array()),
        ]);

        $partyUser = (new PartyUser())
            ->party()->associate($party)
            ->user()->associate($user);

        $userHand = [
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
            $this->randomCard(),
        ];
        $userCardDraw = $this->randomCard();

        $partyUser->hand = json_encode($userHand);
        $partyUser->deck = json_encode(array());
        $partyUser->card_draw_count = $card_draw_count;
        $partyUser->card_draw = json_encode($userCardDraw);
        $partyUser->save();

        $user->setCurrentParty($party);

        return new JsonResponse([
            'partyId' => $party->id,
            'hand' => $userHand,
            'cardDrawCount' => $partyUser->card_draw_count,
            'cardDraw' => $userCardDraw,
            'joinCode' => $party->join_code,
        ]);
    }

    /**
     * @param Request $request
     * @param array $requirements
     * @return void
     * @throws \Exception
     */
    private function checkRequest(Request $request, array $requirements): void
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
    private function checkIfPartyExistsAndNotFinished(string $partyId, string $paramKey = 'id'): Party
    {
        $party = match ($paramKey) {
            'id' => Party::find($partyId),
            'join_code' => Party::where('join_code', $partyId)->first(),
        };
        if (!$party) {
            throw new \Exception('Party not found', 404);
        }
        if ($party->finished) {
            throw new \Exception('Party is finished', 400);
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
            'id' => (string) Str::uuid(),
            'value' => $randomCard,
        ];
    }

    /**
     * @return string
     */
    private function generateJoinCode(): string
    {
        return explode('-', (string) Str::uuid())[0];
    }
}
