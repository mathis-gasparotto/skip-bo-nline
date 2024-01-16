<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 *
 */
class UserMove implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public array $user;

    /**
     * @var array|null
     */
    public ?array $userWin = null;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $userId,
        public string $partyId,
        public array|object $userDeck,
        public array|object $partyStacks,
        public array|object|null $newCardDraw,
        public int $newCardDrawCount,
        public int $userToPlayId,
        int|null $userWinId = null
    ) {
        $this->user = [
            'id' => $userId
        ];
        if ($userWinId) {
            $this->userWin = [
                'id' => $userWinId
            ];
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('party.' . $this->partyId),
        ];
    }
}
