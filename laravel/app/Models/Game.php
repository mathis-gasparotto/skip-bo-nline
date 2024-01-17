<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class Game extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var string[]
     */
    protected $fillable =[
        'join_code',
        'stacks',
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
        return $this->hasMany(User::class, 'current_game');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userToPlay()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gameUsers()
    {
        return $this->hasMany(GameUser::class);
    }

    /**
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->gameUsers()->count();
    }

    /**
     * @return int
     */
    public function getCurrentUserCount(): int
    {
        return $this->currentUsers()->count();
    }
}
