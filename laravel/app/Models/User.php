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
    public function parties()
    {
        return $this->hasMany(PartyUser::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentParty()
    {
        return $this->belongsTo(Party::class, 'current_party');
    }

    /**
     * @param Party $party
     * @return void
     */
    public function setCurrentParty(Party $party): void
    {
        $this->currentParty()->associate($party);
        $this->save();
    }

    /**
     * @return void
     */
    public function deleteCurrentParty(): void
    {
        $this->currentParty()->dissociate();
        $this->save();
    }

    /**
     * @param string $partyId
     * @return bool
     */
    public function isOnTheParty(string $partyId): bool
    {
        return PartyUser::where('user_id', $this->id)->where('party_id', $partyId)->exists();
    }

    /**
     * @param string $partyId
     * @return PartyUser
     * @throws \Exception
     */
    public function getPartyUser(string $partyId): PartyUser
    {
        $partyUser = PartyUser::where('party_id', $partyId)->where('user_id', $this->id)->first();
        if (!$partyUser) {
            throw new \Exception('User not found in party', 404);
        }
        return $partyUser;
    }

//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\HasMany
//     */
//    public function partiesCreated()
//    {
//        return $this->hasMany(Party::class);
//    }
}
