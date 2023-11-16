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
//        'stack1',
//        'stack2',
//        'stack3',
//        'stack4',
        'finished'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
//        return $this->belongsToMany(User::class);
        return $this->hasMany(User::class);
    }
}
