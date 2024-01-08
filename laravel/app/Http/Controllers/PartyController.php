<?php

namespace App\Http\Controllers;

use App\Helper\PartyHelper;
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
        return new JsonResponse([
            'partyId' => $party->id,
            'joinCode' => $party->join_code,
            'author' => $party->author()->get(),
        ]);
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

        $party = $this->partyService->checkForJoinParty($request->input('code'), 'join_code');
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
}
