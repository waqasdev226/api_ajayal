<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;
    protected $table = 'withdraw';
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'note',
        'method',
        'phone',
        'name',
        'passport',
        'bank_account',
        'swift',
        'card_no',
    ];

    public function userDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
