<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class Party extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var string[]
     */
    protected $fillable =[
        'join_code',
        'stack',
        'status',
        'card_draw_count',
        'author_id',
        'status',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentUsers()
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partyUsers()
    {
        return $this->hasMany(PartyUser::class);
    }

    /**
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->partyUsers()->count();
    }
}
