<?php

namespace App\Helper;

final class PartyHelper
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
    public const PARTY_STATUS = [
        self::STATUS_PENDING,
        self::STATUS_STARTED,
        self::STATUS_FINISHED
    ];

    /** @var string  */
    public const CODE_TYPE_JOIN_CODE = 'join_code';

    /** @var string  */
    public const CODE_TYPE_PARTY_ID = 'party_id';

    /** @var string[]  */
    public const CODE_TYPES = [
        self::CODE_TYPE_JOIN_CODE,
        self::CODE_TYPE_PARTY_ID
    ];
}
