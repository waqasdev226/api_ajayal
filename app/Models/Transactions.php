<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;
    protected $table = 'transaction';
    protected $fillable = [
        'from',
        'to',
        'amount',
        'current_profit',
        'type',
        'status',
        'note',
        'method'
    ];

    public function fromUser(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'from')->select('id', 'name');
    }
    public function toUser(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'to')->select('id', 'name');
    }
}
