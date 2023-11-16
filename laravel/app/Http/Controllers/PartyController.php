<?php

namespace App\Http\Controllers;

use App\Events\UserJoined;
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
     */
    public function join(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'partyId' => 'required|string',
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Invalid data'], 400);
        }

        $party = Party::find($request->input('partyId'));
        if (!$party) {
            return new JsonResponse(['message' => 'Party not found'], 404);
        }
        if ($party->finished) {
            return new JsonResponse(['message' => 'Party is finished'], 400);
        }
        $user = $request->user();
        $user->setCurrentParty($party);

        UserJoined::dispatch($request->user(), $party->id);

        return new JsonResponse(['message' => 'Joined party', 'partyId' => $party->id]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'partyId' => 'required|string',
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Invalid data'], 400);
        }

        $party = Party::find($request->input('partyId'));
        if (!$party) {
            return new JsonResponse(['message' => 'Party not found'], 404);
        }
        if ($party->finished) {
            return new JsonResponse(['message' => 'Party is finished'], 400);
        }

        $user = $request->user();
        if ($user->currentParty != $party) {
            return new JsonResponse(['message' => 'You are not allow to join this party'], 400);
        }

        return new JsonResponse(['message' => 'Good']);
    }
}
