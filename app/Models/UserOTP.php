<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOTP extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'users_otp';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'otp',
        'reference',
        'created_at',
        'finish_at',
        'type',
        'type_id',
        'status',
    ];

}
