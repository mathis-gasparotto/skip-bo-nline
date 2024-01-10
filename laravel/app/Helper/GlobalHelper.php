<?php

namespace App\Helper;

use Illuminate\Support\Str;

class GlobalHelper
{
    /** @var int[]  */
    public const CARDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    /**
     * @return array
     */
    public static function randomCard(): array
    {
        $cards = GlobalHelper::CARDS;
        $randomCard = $cards[array_rand(self::CARDS)];
        return [
            'uid' => (string) Str::uuid(),
            'value' => $randomCard,
        ];
    }
}
