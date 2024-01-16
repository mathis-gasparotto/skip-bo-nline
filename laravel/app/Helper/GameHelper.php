<?php

namespace App\Helper;

final class GameHelper
{
    /** @var string  */
    public const STATUS_PENDING = 'pending';

    /** @var string  */
    public const STATUS_STARTED = 'started';

    /** @var string  */
    public const STATUS_FINISHED = 'finished';

    /** @var string  */
    public const CARD_DRAW_COUNT = 5;

    /** @var string[]  */
    public const GAME_STATUS = [
        self::STATUS_PENDING,
        self::STATUS_STARTED,
        self::STATUS_FINISHED
    ];

    /** @var string  */
    public const CODE_TYPE_JOIN_CODE = 'join_code';

    /** @var string  */
    public const CODE_TYPE_GAME_ID = 'game_id';

    /** @var string[]  */
    public const CODE_TYPES = [
        self::CODE_TYPE_JOIN_CODE,
        self::CODE_TYPE_GAME_ID
    ];

    /** @var int  */
    public const HAND_MAX_SIZE = 5;

    /** @var string  */
    public const MOVE_TYPE_HAND = 'hand';

    /** @var string  */
    public const MOVE_TYPE_DECK = 'deck';

    /** @var string  */
    public const MOVE_TYPE_GAME_STACK = 'game_stack';

    /** @var string  */
    public const MOVE_TYPE_PLAYER_CARD_DRAW = 'player_card_draw';

    /** @var string[]  */
    public const MOVE_TYPES = [
        self::MOVE_TYPE_HAND,
        self::MOVE_TYPE_DECK,
        self::MOVE_TYPE_GAME_STACK,
        self::MOVE_TYPE_PLAYER_CARD_DRAW,
    ];
}
