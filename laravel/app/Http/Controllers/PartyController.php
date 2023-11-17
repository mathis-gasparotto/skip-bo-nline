<?php

namespace App\Http\Controllers;

use App\Events\UserJoined;
use App\Events\UserQuited;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'partyId' => 'required|string',
        ]);

        $party = $this->checkIfPartyExistsAndNotFinished($request->input('partyId'));
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
     * @param array $requirement
     * @return JsonResponse|bool
     * @throws \Exception
     */
    private function checkRequest(Request $request, array $requirement): JsonResponse|bool
    {
        try {
            $request->validate($requirement);
        } catch (\Throwable $th) {
            throw new \Exception('Invalid data', 400);
        }
        return true;
    }

    /**
     * @param string $partyId
     * @return Party
     * @throws \Exception
     */
    private function checkIfPartyExistsAndNotFinished(string $partyId): Party
    {
        $party = Party::find($partyId);
        if (!$party) {
            throw new \Exception('Party not found', 404);
        }
        if ($party->finished) {
            throw new \Exception('Party is finished', 400);
        }
        return $party;
    }
}
