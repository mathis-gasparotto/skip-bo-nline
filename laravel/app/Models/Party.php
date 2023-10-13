<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;
    use HasUuids;
    protected $fillable =[
        'stack1',
        'stack2',
        'stack3',
        'stack4',
        'finished'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
