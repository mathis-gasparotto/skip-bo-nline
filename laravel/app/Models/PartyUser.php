<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class PartyUser extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable =[
        'deck',
        'hand',
        'card_draw_count',
        'card_draw',
        'win'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function party()
    {
        return $this->belongsTo(Party::class);
    }
}
