<?php

namespace App\Http\Controllers;

use App\Helper\GlobalHelper;
use App\Helper\PartyHelper;
use App\Models\PartyUser;
use App\Service\PartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 *
 */
class PartyController extends Controller
{
    /**
     * @return PartyService
     */
    public function __construct(private PartyService $partyService)
    {}

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function get(Request $request): JsonResponse
    {
        if ($request->route('uuid')) {
            $party = $this->partyService->getParty($request->route('uuid'));
        } else if ($request->route('code')) {
            $party = $this->partyService->getParty($request->route('code'), PartyHelper::CODE_TYPE_JOIN_CODE);
        } else {
            throw new \Exception('Party not found', 404);
        }

        $toReturn = [
            'partyId' => $party->id,
            'joinCode' => $party->join_code,
            'author' => $party->author()->get(),
        ];

        if ($party->status === PartyHelper::STATUS_STARTED && $request->user()->currentParty == $party) {
            $toReturn['status'] = $party->status;
            $toReturn['myTurn'] = $party->userToPlay->id === $request->user()->id;
            $toReturn['userToPlayId'] = $party->userToPlay->id;

            $opponents = $this->partyService->getOpponents($request->user(), $party);
            $toReturn['opponents'] = $opponents->map(fn (PartyUser $partyUser) => [
                'id' => $partyUser->user->id,
                'username' => $partyUser->user->username,
                'avatar' => $partyUser->user->avatar,
                'cardDrawCount' => $partyUser->card_draw_count,
                'cardDraw' => json_decode($partyUser->card_draw),
//                'hand' => json_decode($partyUser->hand),
                'deck' => json_decode($partyUser->deck),
            ]);
            $toReturn['stack'] = json_decode($party->stack);
        }
        if ($party->status === PartyHelper::STATUS_PENDING && $request->user()->isOnTheParty($party->id)) {
            $toReturn['players'] = $party->partyUsers()->orderBy('created_at')->get()->map(fn (PartyUser $partyUser) => [
                'id' => $partyUser->user->id,
                'username' => $partyUser->user->username,
                'avatar' => $partyUser->user->avatar,
                'isMe' => $partyUser->user_id === $request->user()->id,
            ]);
            $toReturn['stack'] = json_decode($party->stack);
        }

        return new JsonResponse($toReturn);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function start(Request $request): JsonResponse
    {
        $this->partyService->checkRequest($request, [
            'code' => 'required|string',
        ]);

        $this->partyService->startParty($request->user(), $request->input('code'));

        return new JsonResponse(['message' => 'Party started']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function join(Request $request): JsonResponse
    {
        $this->partyService->checkRequest($request, [
            'code' => 'required|string',
        ]);

        $party = $this->partyService->checkForJoinParty($request->input('code'), $request->user(), 'join_code');

        if ($request->user()->currentParty == $party) {
            return new JsonResponse([
                'partyId' => $party->id
            ]);
        }
        $this->partyService->joinParty($request->user(), $party);

        return new JsonResponse([
            'joinCode' => $party->join_code
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function check(Request $request): JsonResponse
    {
        $this->partyService->checkRequest($request, [
            'data' => 'required|string',
            'type' => 'required|string|in:' . implode(',', PartyHelper::CODE_TYPES)
        ]);

        $party = $this->partyService->getParty($request->input('data'), $request->input('type'));

        if ($party->status !== PartyHelper::STATUS_PENDING && $request->user()->currentParty != $party) {
            throw new \Exception('You are not allow to join this party', 400);
        }

        return new JsonResponse(['message' => 'Good']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function leave(Request $request): JsonResponse
    {
        $this->partyService->checkRequest($request, [
            'data' => 'required|string',
            'type' => 'required|string|in:' . implode(',', PartyHelper::CODE_TYPES)
        ]);

        $this->partyService->leaveParty($request->user(), $request->input('data'), $request->input('type'));

        return new JsonResponse(['message' => 'Party leaved']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        $party = $this->partyService->createParty($request->user());

        return new JsonResponse([
            'partyId' => $party->id,
            'joinCode' => $party->join_code,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getPartyUser(Request $request): JsonResponse
    {
        if (!$request->route('uuid')) {
            throw new \Exception('Invalid data', 400);
        }

        $currentPartyUser = $request->user()->getPartyUser($request->route('uuid'));
//        $partyUsers = $this->partyService->getOtherPartyUsers($request->route('uuid'), $request->user()->id);

        return new JsonResponse([
            'partyId' => $currentPartyUser->party_id,
            'joinCode' => $currentPartyUser->party->join_code,
            'deck' => json_decode($currentPartyUser->deck),
            'hand' => json_decode($currentPartyUser->hand),
            'cardDraw' => json_decode($currentPartyUser->card_draw),
            'cardDrawCount' => $currentPartyUser->card_draw_count,
//            'opponents' => $partyUsers->map(fn (PartyUser $partyUser) => [
//                'id' => $partyUser->user->id,
//                'username' => $partyUser->user->username,
//                'avatar' => $partyUser->user->avatar,
//                'cardDrawCount' => $partyUser->card_draw_count,
//                'cardDraw' => json_decode($partyUser->card_draw),
//                'hand' => json_decode($partyUser->hand),
//                'deck' => json_decode($partyUser->deck),
//            ])
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function move(Request $request): JsonResponse
    {
        $this->partyService->checkRequest($request, [
            'from' => 'required|string',
            'to' => 'required|string',
            'partyId' => 'required|string',
            'cardUid' => 'required|string',
            'fromStackIndex' => 'nullable|int|min:0|max:3',
            'toStackIndex' => 'nullable|int|min:0|max:3',
        ]);

        $toReturn = $this->partyService->move(
            $request->user(),
            $request->input('partyId'),
            $request->input('from'),
            $request->input('to'),
            $request->input('cardUid'),
            $request->input('fromStackIndex'),
            $request->input('toStackIndex')
        );

        return new JsonResponse($toReturn);
    }
}
