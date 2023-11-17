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
        'stack1',
        'stack2',
        'stack3',
        'stack4',
        'finished'
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
