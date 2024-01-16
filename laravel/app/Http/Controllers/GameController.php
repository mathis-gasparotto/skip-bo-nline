<?php

namespace App\Http\Controllers;

use App\Helper\GlobalHelper;
use App\Helper\GameHelper;
use App\Models\GameUser;
use App\Service\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 *
 */
class GameController extends Controller
{
    /**
     * @return GameService
     */
    public function __construct(private GameService $gameService)
    {}

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function get(Request $request): JsonResponse
    {
        if ($request->route('uuid')) {
            $game = $this->gameService->getGame($request->route('uuid'));
        } else if ($request->route('code')) {
            $game = $this->gameService->getGame($request->route('code'), GameHelper::CODE_TYPE_JOIN_CODE);
        } else {
            throw new \Exception('Game not found', 404);
        }

        $toReturn = [
            'gameId' => $game->id,
            'joinCode' => $game->join_code,
            'author' => $game->author()->get(),
        ];

        if ($game->status === GameHelper::STATUS_STARTED && $request->user()->currentGame == $game) {
            $toReturn['status'] = $game->status;
            $toReturn['myTurn'] = $game->userToPlay->id === $request->user()->id;
            $toReturn['userToPlayId'] = $game->userToPlay->id;

            $opponents = $this->gameService->getOpponents($request->user(), $game)->filter(fn (GameUser $gameUser) => $gameUser->user->currentGame && $gameUser->user->currentGame->id === $game->id);
            $toReturn['opponents'] = $opponents->map(fn (GameUser $gameUser) => [
                'id' => $gameUser->user->id,
                'username' => $gameUser->user->username,
                'avatar' => $gameUser->user->avatar,
                'cardDrawCount' => $gameUser->card_draw_count,
                'cardDraw' => json_decode($gameUser->card_draw),
//                'hand' => json_decode($gameUser->hand),
                'deck' => json_decode($gameUser->deck),
            ]);
            $toReturn['stacks'] = json_decode($game->stacks);
        }
        if ($game->status === GameHelper::STATUS_PENDING && $request->user()->isOnTheGame($game->id)) {
            $toReturn['players'] = $game->gameUsers()->orderBy('created_at')->get()->map(fn (GameUser $gameUser) => [
                'id' => $gameUser->user->id,
                'username' => $gameUser->user->username,
                'avatar' => $gameUser->user->avatar,
                'isMe' => $gameUser->user_id === $request->user()->id,
            ]);
            $toReturn['stacks'] = json_decode($game->stacks);
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
        $this->gameService->checkRequest($request, [
            'code' => 'required|string',
        ]);

        $this->gameService->startGame($request->user(), $request->input('code'));

        return new JsonResponse(['message' => 'Game started']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function join(Request $request): JsonResponse
    {
        $this->gameService->checkRequest($request, [
            'code' => 'required|string',
        ]);

        $game = $this->gameService->checkForJoinGame($request->input('code'), $request->user(), 'join_code');

        if ($request->user()->currentGame == $game) {
            return new JsonResponse([
                'gameId' => $game->id
            ]);
        }
        $this->gameService->joinGame($request->user(), $game);

        return new JsonResponse([
            'joinCode' => $game->join_code
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function check(Request $request): JsonResponse
    {
        $this->gameService->checkRequest($request, [
            'data' => 'required|string',
            'type' => 'required|string|in:' . implode(',', GameHelper::CODE_TYPES)
        ]);

        $game = $this->gameService->getGame($request->input('data'), $request->input('type'));

        if (
            ($game->status !== GameHelper::STATUS_PENDING && $request->user()->currentGame != $game) ||
            $game->status === GameHelper::STATUS_FINISHED
        ) {
            throw new \Exception('You are not allow to join this game', 400);
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
        $this->gameService->checkRequest($request, [
            'data' => 'required|string',
            'type' => 'required|string|in:' . implode(',', GameHelper::CODE_TYPES)
        ]);

        $this->gameService->leaveGame($request->user(), $request->input('data'), $request->input('type'));

        return new JsonResponse(['message' => 'Game leaved']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        $game = $this->gameService->createGame($request->user());

        return new JsonResponse([
            'gameId' => $game->id,
            'joinCode' => $game->join_code,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getGameUser(Request $request): JsonResponse
    {
        if (!$request->route('uuid')) {
            throw new \Exception('Invalid data', 400);
        }

        $currentGameUser = $request->user()->getGameUser($request->route('uuid'));
//        $gameUsers = $this->gameService->getOtherGameUsers($request->route('uuid'), $request->user()->id);

        return new JsonResponse([
            'gameId' => $currentGameUser->game_id,
            'joinCode' => $currentGameUser->game->join_code,
            'deck' => json_decode($currentGameUser->deck),
            'hand' => json_decode($currentGameUser->hand),
            'cardDraw' => json_decode($currentGameUser->card_draw),
            'cardDrawCount' => $currentGameUser->card_draw_count,
//            'opponents' => $gameUsers->map(fn (GameUser $gameUser) => [
//                'id' => $gameUser->user->id,
//                'username' => $gameUser->user->username,
//                'avatar' => $gameUser->user->avatar,
//                'cardDrawCount' => $gameUser->card_draw_count,
//                'cardDraw' => json_decode($gameUser->card_draw),
//                'hand' => json_decode($gameUser->hand),
//                'deck' => json_decode($gameUser->deck),
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
        $this->gameService->checkRequest($request, [
            'from' => 'required|string',
            'to' => 'required|string',
            'gameId' => 'required|string',
            'cardUid' => 'nullable|string',
            'fromStackIndex' => 'nullable|int|min:0|max:3',
            'toStackIndex' => 'nullable|int|min:0|max:3',
        ]);

        $toReturn = $this->gameService->move(
            $request->user(),
            $request->input('gameId'),
            $request->input('from'),
            $request->input('to'),
            $request->input('cardUid'),
            $request->input('fromStackIndex'),
            $request->input('toStackIndex')
        );

        return new JsonResponse($toReturn);
    }
}
