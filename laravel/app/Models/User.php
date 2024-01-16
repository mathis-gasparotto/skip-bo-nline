<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 *
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'avatar',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function games()
    {
        return $this->hasMany(GameUser::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentGame()
    {
        return $this->belongsTo(Game::class, 'current_game');
    }

    /**
     * @param Game $game
     * @return void
     */
    public function setCurrentGame(Game $game): void
    {
        $this->currentGame()->associate($game);
        $this->save();
    }

    /**
     * @return void
     */
    public function deleteCurrentGame(): void
    {
        $this->currentGame()->dissociate();
        $this->save();
    }

    /**
     * @param string $gameId
     * @return bool
     */
    public function isOnTheGame(string $gameId): bool
    {
        return GameUser::where('user_id', $this->id)->where('game_id', $gameId)->exists();
    }

    /**
     * @param string $gameId
     * @return GameUser
     * @throws \Exception
     */
    public function getGameUser(string $gameId): GameUser
    {
        $gameUser = GameUser::where('game_id', $gameId)->where('user_id', $this->id)->first();
        if (!$gameUser) {
            throw new \Exception('User not found in game', 404);
        }
        return $gameUser;
    }

//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\HasMany
//     */
//    public function gamesCreated()
//    {
//        return $this->hasMany(Game::class);
//    }
}
